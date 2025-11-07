<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Purchase;
use App\Models\ZReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get user's company
        if (!$user->company_id) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Please select a company to view your dashboard.');
        }

        $company = Company::findOrFail($user->company_id);

        // Date range filter (default to last 30 days)
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Get dashboard statistics
        $stats = $this->getDashboardStats($company->id, $startDate, $endDate);

        // Get recent sales
        $recentSales = Sale::where('company_id', $company->id)
            ->with('items')
            ->latest('date_created')
            ->limit(10)
            ->get();

        // Get low stock items
        $lowStockItems = Stock::where('company_id', $company->id)
            ->with('product')
            ->whereColumn('available_quantity', '<=', 'reorder_level')
            ->whereNotNull('reorder_level')
            ->orderBy('available_quantity', 'asc')
            ->limit(10)
            ->get();

        // Get sales chart data (last 30 days)
        $salesChartData = $this->getSalesChartData($company->id, $startDate, $endDate);

        return view('dashboard', compact(
            'company',
            'stats',
            'recentSales',
            'lowStockItems',
            'salesChartData',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats($companyId, $startDate, $endDate)
    {
        $sales = Sale::where('company_id', $companyId)
            ->whereBetween('date_created', [$startDate, $endDate]);

        $totalRevenue = $sales->sum('total');
        $totalSales = $sales->count();
        $fiscalizedSales = $sales->where('status', 'fiscalized')->count();
        $pendingSales = $sales->where('status', 'pending')->count();

        // Product stats
        $totalProducts = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $lowStockCount = Stock::where('company_id', $companyId)
            ->whereColumn('available_quantity', '<=', 'reorder_level')
            ->whereNotNull('reorder_level')
            ->count();

        $outOfStockCount = Stock::where('company_id', $companyId)
            ->where('available_quantity', '<=', 0)
            ->count();

        // Purchase stats
        $totalPurchases = Purchase::where('company_id', $companyId)
            ->whereBetween('date_created', [$startDate, $endDate])
            ->sum('total');

        // Average sale value
        $avgSaleValue = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        // Compare with previous period
        $previousStartDate = Carbon::parse($startDate)->subDays(
            Carbon::parse($endDate)->diffInDays($startDate)
        )->format('Y-m-d');

        $previousRevenue = Sale::where('company_id', $companyId)
            ->whereBetween('date_created', [$previousStartDate, $startDate])
            ->sum('total');

        $revenueChange = $previousRevenue > 0
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100
            : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_sales' => $totalSales,
            'fiscalized_sales' => $fiscalizedSales,
            'pending_sales' => $pendingSales,
            'total_products' => $totalProducts,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'total_purchases' => $totalPurchases,
            'avg_sale_value' => $avgSaleValue,
            'revenue_change' => $revenueChange,
        ];
    }

    /**
     * Get sales chart data for visualization
     */
    private function getSalesChartData($companyId, $startDate, $endDate)
    {
        $salesByDate = Sale::where('company_id', $companyId)
            ->whereBetween('date_created', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(date_created) as date'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $salesByDate->pluck('date')->map(fn($date) =>
                Carbon::parse($date)->format('M d')
            )->toArray(),
            'revenue' => $salesByDate->pluck('total')->toArray(),
            'count' => $salesByDate->pluck('count')->toArray(),
        ];
    }

    /**
     * Sales listing page
     */
    public function sales(Request $request)
    {
        $user = auth()->user();
        $company = Company::findOrFail($user->company_id);

        $query = Sale::where('company_id', $company->id)
            ->with('items');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date_created', [
                $request->start_date,
                $request->end_date
            ]);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('document_number', 'like', "%{$request->search}%")
                  ->orWhere('fiscal_invoice_number', 'like', "%{$request->search}%");
            });
        }

        $sales = $query->latest('date_created')->paginate(20);

        return view('dashboard.sales', compact('company', 'sales'));
    }

    /**
     * Products listing page
     */
    public function products(Request $request)
    {
        $user = auth()->user();
        $company = Company::findOrFail($user->company_id);

        $query = Product::where('company_id', $company->id)
            ->with('stock');

        // Filters
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
                  ->orWhere('barcode', 'like', "%{$request->search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(20);

        return view('dashboard.products', compact('company', 'products'));
    }

    /**
     * Stock/Inventory listing page
     */
    public function stock(Request $request)
    {
        $user = auth()->user();
        $company = Company::findOrFail($user->company_id);

        $query = Stock::where('company_id', $company->id)
            ->with('product');

        // Filters
        if ($request->boolean('low_stock')) {
            $query->whereColumn('available_quantity', '<=', 'reorder_level')
                  ->whereNotNull('reorder_level');
        }

        if ($request->boolean('out_of_stock')) {
            $query->where('available_quantity', '<=', 0);
        }

        if ($request->filled('search')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $stocks = $query->paginate(20);

        return view('dashboard.stock', compact('company', 'stocks'));
    }
    public function inventory(Request $request)
    {
        $user = auth()->user();
        $company = Company::findOrFail($user->company_id);

        $query = Stock::where('company_id', $company->id)
            ->with('product');

        // Filters
        if ($request->boolean('low_stock')) {
            $query->whereColumn('available_quantity', '<=', 'reorder_level')
                  ->whereNotNull('reorder_level');
        }

        if ($request->boolean('out_of_stock')) {
            $query->where('available_quantity', '<=', 0);
        }

        if ($request->filled('search')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $stocks = $query->paginate(20);

        return view('dashboard.stock', compact('company', 'stocks'));
    }
    /**
     * Purchases listing page
     */
    public function purchases(Request $request)
    {
        $user = auth()->user();
        $company = Company::findOrFail($user->company_id);

        $query = Purchase::where('company_id', $company->id)
            ->with('items');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date_created', [
                $request->start_date,
                $request->end_date
            ]);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('document_number', 'like', "%{$request->search}%")
                  ->orWhere('supplier_name', 'like', "%{$request->search}%");
            });
        }

        $purchases = $query->latest('date_created')->paginate(20);

        return view('dashboard.purchases', compact('company', 'purchases'));
    }

    /**
     * Z-Reports listing page
     */
    public function zReports(Request $request)
    {
        $user = auth()->user();
        $company = Company::findOrFail($user->company_id);

        $query = ZReport::where('company_id', $company->id);

        // Filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('report_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        $zReports = $query->latest('report_date')->paginate(20);

        return view('dashboard.z-reports', compact('company', 'zReports'));
    }
}
