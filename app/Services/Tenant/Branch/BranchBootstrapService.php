<?php

namespace App\Services\Tenant\Branch;

use App\Models\Branch;
use App\Models\PriceList;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;

class BranchBootstrapService
{
    public function ensureForUser(TenantUser $user): Branch
    {
        return DB::connection('tenant')->transaction(
            function () use ($user): Branch {
                $branch = Branch::query()
                    ->where('is_head_office', true)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if ($branch === null) {
                    $branch = Branch::query()->create([
                        'code' => 'HO',
                        'name' => 'Laboratory Head Office',
                        'type' => 'laboratory',
                        'country' => 'Nigeria',
                        'is_head_office' => true,
                        'is_active' => true,
                        'created_by' => $user->getKey(),
                        'updated_by' => $user->getKey(),
                    ]);
                }

                DB::connection('tenant')
                    ->table('branch_user')
                    ->where('user_id', $user->getKey())
                    ->update(['is_primary' => false]);

                $user->branches()->syncWithoutDetaching([
                    $branch->getKey() => [
                        'is_primary' => true,
                        'assigned_by' => $user->getKey(),
                    ],
                ]);

                PriceList::query()->firstOrCreate(
                    ['code' => 'STANDARD'],
                    [
                        'name' => 'Standard Price List',
                        'currency' => 'NGN',
                        'is_default' => true,
                        'is_active' => true,
                        'created_by' => $user->getKey(),
                        'updated_by' => $user->getKey(),
                    ]
                );

                return $branch;
            }
        );
    }

    public function assignDefault(TenantUser $user, TenantUser $actor): void
    {
        $branch = Branch::query()
            ->where('is_head_office', true)
            ->where('is_active', true)
            ->first();

        if ($branch === null) {
            $branch = $this->ensureForUser($actor);
        }

        DB::connection('tenant')
            ->table('branch_user')
            ->where('user_id', $user->getKey())
            ->update(['is_primary' => false]);

        $user->branches()->syncWithoutDetaching([
            $branch->getKey() => [
                'is_primary' => true,
                'assigned_by' => $actor->getKey(),
            ],
        ]);
    }
}
