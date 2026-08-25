<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Report — Restaurant BYOB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f1f5f9 0%, #e9eef5 100%); }

        .table-row {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.2s;
        }
        .table-row:hover { background: #f8fafc; }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-token { background: #ffedd5; color: #9a3412; }
        .badge-completed { background: #dcfce7; color: #166534; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-revoked { background: #fde68a; color: #92400e; }
        .badge-hold { background: #fef3c7; color: #92400e; }
        .badge-open { background: #dbeafe; color: #1e40af; }

        .btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #e2e8f0; color: #1e293b; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 50;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }

        .modal-content {
            background: #fff;
            border-radius: 12px;
            width: 92%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
        }

        .main-content { margin-left: 256px; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
    </style>
    @include('layouts.dark-mode')
</head>
<body>

    @include('layouts.navbar')

    <div class="flex" style="padding-top: 67px;">
        <x-sidebar :modules="$modules ?? []" />

        <div class="flex-1 main-content px-6 py-8">
            <div class="w-full max-w-screen-2xl">

                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Sales Report</h1>
                    <p class="text-gray-600">Every sale and what happened to it — completed, held, discarded, revoked, and open</p>
                </div>

                <div class="bg-white rounded-lg p-6 mb-6 border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" id="searchInput" placeholder="Order #, customer name..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All statuses</option>
                                <option value="completed">Completed</option>
                                <option value="hold">On Hold</option>
                                <option value="cancelled">Discarded / Revoked</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From</label>
                            <input type="date" id="dateFromInput" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">To</label>
                            <input type="date" id="dateToInput" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div style="display: flex; align-items: flex-end; gap: 8px;">
                            <button onclick="loadSalesReport(true)" class="flex-1 btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <button onclick="resetFilters()" class="flex-1 btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-6 mb-6 border border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="summaryBar">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Revenue (completed)</p>
                            <p class="text-xl font-bold text-gray-900" id="summaryRevenue">Rs. 0.00</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Completed Bills</p>
                            <p class="text-xl font-bold text-gray-900" id="summaryCount">0</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Avg Bill Value</p>
                            <p class="text-xl font-bold text-gray-900" id="summaryAvg">Rs. 0.00</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-100" id="paymentBreakdownBar"></div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <tr>
                                    <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Order #</th>
                                    <th style="text-align: center; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Token</th>
                                    <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Customer</th>
                                    <th style="text-align: center; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Payment</th>
                                    <th style="text-align: right; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Total</th>
                                    <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Status</th>
                                    <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Date</th>
                                    <th style="text-align: center; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="billsTableBody">
                                <tr style="height: 80px;">
                                    <td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="billsPaginationContainer"></div>
                </div>

            </div>
        </div>
    </div>

    <!-- View Items Modal -->
    <div id="itemsModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #1e293b;">Bill Items</h3>
                <button onclick="closeItemsModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="itemsModalContent">
                <div style="text-align:center; padding: 24px 0;"><i class="fas fa-spinner fa-spin" style="font-size: 22px; color: #2563eb;"></i></div>
            </div>
        </div>
    </div>

    <!-- Revoke / Discard Bill Modal (shared — copy + endpoint swap based on the target bill's status) -->
    <div id="revokeModal" class="modal">
        <div class="modal-content" style="max-width: 420px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 id="revokeModalTitle" style="font-size: 18px; font-weight: 700; color: #1e293b;"><i class="fas fa-rotate-left" style="color:#dc2626; margin-right:6px;"></i>Revoke Bill</h3>
                <button onclick="closeRevokeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p id="revokeModalCopy" style="font-size: 13px; color: #64748b; margin: 0 0 10px;">
                This reverses a paid bill — its items are added back to stock, its revenue is removed from reports, and it's recorded as revoked for audit. This cannot be undone.
            </p>
            <textarea id="revokeReasonInput" rows="3" placeholder="Reason…"
                style="width:100%; font-size:13px; border:1.5px solid #e2e8f0; border-radius:8px; padding:10px; outline:none; resize:none;"></textarea>
            <div style="display:flex; gap:8px; margin-top:14px;">
                <button onclick="closeRevokeModal()" class="btn btn-secondary" style="flex:1; justify-content:center;">Cancel</button>
                <button id="revokeModalConfirmBtn" onclick="submitRevoke()" class="btn btn-danger" style="flex:1; justify-content:center;">Revoke Bill</button>
            </div>
        </div>
    </div>

    <script>
        const STATUS_BADGES = {
            completed: { label: 'Completed', cls: 'badge-completed' },
            hold: { label: 'On Hold', cls: 'badge-hold' },
            pending: { label: 'Pending', cls: 'badge-open' },
            confirmed: { label: 'Confirmed', cls: 'badge-open' },
        };

        const PAYMENT_METHODS = {
            cash: { label: 'Cash', cls: 'background:#dcfce7; color:#166534;' },
            card: { label: 'Card', cls: 'background:#dbeafe; color:#1e40af;' },
            bank_transfer: { label: 'Bank', cls: 'background:#e9d5ff; color:#7e22ce;' },
            mixed: { label: 'Mixed / Split', cls: 'background:#fed7aa; color:#9a3412;' },
            pickme: { label: 'PickMe', cls: 'background:#fee2e2; color:#991b1b;' },
            uber: { label: 'Uber', cls: 'background:#111827; color:#fff;' },
        };

        function paymentLabel(method) {
            return method ? (PAYMENT_METHODS[method]?.label || method.replace('_', ' ')) : '—';
        }

        // Client-side pagination — the API returns every bill matching the
        // current search/status/date filters in one shot (needed for the
        // summary totals above the table), so paging just slices that
        // already-fetched array instead of re-querying the server.
        let allBills = [];
        let billsCurrentPage = 1;
        const BILLS_PER_PAGE = 10;

        function goToBillsPage(page) {
            billsCurrentPage = page;
            renderBillsPage();
        }

        // Mirrors Laravel's own pagination::tailwind view (same classes),
        // so it inherits the exact same dark-mode styling as every other
        // paginated list in the app instead of needing its own overrides.
        function renderPagination(containerId, totalItems, currentPage, perPage, onPageChangeFn) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const totalPages = Math.max(1, Math.ceil(totalItems / perPage));
            if (totalItems === 0 || totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            const firstItem = (currentPage - 1) * perPage + 1;
            const lastItem = Math.min(currentPage * perPage, totalItems);

            const pages = [];
            const addPage = (p) => { if (p >= 1 && p <= totalPages && !pages.includes(p)) pages.push(p); };
            addPage(1);
            for (let p = currentPage - 1; p <= currentPage + 1; p++) addPage(p);
            addPage(totalPages);
            pages.sort((a, b) => a - b);

            let pageLinksHtml = '';
            let prevShown = null;
            pages.forEach(p => {
                if (prevShown !== null && p - prevShown > 1) {
                    pageLinksHtml += '<span aria-disabled="true"><span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5">...</span></span>';
                }
                if (p === currentPage) {
                    pageLinksHtml += `<span aria-current="page"><span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 cursor-default leading-5">${p}</span></span>`;
                } else {
                    pageLinksHtml += `<a href="javascript:void(0)" onclick="${onPageChangeFn}(${p})" class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-700 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 hover:bg-gray-100" aria-label="Go to page ${p}">${p}</a>`;
                }
                prevShown = p;
            });

            const prevHtml = currentPage === 1
                ? '<span aria-disabled="true" aria-label="Previous"><span class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-not-allowed rounded-l-md leading-5" aria-hidden="true"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></span></span>'
                : `<a href="javascript:void(0)" onclick="${onPageChangeFn}(${currentPage - 1})" rel="prev" class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-400 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 hover:bg-gray-100" aria-label="Previous"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></a>`;

            const nextHtml = currentPage === totalPages
                ? '<span aria-disabled="true" aria-label="Next"><span class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-not-allowed rounded-r-md leading-5" aria-hidden="true"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg></span></span>'
                : `<a href="javascript:void(0)" onclick="${onPageChangeFn}(${currentPage + 1})" rel="next" class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 hover:bg-gray-100" aria-label="Next"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg></a>`;

            container.innerHTML = `
                <nav role="navigation" aria-label="Pagination Navigation" class="px-6 py-4 border-t border-gray-200">
                    <div class="sm:flex sm:gap-2 sm:items-center sm:justify-between" style="display:flex; flex-wrap:wrap; gap:12px;">
                        <p class="text-sm text-gray-700 leading-5">
                            Showing <span class="font-medium">${firstItem}</span> to <span class="font-medium">${lastItem}</span> of <span class="font-medium">${totalItems}</span> results
                        </p>
                        <span class="inline-flex rounded-md shadow-sm">${prevHtml}${pageLinksHtml}${nextHtml}</span>
                    </div>
                </nav>
            `;
        }

        let revokeTargetId = null;
        let revokeMode = 'revoke'; // 'revoke' (completed bill, reverses payment) or 'discard' (open bill, never paid)

        const OPEN_STATUSES = ['hold'];

        function statusBadgeFor(bill) {
            if (bill.status === 'cancelled') {
                // A cancelled order that was actually paid at some point (has a
                // payment_method) was reversed after the fact — a revoke, not a
                // pre-payment discard.
                return bill.payment_method
                    ? { label: 'Revoked', cls: 'badge-revoked' }
                    : { label: 'Discarded', cls: 'badge-cancelled' };
            }
            return STATUS_BADGES[bill.status] || { label: bill.status, cls: 'badge-open' };
        }

        function loadSalesReport(showSpinner = false) {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFromInput').value;
            const dateTo = document.getElementById('dateToInput').value;
            const tbody = document.getElementById('billsTableBody');

            if (showSpinner) {
                tbody.innerHTML = '<tr style="height: 80px;"><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i></td></tr>';
            }

            let url = '{{ route("api.sales-report") }}?';
            if (search) url += `search=${encodeURIComponent(search)}&`;
            if (status) url += `status=${encodeURIComponent(status)}&`;
            if (dateFrom) url += `date_from=${encodeURIComponent(dateFrom)}&`;
            if (dateTo) url += `date_to=${encodeURIComponent(dateTo)}&`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data)) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;">Error loading bills</td></tr>';
                        document.getElementById('billsPaginationContainer').innerHTML = '';
                        return;
                    }

                    updateSummary(data);
                    allBills = data;
                    billsCurrentPage = 1;
                    renderBillsPage();
                })
                .catch(err => {
                    console.error(err);
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;">Failed to load bills</td></tr>';
                    document.getElementById('billsPaginationContainer').innerHTML = '';
                });
        }

        function renderBillsPage() {
            const tbody = document.getElementById('billsTableBody');

            if (allBills.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">No bills found</td></tr>';
                document.getElementById('billsPaginationContainer').innerHTML = '';
                return;
            }

            const start = (billsCurrentPage - 1) * BILLS_PER_PAGE;
            const pageBills = allBills.slice(start, start + BILLS_PER_PAGE);

            tbody.innerHTML = pageBills.map(bill => {
                const badge = statusBadgeFor(bill);
                let detail = '';
                if (bill.status === 'cancelled') {
                    detail = `<div style="font-size:11px; color:#94a3b8; margin-top:3px;">${bill.discard_reason ? 'Reason: ' + bill.discard_reason : ''}${bill.discarded_by ? ' — by ' + bill.discarded_by : ''}</div>`;
                }

                let extraBtns = '';
                if (bill.status === 'completed') {
                    extraBtns = `
                        <button onclick="reprintBill(${bill.id})" class="btn btn-primary" style="font-size: 11px;">
                            <i class="fas fa-print"></i> Re-print Bill
                        </button>
                        <button onclick="openRevokeModal(${bill.id}, 'revoke')" class="btn btn-danger" style="font-size: 11px;">
                            <i class="fas fa-rotate-left"></i> Revoke Bill
                        </button>`;
                } else if (OPEN_STATUSES.includes(bill.status)) {
                    // These bills were started but never paid off — give staff a way
                    // to always resolve them instead of leaving them stuck as "pending".
                    extraBtns = `
                        <a href="/pos?resume=${bill.id}" class="btn btn-primary" style="font-size: 11px; text-decoration:none;">
                            <i class="fas fa-play"></i> Resume &amp; Pay
                        </a>
                        <button onclick="openRevokeModal(${bill.id}, 'discard')" class="btn btn-danger" style="font-size: 11px;">
                            <i class="fas fa-ban"></i> Discard
                        </button>`;
                }

                return `
                <tr class="table-row">
                    <td style="padding: 12px 16px; font-weight: 600; color: #1e293b;">${bill.order_number}</td>
                    <td style="padding: 12px 16px; text-align: center;">
                        ${bill.token_number ? `<span class="badge badge-token">#${String(bill.token_number).padStart(2, '0')}</span>` : '<span style="color:#cbd5e1;">—</span>'}
                    </td>
                    <td style="padding: 12px 16px; color: #475569;">${bill.customer_name}</td>
                    <td style="padding: 12px 16px; text-align: center; color: #475569;">${paymentLabel(bill.payment_method)}</td>
                    <td style="padding: 12px 16px; text-align: right; font-weight: 600; color: #1e293b;">Rs. ${parseFloat(bill.total).toFixed(2)}</td>
                    <td style="padding: 12px 16px;"><span class="badge ${badge.cls}">${badge.label}</span>${detail}</td>
                    <td style="padding: 12px 16px; color: #475569; font-size: 13px;">${bill.created_at}</td>
                    <td style="padding: 12px 16px; text-align: center; white-space: nowrap;">
                        <button onclick="viewItems(${bill.id})" class="btn btn-primary" style="font-size: 11px;">
                            <i class="fas fa-list"></i> View Items
                        </button>
                        ${extraBtns}
                    </td>
                </tr>
            `;
            }).join('');

            renderPagination('billsPaginationContainer', allBills.length, billsCurrentPage, BILLS_PER_PAGE, 'goToBillsPage');
        }

        function updateSummary(bills) {
            const completed = bills.filter(b => b.status === 'completed');
            const revenue = completed.reduce((sum, b) => sum + parseFloat(b.total), 0);
            const count = completed.length;
            const avg = count > 0 ? revenue / count : 0;

            document.getElementById('summaryRevenue').textContent = 'Rs. ' + revenue.toFixed(2);
            document.getElementById('summaryCount').textContent = count;
            document.getElementById('summaryAvg').textContent = 'Rs. ' + avg.toFixed(2);

            const byMethod = {};
            completed.forEach(b => {
                if (!b.payment_method) return;
                byMethod[b.payment_method] = (byMethod[b.payment_method] || 0) + 1;
            });

            const bar = document.getElementById('paymentBreakdownBar');
            const methods = Object.keys(byMethod);
            if (methods.length === 0) {
                bar.innerHTML = '<span style="font-size:12px; color:#94a3b8;">No completed sales in this view yet</span>';
                return;
            }
            bar.innerHTML = methods.map(method => {
                const meta = PAYMENT_METHODS[method] || { label: method.replace('_', ' '), cls: 'background:#f1f5f9; color:#475569;' };
                return `<span class="badge" style="${meta.cls}">${meta.label} <span style="opacity:.75; font-weight:400;">${byMethod[method]}</span></span>`;
            }).join('');
        }

        function viewItems(orderId) {
            document.getElementById('itemsModal').classList.add('active');
            const content = document.getElementById('itemsModalContent');
            content.innerHTML = '<div style="text-align:center; padding: 24px 0;"><i class="fas fa-spinner fa-spin" style="font-size: 22px; color: #2563eb;"></i></div>';

            fetch(`/pos/order/${orderId}`)
                .then(res => res.json())
                .then(order => {
                    if (!order.items || order.items.length === 0) {
                        content.innerHTML = '<p style="text-align:center; color:#94a3b8; padding: 24px 0;">No items on this bill</p>';
                        return;
                    }

                    const rows = order.items.map(item => `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 8px 0; color: #1e293b;">${item.product_name}</td>
                            <td style="padding: 8px 0; text-align: center; color: #475569;">${item.quantity}</td>
                            <td style="padding: 8px 0; text-align: right; color: #475569;">Rs. ${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #1e293b;">Rs. ${parseFloat(item.subtotal).toFixed(2)}</td>
                        </tr>
                    `).join('');

                    content.innerHTML = `
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                            Order ${order.order_number} — ${order.customer_name || 'Walk-in'}
                        </div>
                        <table style="width: 100%; font-size: 13px;">
                            <thead>
                                <tr style="border-bottom: 2px solid #e2e8f0;">
                                    <th style="text-align: left; padding-bottom: 8px; color: #475569;">Item</th>
                                    <th style="text-align: center; padding-bottom: 8px; color: #475569;">Qty</th>
                                    <th style="text-align: right; padding-bottom: 8px; color: #475569;">Price</th>
                                    <th style="text-align: right; padding-bottom: 8px; color: #475569;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                        <div style="border-top: 2px solid #e2e8f0; margin-top: 10px; padding-top: 10px; font-size: 14px; font-weight: 700; color: #1e293b; display: flex; justify-content: space-between;">
                            <span>Total</span><span>Rs. ${parseFloat(order.total).toFixed(2)}</span>
                        </div>
                    `;
                })
                .catch(err => {
                    console.error(err);
                    content.innerHTML = '<p style="text-align:center; color:#ef4444; padding: 24px 0;">Failed to load items</p>';
                });
        }

        function closeItemsModal() {
            document.getElementById('itemsModal').classList.remove('active');
        }

        const REVOKE_MODES = {
            revoke: {
                title: '<i class="fas fa-rotate-left" style="color:#dc2626; margin-right:6px;"></i>Revoke Bill',
                copy: "This reverses a paid bill — its items are added back to stock, its revenue is removed from reports, and it's recorded as revoked for audit. This cannot be undone.",
                placeholder: 'Reason for revoking this bill…',
                confirmLabel: 'Revoke Bill',
                endpoint: (id) => `/pos/order/${id}/revoke`,
                failMessage: 'Failed to revoke bill',
            },
            discard: {
                title: '<i class="fas fa-ban" style="color:#dc2626; margin-right:6px;"></i>Discard Bill',
                copy: "This closes out a bill that was never paid — nothing is added to reports and it can't be resumed afterwards. This cannot be undone.",
                placeholder: 'Reason for discarding this bill…',
                confirmLabel: 'Discard Bill',
                endpoint: (id) => `/pos/order/${id}/cancel`,
                failMessage: 'Failed to discard bill',
            },
        };

        function openRevokeModal(orderId, mode) {
            revokeTargetId = orderId;
            revokeMode = mode || 'revoke';
            const cfg = REVOKE_MODES[revokeMode];
            document.getElementById('revokeModalTitle').innerHTML = cfg.title;
            document.getElementById('revokeModalCopy').textContent = cfg.copy;
            document.getElementById('revokeReasonInput').placeholder = cfg.placeholder;
            document.getElementById('revokeReasonInput').value = '';
            document.getElementById('revokeModalConfirmBtn').textContent = cfg.confirmLabel;
            document.getElementById('revokeModal').classList.add('active');
        }

        function closeRevokeModal() {
            revokeTargetId = null;
            document.getElementById('revokeModal').classList.remove('active');
        }

        function submitRevoke() {
            const cfg = REVOKE_MODES[revokeMode];
            const reason = document.getElementById('revokeReasonInput').value.trim();
            if (!reason) {
                alert('Please enter a reason');
                return;
            }
            if (!revokeTargetId) return;

            fetch(cfg.endpoint(revokeTargetId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ reason: reason }),
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeRevokeModal();
                        loadSalesReport();
                    } else {
                        alert(data.message || cfg.failMessage);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert(cfg.failMessage);
                });
        }

        // Preload the Google Review QR as a base64 data URI so it is always
        // available at print-time without needing a live network request inside
        // the popup window (mirrors the same pattern used in pos.blade.php).
        window.googleReviewQrDataUri = '';
        (function preloadGoogleReviewQr() {
            const targetUrl = 'https://www.google.com/maps/place//data=!4m3!3m2!1s0x3ae25b3ace14ea05:0x448c982eb6a931a0!12e1?source=g.page.m.ia._&utm_source=gbp&laa=nmx-review-solicitation-ia2';
            const qrApiUrl = window.location.origin + '/qr-code/generate?text=' + encodeURIComponent(targetUrl);
            fetch(qrApiUrl)
                .then(function(r) { return r.blob(); })
                .then(function(blob) {
                    const reader = new FileReader();
                    reader.onloadend = function() { window.googleReviewQrDataUri = reader.result; };
                    reader.readAsDataURL(blob);
                })
                .catch(function(e) { console.error('QR Preload Error:', e); });
        })();

        async function reprintBill(orderId) {
            try {
                const res = await fetch(`/pos/order/${orderId}/receipt/reprint`);
                const data = await res.json();
                if (data.success) {
                    printThermalReceipt(data);
                } else {
                    alert('Error: ' + (data.message || 'Could not load receipt'));
                }
            } catch (e) {
                console.error(e);
                alert('Failed to load receipt');
            }
        }

        function printThermalReceipt(d) {
            const CO_NAME    = "Cafe' Kdj - BBQ";
            const CO_TAGLINE = 'Fusion Food Court';
            const CO_CONTACT = '07777-04555';
            const CO_ADDRESS = '#9, Galle Road, Dehiwala';

            const itemRows = d.items.map(function(i) {
                return `<tr>
                    <td style="padding:3px 0; vertical-align:top; width:62%;">${i.product_name}</td>
                    <td style="text-align:center; padding:3px 0; vertical-align:top; width:10%;">${i.quantity}</td>
                    <td style="text-align:right; padding:3px 0; vertical-align:top; width:28%;">Rs.${parseFloat(i.subtotal).toFixed(2)}</td>
                </tr>`;
            }).join('');

                const methodLabel = { cash:'Cash', card:'Card', bank:'Bank', bank_transfer:'Bank Transfer', mixed:'Split', split:'Split', pickme:'PickMe', uber:'Uber' };
                const m1Name = methodLabel[d.split_method1] || d.split_method1 || 'Method 1';
                const m2Name = methodLabel[d.split_method2] || d.split_method2 || 'Method 2';
                const a1 = (d.split_amount1 !== null && d.split_amount1 !== undefined) ? parseFloat(d.split_amount1) : 0;
                const a2 = (d.split_amount2 !== null && d.split_amount2 !== undefined) ? parseFloat(d.split_amount2) : 0;

                const paymentSectionHtml = (d.payment_method === 'mixed' || (d.split_method1 && d.split_amount1))
                    ? `<div>Payment: Split</div>
                       <div>Paid: Rs.${parseFloat(d.amount_paid || 0).toFixed(2)}</div>
                       ${(a1 > 0 || d.split_method1) ? `<div style="padding-left:10px; font-size:11px;">- ${m1Name}: Rs.${a1.toFixed(2)}</div>` : ''}
                       ${(a2 > 0 || d.split_method2) ? `<div style="padding-left:10px; font-size:11px;">- ${m2Name}: Rs.${a2.toFixed(2)}</div>` : ''}`
                    : `<div>Payment: ${methodLabel[d.payment_method] || d.payment_method || 'N/A'}</div>
                       <div>Paid: Rs.${parseFloat(d.amount_paid || 0).toFixed(2)}</div>`;

                const html = `
                <div style="text-align:center; padding-bottom:8px;">
                    <img src="/images/KDJ_logo.png" style="max-width:120px; max-height:120px; margin-bottom:6px; display:block; margin-left:auto; margin-right:auto;" />
                    <div style="font-size:16px; font-weight:900;">${CO_NAME}</div>
                    <div style="font-size:12px;">${CO_TAGLINE}</div>
                    <div style="font-size:11px;">${CO_ADDRESS}</div>
                    <div style="font-size:11px;">${CO_CONTACT}</div>
                </div>

                <div style="border-top:2px solid #000; border-bottom:2px solid #000; padding:6px 0; margin-bottom:8px;">
                    <div style="text-align:center; font-size:16px; font-weight:900; color: #2563eb; margin-bottom: 5px;">*** RE-PRINT ***</div>
                    <div style="text-align:center; font-size:14px; letter-spacing:2px; margin-bottom:5px;">FINAL BILL</div>
                    <table width="100%" cellspacing="0" cellpadding="2" style="font-size:12px; font-weight:900;">
                        <tr><td style="width:35%;">Order</td><td style="text-align:right; width:65%;">${d.order_number}</td></tr>
                        ${d.token_number ? `<tr><td>Token</td><td style="text-align:right;">#${String(d.token_number).padStart(2, '0')}</td></tr>` : ''}
                        ${d.customer_name ? `<tr><td>Customer</td><td style="text-align:right;">${d.customer_name}</span></td></tr>` : ''}
                        <tr><td>Date</td><td style="text-align:right;">${d.printed_at}</td></tr>
                    </table>
                </div>

                <table width="100%" cellspacing="0" cellpadding="2" style="font-size:13px; font-weight:900;">
                    <thead><tr style="border-bottom:1px dashed #000;">
                        <th style="text-align:left; width:62%;">ITEM</th>
                        <th style="text-align:center; width:10%;">QTY</th>
                        <th style="text-align:right; width:28%;">AMOUNT</th>
                    </tr></thead>
                    <tbody>${itemRows}</tbody>
                </table>

                <table width="100%" cellspacing="0" cellpadding="2" style="font-size:13px; font-weight:900; border-top:1px dashed #000; margin-top:4px;">
                    <tr><td style="width:65%;">Subtotal</td><td style="text-align:right; width:35%;">Rs.${parseFloat(d.subtotal).toFixed(2)}</td></tr>
                    ${d.discount_amount > 0 ? `<tr><td>Discount</td><td style="text-align:right;">-Rs.${parseFloat(d.discount_amount).toFixed(2)}</td></tr>` : ''}
                    <tr style="border-top:1px solid #000; font-size:17px;"><td style="padding-top:4px;">TOTAL</td><td style="text-align:right; padding-top:4px;">Rs.${parseFloat(d.total).toFixed(2)}</td></tr>
                </table>

                <div style="border-top:1px dashed #000; margin-top:8px; padding-top:6px; font-size:12px; font-weight:900;">
                    ${paymentSectionHtml}
                    ${(d.change_amount > 0) ? `<div>Change: Rs.${parseFloat(d.change_amount).toFixed(2)}</div>` : ''}
                </div>

                <div style="text-align:center; font-size:11px; margin-top:10px; border-top:1px dashed #000; padding-top:8px; line-height:1.2;">
                    <div style="font-size:11px; font-weight:bold; margin-bottom:4px;">Please drop a google review,cafe kdj</div>
                    <img src="${window.googleReviewQrDataUri || (window.location.origin + '/qr-code/generate?text=' + encodeURIComponent('https://www.google.com/maps/place//data=!4m3!3m2!1s0x3ae25b3ace14ea05:0x448c982eb6a931a0!12e1?source=g.page.m.ia._&utm_source=gbp&laa=nmx-review-solicitation-ia2'))}" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https%3A%2F%2Fwww.google.com%2Fmaps%2Fplace%2F%2Fdata%3D!4m3!3m2!1s0x3ae25b3ace14ea05%3A0x448c982eb6a931a0!12e1%3Fsource%3Dg.page.m.ia._%26utm_source%3Dgbp%26laa%3Dnmx-review-solicitation-ia2'" style="width:110px; height:110px; margin:6px auto 4px auto; display:block;" alt="Google Review QR" />
                    RE-PRINTED AT: ${new Date().toLocaleString()}
                </div>
            `;

            const w = window.open('', '', 'width=400,height=700');
            w.document.write(`
                <!DOCTYPE html><html><head><style>
                @page { size: 80mm auto; margin: 0; }
                body { font-family: 'Courier New', monospace; width: 100%; margin: 0; padding: 4mm 5mm; font-size: 14px; font-weight: 900 !important; color: #000; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                * { box-sizing: border-box; font-weight: 900 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                img { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                table { border-collapse: collapse; }
                </style></head><body>${html}</body></html>
            `);
            w.document.close();
            w.focus();
            let printed = false;
            const doPrint = function() {
                if (printed) return;
                printed = true;
                setTimeout(function() {
                    w.print();
                    setTimeout(function() { w.close(); }, 1200);
                }, 250);
            };
            const imgs = w.document.images;
            if (!imgs || imgs.length === 0) {
                doPrint();
            } else {
                let pending = imgs.length;
                const onOne = function() { pending--; if (pending <= 0) doPrint(); };
                Array.prototype.forEach.call(imgs, function(img) {
                    if (img.complete && img.naturalWidth > 0) onOne();
                    else { img.onload = onOne; img.onerror = onOne; }
                });
                setTimeout(doPrint, 1500);
            }
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('dateFromInput').value = '';
            document.getElementById('dateToInput').value = '';
            loadSalesReport();
        }

        loadSalesReport(true);
    </script>

</body>
</html>
