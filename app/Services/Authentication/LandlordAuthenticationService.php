<?php

namespace App\Services\Authentication;

use App\Models\Landlord\User as LandlordUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LandlordAuthenticationService
{
    public function __construct(
        protected SessionService $sessionService,
        protected AuthenticationService $authenticationService
    ) {
    }

    /**
     * Authenticate a landlord (Superadmin).
     */
    public function login(array $credentials): array
    {
        $user = $this->resolveUser(
            $credentials['email'],
            $credentials['password']
        );

        $this->sessionService->startLandlordSession($user);

        return [
            'message' => 'Login successful (Superadmin)',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ],

            'token' => $this->authenticationService->createToken($user),

            'guard' => 'landlord',

            'is_superadmin' => true,
        ];
    }

    /**
     * Resolve and validate the landlord user.
     */
    protected function resolveUser(
        string $email,
        string $password
    ): LandlordUser {

        $user = LandlordUser::query()
            ->where('email', $email)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated.',
            ]);
        }

        if ($user->role !== 'superadmin') {
            throw ValidationException::withMessages([
                'email' => 'You are not authorized to access the landlord portal.',
            ]);
        }

        return $user;
    }
}