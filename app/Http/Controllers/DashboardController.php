<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Purchase;
use App\Models\Company;
use App\Models\ZReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with business statistics
     */
    public function index(Request $request)
    {
        // Get date filter from request or default to today
        $dateFilter = $request->input('date_filter', 'today');
        $customFrom = $request->input('from_date');
        $customTo = $request->input('to_date');

        // Calculate date range based on filter
        $dateRange = $this->getDateRange($dateFilter, $customFrom, $customTo);
        $fromDate = $dateRange['from'];
        $toDate = $dateRange['to'];

        // Get sales statistics
        $salesData = $this->getSalesStatistics($fromDate, $toDate);

        // Get inventory statistics
        $inventoryData = $this->getInventoryStatistics();

        // Get top selling products
        $topProducts = $this->getTopSellingProducts($fromDate, $toDate);

        // Get recent sales
        $recentSales = Sale::with(['company', 'items'])
            ->whereBetween('date_created', [$fromDate, $toDate])
            ->orderBy('date_created', 'desc')
            ->limit(10)
            ->get();

        // Get sales trend (last 7 days)
        $salesTrend = $this->getSalesTrend();

        // Get company information
        $company = Company::first();

        return view('dashboard', compact(
            'salesData',
            'inventoryData',
            'topProducts',
            'recentSales',
            'salesTrend',
            'company',
            'dateFilter',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Get date range based on filter
     */
    private function getDateRange($filter, $customFrom = null, $customTo = null)
    {
        $now = Carbon::now();

        switch ($filter) {
            case 'today':
                return [
                    'from' => $now->copy()->startOfDay(),
                    'to' => $now->copy()->endOfDay()
                ];

            case 'yesterday':
                return [
                    'from' => $now->copy()->subDay()->startOfDay(),
                    'to' => $now->copy()->subDay()->endOfDay()
                ];

            case 'week':
                return [
                    'from' => $now->copy()->startOfWeek(),
                    'to' => $now->copy()->endOfWeek()
                ];

            case 'month':
                return [
                    'from' => $now->copy()->startOfMonth(),
                    'to' => $now->copy()->endOfMonth()
                ];

            case 'year':
                return [
                    'from' => $now->copy()->startOfYear(),
                    'to' => $now->copy()->endOfYear()
                ];

            case 'custom':
                return [
                    'from' => $customFrom ? Carbon::parse($customFrom)->startOfDay() : $now->copy()->startOfMonth(),
                    'to' => $customTo ? Carbon::parse($customTo)->endOfDay() : $now->copy()->endOfDay()
                ];

            default:
                return [
                    'from' => $now->copy()->startOfDay(),
                    'to' => $now->copy()->endOfDay()
                ];
        }
    }

    /**
     * Get sales statistics
     */
    private function getSalesStatistics($fromDate, $toDate)
    {
        $sales = Sale::whereBetween('date_created', [$fromDate, $toDate]);

        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('total');
        $fiscalizedSales = Sale::whereBetween('date_created', [$fromDate, $toDate])
            ->where('status', 'fiscalized')
            ->count();
        $pendingSales = Sale::whereBetween('date_created', [$fromDate, $toDate])
            ->where('status', 'pending')
            ->count();
        $errorSales = Sale::whereBetween('date_created', [$fromDate, $toDate])
            ->where('status', 'error')
            ->count();

        $fiscalizedRevenue = Sale::whereBetween('date_created', [$fromDate, $toDate])
            ->where('status', 'fiscalized')
            ->sum('total');

        $totalTax = Sale::whereBetween('date_created', [$fromDate, $toDate])
            ->sum('tax');

        $totalDiscount = Sale::whereBetween('date_created', [$fromDate, $toDate])
            ->sum('discount');

        // Calculate average sale
        $averageSale = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        // Calculate previous period for comparison
        $periodLength = $fromDate->diffInDays($toDate);
        $previousFrom = $fromDate->copy()->subDays($periodLength + 1);
        $previousTo = $fromDate->copy()->subDay();

        $previousRevenue = Sale::whereBetween('date_created', [$previousFrom, $previousTo])
            ->sum('total');

        $revenueChange = $previousRevenue > 0
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100
            : 0;

        return [
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'fiscalized_sales' => $fiscalizedSales,
            'pending_sales' => $pendingSales,
            'error_sales' => $errorSales,
            'fiscalized_revenue' => $fiscalizedRevenue,
            'total_tax' => $totalTax,
            'total_discount' => $totalDiscount,
            'average_sale' => $averageSale,
            'revenue_change' => $revenueChange,
            'fiscalization_rate' => $totalSales > 0 ? ($fiscalizedSales / $totalSales) * 100 : 0,
        ];
    }

    /**
     * Get inventory statistics
     * FIXED: Corrected the JOIN to use proper column names
     */
    private function getInventoryStatistics()
    {
        $totalProducts = Product::count();

        // FIXED: The stocks.product_id references products.id (not products.product_id)
        // The products table has 'id' as primary key and 'aronium_product_id' as the Aronium reference
        $totalStockValue = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id') // FIXED: Changed from products.product_id to products.id
            ->sum(DB::raw('stocks.quantity * products.price'));

        $lowStockProducts = Stock::whereColumn('quantity', '<=', 'reorder_level')
            ->whereNotNull('reorder_level')
            ->count();

        $outOfStockProducts = Stock::where('quantity', 0)->count();

        return [
            'total_products' => $totalProducts,
            'total_stock_value' => $totalStockValue ?? 0,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
        ];
    }

    /**
     * Get top selling products
     * FIXED: Uses aronium_product_id for the join
     */
    private function getTopSellingProducts($fromDate, $toDate, $limit = 5)
    {
        // The sale_items.product_id contains the aronium_product_id
        // So we join with products.aronium_product_id
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.aronium_product_id') // FIXED: Join on aronium_product_id
            ->whereBetween('sales.date_created', [$fromDate, $toDate])
            ->select(
                'products.name as product_name', // FIXED: Changed from product_name to name
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name') // FIXED: Group by products.id instead of product_id
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sales trend for the last 7 days
     */
    private function getSalesTrend()
    {
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $sales = Sale::whereDate('date_created', $date)->sum('total');

            $trend[] = [
                'date' => $date->format('M d'),
                'sales' => $sales,
            ];
        }

        return $trend;
    }

    /**
     * Display sales list page
     */
    public function sales(Request $request)
    {
        $query = Sale::with(['company', 'items']);

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhere('fiscal_invoice_number', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // Date filter
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('date_created', [
                Carbon::parse($request->from_date)->startOfDay(),
                Carbon::parse($request->to_date)->endOfDay()
            ]);
        }

        $sales = $query->orderBy('date_created', 'desc')->paginate(20);

        return view('sales.index', compact('sales'));
    }

    /**
     * Display products list page
     */
    public function products(Request $request)
    {
        $query = Product::with(['stock', 'company']);

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Active filter
        if ($request->has('is_active') && $request->is_active != 'all') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Category filter
        if ($request->has('category') && $request->category != 'all') {
            $query->where('category_name', $request->category);
        }

        $products = $query->orderBy('name')->paginate(20);

        // Get categories for filter
        $categories = Product::select('category_name')
            ->distinct()
            ->whereNotNull('category_name')
            ->orderBy('category_name')
            ->pluck('category_name');

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Display inventory/stock list page
     */
    public function inventory(Request $request)
    {
        $query = Stock::with(['product', 'company']);

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Stock level filter
        if ($request->has('stock_level')) {
            switch ($request->stock_level) {
                case 'out_of_stock':
                    $query->where('quantity', 0);
                    break;
                case 'low_stock':
                    $query->whereColumn('quantity', '<=', 'reorder_level')
                          ->where('quantity', '>', 0);
                    break;
                case 'in_stock':
                    $query->where('quantity', '>', 0)
                          ->where(function($q) {
                              $q->whereColumn('quantity', '>', 'reorder_level')
                                ->orWhereNull('reorder_level');
                          });
                    break;
            }
        }

        $stocks = $query->orderBy('quantity', 'asc')->paginate(20);

        // Get summary stats
        $stats = [
            'total_items' => Stock::count(),
            'out_of_stock' => Stock::where('quantity', 0)->count(),
            'low_stock' => Stock::whereColumn('quantity', '<=', 'reorder_level')
                ->whereNotNull('reorder_level')
                ->where('quantity', '>', 0)
                ->count(),
            'total_value' => DB::table('stocks')
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->sum(DB::raw('stocks.quantity * products.price'))
        ];

        return view('inventory.index', compact('stocks', 'stats'));
    }

    /**
     * Display purchases list page
     */
    public function purchases(Request $request)
    {
        $query = Purchase::with(['company', 'items']);

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // Date filter
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('date_created', [
                Carbon::parse($request->from_date)->startOfDay(),
                Carbon::parse($request->to_date)->endOfDay()
            ]);
        }

        $purchases = $query->orderBy('date_created', 'desc')->paginate(20);

        // Get summary
        $stats = [
            'total_purchases' => Purchase::count(),
            'total_amount' => Purchase::sum('total'),
            'pending' => Purchase::where('status', 'pending')->count(),
            'received' => Purchase::where('status', 'received')->count(),
        ];

        return view('purchases.index', compact('purchases', 'stats'));
    }

    /**
     * Display Z-Reports list page
     */
    public function zreports(Request $request)
    {
        $query = ZReport::with('company');

        // Date filter
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('report_date', [
                Carbon::parse($request->from_date)->startOfDay(),
                Carbon::parse($request->to_date)->endOfDay()
            ]);
        } else {
            // Default to last 30 days
            $query->where('report_date', '>=', Carbon::now()->subDays(30));
        }

        // Device filter
        if ($request->has('device_id') && $request->device_id != 'all') {
            $query->where('device_id', $request->device_id);
        }

        $reports = $query->orderBy('report_date', 'desc')->paginate(31);

        // Get summary for the period
        $summary = [
            'total_reports' => $reports->total(),
            'total_transactions' => $reports->sum('total_transactions'),
            'gross_sales' => $reports->sum('gross_sales'),
            'net_sales' => $reports->sum('net_sales'),
            'total_tax' => $reports->sum('total_tax'),
        ];

        // Get unique devices for filter
        $devices = ZReport::select('device_id')
            ->distinct()
            ->whereNotNull('device_id')
            ->orderBy('device_id')
            ->pluck('device_id');

        return view('zreports.index', compact('reports', 'summary', 'devices'));
    }
}
