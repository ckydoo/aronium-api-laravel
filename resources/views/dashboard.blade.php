<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                {{ __('Business Dashboard') }}
            </h2>
            <div class="text-sm text-gray-600">
                {{ $company->name ?? 'Your Business' }}
            </div>
        </div>
    </x-slot>
<br>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Date Filter Section -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4">
                    <form method="GET" action="{{ route('dashboard') }}" id="dateFilterForm" class="flex flex-wrap gap-3 items-end">
                        <div class="flex gap-2">
                            <!-- Quick Filter Buttons -->
                            <button type="button" onclick="setFilter('today')"
                                class="quick-filter px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-150
                                {{ $dateFilter == 'today' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                Today
                            </button>
                            <button type="button" onclick="setFilter('yesterday')"
                                class="quick-filter px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-150
                                {{ $dateFilter == 'yesterday' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                Yesterday
                            </button>
                            <button type="button" onclick="setFilter('week')"
                                class="quick-filter px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-150
                                {{ $dateFilter == 'week' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                This Week
                            </button>
                            <button type="button" onclick="setFilter('month')"
                                class="quick-filter px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-150
                                {{ $dateFilter == 'month' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                This Month
                            </button>
                            <button type="button" onclick="setFilter('year')"
                                class="quick-filter px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-150
                                {{ $dateFilter == 'year' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                This Year
                            </button>
                        </div>

                        <!-- Custom Date Range -->
                        <div class="flex gap-2 items-center">
                            <label class="text-sm font-medium text-gray-700">Custom:</label>
                            <input type="date" name="from_date" id="from_date"
                                value="{{ request('from_date') }}"
                                class="rounded-lg border-gray-300 text-sm"
                                onchange="setFilter('custom')">
                            <span class="text-gray-500">to</span>
                            <input type="date" name="to_date" id="to_date"
                                value="{{ request('to_date') }}"
                                class="rounded-lg border-gray-300 text-sm"
                                onchange="setFilter('custom')">
                        </div>

                        <input type="hidden" name="date_filter" id="date_filter" value="{{ $dateFilter }}">
                    </form>
                </div>
            </div>

            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

                <!-- Total Revenue Card -->
                <div class="bg-white border-l-4 border-blue-500 overflow-hidden shadow-lg sm:rounded-lg transform hover:scale-105 transition-transform duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total Revenue</h3>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 mb-1">
                            ${{ number_format($salesData['total_revenue'], 2) }}
                        </div>
                        <div class="flex items-center text-sm">
                            @if($salesData['revenue_change'] >= 0)
                                <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-green-600 font-medium">+{{ number_format($salesData['revenue_change'], 1) }}%</span>
                                <span class="text-gray-500 ml-1">from previous period</span>
                            @else
                                <svg class="w-4 h-4 mr-1 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-red-600 font-medium">{{ number_format($salesData['revenue_change'], 1) }}%</span>
                                <span class="text-gray-500 ml-1">from previous period</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Total Sales Card -->
                <div class="bg-white border-l-4 border-green-500 overflow-hidden shadow-lg sm:rounded-lg transform hover:scale-105 transition-transform duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total Sales</h3>
                            <div class="p-3 bg-green-100 rounded-full">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 mb-1">
                            {{ number_format($salesData['total_sales']) }}
                        </div>
                        <div class="text-sm text-gray-500">
                            Transactions recorded
                        </div>
                    </div>
                </div>

                <!-- Average Sale Card -->
                <div class="bg-white border-l-4 border-purple-500 overflow-hidden shadow-lg sm:rounded-lg transform hover:scale-105 transition-transform duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Average Sale</h3>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 mb-1">
                            ${{ number_format($salesData['average_sale'], 2) }}
                        </div>
                        <div class="text-sm text-gray-500">
                            Per transaction
                        </div>
                    </div>
                </div>

                <!-- Fiscalization Status Card -->
                <div class="bg-white border-l-4 border-orange-500 overflow-hidden shadow-lg sm:rounded-lg transform hover:scale-105 transition-transform duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">Fiscalization Rate</h3>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 mb-1">
                            {{ number_format($salesData['fiscalization_rate'], 1) }}%
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $salesData['fiscalized_sales'] }} of {{ $salesData['total_sales'] }} fiscalized
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Metrics Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

                <!-- Pending Sales -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending Sales</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $salesData['pending_sales'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Sales -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-100 rounded-lg p-3">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Error Sales</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $salesData['error_sales'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Tax Collected -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Tax</p>
                                <p class="text-2xl font-semibold text-gray-900">${{ number_format($salesData['total_tax'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Discounts -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-pink-100 rounded-lg p-3">
                                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Discounts</p>
                                <p class="text-2xl font-semibold text-gray-900">${{ number_format($salesData['total_discount'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

                <!-- Sales Trend Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Sales Trend (Last 7 Days)</h3>
                        <div class="h-64">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Selling Products -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Selling Products</h3>
                        <div class="space-y-3">
                            @forelse($topProducts as $product)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-150">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ $product->product_name }}</p>
                                        <p class="text-sm text-gray-600">{{ number_format($product->total_quantity) }} units sold</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-green-600">${{ number_format($product->total_revenue, 2) }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-8">No sales data available for this period</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-teal-100 rounded-lg p-3">
                                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Products</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ number_format($inventoryData['total_products']) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Stock Value</p>
                                <p class="text-2xl font-semibold text-gray-900">${{ number_format($inventoryData['total_stock_value'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Low Stock</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $inventoryData['low_stock_products'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-100 rounded-lg p-3">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Out of Stock</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $inventoryData['out_of_stock_products'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sales Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Sales</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($recentSales as $sale)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($sale->date_created)->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $sale->document_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            ${{ number_format($sale->total, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($sale->status == 'fiscalized')
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Fiscalized
                                                </span>
                                            @elseif($sale->status == 'pending')
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                            @else
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Error
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $sale->items->count() }} items
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                            No sales recorded for this period
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Sales Trend Chart
        const salesTrendData = @json($salesTrend);
        const ctx = document.getElementById('salesTrendChart').getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: salesTrendData.map(item => item.date),
                datasets: [{
                    label: 'Daily Sales',
                    data: salesTrendData.map(item => item.sales),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Date Filter Functions
        function setFilter(filter) {
            document.getElementById('date_filter').value = filter;

            if (filter === 'custom') {
                // Don't auto-submit for custom, let user pick dates
                return;
            }

            // Clear custom dates for quick filters
            if (filter !== 'custom') {
                document.getElementById('from_date').value = '';
                document.getElementById('to_date').value = '';
            }

            document.getElementById('dateFilterForm').submit();
        }
    </script>
</x-app-layout>
