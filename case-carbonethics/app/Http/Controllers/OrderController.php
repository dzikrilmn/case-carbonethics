<?php

namespace App\Http\Controllers;

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

        try {
            return DB::transaction(function () use ($validated) {
                // Get all products that are being ordered
                $productIds = array_column($validated['items'], 'product_id');
                $products = Product::query()
                    ->whereIn('id', $productIds)
                    ->get()
                    ->keyBy('id');

                // Validate all products are active
                foreach ($validated['items'] as $item) {
                    $product = $products->get($item['product_id']);

                    if (!$product) {
                        throw ValidationException::withMessages([
                            'items' => ["Product with ID {$item['product_id']} not found."],
                        ]);
                    }

                    if ($product->status !== Product::STATUS_ACTIVE) {
                        throw ValidationException::withMessages([
                            'items' => ["Product '{$product->name}' is not active."],
                        ]);
                    }
                }

                // Create order
                $order = Order::query()->create([
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'status' => Order::STATUS_PENDING,
                    'total_price' => 0, // Will be calculated after items are added
                ]);

                // Create order items with price snapshot
                $totalPrice = 0;

                foreach ($validated['items'] as $item) {
                    $product = $products->get($item['product_id']);
                    $subtotal = $product->price * $item['qty'];
                    $totalPrice += $subtotal;

                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'price' => $product->price, // Snapshot of current price
                        'subtotal' => $subtotal,
                    ]);
                }

                // Update order total_price
                $order->update(['total_price' => $totalPrice]);

                return response()->json($order->load('items'), 201);
            });
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
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

        return response()->json($orders);
    }

    /**
     * Get a single order by ID (admin only).
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->ensureAdmin($request);

        return response()->json($order->load('items'));
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
