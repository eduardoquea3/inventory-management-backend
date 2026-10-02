<?php

namespace App\Http\Requests;

class StoreStockMovementRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['type' => ['required', 'in:entrada,salida'], 'quantity' => ['required', 'integer', 'min:1'], 'reason' => ['nullable', 'string', 'max:255']];
    }
}
