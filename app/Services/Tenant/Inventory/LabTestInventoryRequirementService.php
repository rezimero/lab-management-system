<?php

namespace App\Services\Tenant\Inventory;

use App\Models\InventoryItem;
use App\Models\LabTest;
use App\Models\LabTestInventoryRequirement;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabTestInventoryRequirementService
{
    /**
     * @param  array<int, array<string, mixed>>  $requirements
     */
    public function sync(
        LabTest $labTest,
        array $requirements,
        TenantUser $actor
    ): LabTest {
        return DB::connection('tenant')->transaction(
            function () use ($labTest, $requirements, $actor): LabTest {
                $itemIds = [];

                foreach ($requirements as $index => $requirement) {
                    $item = InventoryItem::query()
                        ->where('uuid', $requirement['item_uuid'])
                        ->where('is_active', true)
                        ->whereHas('branchSettings', function ($query): void {
                            $query->where('is_active', true)
                                ->whereHas('branch', fn ($branch) => $branch
                                    ->where('type', 'laboratory')
                                    ->where('is_active', true));
                        })
                        ->first();

                    if ($item === null) {
                        throw ValidationException::withMessages([
                            "requirements.{$index}.item_uuid" => [
                                'Select an active item assigned to an active laboratory branch.',
                            ],
                        ]);
                    }

                    $itemIds[] = (int) $item->getKey();

                    $record = LabTestInventoryRequirement::query()->firstOrNew([
                        'lab_test_id' => $labTest->getKey(),
                        'inventory_item_id' => $item->getKey(),
                    ]);
                    $record->fill([
                        'quantity' => $requirement['quantity'],
                        'is_active' => (bool) ($requirement['is_active'] ?? true),
                        'notes' => $requirement['notes'] ?? null,
                        'updated_by' => $actor->getKey(),
                    ]);
                    $record->created_by ??= $actor->getKey();
                    $record->save();
                }

                LabTestInventoryRequirement::query()
                    ->where('lab_test_id', $labTest->getKey())
                    ->when($itemIds !== [], fn ($query) => $query->whereNotIn('inventory_item_id', $itemIds))
                    ->when($itemIds === [], fn ($query) => $query)
                    ->update([
                        'is_active' => false,
                        'updated_by' => $actor->getKey(),
                        'updated_at' => now(),
                    ]);

                return $labTest->fresh(['inventoryRequirements.item.category', 'inventoryRequirements.item.unit']);
            }, 3
        );
    }
}
