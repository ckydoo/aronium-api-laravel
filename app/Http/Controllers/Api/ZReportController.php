<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZReport;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ZReportController extends Controller
{
    /**
     * Display a listing of Z-Reports.
     */
    public function index(Request $request)
    {
        $query = ZReport::with('company');

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('report_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Filter by specific date
        if ($request->has('report_date')) {
            $query->whereDate('report_date', $request->report_date);
        }

        // Filter by device
        if ($request->has('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        $perPage = $request->get('per_page', 31); // Default to one month
        $reports = $query->latest('report_date')->paginate($perPage);

        return response()->json($reports);
    }

    /**
     * Store a newly created Z-Report.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'aronium_report_id' => 'nullable|integer|unique:z_reports,aronium_report_id',
            'company_id' => 'required|exists:companies,id',
            'report_date' => 'required|date',
            'report_number' => 'required|string|max:255',
            'device_id' => 'nullable|integer',
            'device_name' => 'nullable|string|max:255',
            'total_transactions' => 'required|integer|min:0',
            'total_items_sold' => 'required|integer|min:0',
            'gross_sales' => 'required|numeric|min:0',
            'discounts' => 'nullable|numeric|min:0',
            'returns' => 'nullable|numeric|min:0',
            'net_sales' => 'required|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'payment_breakdown' => 'nullable|array',
            'tax_breakdown' => 'nullable|array',
            'opening_cash' => 'nullable|numeric',
            'closing_cash' => 'nullable|numeric',
            'expected_cash' => 'nullable|numeric',
            'cash_difference' => 'nullable|numeric',
            'opened_at' => 'nullable|date',
            'closed_at' => 'nullable|date',
            'opened_by' => 'nullable|integer',
            'closed_by' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $report = ZReport::create($request->all());

        return response()->json([
            'message' => 'Z-Report created successfully',
            'data' => $report
        ], 201);
    }

    /**
     * Display the specified Z-Report.
     */
    public function show($id)
    {
        $report = ZReport::with('company')->findOrFail($id);
        return response()->json($report);
    }

    /**
     * Update the specified Z-Report.
     */
    public function update(Request $request, $id)
    {
        $report = ZReport::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'report_number' => 'sometimes|string|max:255',
            'total_transactions' => 'sometimes|integer|min:0',
            'total_items_sold' => 'sometimes|integer|min:0',
            'gross_sales' => 'sometimes|numeric|min:0',
            'discounts' => 'nullable|numeric|min:0',
            'returns' => 'nullable|numeric|min:0',
            'net_sales' => 'sometimes|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'payment_breakdown' => 'nullable|array',
            'tax_breakdown' => 'nullable|array',
            'opening_cash' => 'nullable|numeric',
            'closing_cash' => 'nullable|numeric',
            'expected_cash' => 'nullable|numeric',
            'cash_difference' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $report->update($request->all());

        return response()->json([
            'message' => 'Z-Report updated successfully',
            'data' => $report
        ]);
    }

    /**
     * Generate Z-Report from sales data.
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'report_date' => 'required|date',
            'device_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $date = Carbon::parse($request->report_date);
        $companyId = $request->company_id;
        $deviceId = $request->device_id;

        // Check if report already exists
        $existingReport = ZReport::where('company_id', $companyId)
                                  ->whereDate('report_date', $date)
                                  ->when($deviceId, function($query) use ($deviceId) {
                                      return $query->where('device_id', $deviceId);
                                  })
                                  ->first();

        if ($existingReport) {
            return response()->json([
                'message' => 'Z-Report already exists for this date',
                'data' => $existingReport
            ], 409);
        }

        // Get all sales for the date
        $salesQuery = Sale::where('company_id', $companyId)
                          ->whereDate('date_created', $date)
                          ->with('items');

        if ($deviceId) {
            // Filter by device if needed (you may need to add device_id to sales table)
            // $salesQuery->where('device_id', $deviceId);
        }

        $sales = $salesQuery->get();

        // Calculate totals
        $totalTransactions = $sales->count();
        $totalItemsSold = $sales->sum(function($sale) {
            return $sale->items->sum('quantity');
        });
        $grossSales = $sales->sum('total');
        $discounts = $sales->sum('discount');
        $totalTax = $sales->sum('tax');
        $netSales = $grossSales - $discounts;

        // Payment breakdown
        $paymentBreakdown = [];
        // You'll need to add payment data to your sales or get from another source

        // Tax breakdown
        $taxBreakdown = [];
        foreach ($sales as $sale) {
            if ($sale->tax_details) {
                foreach ($sale->tax_details as $tax) {
                    $taxCode = $tax['TaxCode'] ?? 'UNKNOWN';
                    if (!isset($taxBreakdown[$taxCode])) {
                        $taxBreakdown[$taxCode] = [
                            'code' => $taxCode,
                            'rate' => $tax['TaxPercent'] ?? 0,
                            'amount' => 0
                        ];
                    }
                    $taxBreakdown[$taxCode]['amount'] += $tax['TaxAmount'] ?? 0;
                }
            }
        }

        // Generate report number
        $reportNumber = 'Z-' . $date->format('Ymd') . '-' . str_pad($deviceId ?? 1, 3, '0', STR_PAD_LEFT);

        // Create report
        $report = ZReport::create([
            'company_id' => $companyId,
            'report_date' => $date,
            'report_number' => $reportNumber,
            'device_id' => $deviceId,
            'total_transactions' => $totalTransactions,
            'total_items_sold' => $totalItemsSold,
            'gross_sales' => $grossSales,
            'discounts' => $discounts,
            'returns' => 0, // You may want to calculate this from return documents
            'net_sales' => $netSales,
            'total_tax' => $totalTax,
            'payment_breakdown' => $paymentBreakdown,
            'tax_breakdown' => array_values($taxBreakdown),
        ]);

        return response()->json([
            'message' => 'Z-Report generated successfully',
            'data' => $report
        ], 201);
    }

    /**
     * Sync Z-Reports from POS.
     */
    public function syncBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reports' => 'required|array',
            'reports.*.company_id' => 'required|exists:companies,id',
            'reports.*.report_date' => 'required|date',
            'reports.*.report_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $synced = [];
        $errors = [];

        foreach ($request->reports as $reportData) {
            try {
                $report = ZReport::updateOrCreate(
                    [
                        'company_id' => $reportData['company_id'],
                        'report_date' => $reportData['report_date'],
                        'device_id' => $reportData['device_id'] ?? null,
                    ],
                    $reportData
                );

                $synced[] = $report->id;

            } catch (\Exception $e) {
                $errors[] = [
                    'report_date' => $reportData['report_date'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Z-Report batch sync completed',
            'synced_count' => count($synced),
            'synced_ids' => $synced,
            'errors' => $errors
        ], empty($errors) ? 200 : 207);
    }

    /**
     * Get summary statistics for a date range.
     */
    public function summary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reports = ZReport::where('company_id', $request->company_id)
                          ->whereBetween('report_date', [
                              $request->start_date,
                              $request->end_date
                          ])
                          ->get();

        $summary = [
            'total_days' => $reports->count(),
            'total_transactions' => $reports->sum('total_transactions'),
            'total_items_sold' => $reports->sum('total_items_sold'),
            'gross_sales' => $reports->sum('gross_sales'),
            'discounts' => $reports->sum('discounts'),
            'returns' => $reports->sum('returns'),
            'net_sales' => $reports->sum('net_sales'),
            'total_tax' => $reports->sum('total_tax'),
            'average_transaction_value' => $reports->sum('total_transactions') > 0 
                ? $reports->sum('net_sales') / $reports->sum('total_transactions')
                : 0,
            'average_daily_sales' => $reports->count() > 0
                ? $reports->sum('net_sales') / $reports->count()
                : 0,
        ];

        return response()->json([
            'message' => 'Summary retrieved successfully',
            'period' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'summary' => $summary
        ]);
    }

    /**
     * Delete a Z-Report.
     */
    public function destroy($id)
    {
        $report = ZReport::findOrFail($id);
        $report->delete();

        return response()->json([
            'message' => 'Z-Report deleted successfully'
        ]);
    }
}