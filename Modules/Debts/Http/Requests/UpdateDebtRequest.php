<?php

namespace Modules\Debts\Http\Requests;

class UpdateDebtRequest extends StoreDebtRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
