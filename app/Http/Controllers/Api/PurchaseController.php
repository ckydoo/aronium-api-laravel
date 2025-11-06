<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Display a listing of purchases.
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['company', 'items']);

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date_created', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $perPage = $request->get('per_page', 50);
        $purchases = $query->latest('date_created')->paginate($perPage);

        return response()->json($purchases);
    }

    /**
     * Store a newly created purchase.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'aronium_document_id' => 'required|integer|unique:purchases,aronium_document_id',
            'document_number' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'date_created' => 'required|date',
            'supplier_id' => 'nullable|integer',
            'supplier_name' => 'nullable|string|max:255',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'user_id' => 'nullable|integer',
            'status' => 'required|in:pending,received,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.aronium_product_id' => 'required|integer',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.cost' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create purchase
            $purchase = Purchase::create($request->except('items'));

            // Create purchase items
            foreach ($request->items as $itemData) {
                $purchase->items()->create($itemData);

                // Update stock if purchase is received
                if ($purchase->status === 'received') {
                    $this->updateStockForItem($purchase, $itemData);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase created successfully',
                'data' => $purchase->load('items')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Purchase creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified purchase.
     */
    public function show($id)
    {
        $purchase = Purchase::with(['company', 'items.product'])->findOrFail($id);
        return response()->json($purchase);
    }

    /**
     * Update the specified purchase.
     */
    public function update(Request $request, $id)
    {
        $purchase = Purchase::findOrFail($id);
        $oldStatus = $purchase->status;

        $validator = Validator::make($request->all(), [
            'document_number' => 'sometimes|string|max:255',
            'supplier_id' => 'nullable|integer',
            'supplier_name' => 'nullable|string|max:255',
            'status' => 'sometimes|in:pending,received,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $purchase->update($request->all());

            // If status changed to received, update stock
            if ($oldStatus !== 'received' && $purchase->status === 'received') {
                foreach ($purchase->items as $item) {
                    $this->updateStockForItem($purchase, $item->toArray());
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase updated successfully',
                'data' => $purchase->load('items')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Purchase update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync purchases from POS.
     */
    public function syncBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchases' => 'required|array',
            'purchases.*.aronium_document_id' => 'required|integer',
            'purchases.*.company_id' => 'required|exists:companies,id',
            'purchases.*.document_number' => 'required|string',
            'purchases.*.date_created' => 'required|date',
            'purchases.*.total' => 'required|numeric',
            'purchases.*.items' => 'required|array',
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
            foreach ($request->purchases as $purchaseData) {
                try {
                    $items = $purchaseData['items'];
                    unset($purchaseData['items']);

                    $purchase = Purchase::updateOrCreate(
                        ['aronium_document_id' => $purchaseData['aronium_document_id']],
                        $purchaseData
                    );

                    // Sync items
                    $purchase->items()->delete();
                    foreach ($items as $itemData) {
                        $purchase->items()->create($itemData);
                    }

                    // Update stock if received
                    if ($purchase->status === 'received') {
                        foreach ($items as $itemData) {
                            $this->updateStockForItem($purchase, $itemData);
                        }
                    }

                    $synced[] = $purchase->aronium_document_id;

                } catch (\Exception $e) {
                    $errors[] = [
                        'aronium_document_id' => $purchaseData['aronium_document_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase batch sync completed',
                'synced_count' => count($synced),
                'synced_ids' => $synced,
                'errors' => $errors
            ], empty($errors) ? 200 : 207);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Batch sync failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a purchase.
     */
    public function destroy($id)
    {
        $purchase = Purchase::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $purchase->items()->delete();
            $purchase->delete();
            DB::commit();

            return response()->json([
                'message' => 'Purchase deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Purchase deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to update stock when purchase is received.
     */
    private function updateStockForItem($purchase, $itemData)
    {
        // Find product by aronium_product_id
        $product = Product::where('aronium_product_id', $itemData['aronium_product_id'])->first();

        if (!$product || !$product->track_inventory) {
            return;
        }

        $stock = $product->stock;
        if (!$stock) {
            $stock = Stock::create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'quantity' => 0,
                'available_quantity' => 0,
                'reserved_quantity' => 0,
            ]);
        }

        $quantityBefore = $stock->quantity;
        $stock->adjustQuantity($itemData['quantity'], 'purchase');

        // Create stock movement record
        StockMovement::create([
            'product_id' => $product->id,
            'company_id' => $product->company_id,
            'movement_type' => 'purchase',
            'quantity' => $itemData['quantity'],
            'quantity_before' => $quantityBefore,
            'quantity_after' => $stock->quantity,
            'reference_id' => $purchase->id,
            'reference_type' => 'App\Models\Purchase',
            'notes' => "Purchase: {$purchase->document_number}",
            'user_id' => $purchase->user_id,
            'movement_date' => $purchase->date_created,
        ]);
    }
}