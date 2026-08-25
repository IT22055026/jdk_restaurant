<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shift & Till Management — Restaurant BYOB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .badge-success { @apply bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium; }
        .badge-danger { @apply bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium; }
        .badge-warning { @apply bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium; }
        .stat-card { @apply bg-white rounded-lg shadow p-6 border-l-4; }
        .stat-card.sales { @apply border-blue-500; }
        .stat-card.discounts { @apply border-orange-500; }
        .stat-card.tax { @apply border-green-500; }
    </style>
    @include('layouts.dark-mode')
</head>
<body class="bg-gray-50">
    @include('layouts.navbar')

    <div class="flex">
        @include('components.sidebar', ['modules' => $modules])

        <div class="flex-1 ml-0 lg:ml-64 pt-20">
            <div class="p-4 lg:p-8">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center space-x-3">
                        <i class="fas fa-clock text-red-600"></i>
                        <span>Shift & Till Management</span>
                    </h1>
                    <p class="text-gray-600 mt-2">Manage your shifts, track till movements, and reconcile accounts</p>
                </div>

                <!-- Active Shift Section -->
                @if($activeShift)
                    <div class="mb-8 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-green-900">Active Shift</h2>
                                <p class="text-green-700 mt-1">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    Started at {{ $activeShift->started_at ? $activeShift->started_at->format('h:i A') : 'Just now' }}
                                </p>
                            </div>
                            <span class="badge-success">
                                <i class="fas fa-circle text-green-500 animate-pulse"></i> ACTIVE
                            </span>
                        </div>

                        <!-- Active Shift Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="stat-card sales">
                                <p class="text-gray-600 text-sm font-medium">Opening Balance</p>
                                <p class="text-2xl font-bold text-gray-900 mt-2">
                                    Rs. {{ number_format($activeShift->opening_balance ?? 0, 2) }}
                                </p>
                            </div>
                            <div class="stat-card sales">
                                <p class="text-gray-600 text-sm font-medium">Total Sales</p>
                                <p id="activeTotalSales" class="text-2xl font-bold text-blue-600 mt-2">
                                    Rs. 0.00
                                </p>
                            </div>
                            <div class="stat-card discounts">
                                <p class="text-gray-600 text-sm font-medium">Discounts</p>
                                <p id="activeDiscounts" class="text-2xl font-bold text-orange-600 mt-2">
                                    Rs. 0.00
                                </p>
                            </div>
                            <div class="stat-card">
                                <p class="text-gray-600 text-sm font-medium">Expected Total</p>
                                <p id="activeExpectedTotal" class="text-2xl font-bold text-gray-900 mt-2">
                                    Rs. {{ number_format($activeShift->opening_balance, 2) }}
                                </p>
                            </div>
                        </div>

                        <!-- Close Shift Button -->
                        <button onclick="openCloseShiftModal({{ $activeShift->id }})" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Close Shift</span>
                        </button>
                    </div>
                @else
                    <div class="mb-8 bg-blue-50 border-2 border-blue-200 rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold text-blue-900">No Active Shift</h2>
                                <p class="text-blue-700 mt-1">Start a new shift to begin tracking sales and till movements</p>
                            </div>
                            <button onclick="openStartShiftModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center space-x-2">
                                <i class="fas fa-play-circle"></i>
                                <span>Start Shift</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Shift History Section -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Shift History</h2>

                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-100 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Duration</th>
                                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Opening</th>
                                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Sales</th>
                                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Expected</th>
                                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Actual</th>
                                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Variance</th>
                                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Status</th>
                                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse($shifts as $shift)
                                        @php
                                            $sales = $shift->transactions()->where('transaction_type', 'sale')->sum('amount');
                                            $startTime = $shift->started_at ?? $shift->created_at;
                                            $duration = $shift->ended_at ? $shift->ended_at->diffForHumans($startTime, ['syntax' => 'long']) : 'Active';
                                        @endphp
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 text-sm">
                                                <div class="font-medium text-gray-900">{{ $startTime->format('M d, Y') }}</div>
                                                <div class="text-gray-600">{{ $startTime->format('h:i A') }} - {{ $shift->ended_at?->format('h:i A') ?? 'Active' }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $duration }}</td>
                                            <td class="px-6 py-4 text-sm text-right text-gray-900 font-medium">
                                                Rs. {{ number_format($shift->opening_balance, 2) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-right text-blue-600 font-medium">
                                                Rs. {{ number_format($sales, 2) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-right text-gray-900">
                                                Rs. {{ number_format($shift->expected_total ?? 0, 2) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-right text-gray-900 font-medium">
                                                Rs. {{ number_format($shift->actual_total ?? 0, 2) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-center font-medium">
                                                @if($shift->variance !== null)
                                                    @if($shift->variance == 0)
                                                        <span class="badge-success"><i class="fas fa-check"></i> Rs. 0.00</span>
                                                    @elseif($shift->variance > 0)
                                                        <span class="badge-success text-green-600">+Rs. {{ number_format($shift->variance, 2) }}</span>
                                                    @else
                                                        <span class="badge-danger">-Rs. {{ number_format(abs($shift->variance), 2) }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-500">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                @if($shift->status === 'active')
                                                    <span class="badge-success"><i class="fas fa-circle text-green-500 animate-pulse"></i> Active</span>
                                                @else
                                                    <span class="badge-success"><i class="fas fa-check-circle"></i> Closed</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <button onclick="viewShiftDetails({{ $shift->id }})" class="text-blue-600 hover:text-blue-900 font-medium text-xs bg-blue-50 hover:bg-blue-100 px-2.5 py-1.5 rounded transition inline-flex items-center gap-1" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                        <span>View</span>
                                                    </button>
                                                    <a href="{{ url('/shifts/' . $shift->id . '/pdf') }}" target="_blank" class="text-red-600 hover:text-red-900 font-medium text-xs bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded transition inline-flex items-center gap-1" title="Download PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                        <span>PDF</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-inbox text-4xl mb-4 block opacity-50"></i>
                                                <p>No shifts recorded yet. Start your first shift to begin!</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($shifts->hasPages())
                        <div class="mt-6">
                            {{ $shifts->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Start Shift Modal -->
    <div id="startShiftModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden flex flex-col my-auto max-h-[92vh] border border-gray-100">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-5 py-4 text-white flex items-center justify-between shrink-0 shadow-sm">
                <div>
                    <h2 class="text-lg font-bold flex items-center space-x-2">
                        <i class="fas fa-play-circle"></i>
                        <span>Start New Shift</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-blue-100">Optionally enter your opening float</p>
                </div>
                <button type="button" onclick="closeStartShiftModal()" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full p-1.5 transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <form id="startShiftForm" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <div class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Opening Balance (Till Float) <span class="text-slate-400 font-normal lowercase">— optional</span></label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-slate-500 font-bold text-sm">Rs.</span>
                            <input type="number" id="openingBalance" name="opening_balance" step="0.01" min="0"
                                class="w-full pl-12 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-bold text-slate-900 text-sm"
                                placeholder="0.00">
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Leave blank to start with Rs. 0.00 float</p>
                    </div>

                    <div class="bg-blue-50/80 border border-blue-200/80 rounded-xl p-3.5">
                        <p class="text-xs text-blue-900 flex items-start gap-2">
                            <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                            <span>Your shift will start immediately and all sales will be tracked. You can view and close this shift anytime.</span>
                        </p>
                    </div>
                </div>

                <div class="p-3.5 sm:p-4 bg-slate-50 border-t border-slate-200/80 flex gap-3 shrink-0">
                    <button type="button" onclick="closeStartShiftModal()" class="flex-1 bg-white hover:bg-slate-100 text-slate-700 font-bold py-2.5 px-4 rounded-xl border border-slate-300 shadow-xs transition text-sm">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-sm transition text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-play"></i>
                        <span>Start Shift</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Close Shift Modal -->
    <div id="closeShiftModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden flex flex-col my-auto max-h-[92vh] border border-gray-100">
            <div class="bg-gradient-to-r from-red-600 via-red-600 to-rose-700 px-5 py-3.5 text-white flex items-center justify-between shrink-0 shadow-sm">
                <div>
                    <h2 class="text-lg font-bold flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Close Shift</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-red-100">Reconcile till cash and complete shift</p>
                </div>
                <button type="button" onclick="closeCloseShiftModal()" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full p-1.5 transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <form id="closeShiftForm" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <input type="hidden" id="closeShiftId" name="shift_id">

                <div class="p-4 sm:p-5 space-y-3.5 overflow-y-auto flex-1">
                    <!-- Shift Summary Cards (Compact 3-column) -->
                    <div id="closeShiftSummary" class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 sm:p-3.5">
                        <div class="grid grid-cols-3 gap-2 text-center divide-x divide-slate-200">
                            <div class="px-1">
                                <span class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Opening</span>
                                <span id="closeSummaryOpening" class="block font-bold text-slate-800 text-sm mt-0.5">Rs. 0.00</span>
                            </div>
                            <div class="px-1">
                                <span class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Sales</span>
                                <span id="closeSummarySales" class="block font-bold text-blue-600 text-sm mt-0.5">Rs. 0.00</span>
                            </div>
                            <div class="px-1">
                                <span class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Expected</span>
                                <span id="closeSummaryExpected" class="block font-extrabold text-slate-900 text-sm mt-0.5">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Denominations Grid -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Count the Till (notes)</label>
                            <span class="text-[11px] text-slate-500">Enter note counts</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2" id="denominationRows">
                            @foreach ([5000, 1000, 500, 100, 50, 20] as $denom)
                                <div class="flex items-center border border-slate-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-red-500 focus-within:border-red-500 bg-white shadow-xs transition">
                                    <span class="bg-slate-100 text-slate-700 text-xs font-bold px-2 py-1.5 border-r border-slate-200 shrink-0">Rs.{{ $denom }}</span>
                                    <input type="number" class="denom-qty w-full px-2 py-1.5 text-center font-bold text-sm text-slate-900 focus:outline-none" data-denomination="{{ $denom }}"
                                        min="0" step="1" placeholder="0" oninput="recalcDenominationTotal()">
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2.5 flex justify-between items-center bg-slate-900 text-white rounded-xl px-4 py-2.5 shadow-sm">
                            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Total Counted:</span>
                            <span id="actualTotalDisplay" class="text-lg font-extrabold text-emerald-400">Rs. 0.00</span>
                        </div>
                    </div>

                    <!-- Variance Alert -->
                    <div id="varianceAlert" class="hidden rounded-xl p-3 text-xs font-semibold">
                        <p class="flex items-center space-x-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span id="varianceText"></span>
                        </p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Notes (Optional)</label>
                        <textarea id="notes" name="notes"
                            class="w-full px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none text-slate-800"
                            placeholder="Any discrepancies or notes..." rows="2"></textarea>
                    </div>

                    <!-- PDF Download Option -->
                    <div class="flex items-center space-x-2.5 bg-red-50/70 border border-red-200/80 rounded-xl p-2.5">
                        <input type="checkbox" id="downloadPdfOnClose" name="download_pdf" checked class="h-4 w-4 text-red-600 focus:ring-red-500 border-slate-300 rounded cursor-pointer">
                        <label for="downloadPdfOnClose" class="text-xs font-semibold text-red-950 cursor-pointer flex items-center gap-1.5 select-none">
                            <i class="fas fa-file-pdf text-red-600 text-sm"></i>
                            <span>Download shift reconciliation report as PDF</span>
                        </label>
                    </div>
                </div>

                <!-- Fixed Modal Actions Footer -->
                <div class="p-3.5 sm:p-4 bg-slate-50 border-t border-slate-200/80 flex gap-3 shrink-0">
                    <button type="button" onclick="closeCloseShiftModal()" class="flex-1 bg-white hover:bg-slate-100 text-slate-700 font-bold py-2.5 px-4 rounded-xl border border-slate-300 shadow-xs transition text-sm">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-sm transition text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Close Shift</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Shift Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden flex flex-col my-auto max-h-[92vh] border border-gray-100">
            <div class="bg-gradient-to-r from-slate-700 to-slate-800 px-5 py-4 text-white flex items-center justify-between shrink-0 shadow-sm">
                <div>
                    <h2 class="text-lg font-bold flex items-center space-x-2">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Shift Details</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-300">Complete summary and reconciliation breakdown</p>
                </div>
                <button type="button" onclick="closeDetailsModal()" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full p-1.5 transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <div id="detailsContent" class="p-4 sm:p-6 overflow-y-auto flex-1">
                <!-- Loading spinner -->
                <div class="flex justify-center items-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-slate-400"></i>
                </div>
            </div>

            <div class="bg-slate-50 p-3.5 sm:p-4 border-t border-slate-200/80 flex justify-between items-center shrink-0">
                <a id="modalDownloadPdfBtn" href="#" target="_blank" class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-bold py-2 px-4 rounded-xl shadow-xs transition text-sm inline-flex items-center space-x-2">
                    <i class="fas fa-file-pdf"></i>
                    <span>Download PDF</span>
                </a>
                <button onclick="closeDetailsModal()" class="bg-white hover:bg-slate-100 text-slate-700 font-bold py-2 px-6 rounded-xl border border-slate-300 shadow-xs transition text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function openStartShiftModal() {
            document.getElementById('startShiftModal').classList.remove('hidden');
        }

        function closeStartShiftModal() {
            document.getElementById('startShiftModal').classList.add('hidden');
        }

        function openCloseShiftModal(shiftId) {
            document.getElementById('closeShiftId').value = shiftId;
            document.querySelectorAll('.denom-qty').forEach(input => input.value = '');
            recalcDenominationTotal();
            document.getElementById('closeShiftModal').classList.remove('hidden');
            fetchActiveShiftData(shiftId);
        }

        function recalcDenominationTotal() {
            const total = Array.from(document.querySelectorAll('.denom-qty')).reduce((sum, input) => {
                const qty = parseInt(input.value) || 0;
                const denomination = parseInt(input.dataset.denomination);
                return sum + (qty * denomination);
            }, 0);
            document.getElementById('actualTotalDisplay').textContent = 'Rs. ' + total.toFixed(2);
            if (window.currentExpectedTotal !== undefined) {
                calculateVariance(window.currentExpectedTotal);
            }
            return total;
        }

        function closeCloseShiftModal() {
            document.getElementById('closeShiftModal').classList.add('hidden');
        }

        function viewShiftDetails(shiftId) {
            document.getElementById('detailsModal').classList.remove('hidden');
            fetchShiftDetails(shiftId);
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        // Fetch active shift data
        function fetchActiveShiftData(shiftId) {
            fetch('{{ route("shifts.active") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.active && data.shift) {
                        const shift = data.shift;
                        document.getElementById('closeSummaryOpening').textContent = 'Rs. ' + parseFloat(shift.opening_balance).toFixed(2);
                        document.getElementById('closeSummarySales').textContent = 'Rs. ' + parseFloat(shift.total_sales).toFixed(2);
                        document.getElementById('closeSummaryExpected').textContent = 'Rs. ' + parseFloat(shift.current_total).toFixed(2);

                        window.currentExpectedTotal = shift.current_total;
                    }
                });
        }

        function calculateVariance(expectedTotal) {
            const actualTotal = Array.from(document.querySelectorAll('.denom-qty')).reduce((sum, input) => {
                const qty = parseInt(input.value) || 0;
                const denomination = parseInt(input.dataset.denomination);
                return sum + (qty * denomination);
            }, 0);
            const variance = actualTotal - expectedTotal;
            const varianceAlert = document.getElementById('varianceAlert');
            const varianceText = document.getElementById('varianceText');

            if (variance !== 0) {
                varianceAlert.classList.remove('hidden');
                if (variance > 0) {
                    varianceAlert.classList.add('bg-green-50', 'border', 'border-green-200');
                    varianceAlert.classList.remove('bg-red-50', 'border-red-200');
                    varianceText.textContent = `Over by Rs. ${variance.toFixed(2)} - Great job!`;
                } else {
                    varianceAlert.classList.add('bg-red-50', 'border', 'border-red-200');
                    varianceAlert.classList.remove('bg-green-50', 'border-green-200');
                    varianceText.textContent = `Short by Rs. ${Math.abs(variance).toFixed(2)} - Check your transactions`;
                }
            } else {
                varianceAlert.classList.add('hidden');
            }
        }

        // Fetch and display shift details
        function fetchShiftDetails(shiftId) {
            fetch(`/shifts/${shiftId}`)
                .then(response => response.json())
                .then(data => {
                    const shift = data.shift;
                    const html = `
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-gray-600 text-sm">Staff Member</p>
                                    <p class="text-lg font-semibold text-gray-900">${shift.user_name}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Status</p>
                                    <p class="text-lg font-semibold">
                                        <span class="badge-${shift.status === 'active' ? 'success' : 'success'}">
                                            ${shift.status === 'active' ? 'Active' : 'Closed'}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Started</p>
                                    <p class="text-lg font-semibold text-gray-900">${shift.started_at}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Ended</p>
                                    <p class="text-lg font-semibold text-gray-900">${shift.ended_at || 'In Progress'}</p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="font-semibold text-gray-900 mb-4">Financial Summary</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-gray-400">
                                        <p class="text-gray-600 text-sm">Opening Balance</p>
                                        <p class="text-2xl font-bold text-gray-900">Rs. ${parseFloat(shift.opening_balance).toFixed(2)}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500">
                                        <p class="text-gray-600 text-sm">Total Sales</p>
                                        <p class="text-2xl font-bold text-blue-600">Rs. ${parseFloat(shift.total_sales).toFixed(2)}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-orange-500">
                                        <p class="text-gray-600 text-sm">Total Discounts</p>
                                        <p class="text-2xl font-bold text-orange-600">-Rs. ${parseFloat(shift.total_discounts).toFixed(2)}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-green-500">
                                        <p class="text-gray-600 text-sm">Total Tax</p>
                                        <p class="text-2xl font-bold text-green-600">Rs. ${parseFloat(shift.total_tax).toFixed(2)}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-purple-500 md:col-span-2">
                                        <p class="text-gray-600 text-sm">Expected Total</p>
                                        <p class="text-2xl font-bold text-gray-900">Rs. ${parseFloat(shift.expected_total).toFixed(2)}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-gray-400 md:col-span-2">
                                        <p class="text-gray-600 text-sm">Actual Total</p>
                                        <p class="text-2xl font-bold text-gray-900">Rs. ${parseFloat(shift.actual_total).toFixed(2)}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 ${shift.variance === 0 ? 'border-green-500' : (shift.variance > 0 ? 'border-green-500' : 'border-red-500')} md:col-span-2">
                                        <p class="text-gray-600 text-sm">Variance</p>
                                        <p class="text-2xl font-bold ${shift.variance === 0 ? 'text-green-600' : (shift.variance > 0 ? 'text-green-600' : 'text-red-600')}">
                                            ${shift.variance > 0 ? '+' : ''}Rs. ${parseFloat(shift.variance).toFixed(2)}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            ${(shift.denominations && shift.denominations.length > 0) ? `
                                <div class="border-t border-gray-200 pt-6">
                                    <h3 class="font-semibold text-gray-900 mb-4">Cash Denomination Breakdown</h3>
                                    <div class="grid grid-cols-3 gap-2">
                                        ${shift.denominations.map(d => `
                                            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 flex justify-between text-sm">
                                                <span class="text-gray-600">Rs.${d.denomination} × ${d.quantity}</span>
                                                <span class="font-semibold text-gray-900">Rs. ${parseFloat(d.subtotal).toFixed(2)}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            ${shift.notes ? `
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p class="text-sm font-medium text-blue-900 mb-1">Notes</p>
                                    <p class="text-sm text-blue-800">${shift.notes}</p>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    document.getElementById('detailsContent').innerHTML = html;
                    if (document.getElementById('modalDownloadPdfBtn')) {
                        document.getElementById('modalDownloadPdfBtn').href = `/shifts/${shift.id}/pdf`;
                    }
                });
        }

        // Update active shift stats
        function updateActiveShiftStats() {
            fetch('{{ route("shifts.active") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.active && data.shift) {
                        const shift = data.shift;
                        document.getElementById('activeTotalSales').textContent = 'Rs. ' + parseFloat(shift.total_sales).toFixed(2);
                        document.getElementById('activeDiscounts').textContent = 'Rs. ' + parseFloat(shift.total_discounts).toFixed(2);
                        document.getElementById('activeExpectedTotal').textContent = 'Rs. ' + parseFloat(shift.current_total).toFixed(2);
                    }
                });
        }

        // Form submissions
        document.getElementById('startShiftForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Starting...';

            const openingBalanceVal = document.getElementById('openingBalance').value;
            const payload = {
                opening_balance: openingBalanceVal !== '' ? parseFloat(openingBalanceVal) : null,
            };

            fetch('{{ route("shifts.start") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    closeStartShiftModal();
                    document.getElementById('startShiftForm').reset();
                    window.location.href = '{{ route("pos.index") }}';
                } else {
                    alert(data.message || 'Failed to start shift. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to start shift. Please check your connection and try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        document.getElementById('closeShiftForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const denominations = Array.from(document.querySelectorAll('.denom-qty')).map(input => ({
                denomination: parseInt(input.dataset.denomination),
                quantity: parseInt(input.value) || 0,
            }));

            const payload = {
                shift_id: document.getElementById('closeShiftId').value,
                denominations: denominations,
                notes: document.getElementById('notes').value,
            };

            const shouldDownloadPdf = document.getElementById('downloadPdfOnClose') && document.getElementById('downloadPdfOnClose').checked;

            fetch('{{ route("shifts.close") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (shouldDownloadPdf && data.pdf_url) {
                        const link = document.createElement('a');
                        link.href = data.pdf_url;
                        link.target = '_blank';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                    alert('Shift closed successfully!');
                    closeCloseShiftModal();
                    document.getElementById('closeShiftForm').reset();
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Auto-update active shift stats every 10 seconds
        setInterval(updateActiveShiftStats, 10000);
    </script>
</body>
</html>
