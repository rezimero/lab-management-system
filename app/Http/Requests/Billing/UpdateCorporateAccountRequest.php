<?php

namespace App\Http\Requests\Billing;

use App\Models\CorporateAccount;
use Illuminate\Validation\Rule;

class UpdateCorporateAccountRequest extends StoreCorporateAccountRequest
{
    public function rules(): array
    {
        $account = $this->route('corporateAccount');
        $id = $account instanceof CorporateAccount
            ? $account->getKey()
            : null;

        $rules = parent::rules();
        $rules['code'] = [
            'required', 'string', 'max:60',
            Rule::unique('corporate_accounts', 'code')->ignore($id),
        ];

        return $rules;
    }
}
