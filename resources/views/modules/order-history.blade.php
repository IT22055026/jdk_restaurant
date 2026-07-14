<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order History — Restaurant BYOB</title>
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
        }

        .badge-token { background: #ffedd5; color: #9a3412; }

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

        .loader {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loader.active { display: block; }

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
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Order History</h1>
                <p class="text-gray-600">View and reprint receipts from past orders</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg p-6 mb-6 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" id="searchInput" placeholder="Order #, customer name, phone..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div style="display: flex; align-items: flex-end; gap: 8px;">
                        <button onclick="loadOrders(true)" class="flex-1 btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <button onclick="resetFilters()" class="flex-1 btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                    <div id="refreshIndicator" class="ml-auto flex items-center gap-2 text-xs text-gray-400 font-medium mr-2" style="grid-column: span 3; justify-content: flex-end; margin-top: 8px;">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Auto-refreshing...
                    </div>
                </div>
            </div>

            <!-- Orders table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <tr>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Order #</th>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Customer</th>
                                <th style="text-align: center; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Token</th>
                                <th style="text-align: right; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Total</th>
                                <th style="text-align: center; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Items</th>
                                <th style="text-align: left; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Date</th>
                                <th style="text-align: center; padding: 12px 16px; font-weight: 600; font-size: 13px; color: #475569;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <tr style="height: 80px;">
                                <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #1e293b;">Receipt</h3>
                <button onclick="closeReceiptModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="receiptContent" class="loader active">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #dc2626;"></i>
            </div>
        </div>
    </div>

    <script>
        function loadOrders(showSpinner = false) {
            const search = document.getElementById('searchInput').value;
            const tbody = document.getElementById('ordersTableBody');

            if (showSpinner) {
                tbody.innerHTML = '<tr style="height: 80px;"><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i></td></tr>';
            }

            let url = '/api/order-history?';
            if (search) url += `search=${encodeURIComponent(search)}&`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data)) {
                        if (showSpinner) tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #ef4444;">Error loading orders</td></tr>';
                        return;
                    }

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">No orders found</td></tr>';
                        return;
                    }

                    tbody.innerHTML = data.map(order => `
                        <tr class="table-row">
                            <td style="padding: 12px 16px; font-weight: 600; color: #1e293b;">${order.order_number}</td>
                            <td style="padding: 12px 16px; color: #475569;">
                                <div>${order.customer_name}</div>
                                ${order.customer_phone ? `<div style="font-size: 12px; color: #94a3b8;">${order.customer_phone}</div>` : ''}
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                ${order.token_number ? `<span class="badge badge-token">#${String(order.token_number).padStart(2, '0')}</span>` : '<span style="color:#cbd5e1;">—</span>'}
                            </td>
                            <td style="padding: 12px 16px; text-align: right; font-weight: 600; color: #1e293b;">LKR ${parseFloat(order.total).toFixed(2)}</td>
                            <td style="padding: 12px 16px; text-align: center; color: #475569;">${order.items_count}</td>
                            <td style="padding: 12px 16px; color: #475569; font-size: 13px;">${order.created_at}</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <button onclick="reprintBill(${order.id})" class="btn btn-primary" style="font-size: 11px;">
                                    <i class="fas fa-print"></i> Re-Print Bill
                                </button>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(err => {
                    console.error(err);
                    if (showSpinner) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #ef4444;">Failed to load orders</td></tr>';
                    }
                });
        }

        async function reprintBill(orderId) {
            try {
                const res = await fetch(`/pos/order/${orderId}/receipt/reprint`);
                const data = await res.json();
                if (data.success) {
                    printThermalReceipt(data);
                } else {
                    alert('Error: ' + (data.message || 'Could not load receipt'));
                }
            } catch (e) { console.error(e); }
        }

        function printThermalReceipt(d) {
            const CO_NAME    = 'Suasa Family Restaurant';
            const CO_CONTACT = '071 979 9799';
            const CO_ADDRESS = '583 Avissawella Road, mulleriyawa';
            const CO_EMAIL   = 'info@suasafamily.com';

            const itemRows = d.items.map(function(i) {
                return `<tr>
                    <td style="padding:3px 0; vertical-align:top; width:62%;">${i.product_name}</td>
                    <td style="text-align:center; padding:3px 0; vertical-align:top; width:10%;">${i.quantity}</td>
                    <td style="text-align:right; padding:3px 0; vertical-align:top; width:28%;">Rs.${parseFloat(i.subtotal).toFixed(2)}</td>
                </tr>`;
            }).join('');

            const html = `
                <div style="text-align:center; padding-bottom:8px;">
                    <img src="/images/logo.jpeg" style="max-width:120px; max-height:120px; margin-bottom:6px; display:block; margin-left:auto; margin-right:auto;" />
                    <div style="font-size:16px; font-weight:900;">${CO_NAME}</div>
                    <div style="font-size:12px;">${CO_CONTACT}</div>
                    <div style="font-size:11px;">${CO_ADDRESS}</div>
                </div>

                <div style="border-top:2px solid #000; border-bottom:2px solid #000; padding:6px 0; margin-bottom:8px;">
                    <div style="text-align:center; font-size:16px; font-weight:900; color: #dc2626; margin-bottom: 5px;">*** RE-PRINT ***</div>
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
                    <div>Payment: ${d.payment_method || 'N/A'}</div>
                    <div>Paid: Rs.${parseFloat(d.amount_paid || 0).toFixed(2)}</div>
                    ${(d.change_amount > 0) ? `<div>Change: Rs.${parseFloat(d.change_amount).toFixed(2)}</div>` : ''}
                </div>

                <div style="text-align:center; font-size:11px; margin-top:10px; border-top:1px dashed #000; padding-top:8px; line-height:1.2;">
                    Thank you for dining with us!<br>
                    RE-PRINTED AT: ${new Date().toLocaleString()}<br>
                    Powered By JAAN Network (PVT) Ltd
                </div>
            `;

            const w = window.open('', '', 'width=400');
            w.document.write(`
                <!DOCTYPE html><html><head><style>
                @page { size: 80mm auto; margin: 0; }
                body { font-family: 'Courier New', monospace; width: 100%; margin: 0; padding: 4mm 5mm; font-size: 14px; font-weight: 900 !important; color: #000; }
                * { box-sizing: border-box; font-weight: 900 !important; }
                table { border-collapse: collapse; }
                </style></head><body onload="window.print(); window.close();">${html}</body></html>
            `);
            w.document.close();
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            loadOrders();
        }

        loadOrders(true);

        // Auto-refresh every 5 seconds
        setInterval(() => {
            if (!document.getElementById('searchInput').value) {
                loadOrders(false);
            }
        }, 5000);
    </script>

            </div>
        </div>
    </div>

</body>
</html>
