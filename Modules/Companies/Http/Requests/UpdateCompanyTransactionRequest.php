<?php

namespace Modules\Companies\Http\Requests;

class UpdateCompanyTransactionRequest extends StoreCompanyTransactionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        return $rules;
    }
}
