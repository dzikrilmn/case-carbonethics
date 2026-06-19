<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Product::query()->latest()->get()
        );
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in([
                Product::STATUS_ACTIVE,
                Product::STATUS_INACTIVE,
            ])],
        ]);

        $product = Product::query()->create($validated);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in([
                Product::STATUS_ACTIVE,
                Product::STATUS_INACTIVE,
            ])],
        ]);

        $product->update($validated);

        return response()->json($product->refresh());
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->ensureAdmin($request);

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();

        abort_if(! $user || ! $user->isAdmin(), 403, 'Forbidden.');
    }
}
