<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Token History — Restaurant BYOB</title>
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

        .btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-primary:hover { background: #1d4ed8; }

        .btn-secondary {
            background: #e2e8f0;
            color: #1e293b;
        }
        .btn-secondary:hover { background: #cbd5e1; }

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
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-token { background: #ffedd5; color: #9a3412; }

        .main-content { margin-left: 256px; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
    </style>
    @include('layouts.dark-mode')
</head>
<body>

    <!-- Navbar -->
    @include('layouts.navbar')

    <!-- Main Layout -->
    <div class="flex" style="padding-top: 67px;">
        <!-- Sidebar -->
        <x-sidebar :modules="$modules ?? []" />

        <!-- Page content -->
        <div class="flex-1 main-content px-6 py-8">
            <div class="w-full max-w-screen-2xl">

            <!-- Page header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Token History</h1>
                <p class="text-gray-600">View and reprint kitchen tokens in real-time</p>
            </div>

            <!-- Search -->
            <div class="bg-white rounded-xl p-5 mb-6 border border-gray-200 shadow-sm">
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="searchInput" placeholder="Search by order # or customer name..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                            onkeyup="if(event.key==='Enter') loadTokenHistory(true)">
                    </div>
                    <button onclick="loadTokenHistory(true)" class="btn btn-primary px-6 py-2.5 rounded-lg shadow-md shadow-blue-100">
                         Search
                    </button>
                    <button onclick="resetFilters()" class="btn btn-secondary px-6 py-2.5 rounded-lg">
                        <i class="fas fa-undo"></i>
                    </button>
                    <div id="refreshIndicator" class="ml-auto flex items-center gap-2 text-xs text-gray-400 font-medium mr-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Auto-refreshing...
                    </div>
                </div>
            </div>

            <!-- Token History table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <tr>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Order #</th>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Token</th>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Customer</th>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Items Count</th>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Token Printed At</th>
                                <th style="text-align: center; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tokenTableBody">
                            <tr style="height: 80px;">
                                <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="tokenPaginationContainer"></div>
            </div>

        </div>
    </div>

    <!-- Token Modal -->
    <div id="tokenModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="tokenModalTitle" style="font-size: 18px; font-weight: 700; color: #1e293b;">Token</h3>
                <button onclick="closeTokenModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="tokenModalContent" style="text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #2563eb;"></i>
            </div>
        </div>
    </div>

    <script>
        // Client-side pagination — see order-history.blade.php for the same
        // pattern: the current page only resets on a user-initiated load
        // (spinner shown), never on the silent 5s auto-refresh below.
        let allTokenOrders = [];
        let tokenCurrentPage = 1;
        const TOKEN_PER_PAGE = 10;

        function goToTokenPage(page) {
            tokenCurrentPage = page;
            renderTokenPage();
        }

        function loadTokenHistory(showSpinner = false) {
            const search = document.getElementById('searchInput').value;
            const tbody = document.getElementById('tokenTableBody');

            if (showSpinner) {
                tbody.innerHTML = '<tr style="height: 80px;"><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i></td></tr>';
            }

            let url = '/api/token-history';
            if (search) url += `?search=${encodeURIComponent(search)}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data)) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">Error loading token history</td></tr>';
                        document.getElementById('tokenPaginationContainer').innerHTML = '';
                        return;
                    }

                    allTokenOrders = data;
                    if (showSpinner) tokenCurrentPage = 1;
                    else if (tokenCurrentPage > Math.max(1, Math.ceil(allTokenOrders.length / TOKEN_PER_PAGE))) tokenCurrentPage = 1;
                    renderTokenPage();
                })
                .catch(err => {
                    console.error(err);
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">Failed to load token history</td></tr>';
                    document.getElementById('tokenPaginationContainer').innerHTML = '';
                });
        }

        function renderTokenPage() {
            const tbody = document.getElementById('tokenTableBody');

            if (allTokenOrders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">No token records found</td></tr>';
                document.getElementById('tokenPaginationContainer').innerHTML = '';
                return;
            }

            const start = (tokenCurrentPage - 1) * TOKEN_PER_PAGE;
            const pageOrders = allTokenOrders.slice(start, start + TOKEN_PER_PAGE);

            tbody.innerHTML = pageOrders.map(order => {
                return `
                    <tr class="table-row">
                        <td style="padding: 12px 16px; font-weight: 600; color: #1e293b;">${order.order_number}</td>
                        <td style="padding: 12px 16px;">${order.token_number ? `<span class="badge badge-token">#${String(order.token_number).padStart(2, '0')}</span>` : '<span style="color:#94a3b8;">—</span>'}</td>
                        <td style="padding: 12px 16px; color: #475569;">${order.customer_name}</td>
                        <td style="padding: 12px 16px; color: #475569;">${order.items_count}</td>
                        <td style="padding: 12px 16px; color: #475569; font-size: 13px;">${order.token_printed_at || '-'}</td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <button onclick="reprintToken(${order.id})" class="btn btn-primary" style="font-size: 11px; padding: 6px 16px;">
                                <i class="fas fa-print"></i> Re-print Token
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            renderPagination('tokenPaginationContainer', allTokenOrders.length, tokenCurrentPage, TOKEN_PER_PAGE, 'goToTokenPage');
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

        async function reprintToken(orderId) {
            try {
                const res = await fetch(`/pos/order/${orderId}/token/reprint`);
                const data = await res.json();
                if (data.success) {
                    printTicket(data, 'TOKEN');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) { console.error(e); }
        }

        function printTicket(data, title) {
            const html = `
                <div style="text-align:center; padding: 10px 0; border-bottom: 2px solid #000; margin-bottom: 10px;">
                    <img src="/images/KDJ_logo.png" style="max-width:70px; max-height:70px; margin-bottom: 6px; display: inline-block;" />
                    <div style="font-size: 24px; font-weight: 900; color: #2563eb; border: 3px solid #2563eb; display: inline-block; padding: 4px 15px; margin-bottom: 10px; border-radius: 8px; letter-spacing: 2px;">RE-PRINT</div>
                    <div style="font-weight: 900; font-size: 16px; color:#000;">${title}</div>
                    <div style="font-size: 13px; font-weight: 800; color:#000; margin-top: 5px;">Order: ${data.order_number}</div>
                    <div style="margin-top: 6px;"><span style="font-size: 13px; font-weight: 900; color: #000; letter-spacing: 1.5px; border: 2px solid #000; display: inline-block; padding: 2px 10px; border-radius: 6px; text-transform: uppercase;">${data.order_type === 'takeaway' ? 'TAKEAWAY' : 'DINE IN'}</span></div>
                    <div style="font-size: 32px; font-weight: 900; margin:4px 0; color:#000;">${data.token_number ? '#' + String(data.token_number).padStart(2, '0') : 'No token'}</div>
                    <div style="font-size: 10px; color:#000;">Original: ${data.date_time}</div>
                </div>
                <div style="border-bottom: 1px solid #000; padding-bottom: 10px;">
                    ${data.items.map(i => `
                        <div style="display:flex; justify-content:space-between; font-size:13px; font-weight:700; margin:8px 0; border-bottom:1px dashed #000; padding-bottom:6px; color:#000;">
                            <span>${i.product_name}</span>
                            <span style="font-size:16px; font-weight:900;">×${i.quantity}</span>
                        </div>
                        ${i.kitchen_notes ? `<div style="font-size:11px; color:#000; margin-top:-4px; margin-bottom:6px; font-style: italic;">Note: ${i.kitchen_notes}</div>` : ''}
                    `).join('')}
                </div>
                <div style="text-align:center; font-size:10px; margin-top:10px; font-weight:800;">
                    RE-PRINTED AT: ${new Date().toLocaleString()}
                </div>
            `;

            const w = window.open('', '', 'width=400');
            w.document.write(`
                <!DOCTYPE html><html><head><style>
                @page { size: 80mm auto; margin: 0; }
                body { font-family: 'Courier New', monospace; width: 100%; margin: 0; padding: 4mm 5mm; font-size: 14px; font-weight: 900 !important; color: #000; }
                * { box-sizing: border-box; font-weight: 900 !important; }
                div { line-height: 1.2; }
                </style></head><body onload="window.print(); window.close();">${html.trim()}</body></html>
            `);
            w.document.close();
            w.focus();
            setTimeout(() => { w.print(); w.close(); }, 500);
        }

        function closeTokenModal() {
            document.getElementById('tokenModal').classList.remove('active');
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            loadTokenHistory(true);
        }

        document.getElementById('tokenModal').addEventListener('click', (e) => {
            if (e.target.id === 'tokenModal') closeTokenModal();
        });

        // Initial load
        loadTokenHistory(true);

        // Auto-refresh every 5 seconds
        setInterval(() => {
            // Only auto-refresh if the search input is empty to avoid interrupting the user
            if (!document.getElementById('searchInput').value) {
                loadTokenHistory(false);
            }
        }, 5000);
    </script>

            </div>
        </div>
    </div>

</body>
</html>
