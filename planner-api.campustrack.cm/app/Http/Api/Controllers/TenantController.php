<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion des tenants (super-admin).
 */
class TenantController extends Controller
{
    use HttpResponses;

    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::query()
            ->withCount('users')
            ->when($request->has('search'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('slug', 'like', '%'.$request->search.'%');
            })
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(['tenants' => $tenants], 'Liste des tenants récupérée');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
            'trial_days' => 'sometimes|integer|min:1|max:365',
        ]);

        $tenant = app(TenantService::class)->create($validated);

        return $this->success(['tenant' => $tenant->load('subscriptions')], 'Tenant créé', 201);
    }

    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::query()
            ->withCount('users')
            ->with('subscriptions.pack')
            ->findOrFail($id);

        return $this->success(['tenant' => $tenant], 'Tenant récupéré');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:tenants,slug,'.$id,
            'billing_phone' => 'sometimes|nullable|string|max:20',
        ]);

        $tenant->update($validated);

        return $this->success(['tenant' => $tenant], 'Tenant mis à jour');
    }

    public function suspend(int $id): JsonResponse
    {
        $tenant = app(TenantService::class)->suspend(Tenant::findOrFail($id));

        return $this->success(['tenant' => $tenant], 'Tenant suspendu');
    }

    public function reactivate(int $id): JsonResponse
    {
        $tenant = app(TenantService::class)->reactivate(Tenant::findOrFail($id));

        return $this->success(['tenant' => $tenant], 'Tenant réactivé');
    }

    public function destroy(int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        if ($tenant->is_demo) {
            return $this->error(null, 'Le tenant démo ne peut pas être supprimé.', 403);
        }

        $tenant->delete();

        return $this->success(null, 'Tenant supprimé');
    }
}
