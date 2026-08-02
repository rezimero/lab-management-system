<?php

namespace App\Services\Tenant\Billing;

use App\Models\Invoice;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceQueryService
{
    public function paginate(
        TenantUser $user,
        ?string $search,
        ?string $status,
        ?string $branchUuid,
        int $perPage
    ): LengthAwarePaginator {
        $assignedBranchIds = $user->isAdmin()
            ? []
            : $user->branches()->pluck('branches.id')->all();

        return Invoice::query()
            ->with(['order', 'patient', 'branch', 'corporateAccount'])
            ->withCount('payments')
            ->when(
                ! $user->isAdmin(),
                fn ($query) => $query->whereIn('branch_id', $assignedBranchIds)
            )
            ->when(
                $search,
                fn ($query, string $value) => $query->where(
                    function ($query) use ($value): void {
                        $query->where('invoice_number', 'like', "%{$value}%")
                            ->orWhereHas(
                                'order',
                                fn ($query) => $query->where(
                                    'order_number',
                                    'like',
                                    "%{$value}%"
                                )
                            )
                            ->orWhereHas(
                                'patient',
                                fn ($query) => $query
                                    ->where('first_name', 'like', "%{$value}%")
                                    ->orWhere('last_name', 'like', "%{$value}%")
                                    ->orWhere('medical_record_number', 'like', "%{$value}%")
                            );
                    }
                )
            )
            ->when($status, fn ($query, string $value) => $query->where('status', $value))
            ->when(
                $branchUuid,
                fn ($query, string $value) => $query->whereHas(
                    'branch',
                    fn ($query) => $query->where('uuid', $value)
                )
            )
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function find(TenantUser $user, string $uuid): Invoice
    {
        $assignedBranchIds = $user->isAdmin()
            ? []
            : $user->branches()->pluck('branches.id')->all();

        return Invoice::query()
            ->with([
                'order',
                'patient',
                'branch',
                'corporateAccount',
                'items',
                'payments.receivedBy',
            ])
            ->when(
                ! $user->isAdmin(),
                fn ($query) => $query->whereIn('branch_id', $assignedBranchIds)
            )
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
