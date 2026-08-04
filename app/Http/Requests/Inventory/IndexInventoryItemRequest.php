<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Validation\Rule;

class IndexInventoryItemRequest extends IndexInventoryLookupRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category_uuid' => ['nullable', 'uuid'],
            'item_type' => ['nullable', Rule::in(['reagent', 'consumable', 'general'])],
        ]);
    }
}
