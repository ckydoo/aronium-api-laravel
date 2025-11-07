<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Display a listing of stock records.
     */
    public function index(Request $request)
    {
        $query = Stock::with(['product', 'company']);

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by low stock (below reorder level)
        if ($request->boolean('low_stock')) {
            $query->whereColumn('available_quantity', '<=', 'reorder_level')
                  ->whereNotNull('reorder_level');
        }

        // Filter by out of stock
        if ($request->boolean('out_of_stock')) {
            $query->where('available_quantity', '<=', 0);
        }

        // Search by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $perPage = $request->get('per_page', 50);
        $stocks = $query->paginate($perPage);

        return response()->json($stocks);
    }

    /**
     * Update stock for a product.
     */
    public function update(Request $request, $id)
    {
        $stock = Stock::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'quantity' => 'sometimes|numeric',
            'available_quantity' => 'sometimes|numeric',
            'reserved_quantity' => 'sometimes|numeric',
            'reorder_level' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $stock->update($request->all());

        return response()->json([
            'message' => 'Stock updated successfully',
            'data' => $stock->load('product')
        ]);
    }

    /**
     * Adjust stock quantity with movement tracking.
     */
    public function adjust(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric',
            'movement_type' => 'required|in:adjustment,return,transfer',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($productId);
            $stock = $product->stock;

            if (!$stock) {
                return response()->json([
                    'message' => 'Stock record not found for this product'
                ], 404);
            }

            $quantityBefore = $stock->quantity;
            $stock->adjustQuantity($request->quantity, $request->movement_type);

            // Create stock movement record
            StockMovement::create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'movement_type' => $request->movement_type,
                'quantity' => $request->quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $stock->quantity,
                'notes' => $request->notes,
                'user_id' => $request->user_id,
                'movement_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Stock adjusted successfully',
                'data' => $stock->fresh()->load('product')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Stock adjustment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync stock levels from POS.
     */
    public function syncBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stocks' => 'required|array',
            'stocks.*.product_id' => 'required|exists:products,id',
            'stocks.*.company_id' => 'required|exists:companies,id',
            'stocks.*.quantity' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $synced = [];
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($request->stocks as $stockData) {
                $stock = Stock::updateOrCreate(
                    [
                        'product_id' => $stockData['product_id'],
                        'company_id' => $stockData['company_id']
                    ],
                    [
                        'quantity' => $stockData['quantity'],
                        'available_quantity' => $stockData['quantity'],
                        'reserved_quantity' => $stockData['reserved_quantity'] ?? 0,
                        'reorder_level' => $stockData['reorder_level'] ?? null,
                        'reorder_quantity' => $stockData['reorder_quantity'] ?? null,
                        'location' => $stockData['location'] ?? null,
                    ]
                );

                $synced[] = $stock->id;
            }

            DB::commit();

            return response()->json([
                'message' => 'Stock batch sync completed',
                'synced_count' => count($synced),
                'synced_ids' => $synced
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Batch sync failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock movements for a product.
     */
    public function movements(Request $request, $productId)
    {
        $query = StockMovement::where('product_id', $productId)
                              ->with('product');

        // Filter by movement type
        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('movement_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $movements = $query->latest('movement_date')
                           ->paginate($request->get('per_page', 50));

        return response()->json($movements);
    }

    /**
     * Get products that need reordering.
     */
    public function lowStock(Request $request)
    {
        $query = Stock::with(['product'])
                      ->whereColumn('available_quantity', '<=', 'reorder_level')
                      ->whereNotNull('reorder_level');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $lowStockItems = $query->orderBy('available_quantity', 'asc')->get();

        return response()->json([
            'message' => 'Low stock items retrieved',
            'count' => $lowStockItems->count(),
            'data' => $lowStockItems
        ]);
    }
}
