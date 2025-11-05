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
            'sale' => 'required|array',
            'sale.document_id' => 'required|integer',
            'sale.document_number' => 'nullable|string',
            'sale.date_created' => 'required|date',
            'sale.total' => 'required|numeric',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric',
            'items.*.price' => 'required|numeric',
            'company' => 'required|array',
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

            $saleData = $request->input('sale');
            $itemsData = $request->input('items');
            $companyData = $request->input('company');
            $fiscalData = $request->input('fiscal_data');

            // Store or update company
            $company = Company::updateOrCreate(
                ['tax_id' => $companyData['tax_id']],
                [
                    'name' => $companyData['name'],
                    'address' => $companyData['address'] ?? null,
                    'phone' => $companyData['phone'] ?? null,
                    'email' => $companyData['email'] ?? null,
                    'vat_number' => $companyData['vat_number'] ?? null,
                ]
            );

            // Create the sale
            $sale = Sale::create([
                'document_id' => $saleData['document_id'],
                'document_number' => $saleData['document_number'],
                'company_id' => $company->id,
                'date_created' => $saleData['date_created'],
                'total' => $saleData['total'],
                'tax' => $saleData['tax'] ?? 0,
                'discount' => $saleData['discount'] ?? 0,
                'customer_id' => $saleData['customer_id'] ?? null,
                'user_id' => $saleData['user_id'] ?? null,
                'status' => $saleData['status'] ?? 'pending',
                'fiscal_signature' => $fiscalData['fiscal_signature'] ?? null,
                'qr_code' => $fiscalData['qr_code'] ?? null,
                'fiscal_invoice_number' => $fiscalData['fiscal_invoice_number'] ?? null,
                'fiscalized_at' => $fiscalData ? now() : null,
                'tax_details' => $fiscalData['tax_details'] ?? null,
            ]);

            // Create sale items
            foreach ($itemsData as $itemData) {
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
                    
                    $results[] = [
                        'document_id' => $saleData['sale']['document_id'],
                        'success' => true,
                    ];
                    $successCount++;
                } catch (\Exception $e) {
                    $results[] = [
                        'document_id' => $saleData['sale']['document_id'],
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
     * Update fiscal data for a sale
     */
    public function updateFiscalData(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fiscal_data' => 'required|array',
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
            $fiscalData = $request->input('fiscal_data');

            $sale->update([
                'fiscal_signature' => $fiscalData['fiscal_signature'] ?? null,
                'qr_code' => $fiscalData['qr_code'] ?? null,
                'fiscal_invoice_number' => $fiscalData['fiscal_invoice_number'] ?? null,
                'fiscalized_at' => now(),
                'tax_details' => $fiscalData['tax_details'] ?? null,
                'status' => isset($fiscalData['fiscal_signature']) ? 'fiscalized' : 'error',
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
            $perPage = $request->input('per_page', 15);
            $sales = Sale::with(['company', 'items'])
                ->orderBy('date_created', 'desc')
                ->paginate($perPage);

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
     * Get unfiscalized sales
     */
    public function getUnfiscalized()
    {
        try {
            $sales = Sale::with(['company', 'items'])
                ->where('status', 'pending')
                ->orWhere('status', 'error')
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
                'message' => 'Failed to fetch unfiscalized sales: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get fiscalized sales
     */
    public function getFiscalized()
    {
        try {
            $sales = Sale::with(['company', 'items'])
                ->where('status', 'fiscalized')
                ->whereNotNull('fiscal_signature')
                ->orderBy('fiscalized_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sales,
                'count' => $sales->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fiscalized sales: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sales summary
     */
    public function getSummary()
    {
        try {
            $summary = [
                'total_sales' => Sale::count(),
                'fiscalized_sales' => Sale::where('status', 'fiscalized')->count(),
                'pending_sales' => Sale::where('status', 'pending')->count(),
                'error_sales' => Sale::where('status', 'error')->count(),
                'total_revenue' => Sale::sum('total'),
                'fiscalized_revenue' => Sale::where('status', 'fiscalized')->sum('total'),
                'today_sales' => Sale::whereDate('date_created', today())->count(),
                'today_revenue' => Sale::whereDate('date_created', today())->sum('total'),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a sale
     */
    public function destroy($id)
    {
        try {
            $sale = Sale::where('document_id', $id)->firstOrFail();
            $sale->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sale deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sale: ' . $e->getMessage(),
            ], 500);
        }
    }
}