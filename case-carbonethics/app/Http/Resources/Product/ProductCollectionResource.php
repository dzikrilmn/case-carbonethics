<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollectionResource extends ResourceCollection
{
    public $collects = ProductResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
