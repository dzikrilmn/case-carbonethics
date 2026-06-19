<?php

namespace App\Http\Controllers;

use App\Http\Resources\Order\OrderCollectionResource;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Create a new order with items.
     * Public endpoint - no authentication required.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            $productIds = array_column($validated['items'], 'product_id');
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $order = Order::query()->create([
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'status' => Order::STATUS_PENDING,
                'total_price' => 0,
            ]);

            $totalPrice = 0;

            foreach ($validated['items'] as $index => $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => ['Product not found.'],
                    ]);
                }

                if ($product->status !== Product::STATUS_ACTIVE) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => ['Product is not active.'],
                    ]);
                }

                $subtotal = $product->price * $item['qty'];
                $totalPrice += $subtotal;

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_price' => $totalPrice]);

            return $order->load('items');
        });

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    /**
     * List all orders (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $orders = Order::query()
            ->with('items')
            ->latest()
            ->get();

        return (new OrderCollectionResource($orders))->response();
    }

    /**
     * Get a single order by ID (admin only).
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->ensureAdmin($request);

        return (new OrderResource($order->load('items')))->response();
    }

    /**
     * Ensure the authenticated user is an admin.
     */
    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();

        abort_if(! $user || ! $user->isAdmin(), 403, 'Forbidden.');
    }
}
