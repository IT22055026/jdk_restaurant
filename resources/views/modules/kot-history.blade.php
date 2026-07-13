<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KOT History — Restaurant BYOB</title>
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
            background: #dc2626;
            color: #fff;
        }
        .btn-primary:hover { background: #b91c1c; }

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

        .badge-kitchen { background: #dbeafe; color: #1e40af; }
        .badge-bar { background: #fef3c7; color: #92400e; }

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
                <h1 class="text-3xl font-bold text-gray-900 mb-2">KOT History</h1>
                <p class="text-gray-600">View and reprint kitchen orders in real-time</p>
            </div>

            <!-- Search -->
            <div class="bg-white rounded-xl p-5 mb-6 border border-gray-200 shadow-sm">
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="searchInput" placeholder="Search by order # or customer name..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 transition-all"
                            onkeyup="if(event.key==='Enter') loadKotHistory(true)">
                    </div>
                    <button onclick="loadKotHistory(true)" class="btn btn-primary px-6 py-2.5 rounded-lg shadow-md shadow-red-100">
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

            <!-- KOT History table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <tr>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Order #</th>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Customer</th>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Items Count</th>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">KOT Printed At</th>
                                <th style="text-align: center; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="kotTableBody">
                            <tr style="height: 80px;">
                                <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- KOT/BOT Modal -->
    <div id="kotModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="kotModalTitle" style="font-size: 18px; font-weight: 700; color: #1e293b;">Kitchen Order</h3>
                <button onclick="closeKotModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="kotContent" style="text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #dc2626;"></i>
            </div>
        </div>
    </div>

    <script>
        function loadKotHistory(showSpinner = false) {
            const search = document.getElementById('searchInput').value;
            const tbody = document.getElementById('kotTableBody');

            if (showSpinner) {
                tbody.innerHTML = '<tr style="height: 80px;"><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i></td></tr>';
            }

            let url = '/api/kot-history';
            if (search) url += `?search=${encodeURIComponent(search)}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data)) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">Error loading KOT history</td></tr>';
                        return;
                    }

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">No KOT records found</td></tr>';
                        return;
                    }

                    tbody.innerHTML = data.map(order => {
                        return `
                            <tr class="table-row">
                                <td style="padding: 12px 16px; font-weight: 600; color: #1e293b;">${order.order_number}</td>
                                <td style="padding: 12px 16px; color: #475569;">${order.customer_name}</td>
                                <td style="padding: 12px 16px; color: #475569;">${order.items_count}</td>
                                <td style="padding: 12px 16px; color: #475569; font-size: 13px;">${order.kot_printed_at || '-'}</td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <button onclick="reprintKot(${order.id})" class="btn btn-primary" style="font-size: 11px; padding: 6px 16px;">
                                        <i class="fas fa-print"></i> Re-print KOT
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                })
                .catch(err => {
                    console.error(err);
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">Failed to load KOT history</td></tr>';
                });
        }

        async function reprintKot(orderId) {
            try {
                const res = await fetch(`/pos/order/${orderId}/kot/reprint`);
                const data = await res.json();
                if (data.success) {
                    printTicket(data, 'KITCHEN ORDER (KOT)');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) { console.error(e); }
        }

        function printTicket(data, title) {
            const html = `
                <div style="text-align:center; padding: 10px 0; border-bottom: 2px solid #000; margin-bottom: 10px;">
                    <div style="font-size: 24px; font-weight: 900; color: #dc2626; border: 3px solid #dc2626; display: inline-block; padding: 4px 15px; margin-bottom: 10px; border-radius: 8px; letter-spacing: 2px;">RE-PRINT</div>
                    <div style="font-weight: 900; font-size: 16px; color:#000;">${title}</div>
                    <div style="font-size: 13px; font-weight: 800; color:#000; margin-top: 5px;">Order: ${data.order_number}</div>
                    <div style="font-size: 14px; font-weight: 900; margin:4px 0; color:#000;">Table ${data.table_number || '—'}</div>
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

        function closeKotModal() {
            document.getElementById('kotModal').classList.remove('active');
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            loadKotHistory(true);
        }

        document.getElementById('kotModal').addEventListener('click', (e) => {
            if (e.target.id === 'kotModal') closeKotModal();
        });

        // Initial load
        loadKotHistory(true);

        // Auto-refresh every 5 seconds
        setInterval(() => {
            // Only auto-refresh if the search input is empty to avoid interrupting the user
            if (!document.getElementById('searchInput').value) {
                loadKotHistory(false);
            }
        }, 5000);
    </script>

            </div>
        </div>
    </div>

</body>
</html>
