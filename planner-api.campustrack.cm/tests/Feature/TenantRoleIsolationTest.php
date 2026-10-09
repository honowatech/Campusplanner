<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\RoleCatalog;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantRoleIsolationTest extends TestCase
{
    /** @test */
    public function roles_are_scoped_per_tenant(): void
    {
        $tenantA = Tenant::firstOrCreate(['slug' => 'school-a'], ['name' => 'School A']);
        $tenantB = Tenant::firstOrCreate(['slug' => 'school-b'], ['name' => 'School B']);

        RoleCatalog::seedTenantRoles($tenantA->id);
        RoleCatalog::seedTenantRoles($tenantB->id);

        // Chaque école possède sa propre ligne de rôle « administrateur ».
        $this->assertEquals(1, Role::where('name', 'administrateur')->where('tenant_id', $tenantA->id)->count());
        $this->assertEquals(1, Role::where('name', 'administrateur')->where('tenant_id', $tenantB->id)->count());

        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        setPermissionsTeamId($tenantA->id);
        $adminA->assignRole('administrateur');

        $adminB = User::factory()->create(['tenant_id' => $tenantB->id]);
        setPermissionsTeamId($tenantB->id);
        $adminB->assignRole('administrateur');

        // La relation roles() est scopée à l'équipe courante : on capture les
        // identifiants pendant que l'équipe correspondante est active.
        setPermissionsTeamId($tenantA->id);
        $roleAId = $adminA->roles()->first()->id;

        setPermissionsTeamId($tenantB->id);
        $roleBId = $adminB->roles()->first()->id;

        // Les deux rôles « administrateur » sont des enregistrements distincts.
        $this->assertNotEquals($roleAId, $roleBId);
    }

    /** @test */
    public function tenant_admin_cannot_list_other_tenant_roles(): void
    {
        $tenantA = Tenant::firstOrCreate(['slug' => 'school-a2'], ['name' => 'School A2']);
        $tenantB = Tenant::firstOrCreate(['slug' => 'school-b2'], ['name' => 'School B2']);

        RoleCatalog::seedTenantRoles($tenantA->id);
        RoleCatalog::seedTenantRoles($tenantB->id);

        // Un admin de A (tenant_id = A) ne voit que les rôles de A via le scope.
        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        setPermissionsTeamId($tenantA->id);
        $adminA->assignRole('administrateur');

        $this->actingAs($adminA);
        $this->getJson('/api/roles')
            ->assertOk()
            ->assertJsonFragment(['name' => 'administrateur'])
            ->assertJsonMissing(['name' => 'School B']);
    }
}
