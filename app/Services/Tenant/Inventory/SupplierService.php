<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Supplier;
use App\Models\Tenant\User as TenantUser;

class SupplierService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(
        ?Supplier $supplier,
        array $data,
        TenantUser $actor
    ): Supplier {
        $supplier ??= new Supplier;
        $data['code'] = strtoupper(trim((string) $data['code']));
        $data['name'] = trim((string) $data['name']);
        $data['updated_by'] = $actor->getKey();
        $data['created_by'] ??= $supplier->exists
            ? $supplier->created_by
            : $actor->getKey();

        $supplier->fill($data)->save();

        return $supplier->fresh();
    }
}
