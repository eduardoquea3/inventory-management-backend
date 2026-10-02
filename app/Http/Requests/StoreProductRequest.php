<?php

namespace App\Http\Requests;

class StoreProductRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'price' => ['sometimes', 'numeric', 'min:0'], 'stock' => ['sometimes', 'integer', 'min:0'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'status' => ['sometimes', 'boolean']];
    }
}
