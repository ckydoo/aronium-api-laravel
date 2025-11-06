<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::with(['stock', 'company']);

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name, code, or barcode
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 50);
        $products = $query->orderBy('name')->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'aronium_product_id' => 'required|integer|unique:products,aronium_product_id',
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer',
            'category_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|integer',
            'tax_code' => 'nullable|string|max:50',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'unit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($request->all());

        // Create initial stock record if tracking inventory
        if ($product->track_inventory) {
            $product->stock()->create([
                'company_id' => $product->company_id,
                'quantity' => 0,
                'available_quantity' => 0,
                'reserved_quantity' => 0,
            ]);
        }

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product->load('stock')
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        $product = Product::with(['stock', 'company', 'stockMovements' => function($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);

        return response()->json($product);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'aronium_product_id' => 'sometimes|integer|unique:products,aronium_product_id,' . $id,
            'company_id' => 'sometimes|exists:companies,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer',
            'category_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|integer',
            'tax_code' => 'nullable|string|max:50',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'unit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($request->all());

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product->load('stock')
        ]);
    }

    /**
     * Sync multiple products from POS.
     */
    public function syncBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.aronium_product_id' => 'required|integer',
            'products.*.company_id' => 'required|exists:companies,id',
            'products.*.name' => 'required|string|max:255',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $synced = [];
        $errors = [];

        foreach ($request->products as $productData) {
            try {
                $product = Product::updateOrCreate(
                    ['aronium_product_id' => $productData['aronium_product_id']],
                    $productData
                );

                // Ensure stock record exists
                if ($product->track_inventory && !$product->stock) {
                    $product->stock()->create([
                        'company_id' => $product->company_id,
                        'quantity' => 0,
                        'available_quantity' => 0,
                        'reserved_quantity' => 0,
                    ]);
                }

                $synced[] = $product->aronium_product_id;
            } catch (\Exception $e) {
                $errors[] = [
                    'aronium_product_id' => $productData['aronium_product_id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Batch sync completed',
            'synced_count' => count($synced),
            'synced_ids' => $synced,
            'errors' => $errors
        ], empty($errors) ? 200 : 207);
    }

    /**
     * Remove the specified product.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}