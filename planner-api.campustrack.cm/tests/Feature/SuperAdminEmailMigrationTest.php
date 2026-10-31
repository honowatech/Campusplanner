<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SuperAdminEmailMigrationTest extends TestCase
{
    private function runMigration(): void
    {
        (require database_path('migrations/2026_10_31_000001_set_super_admin_email.php'))->up();
    }

    /** @test */
    public function the_seeded_super_admin_is_renamed(): void
    {
        $user = User::factory()->create(['email' => 'superadmin@campustrack.com']);

        $this->runMigration();

        $this->assertSame('admin@focusrent.cm', $user->fresh()->email);
    }

    /** @test */
    public function the_only_super_admin_is_renamed_whatever_its_email(): void
    {
        $user = User::factory()->create(['email' => 'direction@ancien.cm']);
        $user->assignRole('super-admin');
        User::factory()->create(['email' => 'prof@ecole.cm']);

        $this->runMigration();

        $this->assertSame('admin@focusrent.cm', $user->fresh()->email);
    }

    /** @test */
    public function nothing_changes_when_the_address_is_taken_or_ambiguous(): void
    {
        $first = User::factory()->create(['email' => 'un@focus.cm']);
        $second = User::factory()->create(['email' => 'deux@focus.cm']);
        $first->assignRole('super-admin');
        $second->assignRole('super-admin');

        $this->runMigration();

        $this->assertSame('un@focus.cm', $first->fresh()->email);
        $this->assertSame('deux@focus.cm', $second->fresh()->email);
    }
}
