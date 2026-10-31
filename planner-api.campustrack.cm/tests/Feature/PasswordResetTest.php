<?php

namespace Tests\Feature;

use App\Models\MailSetting;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    /** @test */
    public function a_tenant_user_receives_a_reset_link_pointing_to_the_frontend(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://planner.example.test']);

        $user = User::factory()->create(['email' => 'admin@ecole.cm']);

        $this->postJson('/api/forgot-password', ['email' => 'admin@ecole.cm'])
            ->assertOk()
            ->assertJsonPath('message', __('api.password_reset.link_sent'));

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_starts_with($mail->actionUrl, 'https://planner.example.test/reset-password?token=')
                && str_contains($mail->actionUrl, 'email=admin%40ecole.cm');
        });
    }

    /** @test */
    public function the_super_admin_can_reset_its_password(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->create(['email' => 'admin@focusrent.cm']);
        $superAdmin->assignRole('super-admin');

        $this->postJson('/api/forgot-password', ['email' => 'admin@focusrent.cm'])->assertOk();

        Notification::assertSentTo($superAdmin, ResetPassword::class);
    }

    /** @test */
    public function an_unknown_address_gets_the_same_answer_and_no_email(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', ['email' => 'personne@nulle-part.cm'])
            ->assertOk()
            ->assertJsonPath('message', __('api.password_reset.link_sent'));

        Notification::assertNothingSent();
    }

    /** @test */
    public function a_mail_server_failure_does_not_change_the_answer(): void
    {
        MailSetting::create([
            'mailer' => 'smtp',
            'host' => '127.0.0.1',
            'port' => 1,
            'from_address' => 'admin@focusrent.cm',
            'is_active' => true,
        ]);
        User::factory()->create(['email' => 'admin@ecole.cm']);

        $this->postJson('/api/forgot-password', ['email' => 'admin@ecole.cm'])
            ->assertOk()
            ->assertJsonPath('message', __('api.password_reset.link_sent'));
    }

    /** @test */
    public function the_reset_link_is_sent_from_the_configured_address(): void
    {
        Mail::fake();
        MailSetting::create(['mailer' => 'log', 'from_address' => 'admin@focusrent.cm', 'from_name' => 'FocusRent', 'is_active' => true]);
        User::factory()->create(['email' => 'admin@ecole.cm']);

        $this->postJson('/api/forgot-password', ['email' => 'admin@ecole.cm'])->assertOk();

        $this->assertSame('admin@focusrent.cm', config('mail.from.address'));
        $this->assertSame('FocusRent', config('mail.from.name'));
        $this->assertSame('log', config('mail.default'));
    }

    /** @test */
    public function a_valid_token_sets_the_new_password_and_signs_out_other_sessions(): void
    {
        $this->usePersistedSession();
        $user = User::factory()->create(['email' => 'prof@ecole.cm']);
        \DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'prof@ecole.cm',
            'password' => 'nouveau-mdp-2026',
            'password_confirmation' => 'nouveau-mdp-2026',
        ])->assertOk()->assertJsonPath('message', __('api.password_reset.done'));

        $this->assertTrue(Hash::check('nouveau-mdp-2026', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);

        // Le jeton ne sert qu'une fois.
        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'prof@ecole.cm',
            'password' => 'encore-autre-2026',
            'password_confirmation' => 'encore-autre-2026',
        ])->assertStatus(422);
    }

    /** @test */
    public function an_invalid_token_or_mismatched_confirmation_is_refused(): void
    {
        $user = User::factory()->create(['email' => 'prof@ecole.cm']);

        $this->postJson('/api/reset-password', [
            'token' => 'faux-jeton',
            'email' => 'prof@ecole.cm',
            'password' => 'nouveau-mdp-2026',
            'password_confirmation' => 'nouveau-mdp-2026',
        ])->assertStatus(422)->assertJsonPath('message', __('api.password_reset.invalid_token'));

        $this->postJson('/api/reset-password', [
            'token' => Password::createToken($user),
            'email' => 'prof@ecole.cm',
            'password' => 'nouveau-mdp-2026',
            'password_confirmation' => 'different',
        ])->assertStatus(422);
    }
}
