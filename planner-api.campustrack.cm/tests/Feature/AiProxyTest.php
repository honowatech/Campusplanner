<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProxyTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        config(['services.gemini.key' => 'test-key']);
    }

    /** @test */
    public function unauthenticated_user_cannot_use_the_ai_proxy(): void
    {
        $this->postJson('/api/ai/generate', ['prompt' => 'Analyse'])
            ->assertStatus(401);
    }

    /** @test */
    public function missing_server_key_returns_503(): void
    {
        config(['services.gemini.key' => null]);

        $this->actingAs($this->user)
            ->postJson('/api/ai/generate', ['prompt' => 'Analyse'])
            ->assertStatus(503);
    }

    /** @test */
    public function prompt_is_required_and_bounded(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/ai/generate', ['prompt' => ''])
            ->assertStatus(422);

        $this->actingAs($this->user)
            ->postJson('/api/ai/generate', ['prompt' => str_repeat('a', 20001)])
            ->assertStatus(422);
    }

    /** @test */
    public function non_whitelisted_model_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/ai/generate', [
                'prompt' => 'Analyse',
                'model' => 'gemini-2.5-pro',
            ])
            ->assertStatus(422);
    }

    /** @test */
    public function proxy_forwards_the_prompt_and_returns_text(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Analyse de charge : '],
                                ['text' => 'équilibre correct.'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/ai/generate', [
                'prompt' => 'Analyse ce planning',
                'system_instruction' => 'Tu es un expert.',
                'response_json' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.text', 'Analyse de charge : équilibre correct.');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'gemini-2.5-flash:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && str_contains($request->body(), 'Analyse ce planning')
                && str_contains($request->body(), 'Tu es un expert.');
        });
    }

    /** @test */
    public function upstream_error_maps_to_502(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/ai/generate', ['prompt' => 'Analyse'])
            ->assertStatus(502);
    }

    /** @test */
    public function empty_upstream_response_maps_to_502(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => []]),
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/ai/generate', ['prompt' => 'Analyse'])
            ->assertStatus(502);
    }
}
