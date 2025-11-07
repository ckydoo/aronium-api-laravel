<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Z-Reports') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white border-l-4 border-blue-500 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Reports</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($summary['total_reports']) }}</p>
                    </div>
                </div>

                <div class="bg-white border-l-4 border-green-500 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <p class="text-sm font-medium text-gray-600 mb-1">Transactions</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($summary['total_transactions']) }}</p>
                    </div>
                </div>

                <div class="bg-white border-l-4 border-purple-500 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <p class="text-sm font-medium text-gray-600 mb-1">Gross Sales</p>
                        <p class="text-2xl font-semibold text-gray-900">${{ number_format($summary['gross_sales'], 2) }}</p>
                    </div>
                </div>

                <div class="bg-white border-l-4 border-orange-500 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <p class="text-sm font-medium text-gray-600 mb-1">Net Sales</p>
                        <p class="text-2xl font-semibold text-gray-900">${{ number_format($summary['net_sales'], 2) }}</p>
                    </div>
                </div>

                <div class="bg-white border-l-4 border-indigo-500 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Tax</p>
                        <p class="text-2xl font-semibold text-gray-900">${{ number_format($summary['total_tax'], 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4">
                    <form method="GET" action="{{ route('dashboard.z-reports') }}" class="flex flex-wrap gap-3 items-end">
                        <!-- Date Range -->
                        <div class="min-w-[150px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" name="from_date"
                                   value="{{ request('from_date', now()->subDays(30)->format('Y-m-d')) }}"
                                   class="w-full rounded-lg border-gray-300">
                        </div>

                        <div class="min-w-[150px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" name="to_date"
                                   value="{{ request('to_date', now()->format('Y-m-d')) }}"
                                   class="w-full rounded-lg border-gray-300">
                        </div>

                        <!-- Device Filter -->
                        <div class="min-w-[150px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                            <select name="device_id" class="w-full rounded-lg border-gray-300">
                                <option value="all">All Devices</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device }}" {{ request('device_id') == $device ? 'selected' : '' }}>
                                        Device {{ $device }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Filter
                            </button>
                            <a href="{{ route('dashboard.z-reports') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Z-Reports Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        Daily Z-Reports ({{ $reports->total() }} total)
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items Sold</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Sales</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discounts</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Sales</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tax</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reports as $report)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $report->report_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $report->device_id ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($report->total_transactions) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ number_format($report->total_items_sold) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            ${{ number_format($report->gross_sales, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                            -${{ number_format($report->discounts, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                            ${{ number_format($report->net_sales, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            ${{ number_format($report->total_tax, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                            No Z-Reports found for the selected period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-gray-50 font-semibold">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-sm text-gray-900">Period Total:</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($summary['total_transactions']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ${{ number_format($summary['gross_sales'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        ${{ number_format($summary['net_sales'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ${{ number_format($summary['total_tax'], 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $reports->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
