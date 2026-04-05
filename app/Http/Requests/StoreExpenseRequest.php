<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
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
            'period'      => ['nullable', 'date_format:Y-m-d'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
