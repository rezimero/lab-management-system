<?php

namespace App\Services\Tenant\Billing;

use App\Models\LabTest;
use App\Models\PriceList;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PriceListService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(
        ?PriceList $priceList,
        array $data,
        TenantUser $actor
    ): PriceList {
        return DB::connection('tenant')->transaction(
            function () use ($priceList, $data, $actor): PriceList {
                $priceList ??= new PriceList;

                if ((bool) ($data['is_default'] ?? false)) {
                    PriceList::query()
                        ->lockForUpdate()
                        ->get();

                    PriceList::query()->update(['is_default' => false]);
                }

                $priceList->fill([
                    'code' => strtoupper(trim($data['code'])),
                    'name' => trim($data['name']),
                    'currency' => strtoupper($data['currency']),
                    'is_default' => $data['is_default'] ?? false,
                    'is_active' => $data['is_active'] ?? true,
                    'effective_from' => $data['effective_from'] ?? null,
                    'effective_to' => $data['effective_to'] ?? null,
                    'updated_by' => $actor->getKey(),
                ]);

                if (! $priceList->exists) {
                    $priceList->created_by = $actor->getKey();
                }

                $priceList->save();

                if (array_key_exists('items', $data)) {
                    $items = collect($data['items'] ?? []);
                    $labTestIds = $items
                        ->pluck('lab_test_id')
                        ->map(static fn ($id): int => (int) $id)
                        ->all();

                    if ($labTestIds !== []) {
                        $available = LabTest::query()
                            ->whereIn('id', $labTestIds)
                            ->where('is_active', true)
                            ->count();

                        if ($available !== count(array_unique($labTestIds))) {
                            throw ValidationException::withMessages([
                                'items' => [
                                    'One or more selected laboratory tests are unavailable or inactive.',
                                ],
                            ]);
                        }
                    }

                    foreach ($items as $item) {
                        $priceList->items()->updateOrCreate(
                            ['lab_test_id' => (int) $item['lab_test_id']],
                            ['price' => $item['price']]
                        );
                    }

                    $priceList->items()
                        ->when(
                            $labTestIds !== [],
                            fn ($query) => $query->whereNotIn(
                                'lab_test_id',
                                $labTestIds
                            )
                        )
                        ->delete();
                }

                return $priceList->fresh('items.labTest');
            }
        );
    }

    public function effective(?string $uuid = null): ?PriceList
    {
        return PriceList::query()
            ->with('items')
            ->currentlyEffective()
            ->when(
                $uuid,
                fn ($query, string $value) => $query->where('uuid', $value),
                fn ($query) => $query
                    ->orderByDesc('is_default')
                    ->orderBy('name')
            )
            ->first();
    }
}
