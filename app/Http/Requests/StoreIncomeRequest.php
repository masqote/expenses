<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quick_input' => ['sometimes', 'string'],
            'label'       => ['required_without:quick_input', 'string', 'max:255'],
            'amount'      => ['required_without:quick_input', 'numeric', 'min:0.01'],
            'period'      => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
        ];
    }
}
