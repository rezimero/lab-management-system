<?php

namespace App\Http\Requests\Billing;

use App\Models\PriceList;
use Illuminate\Validation\Rule;

class UpdatePriceListRequest extends StorePriceListRequest
{
    public function rules(): array
    {
        $priceList = $this->route('priceList');
        $id = $priceList instanceof PriceList
            ? $priceList->getKey()
            : null;

        $rules = parent::rules();
        $rules['code'] = [
            'required', 'string', 'max:60',
            Rule::unique('price_lists', 'code')->ignore($id),
        ];

        return $rules;
    }
}
