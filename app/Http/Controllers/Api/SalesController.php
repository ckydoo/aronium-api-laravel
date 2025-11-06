<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SalesController extends Controller
{
    /**
     * Store a new sale from the fiscalisation app
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'document_id' => 'required|integer',
            'document_number' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',  // Use existing company_id
            'date_created' => 'required|date',
            'total' => 'required|numeric',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'customer_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'status' => 'nullable|string',
            'fiscal_signature' => 'nullable|string',
            'qr_code' => 'nullable|string',
            'fiscal_invoice_number' => 'nullable|string',
            'fiscalized_at' => 'nullable|date',
            'tax_details' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric',
            'items.*.price' => 'required|numeric',
            'items.*.discount' => 'nullable|numeric',
            'items.*.tax' => 'nullable|numeric',
            'items.*.total' => 'required|numeric',
            'items.*.tax_id' => 'nullable|integer',
            'items.*.tax_code' => 'nullable|string',
            'items.*.tax_percent' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if sale already exists
            $existingSale = Sale::where('document_id', $request->document_id)->first();
            if ($existingSale) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Sale already exists',
                    'data' => [
                        'sale_id' => $existingSale->id,
                        'document_id' => $existingSale->document_id,
                        'document_number' => $existingSale->document_number,
                    ],
                ], 200);
            }

            // Create the sale - using the company_id from request
            $sale = Sale::create([
                'document_id' => $request->document_id,
                'document_number' => $request->document_number,
                'company_id' => $request->company_id,  // Use the provided company_id
                'date_created' => $request->date_created,
                'total' => $request->total,
                'tax' => $request->tax ?? 0,
                'discount' => $request->discount ?? 0,
                'customer_id' => $request->customer_id,
                'user_id' => $request->user_id,
                'status' => $request->status ?? 'pending',
                'fiscal_signature' => $request->fiscal_signature,
                'qr_code' => $request->qr_code,
                'fiscal_invoice_number' => $request->fiscal_invoice_number,
                'fiscalized_at' => $request->fiscalized_at ?? ($request->fiscal_signature ? now() : null),
                'tax_details' => $request->tax_details,
            ]);

            // Create sale items
            foreach ($request->items as $itemData) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'product_name' => $itemData['product_name'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'tax' => $itemData['tax'] ?? 0,
                    'total' => $itemData['total'],
                    'tax_id' => $itemData['tax_id'] ?? null,
                    'tax_code' => $itemData['tax_code'] ?? null,
                    'tax_percent' => $itemData['tax_percent'] ?? 0,
                ]);
            }

            DB::commit();

            Log::info('Sale stored successfully', [
                'sale_id' => $sale->id,
                'document_id' => $sale->document_id,
                'company_id' => $sale->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sale stored successfully',
                'data' => [
                    'sale_id' => $sale->id,
                    'document_id' => $sale->document_id,
                    'document_number' => $sale->document_number,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to store sale', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update fiscal data for a sale
     */
    public function updateFiscalData(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fiscal_signature' => 'nullable|string',
            'qr_code' => 'nullable|string',
            'fiscal_invoice_number' => 'nullable|string',
            'tax_details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sale = Sale::where('document_id', $id)->firstOrFail();

            $sale->update([
                'fiscal_signature' => $request->fiscal_signature ?? $sale->fiscal_signature,
                'qr_code' => $request->qr_code ?? $sale->qr_code,
                'fiscal_invoice_number' => $request->fiscal_invoice_number ?? $sale->fiscal_invoice_number,
                'fiscalized_at' => now(),
                'tax_details' => $request->tax_details ?? $sale->tax_details,
                'status' => $request->fiscal_signature ? 'fiscalized' : 'error',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fiscal data updated successfully',
                'data' => $sale,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to update fiscal data', [
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update fiscal data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all sales
     */
    public function index(Request $request)
    {
        try {
            $query = Sale::with(['company', 'items']);

            // Filter by company
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('from') && $request->has('to')) {
                $query->whereBetween('date_created', [$request->from, $request->to]);
            }

            $perPage = $request->input('per_page', 15);
            $sales = $query->orderBy('date_created', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $sales,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single sale
     */
    public function show($id)
    {
        try {
            $sale = Sale::with(['company', 'items'])
                ->where('document_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $sale,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found',
            ], 404);
        }
    }

    /**
     * Get sales by date range
     */
    public function getByDateRange($from, $to)
    {
        try {
            $sales = Sale::with(['company', 'items'])
                ->whereBetween('date_created', [$from, $to])
                ->orderBy('date_created', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sales,
                'count' => $sales->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store batch sales
     */
    public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sales' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $salesData = $request->input('sales');
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($salesData as $saleData) {
                try {
                    // Create a new request for each sale
                    $saleRequest = new Request($saleData);
                    $result = $this->store($saleRequest);
                    
                    $responseData = $result->getData();
                    $results[] = [
                        'document_id' => $saleData['document_id'],
                        'success' => $responseData->success ?? false,
                    ];
                    
                    if ($responseData->success ?? false) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'document_id' => $saleData['document_id'] ?? 'unknown',
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                    $failureCount++;
                }
            }

            return response()->json([
                'success' => $failureCount === 0,
                'message' => "Processed {$successCount} of " . count($salesData) . " sales",
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to store batch sales', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store batch sales: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sales statistics
     */
    public function statistics(Request $request)
    {
        try {
            $query = Sale::query();

            // Filter by company
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            // Filter by date range
            if ($request->has('from') && $request->has('to')) {
                $query->whereBetween('date_created', [$request->from, $request->to]);
            }

            $stats = [
                'total_sales' => $query->count(),
                'total_revenue' => $query->sum('total'),
                'total_tax' => $query->sum('tax'),
                'total_discount' => $query->sum('discount'),
                'fiscalized_sales' => (clone $query)->where('status', 'fiscalized')->count(),
                'pending_sales' => (clone $query)->where('status', 'pending')->count(),
                'error_sales' => (clone $query)->where('status', 'error')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}