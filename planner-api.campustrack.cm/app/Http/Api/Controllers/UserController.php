<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use HttpResponses;

    /**
     * Display a paginated list of users.
     */
    public function index(Request $request): JsonResponse
    {
        // Les demandes d'inscription sont listées via /users/pending.
        // Les comptes suspendus (déjà validés par le passé) restent visibles ici.
        $query = User::with(['roles', 'permissions', 'department'])
            ->where(function ($q) {
                $q->where('is_approved', true)->orWhereNotNull('approved_at');
            });

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->role($request->role);
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 9);

        return $this->success(['users' => $users], 'Liste des utilisateurs récupérée', 200);
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['roles', 'permissions', 'department'])->findOrFail($id);

        return $this->success(['user' => $user], 'Utilisateur récupéré', 200);
    }

    /**
     * Rôles attribuables via l'inscription self-service et l'approbation.
     * Les rôles d'administration ne sont jamais auto-attribuables.
     */
    private const ASSIGNABLE_ROLES = 'in:professeur,etudiant,personnel-administratif,responsable-departement';

    /**
     * Comptes en attente de validation.
     */
    public function pending(Request $request): JsonResponse
    {
        $users = User::with(['department'])
            ->where('is_approved', false)
            ->whereNull('approved_at')
            ->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 15);

        return $this->success(['users' => $users], 'Comptes en attente de validation', 200);
    }

    /**
     * Valider un compte : assigne le rôle demandé (ou celui fourni) et débloque la connexion.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $user = User::where('is_approved', false)->findOrFail($id);

        $validated = $request->validate([
            // L'admin peut valider le rôle demandé ou en choisir un autre (attribuable)
            'role' => ['nullable', 'string', self::ASSIGNABLE_ROLES],
        ]);

        $roleName = $validated['role'] ?? $user->requested_role;

        if (empty($roleName) && $user->roles()->doesntExist()) {
            return $this->error(
                null,
                'Aucun rôle demandé pour ce compte. Veuillez en spécifier un.',
                422
            );
        }

        if (! empty($roleName)) {
            if (! Role::where('name', $roleName)->exists()) {
                return $this->error(null, "Le rôle '{$roleName}' n'existe pas.", 422);
            }
            $user->assignRole($roleName);
        }

        $user->update([
            'is_approved' => true,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return $this->success(
            ['user' => $user->load(['roles', 'permissions', 'department'])],
            'Compte validé avec succès',
            200
        );
    }

    /**
     * Rejeter un compte en attente : le compte est supprimé.
     * La session cookie associée devient inutile (utilisateur introuvable).
     */
    public function reject(int $id): JsonResponse
    {
        $user = User::where('is_approved', false)->findOrFail($id);

        $user->delete();

        return $this->success(null, 'Demande de compte rejetée', 200);
    }

    /**
     * Suspendre un compte : la session cookie est révoquée immédiatement
     * (middleware EnsureUserIsApproved sur le groupe 'api').
     */
    public function deactivate(Request $request, int $id): JsonResponse
    {
        if ((int) $id === $request->user()->id) {
            return $this->error(
                null,
                'Vous ne pouvez pas suspendre votre propre compte.',
                400
            );
        }

        $user = User::where('is_approved', true)->findOrFail($id);

        // approved_at marque « déjà validé un jour » : le compte suspendu reste
        // listé côté index et n'apparaît pas dans les demandes d'inscription.
        $user->update([
            'is_approved' => false,
            'approved_at' => $user->approved_at ?? now(),
        ]);

        return $this->success(
            ['user' => $user->load(['roles', 'permissions', 'department'])],
            'Compte suspendu. Toutes les sessions ont été révoquées.',
            200
        );
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'department_id' => 'sometimes|exists:departments,id',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        // Les rôles d'administration ne sont attribuables que par un super-admin
        $adminRoles = ['super-admin', 'administrateur'];
        $requestedRoles = $validated['roles'] ?? [];
        if (array_intersect($requestedRoles, $adminRoles)
            && ! $request->user()->hasRole('super-admin')) {
            return $this->error(
                null,
                'Seul un super-admin peut attribuer les rôles : '.implode(', ', $adminRoles),
                403
            );
        }

        $user = DB::transaction(function () use ($validated, $requestedRoles) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'department_id' => $validated['department_id'] ?? null,
            ]);

            if (! empty($requestedRoles)) {
                $user->assignRole($requestedRoles);
            }

            return $user;
        });

        return $this->success(['user' => $user->load(['roles', 'permissions', 'department'])], 'Utilisateur créé avec succès', 201);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin')) {
            return $this->error(null, 'Non autorisé à modifier ce utilisateur', 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|string|min:8',
            'department_id' => 'sometimes|nullable|exists:departments,id',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if (array_key_exists('department_id', $validated)) {
            $user->department_id = $validated['department_id'];
        }

        $user->save();

        return $this->success(
            ['user' => $user->load(['roles', 'permissions', 'department'])],
            'Utilisateur mis à jour avec succès',
            200
        );
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super-admin')) {
            return $this->error(null, 'Le super-admin ne peut pas être supprimé', 403);
        }

        if ($user->id === $request->user()->id) {
            return $this->error(null, 'Vous ne pouvez pas vous supprimer vous-même', 400);
        }

        $user->delete();

        return $this->success(null, 'Utilisateur supprimé avec succès', 200);
    }
}
