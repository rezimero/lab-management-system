<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Branch;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryCatalogService
{
    public function __construct(
        protected InventoryBranchAccessService $branchAccess
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveCategory(
        ?InventoryCategory $category,
        array $data,
        TenantUser $actor
    ): InventoryCategory {
        return $this->saveLookup($category ?? new InventoryCategory, $data, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveUnit(
        ?InventoryUnit $unit,
        array $data,
        TenantUser $actor
    ): InventoryUnit {
        return $this->saveLookup($unit ?? new InventoryUnit, $data, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveItem(
        ?InventoryItem $item,
        array $data,
        TenantUser $actor
    ): InventoryItem {
        return DB::connection('tenant')->transaction(
            function () use ($item, $data, $actor): InventoryItem {
                $item ??= new InventoryItem;
                $branches = $data['branches'] ?? null;
                unset($data['branches']);

                $category = InventoryCategory::query()
                    ->where('uuid', $data['category_uuid'])
                    ->where('is_active', true)
                    ->first();
                $unit = InventoryUnit::query()
                    ->where('uuid', $data['unit_uuid'])
                    ->where('is_active', true)
                    ->first();

                if ($category === null) {
                    throw ValidationException::withMessages([
                        'category_uuid' => ['Select an active inventory category.'],
                    ]);
                }

                if ($unit === null) {
                    throw ValidationException::withMessages([
                        'unit_uuid' => ['Select an active stock unit.'],
                    ]);
                }

                unset($data['category_uuid'], $data['unit_uuid']);
                $data['inventory_category_id'] = $category->getKey();
                $data['inventory_unit_id'] = $unit->getKey();
                $data['sku'] = strtoupper(trim((string) $data['sku']));
                $data['name'] = trim((string) $data['name']);
                $data['updated_by'] = $actor->getKey();
                $data['created_by'] ??= $item->exists ? $item->created_by : $actor->getKey();

                $item->fill($data)->save();

                if (is_array($branches)) {
                    $this->syncBranches($item, $branches, $actor);
                }

                return $item->fresh(['category', 'unit', 'branchSettings.branch']);
            }, 3
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $branches
     */
    private function syncBranches(
        InventoryItem $item,
        array $branches,
        TenantUser $actor
    ): void {
        $seen = [];

        foreach ($branches as $index => $setting) {
            $branch = Branch::query()
                ->where('uuid', $setting['branch_uuid'])
                ->first();

            if ($branch === null) {
                throw ValidationException::withMessages([
                    "branches.{$index}.branch_uuid" => ['The selected branch does not exist.'],
                ]);
            }

            $this->branchAccess->authorize($actor, $branch, false);
            $seen[] = (int) $branch->getKey();

            $branchSetting = $item->branchSettings()->firstOrNew([
                'branch_id' => $branch->getKey(),
            ]);
            $branchSetting->fill([
                'reorder_level' => $setting['reorder_level'] ?? null,
                'is_active' => (bool) ($setting['is_active'] ?? true),
                'updated_by' => $actor->getKey(),
            ]);
            $branchSetting->created_by ??= $actor->getKey();
            $branchSetting->save();
        }

        if ($this->branchAccess->isAdministrator($actor)) {
            $item->branchSettings()
                ->when($seen !== [], fn ($query) => $query->whereNotIn('branch_id', $seen))
                ->when($seen === [], fn ($query) => $query)
                ->update([
                    'is_active' => false,
                    'updated_by' => $actor->getKey(),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    private function saveLookup(Model $model, array $data, TenantUser $actor): Model
    {
        $data['code'] = strtoupper(trim((string) $data['code']));
        $data['name'] = trim((string) $data['name']);
        $data['updated_by'] = $actor->getKey();
        $data['created_by'] ??= $model->exists
            ? $model->getAttribute('created_by')
            : $actor->getKey();

        $model->fill($data)->save();

        return $model->fresh();
    }
}
