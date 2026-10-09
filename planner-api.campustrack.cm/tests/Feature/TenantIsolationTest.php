<?php

namespace Tests\Feature;

use App\Models\CourseClass;
use App\Models\Department;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RoleCatalog;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    private function tenantWithAdmin(string $slug): array
    {
        $tenant = Tenant::firstOrCreate(['slug' => $slug], ['name' => $slug]);
        RoleCatalog::seedTenantRoles($tenant->id);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        setPermissionsTeamId($tenant->id);
        $admin->assignRole('administrateur');

        return [$tenant, $admin];
    }

    private function makeClass(string $name, string $code, Department $department, Tenant $tenant): CourseClass
    {
        return CourseClass::create([
            'name' => $name,
            'code' => $code,
            'department_id' => $department->id,
            'level' => 1,
            'capacity' => 30,
            'academic_year' => '2026-2027',
            'tenant_id' => $tenant->id,
        ]);
    }

    /** @test */
    public function tenant_admin_cannot_access_another_tenants_room_by_id(): void
    {
        [$tenantA, $adminA] = $this->tenantWithAdmin('iso-a');
        [$tenantB] = $this->tenantWithAdmin('iso-b');

        $roomB = Room::factory()->create(['tenant_id' => $tenantB->id]);

        // L'admin de A accède à une salle de B par son ID : le TenantScope
        // filtre la requête et renvoie 404 (pas de fuite inter-tenant).
        $this->actingAs($adminA)
            ->getJson('/api/rooms/'.$roomB->id)
            ->assertNotFound();
    }

    /** @test */
    public function tenant_admin_only_lists_their_own_rooms(): void
    {
        [$tenantA, $adminA] = $this->tenantWithAdmin('iso-a2');
        [$tenantB] = $this->tenantWithAdmin('iso-b2');

        Room::factory()->count(3)->create(['tenant_id' => $tenantA->id]);
        Room::factory()->count(2)->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA)
            ->getJson('/api/rooms')
            ->assertOk()
            ->assertJsonCount(3, 'data.rooms.data');
    }

    /** @test */
    public function super_admin_sees_all_tenants(): void
    {
        [$tenantA] = $this->tenantWithAdmin('iso-a3');
        [$tenantB] = $this->tenantWithAdmin('iso-b3');

        Room::factory()->create(['tenant_id' => $tenantA->id]);
        Room::factory()->create(['tenant_id' => $tenantB->id]);

        $superAdmin = User::factory()->create();
        setPermissionsTeamId(null);
        $superAdmin->assignRole('super-admin');
        $superAdmin->forceFill(['tenant_id' => null])->save();

        $this->actingAs($superAdmin)
            ->getJson('/api/rooms')
            ->assertOk()
            ->assertJsonCount(2, 'data.rooms.data');
    }

    /** @test */
    public function tenant_admin_lists_all_classes_across_departments(): void
    {
        [$tenantA, $adminA] = $this->tenantWithAdmin('cls-a');

        $deptX = Department::factory()->create(['tenant_id' => $tenantA->id]);
        $deptY = Department::factory()->create(['tenant_id' => $tenantA->id]);

        // L'administrateur est rattaché à un département mais doit voir toutes
        // les classes de son tenant, y compris celles des autres départements.
        $adminA->update(['department_id' => $deptX->id]);

        $this->makeClass('Licence 1', 'L1', $deptX, $tenantA);
        $this->makeClass('Master 2', 'M2', $deptY, $tenantA);

        $this->actingAs($adminA)
            ->getJson('/api/classes')
            ->assertOk()
            ->assertJsonCount(2, 'data.classes.data')
            ->assertJsonFragment(['name' => 'Licence 1'])
            ->assertJsonFragment(['name' => 'Master 2']);
    }

    /** @test */
    public function tenant_admin_cannot_list_another_tenants_classes(): void
    {
        [$tenantA, $adminA] = $this->tenantWithAdmin('cls-b');
        [$tenantB] = $this->tenantWithAdmin('cls-b2');

        $deptA = Department::factory()->create(['tenant_id' => $tenantA->id]);
        $deptB = Department::factory()->create(['tenant_id' => $tenantB->id]);

        $this->makeClass('Classe A', 'CLA', $deptA, $tenantA);
        $this->makeClass('Classe B', 'CLB', $deptB, $tenantB);

        $this->actingAs($adminA)
            ->getJson('/api/classes')
            ->assertOk()
            ->assertJsonCount(1, 'data.classes.data')
            ->assertJsonFragment(['name' => 'Classe A'])
            ->assertJsonMissing(['name' => 'Classe B']);
    }
}
