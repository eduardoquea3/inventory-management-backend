<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'category' => $this->category,
            'category_name' => $this->category_name,
            'total_movements' => $this->total_movements,
        ]);
    }
}
