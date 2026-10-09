<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TenantService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Onboarding public : inscription d'une école (tenant + compte admin initial).
 */
class OnboardingController extends Controller
{
    use HttpResponses;

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        $tenant = app(TenantService::class)->create($validated);

        return $this->success(['tenant' => $tenant], 'Inscription réussie', 201);
    }
}
