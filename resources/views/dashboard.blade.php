<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Dashboard') }} - {{ $company->name }}
            </h2>
            
            <!-- Date Range Filter -->
            <form method="GET" class="flex gap-2">
                <input type="date" name="start_date" value="{{ $startDate }}" 
                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <input type="date" name="end_date" value="{{ $endDate }}" 
                    class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Filter
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Total Revenue -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</p>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                ${{ number_format($stats['total_revenue'], 2) }}
                            </h3>
                            @if($stats['revenue_change'] != 0)
                                <p class="text-xs mt-2 {{ $stats['revenue_change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $stats['revenue_change'] > 0 ? '↑' : '↓' }} 
                                    {{ abs(number_format($stats['revenue_change'], 1)) }}% from previous period
                                </p>
                            @endif
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Sales -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Sales</p>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ number_format($stats['total_sales']) }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                {{ $stats['fiscalized_sales'] }} fiscalized
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Average Sale Value -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Avg. Sale Value</p>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                ${{ number_format($stats['avg_sale_value'], 2) }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                {{ $stats['pending_sales'] }} pending
                            </p>
                        </div>
                        <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Products & Stock -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Products</p>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ number_format($stats['total_products']) }}
                            </h3>
                            @if($stats['low_stock_count'] > 0)
                                <p class="text-xs text-orange-600 dark:text-orange-400 mt-2">
                                    {{ $stats['low_stock_count'] }} low stock
                                </p>
                            @endif
                        </div>
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Sales Chart -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sales Trend</h3>
                    <canvas id="salesChart" height="200"></canvas>
                </div>

                <!-- Low Stock Alert -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Low Stock Alerts</h3>
                    @if($lowStockItems->count() > 0)
                        <div class="space-y-3 max-h-[300px] overflow-y-auto">
                            @foreach($lowStockItems as $stock)
                                <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $stock->product->name }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $stock->product->code }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-red-600 dark:text-red-400">
                                            {{ number_format($stock->available_quantity, 0) }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Reorder at {{ $stock->reorder_level }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('dashboard.stock', ['low_stock' => 1]) }}" 
                           class="block mt-4 text-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            View all low stock items →
                        </a>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No low stock items</p>
                    @endif
                </div>
            </div>

            <!-- Recent Sales -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Sales</h3>
                        <a href="{{ route('dashboard.sales') }}" 
                           class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            View all →
                        </a>
                    </div>
                    
                    @if($recentSales->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Document</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Items</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($recentSales as $sale)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $sale->document_number }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $sale->date_created->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $sale->items->count() }} items
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                ${{ number_format($sale->total, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $sale->status === 'fiscalized' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                       ($sale->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                                       'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                                    {{ ucfirst($sale->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No recent sales</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
   
    </script>
</x-app-layout>