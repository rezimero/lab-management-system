<?php

namespace Tests\Support\Authentication\Concerns;

use App\Models\Landlord\User as LandlordUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait ManagesTestLandlordUsers
{
    /**
     * Landlord users created during the current test.
     *
     * @var array<int, int>
     */
    private array $trackedLandlordUserIds = [];

    /**
     * Create a landlord user for the current test.
     */
    protected function createTestLandlordUser(
        string $role,
        bool $isActive
    ): LandlordUser {
        $user = new LandlordUser();

        $user->forceFill([
            'name' => 'Landlord Test User',
            'email' => sprintf(
                'landlord-%s@example.test',
                strtolower(Str::random(16))
            ),
            'email_verified_at' => now(),
            'password' => static::PASSWORD,
            'role' => $role,
            'is_active' => $isActive,
        ]);

        $user->save();
        $user->refresh();

        $this->trackedLandlordUserIds[] = (int) $user->getKey();

        return $user;
    }

    /**
     * Authenticate a landlord user through the real API.
     */
    protected function loginLandlord(
        LandlordUser $user
    ): TestResponse {
        return $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => static::PASSWORD,
        ]);
    }

    /**
     * Remove landlord users and tokens created by the current test.
     */
    protected function cleanupTestLandlordUsers(): void
    {
        if ($this->trackedLandlordUserIds === []) {
            return;
        }

        $landlordUserType = (
            new LandlordUser()
        )->getMorphClass();

        DB::connection('landlord')
            ->table('personal_access_tokens')
            ->where(
                'tokenable_type',
                $landlordUserType
            )
            ->whereIn(
                'tokenable_id',
                $this->trackedLandlordUserIds
            )
            ->delete();

        LandlordUser::query()
            ->whereIn(
                'id',
                $this->trackedLandlordUserIds
            )
            ->delete();

        $this->trackedLandlordUserIds = [];
    }
}