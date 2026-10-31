<?php

namespace Tests\Feature;

use App\Models\MailSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    private function superAdmin(): User
    {
        $admin = User::factory()->create(['email' => 'admin@focusrent.cm']);
        $admin->assignRole('super-admin');

        return $admin;
    }

    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'mailer' => 'smtp',
            'host' => 'smtp.example.test',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'admin@focusrent.cm',
            'password' => 'secret-smtp',
            'from_address' => 'admin@focusrent.cm',
            'from_name' => 'FocusRent',
            'is_active' => true,
        ], $overrides);
    }

    /** @test */
    public function default_sender_is_admin_at_focusrent(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson('/api/admin/mail-settings')
            ->assertOk()
            ->assertJsonPath('data.settings.from_address', 'admin@focusrent.cm')
            ->assertJsonPath('data.settings.is_active', true);
    }

    /** @test */
    public function super_admin_saves_the_settings_and_the_password_stays_secret(): void
    {
        $this->actingAs($this->superAdmin())
            ->putJson('/api/admin/mail-settings', $this->settingsPayload())
            ->assertOk()
            ->assertJsonPath('data.settings.has_password', true)
            ->assertJsonMissingPath('data.settings.password');

        $this->assertSame('secret-smtp', MailSetting::first()->password);
        $this->assertNotSame('secret-smtp', \DB::table('mail_settings')->value('password'));

        // Mot de passe vide = inchangé.
        $this->putJson('/api/admin/mail-settings', $this->settingsPayload(['password' => '', 'from_name' => 'Focus']))
            ->assertOk();
        $this->assertSame('secret-smtp', MailSetting::first()->password);
        $this->assertSame(1, MailSetting::count());
    }

    /** @test */
    public function settings_are_validated(): void
    {
        $this->actingAs($this->superAdmin());

        $this->putJson('/api/admin/mail-settings', $this->settingsPayload(['host' => null]))->assertStatus(422);
        $this->putJson('/api/admin/mail-settings', $this->settingsPayload(['from_address' => 'pas-un-email']))->assertStatus(422);
        $this->putJson('/api/admin/mail-settings', $this->settingsPayload(['encryption' => 'starttls']))->assertStatus(422);
    }

    /** @test */
    public function connection_test_sends_a_test_email(): void
    {
        Mail::fake();
        MailSetting::create(['mailer' => 'log', 'from_address' => 'admin@focusrent.cm', 'is_active' => true]);

        $this->actingAs($this->superAdmin())
            ->postJson('/api/admin/mail-settings/test', ['to' => 'controle@focusrent.cm'])
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.to', 'controle@focusrent.cm');
    }

    /** @test */
    public function connection_test_reports_an_unreachable_server(): void
    {
        MailSetting::create(['mailer' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'from_address' => 'admin@focusrent.cm', 'is_active' => true]);

        $this->actingAs($this->superAdmin())
            ->postJson('/api/admin/mail-settings/test')
            ->assertOk()
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.to', 'admin@focusrent.cm');
    }

    /** @test */
    public function only_the_super_admin_manages_the_email_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrateur');

        $this->actingAs($admin)->getJson('/api/admin/mail-settings')->assertForbidden();
        $this->actingAs($admin)->putJson('/api/admin/mail-settings', $this->settingsPayload())->assertForbidden();
        $this->actingAs($admin)->postJson('/api/admin/mail-settings/test')->assertForbidden();
    }
}
