<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\ProductCollectionResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return new ProductCollectionResource(
            Product::query()->latest()->get()
        );
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function store(ProductRequest $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validated();

        $product = Product::query()->create($validated);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->ensureAdmin($request);

        $validated = $request->validated();

        $product->update($validated);

        return new ProductResource($product->refresh());
    }

    public function destroy(Request $request, Product $product)
    {
        $this->ensureAdmin($request);

        $product->delete();

        return response()->json([
            'data' => [
                'message' => 'Product deleted successfully.',
            ],
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();

        abort_if(! $user || ! $user->isAdmin(), 403, 'Forbidden.');
    }
}
