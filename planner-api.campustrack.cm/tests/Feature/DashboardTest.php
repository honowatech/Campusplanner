<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private User $superAdmin;

    private User $admin;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();

        $this->superAdmin = User::factory()->create([
            'department_id' => $this->department->id,
        ]);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create([
            'department_id' => $this->department->id,
        ]);
        $this->admin->assignRole('administrateur');
    }

    /** @test */
    public function super_admin_can_access_dashboard_overview(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson('/api/dashboard/overview?period=7d');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'realtime',
                    'heavy',
                    'period',
                    'generated_at',
                ],
            ]);
    }

    /** @test */
    public function admin_can_access_dashboard_with_department_restriction(): void
    {
        $this->actingAs($this->admin);

        $response = $this->getJson('/api/dashboard/overview?period=7d');

        $response->assertStatus(200)
            ->assertJsonPath('data.department_id', $this->department->id);
    }

    /** @test */
    public function admin_cannot_access_other_department_data(): void
    {
        $otherDept = Department::factory()->create();

        $this->actingAs($this->admin);

        $response = $this->getJson("/api/dashboard/overview?department_id={$otherDept->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function dashboard_supports_all_periods(): void
    {
        $this->actingAs($this->superAdmin);

        foreach (['24h', '7d', '30d'] as $period) {
            $response = $this->getJson("/api/dashboard/overview?period={$period}");
            $response->assertStatus(200)
                ->assertJsonPath('data.period', $period);
        }
    }

    /** @test */
    public function invalid_period_returns_error(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson('/api/dashboard/overview?period=invalid');

        $response->assertStatus(422);
    }

    /** @test */
    public function dashboard_alerts_returns_paginated_response(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson('/api/dashboard/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'alerts' => [
                        'critical',
                        'warnings',
                        'info',
                    ],
                    'pagination',
                ],
            ]);
    }

    /** @test */
    public function super_admin_can_refresh_cache(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->postJson('/api/dashboard/refresh-cache');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_cannot_refresh_cache(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/dashboard/refresh-cache');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/dashboard/overview');

        $response->assertStatus(401);
    }

    /** @test */
    public function dashboard_returns_iso_8601_dates(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson('/api/dashboard/overview');

        $generatedAt = $response->json('data.generated_at');
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
            $generatedAt
        );
    }

    /** @test */
    public function dashboard_departments_returns_all_departments(): void
    {
        $this->actingAs($this->superAdmin);

        // Create additional departments
        Department::factory()->count(3)->create();

        $response = $this->getJson('/api/dashboard/departments?period=7d');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data.departments');
    }

    /** @test */
    public function dashboard_resources_returns_utilization_data(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson('/api/dashboard/resources?period=7d');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'resources' => [
                        'rooms',
                        'teachers',
                    ],
                    'period',
                ],
            ]);
    }

    /** @test */
    public function dashboard_charts_returns_evolution_and_comparison(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson('/api/dashboard/charts?period=7d');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'charts' => [
                        'evolution' => [
                            'labels',
                            'datasets',
                        ],
                        'comparison',
                    ],
                    'period',
                ],
            ]);
    }

    /** @test */
    public function dashboard_activity_returns_paginated_list(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson('/api/dashboard/activity?period=7d&page=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'activity',
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                    ],
                ],
            ]);
    }
}
