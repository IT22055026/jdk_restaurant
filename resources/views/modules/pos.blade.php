<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS & Billing — Restaurant BYOB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; height: 100vh; overflow: hidden; display: flex; flex-direction: column; }

        /* ── Layout ── */
        .pos-grid { display: grid; grid-template-columns: 320px 1fr 520px; flex: 1; min-height: 0; }

        /* ── Panels ── */
        .tables-panel  { background: #fff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; }
        .menu-panel    { background: #f8fafc; display: flex; flex-direction: column; overflow: hidden; }
        .bill-panel    { background: #fff; border-left: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; }

        /* ── Table cards ── */
        .table-card {
            cursor: pointer;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 7px 6px;
            text-align: center;
            transition: all 0.18s ease;
            position: relative;
            background: #fff;
            user-select: none;
        }
        .table-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .table-card.selected { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }

        /* Status colours */
        .table-card.available { border-color: #22c55e; background: linear-gradient(135deg,#f0fdf4,#dcfce7); }
        .table-card.available:hover { border-color: #16a34a; }
        .table-card.occupied  { border-color: #ef4444; background: linear-gradient(135deg,#fff1f1,#fee2e2); }
        .table-card.occupied:hover  { border-color: #dc2626; }
        .table-card.reserved  { border-color: #f59e0b; background: linear-gradient(135deg,#fffbeb,#fef3c7); }
        .table-card.reserved:hover  { border-color: #d97706; }
        .table-card.cleaning  { border-color: #94a3b8; background: #f8fafc; }

        /* Status badge on card */
        .table-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 4px; }
        .dot-available { background: #22c55e; }
        .dot-occupied  { background: #ef4444; }
        .dot-reserved  { background: #f59e0b; }
        .dot-cleaning  { background: #94a3b8; }

        /* Bottom action bar that expands on click */
        .table-card-actions {
            display: none;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed rgba(0,0,0,0.12);
            gap: 6px;
        }
        .table-card.expanded .table-card-actions { display: flex; }

        /* ── Category pills ── */
        .cat-pill {
            padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
            border: 2px solid #e2e8f0; cursor: pointer; white-space: nowrap; transition: all 0.15s;
            background: #fff; color: #64748b;
        }
        .cat-pill:hover { border-color: #dc2626; color: #dc2626; }
        .cat-pill.active { background: #dc2626; color: #fff; border-color: #dc2626; }

        /* ── Product cards ── */
        .product-card {
            background: #fff; border: 2px solid #e2e8f0; border-radius: 12px;
            padding: 14px 10px; cursor: pointer; transition: all 0.18s; text-align: center;
        }
        .product-card:hover { border-color: #dc2626; box-shadow: 0 4px 16px rgba(220,38,38,0.15); transform: translateY(-2px); }
        .product-card:active { transform: scale(0.97); }

        /* ── Bill items ── */
        .bill-item {
            display: flex; align-items: center; padding: 10px 0;
            border-bottom: 1px solid #f1f5f9; gap: 8px;
        }
        .qty-btn {
            width: 26px; height: 26px; border: 1.5px solid #e2e8f0; border-radius: 6px;
            background: #f8fafc; cursor: pointer; font-size: 13px; font-weight: bold;
            display: flex; align-items: center; justify-content: center; transition: all 0.12s;
            color: #374151;
        }
        .qty-btn:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

        /* ── Payment method buttons ── */
        .pay-method-btn {
            flex: 1; padding: 10px 4px; border: 2px solid #e2e8f0; border-radius: 10px;
            font-size: 12px; font-weight: 700; cursor: pointer; text-align: center;
            background: #fff; transition: all 0.15s; color: #64748b;
        }
        .pay-method-btn:hover { border-color: #3b82f6; color: #3b82f6; }
        .pay-method-btn.active { border-color: #dc2626; background: #fef2f2; color: #dc2626; }

        /* ── Modals ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15,23,42,0.55); backdrop-filter: blur(3px);
            z-index: 50; align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px; padding: 28px;
            max-width: 480px; width: 92%; max-height: 92vh; overflow-y: auto;
            box-shadow: 0 24px 64px rgba(0,0,0,0.2);
        }

        /* ── Active order banner ── */
        #activeOrderBanner {
            align-items: center;
            justify-content: space-between;
        }
        #activeOrderBanner[style*="display:flex"] {
            display: flex !important;
        }

        /* ── Live bill prompt ── */
        .live-bill-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15,23,42,0.6); backdrop-filter: blur(4px);
            z-index: 60; align-items: center; justify-content: center;
        }
        .live-bill-overlay.open { display: flex; }

        /* ── Buttons ── */
        .btn-primary   { background: #dc2626; color: #fff; border: none; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 13px; }
        .btn-primary:hover   { background: #b91c1c; }
        .btn-secondary { background: #f1f5f9; color: #374151; border: none; border-radius: 10px; padding: 10px 16px; font-weight: 600; cursor: pointer; transition: background 0.15s; font-size: 13px; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-green   { background: #16a34a; color: #fff; border: none; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 13px; }
        .btn-green:hover   { background: #15803d; }
        .btn-blue    { background: #2563eb; color: #fff; border: none; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 13px; }
        .btn-blue:hover    { background: #1d4ed8; }
        .btn-orange  { background: #ea580c; color: #fff; border: none; border-radius: 10px; padding: 10px 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 12px; }
        .btn-orange:hover  { background: #c2410c; }
        .btn-purple  { background: #7c3aed; color: #fff; border: none; border-radius: 10px; padding: 10px 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 12px; }
        .btn-purple:hover  { background: #6d28d9; }

        /* ── Scrollbars ── */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        /* ── Notification toast ── */
        #toast {
            position: fixed; bottom: 24px; right: 24px; z-index: 100;
            background: #1e293b; color: #fff; padding: 12px 20px; border-radius: 10px;
            font-size: 13px; font-weight: 500; opacity: 0; transition: opacity 0.3s;
            pointer-events: none; max-width: 300px;
        }
        #toast.show { opacity: 1; }
        #toast.success { background: #166534; }
        #toast.error   { background: #991b1b; }
        #toast.warning { background: #92400e; }

        /* ── Print ── */
        @media print {
            body > * { display: none !important; }
            #printArea { display: block !important; }
        }
        #printArea { display: none; }

        /* ══════════════════════════════════════════
           DARK MODE — blue / black / white theme
        ══════════════════════════════════════════ */
        html.dark-mode body { background: #0a0e17; }
        html.dark-mode .tables-panel,
        html.dark-mode .bill-panel { background: #10162a; border-color: #1f2942; }
        html.dark-mode .menu-panel { background: #0d1220; }

        html.dark-mode .table-card { background: #10162a; border-color: #1f2942; }
        html.dark-mode .table-card.available { background: linear-gradient(135deg,#0c1c14,#0f2318); border-color: #16a34a; }
        html.dark-mode .table-card.occupied  { background: linear-gradient(135deg,#1c1010,#241212); border-color: #ef4444; }
        html.dark-mode .table-card.reserved  { background: linear-gradient(135deg,#20180a,#291d0c); border-color: #f59e0b; }
        html.dark-mode .table-card.cleaning  { background: #131a2e; border-color: #26314d; }
        html.dark-mode .table-card.selected  { border-color: #2f5bff; box-shadow: 0 0 0 3px rgba(47,91,255,0.25); }

        html.dark-mode .cat-pill { background: #10162a; border-color: #26314d; color: #9aa7c2; }
        html.dark-mode .cat-pill:hover { border-color: #2f5bff; color: #6d94ff; }
        html.dark-mode .cat-pill.active { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }

        html.dark-mode .product-card { background: #10162a; border-color: #1f2942; }
        html.dark-mode .product-card:hover { border-color: #2f5bff; box-shadow: 0 4px 16px rgba(47,91,255,0.2); }

        html.dark-mode .bill-item { border-bottom-color: #1f2942; }
        html.dark-mode .qty-btn { background: #141b30; border-color: #26314d; color: #e8ecf4; }
        html.dark-mode .qty-btn:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }

        html.dark-mode .pay-method-btn { background: #10162a; border-color: #26314d; color: #9aa7c2; }
        html.dark-mode .pay-method-btn:hover { border-color: #2f5bff; color: #6d94ff; }
        html.dark-mode .pay-method-btn.active { background: #101f45; border-color: #1d4ed8; color: #6d94ff; }

        html.dark-mode .modal-box { background: #10162a; color: #e8ecf4; }
        html.dark-mode .btn-secondary { background: #182036; color: #e8ecf4; }
        html.dark-mode .btn-secondary:hover { background: #212b47; }

        html.dark-mode input, html.dark-mode select, html.dark-mode textarea {
            background: #101627 !important; border-color: #26314d !important; color: #e8ecf4 !important;
        }
        html.dark-mode #searchInput { background: #101627; }

        html.dark-mode h2, html.dark-mode h3 { color: #f1f5f9 !important; }
        html.dark-mode #selectedTableLabel,
        html.dark-mode #tableStatusBadge { color: #9aa7c2 !important; }

        html.dark-mode #customerInfoSection,
        html.dark-mode [style*="background:#f8fafc"] { background: #0d1324 !important; }
        html.dark-mode [style*="background: #f8fafc"] { background: #0d1324 !important; }
        html.dark-mode [style*="background:#fafafa"] { background: #0a0e17 !important; }
        html.dark-mode [style*="background:#fff;"] { background: #10162a !important; }
        html.dark-mode [style*="background: #fff;"] { background: #10162a !important; }
        html.dark-mode [style*="border-top:1px solid #e2e8f0"] { border-top-color: #1f2942 !important; }
        html.dark-mode [style*="border-bottom:1px solid #e2e8f0"] { border-bottom-color: #1f2942 !important; }
        html.dark-mode [style*="color:#0f172a"] { color: #f1f5f9 !important; }
        html.dark-mode [style*="color:#374151"] { color: #e8ecf4 !important; }
        html.dark-mode [style*="color: #374151"] { color: #e8ecf4 !important; }
        html.dark-mode [style*="color:#64748b"] { color: #9aa7c2 !important; }
        html.dark-mode [style*="color: #64748b"] { color: #9aa7c2 !important; }

        html.dark-mode .btn-primary,
        html.dark-mode .btn-blue { background: #1d4ed8; }
        html.dark-mode .btn-primary:hover,
        html.dark-mode .btn-blue:hover { background: #1e3a8a; }

        html.dark-mode ::-webkit-scrollbar-thumb { background: #26314d; }

    </style>
    @include('layouts.dark-mode')
</head>
<body>

@include('layouts.navbar')


<!-- Hidden print area -->
<div id="printArea"></div>

<div class="pos-grid" style="margin-top: 0; height: calc(100vh - 64px);">

    <!-- ════════════════════════════════════════
         COLUMN 1 — TABLES PANEL
    ════════════════════════════════════════ -->
    <div class="tables-panel">

        <!-- Header -->
        <div style="padding: 16px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <h2 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">
                    <i class="fas fa-chair" style="color:#dc2626; margin-right:6px;"></i>Tables
                </h2>
                <span id="tableStatusBadge" style="font-size:11px; color:#64748b; font-weight:600;"></span>
            </div>
            <!-- Legend -->
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
                <span style="font-size:10px; font-weight:600; color:#16a34a;"><span class="table-status-dot dot-available"></span>Free</span>
                <span style="font-size:10px; font-weight:600; color:#dc2626;"><span class="table-status-dot dot-occupied"></span>Occupied</span>
                <span style="font-size:10px; font-weight:600; color:#d97706;"><span class="table-status-dot dot-reserved"></span>Reserved</span>
            </div>
            <!-- Filter tabs -->
            <div style="display:flex; gap:6px; margin-bottom:10px;">
                <button onclick="filterTables('all', this)" class="cat-pill active" style="padding:4px 12px;">All</button>
                <button onclick="filterTables('main', this)" class="cat-pill" style="padding:4px 12px;">Main</button>
                <button onclick="filterTables('vip', this)" class="cat-pill" style="padding:4px 12px;">VIP</button>
            </div>
            <!-- QR Scanner Button -->
            <button onclick="openQrScanner()" class="btn-blue" style="width:100%; padding:10px; font-size:12px; font-weight:700; margin-bottom:6px; background:#3b82f6;">
                <i class="fas fa-qrcode" style="margin-right:6px;"></i>Scan Table QR
            </button>
            <!-- Takeaway Order Button -->
            <button onclick="startTakeawayOrder()" class="btn-primary" style="width:100%; padding:10px; font-size:12px; font-weight:700;">
                <i class="fas fa-shopping-bag" style="margin-right:6px;"></i>Takeaway Order
            </button>
        </div>

        <!-- Tables list -->
        <div style="flex:1; overflow-y:auto; padding:10px; display:grid; grid-template-columns: repeat(2, 1fr); gap: 7px; align-content: start;" id="tablesContainer">
            <p style="grid-column:1/-1; text-align:center; color:#94a3b8; padding:32px 0; font-size:13px;">Loading tables…</p>
        </div>

    </div>

    <!-- ════════════════════════════════════════
         COLUMN 2 — MENU PANEL
    ════════════════════════════════════════ -->
    <div class="menu-panel">

        <!-- Toolbar -->
        <div style="padding:16px; background:#fff; border-bottom:1px solid #e2e8f0; flex-shrink:0;">
            <div style="display:flex; gap:10px; margin-bottom:12px; align-items:center;">
                <select id="orderTypeSelect"
                        style="padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; background:#f8fafc; color:#374151; outline:none; cursor:pointer;">
                    <option value="dine_in">Dine In</option>
                    <option value="takeaway">Takeaway</option>
                    <option value="delivery">Delivery</option>
                    <option value="vip_room">VIP Room</option>
                </select>
            </div>
            <!-- Categories -->
            <div style="display:flex; gap:8px; overflow-x:auto; padding-bottom:8px;" id="categoriesContainer">
                <button class="cat-pill active" data-category="0" onclick="selectCategory(0, this)">All</button>
            </div>
            <!-- Search Bar -->
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                <input type="text" id="searchInput" placeholder="Search by product name…"
                       style="width:100%; padding:9px 12px 9px 36px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; outline:none; background:#f8fafc;"
                       onfocus="this.style.borderColor='#dc2626'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
        </div>

        <!-- Active order indicator -->
        <div id="activeOrderBanner" style="display:none; background:linear-gradient(90deg,#fef2f2,#fff1f1); border-bottom:1px solid #fecaca; padding:8px 16px; flex-shrink:0;">
            <span style="font-size:12px; font-weight:600; color:#dc2626; flex:1;">
                <i class="fas fa-circle-dot" style="margin-right:4px;"></i>
                <span id="activeOrderText">Adding to Table —</span>
                <span style="color:#374151; font-weight:500; margin-left:4px;">tap a product to add it</span>
            </span>
            <button id="closeOrderBtn" onclick="closeCurrentOrder(); event.stopPropagation();" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:18px; padding:0 8px; width:32px; height:32px; display:flex; align-items:center; justify-content:center; transition:all 0.2s; flex-shrink:0;">
                <i class="fas fa-times-circle" style="font-size:18px;"></i>
            </button>
        </div>

        <!-- Products grid -->
        <div style="flex:1; overflow-y:auto; padding:16px;">
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:12px;" id="productsContainer">
                <p style="grid-column:1/-1; text-align:center; color:#94a3b8; padding:48px 0; font-size:13px;">Loading products…</p>
            </div>
        </div>

    </div>

    <!-- ════════════════════════════════════════
         COLUMN 3 — BILL PANEL
    ════════════════════════════════════════ -->
    <div class="bill-panel">

        <!-- Zone 1: Header with Table Info -->
        <div style="padding:12px 16px; border-bottom:1px solid #e2e8f0; flex-shrink:0; background:#fff;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                <h3 style="font-size:14px; font-weight:800; color:#0f172a; margin:0;">
                    <i class="fas fa-receipt" style="color:#dc2626; margin-right:6px;"></i>Order
                </h3>
                <button onclick="loadHeldOrders(true)" style="font-size:10px; background:#fef3c7; color:#92400e; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; font-weight:700;">
                    <i class="fas fa-pause-circle" style="margin-right:2px;"></i>Held <span id="heldCount" style="background:#f59e0b;color:#fff;border-radius:8px;padding:0px 5px; font-size:9px;">0</span>
                </button>
            </div>
            <div id="selectedTableLabel" style="font-size:12px; font-weight:700; color:#64748b;">
                <i class="fas fa-arrow-left" style="font-size:10px; margin-right:4px;"></i>Select a table to begin
            </div>
        </div>

        <!-- Zone 2: Expandable Customer Info -->
        <div style="padding:0; border-bottom:1px solid #e2e8f0; flex-shrink:0; background:#f8fafc;">
            <button id="customerInfoToggle" onclick="toggleCustomerInfo()" style="width:100%; padding:10px 16px; background:none; border:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; text-align:left;">
                <div style="display:flex; align-items:center;">
                    <i class="fas fa-user-circle" style="color:#1d4ed8; margin-right:6px; font-size:13px;"></i>
                    <span style="font-size:11px; font-weight:700; color:#1d4ed8; text-transform:uppercase;">Customer</span>
                </div>
                <i class="fas fa-chevron-down" id="customerInfoChevron" style="font-size:11px; color:#64748b;"></i>
            </button>
            <div id="customerInfoSection" style="display:none; padding:8px 16px; border-top:1px solid #e2e8f0;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                    <input type="text" id="customerName" placeholder="Name"
                           style="font-size:11px; border:1.5px solid #bfdbfe; border-radius:6px; padding:6px 8px; background:#fff; outline:none; width:100%;"
                           onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#bfdbfe'; saveCustomerInfo()">
                    <input type="text" id="customerPhone" placeholder="Phone"
                           style="font-size:11px; border:1.5px solid #bfdbfe; border-radius:6px; padding:6px 8px; background:#fff; outline:none; width:100%;"
                           onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#bfdbfe'; saveCustomerInfo()">
                </div>
            </div>
        </div>

        <!-- Zone 3: Order items (scrollable) - MAIN AREA -->
        <div style="flex:1; overflow-y:auto; padding:12px 16px; background:#fafafa;" id="billItemsWrapper">
            <div id="billItems">
                <div style="text-align:center; padding:48px 0; color:#cbd5e1;">
                    <i class="fas fa-utensils" style="font-size:36px; margin-bottom:12px; display:block;"></i>
                    <p style="font-size:12px; margin:0;">Select a table, then add items</p>
                </div>
            </div>
        </div>

        <!-- Zone 4: Fixed bottom controls -->
        <div style="border-top:1px solid #e2e8f0; padding:10px 16px; background:#fff; flex-shrink:0; display:flex; flex-direction:column; gap:6px;">

            <!-- Totals + Payment summary — single 4-column row -->
            <div style="display:grid; grid-template-columns: 1fr 1.25fr 1fr 1.1fr; gap:8px; align-items:start;">
                <!-- Subtotal -->
                <div>
                    <div style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#94a3b8; margin-bottom:2px;">Subtotal</div>
                    <div id="subtotalDisplay" style="font-size:12px; font-weight:600; color:#374151;">Rs. 0.00</div>
                </div>
                <!-- Discount -->
                <div>
                    <div style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#94a3b8; margin-bottom:2px;">Discount</div>
                    <div style="display:flex; gap:4px;">
                        <select id="discountType" onchange="recalcTotal()"
                                style="flex:1; min-width:0; font-size:10px; border:1px solid #e2e8f0; border-radius:5px; padding:3px 4px; background:#f8fafc; outline:none; cursor:pointer;">
                            <option value="">None</option>
                            <option value="percentage">%</option>
                            <option value="fixed">Rs</option>
                        </select>
                        <input type="number" id="discountValue" placeholder="0" min="0" oninput="recalcTotal()"
                               style="width:42px; font-size:10px; border:1px solid #e2e8f0; border-radius:5px; padding:3px 4px; outline:none; background:#f8fafc;">
                    </div>
                </div>
                <!-- Total -->
                <div>
                    <div style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#dc2626; margin-bottom:2px;">Total</div>
                    <div id="totalDisplay" style="font-size:14px; font-weight:800; color:#dc2626;">Rs. 0.00</div>
                </div>
                <!-- Payment toggle (hidden until items exist) -->
                <button type="button" id="paymentToggle" onclick="togglePaymentSection()" style="display:none; flex-direction:column; align-items:flex-start; gap:2px; background:none; border:none; padding:0; cursor:pointer; text-align:left;">
                    <span style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#94a3b8;">Payment</span>
                    <span style="font-size:12px; font-weight:700; color:#dc2626; display:flex; align-items:center; gap:4px;">
                        <span id="paymentMethodSummary">Cash</span>
                        <i class="fas fa-chevron-down" id="paymentChevron" style="font-size:9px; color:#64748b; transition:transform 0.15s;"></i>
                    </span>
                </button>
            </div>

            <!-- Payment details (collapsible, hidden until items exist) -->
            <div id="paymentSection" style="display:none;">
                <div id="paymentBody" style="display:none; padding-top:8px; margin-top:2px; border-top:1px solid #e2e8f0;">
                <div style="display:flex; gap:5px; margin-bottom:8px;">
                    <button class="pay-method-btn active" data-method="cash" onclick="selectPaymentMethod('cash')" style="flex:1; padding:6px 4px; font-size:10px;">
                        <i class="fas fa-money-bill-wave" style="display:block; font-size:13px; margin-bottom:2px;"></i>Cash
                    </button>
                    <button class="pay-method-btn" data-method="card" onclick="selectPaymentMethod('card')" style="flex:1; padding:6px 4px; font-size:10px;">
                        <i class="fas fa-credit-card" style="display:block; font-size:13px; margin-bottom:2px;"></i>Card
                    </button>
                    <button class="pay-method-btn" data-method="bank_transfer" onclick="selectPaymentMethod('bank_transfer')" style="flex:1; padding:6px 4px; font-size:10px;">
                        <i class="fas fa-university" style="display:block; font-size:13px; margin-bottom:2px;"></i>Bank
                    </button>
                    <button class="pay-method-btn" data-method="split" onclick="selectPaymentMethod('split')" style="flex:1; padding:6px 4px; font-size:10px;">
                        <i class="fas fa-code-branch" style="display:block; font-size:13px; margin-bottom:2px;"></i>Split
                    </button>
                </div>
                <!-- Cash amount input -->
                <div id="cashSection" style="display:flex; gap:6px;">
                    <div style="flex:1;">
                        <label style="font-size:9px; font-weight:600; color:#64748b; display:block; margin-bottom:2px;">Paid</label>
                        <input type="number" id="amountPaid" placeholder="0.00" min="0" oninput="updateChange()"
                               style="width:100%; font-size:11px; font-weight:700; border:1px solid #e2e8f0; border-radius:5px; padding:5px 6px; outline:none;"
                               onfocus="this.style.borderColor='#dc2626'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:9px; font-weight:600; color:#64748b; display:block; margin-bottom:2px;">Change</label>
                        <div id="changeDisplay" style="font-size:12px; font-weight:700; color:#16a34a; padding:5px 6px; background:#f0fdf4; border-radius:5px; border:1px solid #bbf7d0; text-align:center;">Rs. 0.00</div>
                    </div>
                </div>

                <!-- Split Payment inputs -->
                <div id="splitSection" style="display:none; border-top:1px solid #e2e8f0; padding-top:8px;">
                    <div style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">Split Payment</div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px; margin-bottom:6px;">
                        <div>
                            <label for="splitMethod1" style="font-size:9px; font-weight:600; color:#64748b; display:block; margin-bottom:2px;">Method 1</label>
                            <select id="splitMethod1" onchange="updateSplitTotal()" style="width:100%; font-size:10px; border:1px solid #e2e8f0; border-radius:5px; padding:4px 6px; outline:none;">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank">Bank</option>
                            </select>
                        </div>
                        <div>
                            <label for="splitAmount1" style="font-size:9px; font-weight:600; color:#64748b; display:block; margin-bottom:2px;">Amount</label>
                            <input type="number" id="splitAmount1" placeholder="0.00" min="0" oninput="updateSplitTotal()"
                                   style="width:100%; font-size:10px; border:1px solid #e2e8f0; border-radius:5px; padding:4px 6px; outline:none;">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px; margin-bottom:6px;">
                        <div>
                            <label for="splitMethod2" style="font-size:9px; font-weight:600; color:#64748b; display:block; margin-bottom:2px;">Method 2</label>
                            <select id="splitMethod2" onchange="updateSplitTotal()" style="width:100%; font-size:10px; border:1px solid #e2e8f0; border-radius:5px; padding:4px 6px; outline:none;">
                                <option value="">-- Select --</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank">Bank</option>
                            </select>
                        </div>
                        <div>
                            <label for="splitAmount2" style="font-size:9px; font-weight:600; color:#64748b; display:block; margin-bottom:2px;">Amount</label>
                            <input type="number" id="splitAmount2" placeholder="0.00" min="0" oninput="updateSplitTotal()"
                                   style="width:100%; font-size:10px; border:1px solid #e2e8f0; border-radius:5px; padding:4px 6px; outline:none;">
                        </div>
                    </div>
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:5px; padding:5px 6px; text-align:center;">
                        <div style="font-size:8px; color:#64748b; margin-bottom:2px;">Total Paid</div>
                        <div id="splitTotalDisplay" style="font-size:12px; font-weight:700; color:#16a34a;">Rs. 0.00</div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div id="orderControls" style="display:flex; flex-direction:column; gap:6px;">

                <!-- Row 1: KOT -->
                <div style="display:flex; gap:6px;">
                    <button onclick="printKot()" class="btn-orange" style="flex:1; padding:8px 6px; font-size:11px;">
                        <i class="fas fa-receipt" style="margin-right:3px;"></i>KOT
                    </button>
                </div>

                <!-- Row 2: Waiter Bill + Pay (side by side) -->
                <div id="waiterPayRow" style="display:none; gap:6px; display:flex;">
                    <button onclick="printBill()" id="waiterBillBtn" class="btn-blue" style="flex:1; padding:8px 6px; font-size:11px;">
                        <i class="fas fa-file-invoice" style="margin-right:3px;"></i>Bill
                    </button>
                    <button onclick="initiatePayment()" id="payBtn" class="btn-green" style="flex:1; padding:8px 6px; font-size:11px;">
                        <i class="fas fa-check-circle" style="margin-right:3px;"></i>Pay
                    </button>
                </div>

                <!-- Row 3: Hold -->
                <button onclick="holdCurrentOrder()" id="holdBtn" class="btn-secondary" style="display:none; width:100%; padding:8px; font-size:11px;">
                    <i class="fas fa-pause" style="margin-right:3px;"></i>Hold Order
                </button>

            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: Final Bill (paid)
══════════════════════════════════════════════════ -->
<div id="finalBillModal" class="modal-overlay">
    <div class="modal-box" style="max-width:380px; padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;"><i class="fas fa-receipt" style="color:#16a34a; margin-right:6px;"></i>Final Bill</h2>
            <button onclick="closeModal('finalBillModal')" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8; line-height:1;">&times;</button>
        </div>
        <div id="billContent" style="font-family:'Courier New',monospace; background:#fafafa; border-radius:8px; padding:16px; font-size:12px; border:1px solid #e2e8f0;"></div>
        <div style="display:flex; gap:10px; margin-top:16px;">
            <button onclick="closeModal('finalBillModal')" class="btn-secondary" style="flex:1;">Cancel</button>
            <button onclick="printBillContent()" class="btn-primary" style="flex:1;"><i class="fas fa-print" style="margin-right:4px;"></i>Print</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: KOT
══════════════════════════════════════════════════ -->
<div id="kotModal" class="modal-overlay">
    <div class="modal-box" style="max-width:400px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0;"><i class="fas fa-utensils" style="color:#ea580c; margin-right:6px;"></i>Kitchen Order</h2>
            <button onclick="closeModal('kotModal')" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <div style="background:#f8fafc; border-radius:10px; padding:12px; margin-bottom:16px;">
            <p style="font-size:13px; font-weight:700; margin:0 0 3px;" id="kotOrderNumber">Order #—</p>
            <p style="font-size:13px; color:#64748b; margin:0;" id="kotTableNumber">Table —</p>
        </div>
        <div id="kotItems" style="max-height:260px; overflow-y:auto; background:#fff; border:1.5px solid #e2e8f0; border-radius:10px; padding:12px; display:flex; flex-direction:column; gap:10px;"></div>
        <div style="display:flex; gap:10px; margin-top:20px;">
            <button onclick="closeModal('kotModal')" class="btn-secondary" style="flex:1;">Close</button>
            <button onclick="printKotContent()" class="btn-orange" style="flex:1;"><i class="fas fa-print" style="margin-right:4px;"></i>Print</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: Held Orders
══════════════════════════════════════════════════ -->
<div id="heldOrdersModal" class="modal-overlay">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0;"><i class="fas fa-pause-circle" style="color:#f59e0b; margin-right:6px;"></i>Held Orders</h2>
            <button onclick="closeModal('heldOrdersModal')" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <div id="heldOrdersList" style="display:flex; flex-direction:column; gap:10px; max-height:400px; overflow-y:auto;"></div>
    </div>
</div>

<!-- Shift Not Started Modal -->
<div id="shiftModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 400px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 48px; margin-bottom: 12px;">
                <i class="fas fa-exclamation-circle" style="color: #dc2626;"></i>
            </div>
            <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">No Active Shift</h2>
            <p style="font-size: 14px; color: #64748b; margin: 0;">You must start a shift before processing orders</p>
        </div>
        <div style="background: #f9fafb; border-radius: 10px; padding: 16px; margin-bottom: 16px;">
            <p style="font-size: 12px; color: #64748b; margin: 0;">
                <i class="fas fa-info-circle" style="color: #3b82f6; margin-right: 6px;"></i>
                Go to the Shifts & Till Management module to start your shift with an opening balance.
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="closeModal('shiftModal')" style="flex: 1; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; background: #fff; color: #374151; font-weight: 700; cursor: pointer; transition: all 0.2s;">
                Cancel
            </button>
            <a href="{{ route('shifts.index') }}" style="flex: 1; padding: 10px; border: none; border-radius: 8px; background: #dc2626; color: #fff; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-arrow-right" style="margin-right: 6px;"></i> Go to Shifts
            </a>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: QR Code Scanner
══════════════════════════════════════════════════ -->
<div id="qrScannerModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 450px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0;"><i class="fas fa-qrcode" style="color:#3b82f6; margin-right:6px;"></i>Scan Table QR Code</h2>
            <button onclick="closeQrScanner()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8; line-height:1;">&times;</button>
        </div>

        <div style="background:#f8fafc; border-radius:10px; padding:16px; margin-bottom:16px; text-align:center;">
            <p style="font-size:13px; color:#64748b; margin:0 0 12px;">Point your camera at the table QR code</p>
            <video id="qrVideo" style="width:100%; max-width:300px; border-radius:8px; border:2px solid #e2e8f0; display:none;"></video>
            <div id="qrCanvasContainer" style="display:none;">
                <canvas id="qrCanvas" style="display:none;"></canvas>
            </div>
            <div id="qrLoadingState" style="padding:32px 0; text-align:center;">
                <div style="font-size:14px; color:#94a3b8; margin-bottom:8px;">Initializing camera...</div>
                <i class="fas fa-circle-notch fa-spin" style="color:#3b82f6; font-size:24px;"></i>
            </div>
        </div>

        <div style="background:#fef3c7; border-left:4px solid #f59e0b; border-radius:6px; padding:12px; margin-bottom:16px;">
            <p style="font-size:12px; color:#92400e; margin:0;">
                <i class="fas fa-info-circle" style="margin-right:6px;"></i>
                Scanned QR data will appear below
            </p>
        </div>

        <input type="hidden" id="scannedQrData">
        <div id="qrScanResult" style="display:none; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px; margin-bottom:16px;">
            <p style="font-size:12px; color:#16a34a; margin:0 0 8px; font-weight:600;">✓ QR Scanned Successfully</p>
            <div id="qrResultDetails" style="font-size:11px; color:#65a30d; margin-bottom:8px;"></div>
        </div>

        <div style="display:flex; gap:10px;">
            <button onclick="closeQrScanner()" class="btn-secondary" style="flex:1;">Cancel</button>
            <button onclick="confirmQrScan()" id="confirmQrBtn" class="btn-primary" style="flex:1; display:none;">
                <i class="fas fa-check" style="margin-right:4px;"></i>Confirm & Open Table
            </button>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast"></div>

<script>
    // ── State ──
    let currentOrder  = null;
    let currentTable  = null;
    let allTables     = [];
    let allProducts   = [];
    let allCategories = @json($categories);
    let selectedPaymentMethod = 'cash';
    let currentKotContent     = '';
    let currentBillContent    = '';
    let tableFilter           = 'all';
    let stockCache            = {}; // { productId: remainingQty } for non-unlimited products
    let openDiscountRows      = new Set(); // item IDs whose discount input row is open
    let qtyLock               = {}; // { itemId: true } — prevents overlapping qty updates

    // ── Bootstrap ──
    async function initPos() {
        await loadTables();
        loadCategories();
        await loadProducts();
        loadHeldOrders();
        setupEventListeners();
        updateShiftStatus();
        setInterval(updateShiftStatus, 15000); // Update every 15 seconds
    }

    // Update shift status in banner
    async function updateShiftStatus() {
        @if($activeShift)
            try {
                const res = await fetch('{{ route("shifts.active") }}');
                const data = await res.json();
                if (data.active && data.shift) {
                    const el = document.getElementById('posTotalSales');
                    if (el) {
                        el.textContent = 'Rs. ' + parseFloat(data.shift.total_sales).toFixed(2);
                    }
                }
            } catch (e) {
                console.error('Error updating shift status:', e);
            }
        @endif
    }

    // ═══════════════════════════════════════════
    // TABLES
    // ═══════════════════════════════════════════

    async function loadTables() {
        try {
            const res = await fetch('{{ route("pos.tables") }}');
            if (!res.ok) { toast('Failed to load tables', 'error'); return; }
            allTables = await res.json();
            renderTables();
            updateTableStatusBadge();
        } catch (e) {
            console.error('Load tables error:', e);
            toast('Error loading tables', 'error');
        }
    }

    function updateTableStatusBadge() {
        const occupied = allTables.filter(t => t.status === 'occupied').length;
        const total    = allTables.length;
        document.getElementById('tableStatusBadge').textContent = occupied + '/' + total + ' occupied';
    }

    function filterTables(section, btn) {
        tableFilter = section;
        const panel = document.querySelector('.tables-panel');
        if (panel) panel.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        renderTables();
    }

    function renderTables() {
        const container = document.getElementById('tablesContainer');
        const filtered  = tableFilter === 'all'
            ? allTables
            : allTables.filter(t => t.section === tableFilter);

        if (filtered.length === 0) {
            container.innerHTML = '<p style="text-align:center; color:#94a3b8; padding:32px 0; font-size:13px;">No tables found</p>';
            return;
        }

        container.innerHTML = filtered.map(function(table) {
            const isOccupied = table.status === 'occupied' || table.status === 'reserved';
            const isSelected = currentTable && currentTable.id === table.id;

            let itemBadge = '';
            if (table.has_order && table.order_items_count > 0) {
                itemBadge = '<div style="font-size:11px; font-weight:800; color:#dc2626; margin-top:4px;">'
                    + '<i class="fas fa-circle-dot" style="font-size:8px;"></i> '
                    + table.order_items_count + ' item' + (table.order_items_count !== 1 ? 's' : '')
                    + '</div>';
            }

            let timeLabel = '';
            if (table.occupied_at) {
                const t = new Date(table.occupied_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                timeLabel = '<div style="font-size:10px; color:#94a3b8; margin-top:2px;"><i class="fas fa-clock" style="font-size:9px;"></i> ' + t + '</div>';
            }

            let actionBar = '';
            if (isOccupied && table.has_order) {
                actionBar = '<div class="table-card-actions">'
                    + '<button onclick="printKotForTable(' + table.order_id + '); event.stopPropagation();" '
                    + 'style="flex:1; font-size:11px; font-weight:700; background:#ea580c; color:#fff; border:none; border-radius:7px; padding:6px 4px; cursor:pointer;">'
                    + '<i class="fas fa-print" style="margin-right:3px;"></i>KOT</button>'
                    + '</div>';
            }

            const clickFn = isOccupied && table.has_order
                ? 'viewTableOrder(' + table.order_id + ')'
                : (isOccupied ? 'expandTableCard(' + table.id + ', event)' : 'startNewOrder(' + table.id + ')');

            const vipBadge = table.section === 'vip'
                ? '<div style="position:absolute; top:6px; left:6px; font-size:9px; font-weight:800; background:#7c3aed; color:#fff; padding:2px 6px; border-radius:6px;">VIP</div>'
                : '';

            return '<div id="tc-' + table.id + '" class="table-card ' + table.status + (isSelected ? ' selected' : '') + '" onclick="' + clickFn + '">'
                + vipBadge
                + '<div style="font-size:16px; font-weight:900; color:#0f172a; line-height:1;">' + table.table_number + '</div>'
                + '<div style="font-size:10px; font-weight:600; color:#64748b; margin-top:2px;">' + escapeHtml(table.name) + '</div>'
                
                + itemBadge + timeLabel + actionBar
                + '</div>';
        }).join('');
    }

    function expandTableCard(tableId, event) {
        event.stopPropagation();
        const card       = document.getElementById('tc-' + tableId);
        const isExpanded = card.classList.contains('expanded');
        document.querySelectorAll('.table-card.expanded').forEach(function(c) { c.classList.remove('expanded'); });
        if (!isExpanded) {
            card.classList.add('expanded');
        }
    }

    async function viewTableOrder(orderId) {
        try {
            showLoading();
            const res   = await fetch('{{ route("pos.order.show", ":id") }}'.replace(':id', orderId));
            if (!res.ok) { toast('Failed to load order', 'error'); hideLoading(); return; }
            const order = await res.json();
            currentOrder = order;
            currentTable = allTables.find(function(t) { return t.id === order.table_id; }) || null;
            // Collapse all expanded cards, mark selected
            document.querySelectorAll('.table-card.expanded').forEach(function(c) { c.classList.remove('expanded'); });
            document.querySelectorAll('.table-card.selected').forEach(function(c) { c.classList.remove('selected'); });
            if (currentTable) {
                const card = document.getElementById('tc-' + currentTable.id);
                if (card) card.classList.add('selected');
            }
            renderTableView();
            renderBill();
            hideLoading();
        } catch (e) {
            console.error('View order error:', e);
            hideLoading();
            toast('Error loading order', 'error');
        }
    }

    async function startNewOrder(tableId) {
        const table = allTables.find(function(t) { return t.id === tableId; });
        if (!table) return;

        // If clicking the same table that's already selected and no items, deselect it
        if (currentTable && currentTable.id === tableId && (!currentOrder || !currentOrder.items || currentOrder.items.length === 0)) {
            resetOrder();
            await loadTables();
            return;
        }

        // If switching to a different table while the current order is empty, release the old table first
        if (currentOrder && currentOrder.id && (!currentOrder.items || currentOrder.items.length === 0)) {
            const oldTableId = currentTable ? currentTable.id : null;
            try {
                await fetch('{{ route("pos.order.close_table", ":id") }}'.replace(':id', currentOrder.id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
            } catch (e) {
                console.error('Failed to release empty table:', e);
            }
            // Immediately show the old table as available in frontend for instant feedback
            if (oldTableId) {
                const oldCard = document.getElementById('tc-' + oldTableId);
                if (oldCard) {
                    oldCard.classList.remove('occupied', 'selected', 'expanded');
                    oldCard.classList.add('available');
                }
            }
            currentOrder = null;
            currentTable = null;
        }

        showLoading();
        currentTable = table;
        const res  = await fetch('{{ route("pos.order.create") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                table_id: tableId,
                order_type: 'dine_in'
            })
        });
        if (!res.ok) {
            hideLoading();
            toast('Failed to open table', 'error');
            return;
        }
        const data = await res.json();
        currentOrder = {
            id: data.order_id, order_number: data.order_number,
            items: [], subtotal: 0, total: 0,
            discount_amount: 0, live_bill_enabled: false,
            customer_name: null, customer_phone: null,
            table_id: tableId,
        };
        renderTableView();
        renderBill();
        await loadTables();
        hideLoading();
        toast('Table ' + table.table_number + ' opened', 'success');
    }

    async function startTakeawayOrder(forceType) {
        showLoading();
        try {
            // Deselect any previously selected table
            document.querySelectorAll('.table-card.selected').forEach(function(c) { c.classList.remove('selected'); });
            currentTable = null;

            let orderType = 'takeaway';
            if (typeof forceType === 'string' && ['takeaway', 'delivery', 'vip_room'].includes(forceType)) {
                orderType = forceType;
            }

            const selectEl = document.getElementById('orderTypeSelect');
            if (selectEl && selectEl.value !== orderType) {
                selectEl.value = orderType;
            }

            const res = await fetch('{{ route("pos.order.create") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_type: orderType
                })
            });

            if (!res.ok) {
                hideLoading();
                console.error('Order creation HTTP error. Status:', res.status, 'Text:', res.statusText);
                let errorMessage = 'Failed to create ' + orderType + ' order';

                if (res.status === 419) {
                    errorMessage = 'Session expired. Please reload the page and try again.';
                    console.error('CSRF/Session token error');
                } else if (res.status === 422) {
                    try {
                        const errorData = await res.json();
                        console.error('Validation errors:', errorData);
                        if (errorData.errors) {
                            errorMessage = Object.values(errorData.errors)[0][0] || errorMessage;
                        }
                    } catch (e) {
                        console.error('Could not parse error response');
                    }
                } else {
                    try {
                        const errorData = await res.json();
                        console.error('Order creation error:', errorData);
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (e) {
                        console.error('Could not parse error response');
                    }
                }

                toast(errorMessage, 'error');
                return false;
            }

            let data;
            try {
                data = await res.json();
                console.log('Order created successfully:', data);
            } catch (e) {
                hideLoading();
                console.error('Failed to parse response JSON:', e);
                toast('Invalid server response. Please try again.', 'error');
                return false;
            }

            if (!data || !data.order_id) {
                hideLoading();
                console.error('Missing order_id in response:', data);
                toast('Failed to create ' + orderType + ' order: Invalid response from server', 'error');
                return false;
            }

            currentOrder = {
                id: data.order_id,
                order_number: data.order_number,
                items: [],
                subtotal: 0,
                total: 0,
                discount_amount: 0,
                live_bill_enabled: false,
                customer_name: null,
                customer_phone: null,
                table_id: null,
                order_type: orderType,
                table_number: null,
                table_name: null
            };

            renderTableView();
            renderBill();
            hideLoading();

            const typeLabel = orderType.charAt(0).toUpperCase() + orderType.slice(1);
            toast(typeLabel + ' order created — ready to add items', 'success');
            return true;
        } catch (e) {
            console.error('startTakeawayOrder error:', e);
            hideLoading();
            toast('Error creating order: ' + e.message, 'error');
            return false;
        }
    }

    // ═══════════════════════════════════════════
    // PRODUCTS
    // ═══════════════════════════════════════════

    async function loadProducts(search, categoryId) {
        try {
            search     = search     || '';
            categoryId = categoryId || 0;
            const params = new URLSearchParams();
            if (search)         params.append('search', search);
            if (categoryId > 0) params.append('category_id', categoryId);
            const res = await fetch('{{ route("pos.products") }}?' + params);
            if (!res.ok) { toast('Failed to load products', 'error'); return; }
            allProducts = await res.json();
            stockCache = {};
            allProducts.forEach(function(p) {
                if (!p.is_unlimited_stock) stockCache[p.id] = p.quantity;
            });
            renderProducts();
        } catch (e) {
            console.error('Load products error:', e);
            toast('Error loading products', 'error');
        }
    }

    function loadCategories() {
        const container = document.getElementById('categoriesContainer');
        container.innerHTML = '<button class="cat-pill active" data-category="0" onclick="selectCategory(0, this)">All</button>';
        allCategories.forEach(function(cat) {
            const btn = document.createElement('button');
            btn.className = 'cat-pill';
            btn.textContent = cat.name;
            btn.setAttribute('data-category', cat.id);
            btn.onclick = function() { selectCategory(cat.id, btn); };
            container.appendChild(btn);
        });
    }

    function selectCategory(id, btn) {
        document.querySelectorAll('#categoriesContainer .cat-pill').forEach(function(b) { b.classList.remove('active'); });
        if (btn) btn.classList.add('active');
        loadProducts(document.getElementById('searchInput').value, id);
    }

    function renderProducts() {
        const container = document.getElementById('productsContainer');
        if (allProducts.length === 0) {
            container.innerHTML = '<p style="grid-column:1/-1; text-align:center; color:#94a3b8; padding:48px 0; font-size:13px;"><i class="fas fa-search" style="font-size:28px; display:block; margin-bottom:10px;"></i>No products found</p>';
            return;
        }
        container.innerHTML = allProducts.map(function(p) {
            let imageHtml = '';
            if (p.image) {
                imageHtml = '<img src="/storage/' + p.image + '" alt="' + escapeHtml(p.name) + '" '
                    + 'style="width:100%; height:100%; object-fit:cover;">';
            } else {
                imageHtml = '<i class="fas fa-utensils" style="color:#dc2626; font-size:18px;"></i>';
            }

            const availableQty = p.is_unlimited_stock ? null : (stockCache.hasOwnProperty(p.id) ? stockCache[p.id] : p.quantity);
            const isOutOfStock = !p.is_unlimited_stock && availableQty <= 0;
            let stockBadge;
            if (p.is_unlimited_stock) {
                stockBadge = '<p style="font-size:10px; color:#16a34a; margin:2px 0 0; font-weight:600;">∞ Unlimited</p>';
            } else if (availableQty > 0) {
                stockBadge = '<p style="font-size:10px; color:#64748b; margin:2px 0 0;">Stock: ' + availableQty + '</p>';
            } else {
                stockBadge = '<p style="font-size:10px; color:#ef4444; margin:2px 0 0; font-weight:700;">Out of Stock</p>';
            }
            const cardExtra = isOutOfStock
                ? 'style="opacity:0.5; cursor:not-allowed; pointer-events:none;"'
                : 'onclick="addProductToOrder(' + p.id + ', \'' + escapeJs(p.name) + '\', ' + p.price + ')"';

            return '<div class="product-card" ' + cardExtra + '>'
                + '<div style="height:80px; background:linear-gradient(135deg,#fef2f2,#fee2e2); border-radius:10px; display:flex; align-items:center; justify-content:center; margin-bottom:10px; overflow:hidden; position:relative;">'
                + imageHtml
                + '</div>'
                + '<p style="font-size:12px; font-weight:700; color:#0f172a; margin:0 0 4px; line-height:1.3; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">' + escapeHtml(p.name) + '</p>'
                + '<p style="font-size:14px; font-weight:900; color:#dc2626; margin:0;">Rs. ' + p.price.toFixed(2) + '</p>'
                + stockBadge
                + '</div>';
        }).join('');
    }

    // ═══════════════════════════════════════════
    // ORDER MANAGEMENT
    // ═══════════════════════════════════════════

    async function addProductToOrder(productId, productName, price) {
        // CHECK FOR ACTIVE SHIFT FIRST
        const hasActiveShift = {{ $activeShift ? 'true' : 'false' }};
        if (!hasActiveShift) {
            showShiftModal();
            return;
        }

        if (!currentOrder || !currentOrder.id) {
            const selectEl = document.getElementById('orderTypeSelect');
            const orderType = selectEl ? selectEl.value : 'dine_in';
            if (orderType === 'takeaway' || orderType === 'delivery' || orderType === 'vip_room') {
                const created = await startTakeawayOrder(orderType);
                if (!created) {
                    toast('Failed to create order. Please try again.', 'error');
                    return;
                }
                // Small delay to ensure order is created
                await new Promise(resolve => setTimeout(resolve, 100));
            } else {
                toast('Please select a table or create a takeaway order first', 'error');
                return;
            }
        }

        // Verify order is valid before adding items
        if (!currentOrder || !currentOrder.id || !Array.isArray(currentOrder.items)) {
            toast('No active order. Please create an order first.', 'error');
            return;
        }

        // Check stock before adding
        if (stockCache.hasOwnProperty(productId) && stockCache[productId] <= 0) {
            toast('This item is out of stock', 'error');
            return;
        }

        // Optimistic update - only increase qty if item exists and NOT printed to kitchen
        const existing = currentOrder.items.find(function(i) {
            return i.product_id === productId && (!i.kot_printed);
        });
        if (existing) {
            existing.quantity++;
            existing.subtotal = existing.unit_price * existing.quantity;
        } else {
            currentOrder.items.push({
                id: null, product_id: productId, product_name: productName,
                unit_price: price, quantity: 1, subtotal: price, kitchen_notes: null, kot_printed: false
            });
        }
        // Deduct from stock cache
        if (stockCache.hasOwnProperty(productId)) {
            stockCache[productId] = Math.max(0, stockCache[productId] - 1);
        }
        recalcOrderTotals();
        renderBill();
        renderProducts();

        try {
            const res = await fetch('{{ route("pos.item.add", ":id") }}'.replace(':id', currentOrder.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            });
            const data = await res.json();
            if (data.success) {
                // Patch the optimistic item with the real server ID — no re-fetch needed
                const optimisticItem = currentOrder.items.find(function(i) {
                    return i.product_id === productId && i.id === null;
                });
                if (optimisticItem) {
                    optimisticItem.id = data.item_id;
                    optimisticItem.kot_printed = data.item_kot_printed || false;
                } else {
                    // Item was merged into an existing row server-side; patch that row's id
                    const existingItem = currentOrder.items.find(function(i) {
                        return i.product_id === productId;
                    });
                    if (existingItem) existingItem.id = data.item_id;
                }
                renderBill();
                if (data.low_stock_alert) {
                    toast(data.low_stock_alert, 'warning');
                }
            } else {
                // Roll back optimistic update on failure
                if (existing) {
                    existing.quantity--;
                    existing.subtotal = existing.unit_price * existing.quantity;
                } else {
                    currentOrder.items = currentOrder.items.filter(function(i) {
                        return !(i.product_id === productId && i.id === null);
                    });
                }
                if (stockCache.hasOwnProperty(productId)) stockCache[productId]++;
                recalcOrderTotals();
                renderBill();
                renderProducts();
                toast('Failed to add item to order', 'error');
            }
        } catch (e) {
            console.error('Add item error:', e);
            toast('Failed to add item to order', 'error');
        }
    }

    async function syncOrder() {
        try {
            if (!currentOrder || !currentOrder.id) return;
            const res = await fetch('{{ route("pos.order.show", ":id") }}'.replace(':id', currentOrder.id));
            if (!res.ok) return;
            currentOrder = await res.json();
            renderBill();
        } catch (e) {
            console.error('Sync order error:', e);
        }
    }

    async function increaseQty(itemId) {
        if (qtyLock[itemId]) return;
        const item = currentOrder.items.find(function(i) { return i.id === itemId; });
        if (!item) return;

        if (stockCache.hasOwnProperty(item.product_id) && stockCache[item.product_id] <= 0) {
            toast('No more stock available for this item', 'error');
            return;
        }

        qtyLock[itemId] = true;
        if (stockCache.hasOwnProperty(item.product_id)) stockCache[item.product_id]--;
        item.quantity++;
        item.subtotal = item.unit_price * item.quantity * (1 - (item.discount_percent || 0) / 100);
        recalcOrderTotals();
        renderBill();
        renderProducts();
        fetch('{{ route("pos.item.update", [":id", ":item"]) }}'.replace(':id', currentOrder.id).replace(':item', itemId), {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ quantity: item.quantity, discount_percent: item.discount_percent || 0 })
        }).catch(function() { toast('Failed to update quantity', 'error'); });
        delete qtyLock[itemId];
    }

    async function decreaseQty(itemId) {
        if (qtyLock[itemId]) return;
        const item = currentOrder.items.find(function(i) { return i.id === itemId; });
        if (!item || item.quantity <= 1) return;

        qtyLock[itemId] = true;
        if (stockCache.hasOwnProperty(item.product_id)) stockCache[item.product_id]++;
        item.quantity--;
        item.subtotal = item.unit_price * item.quantity * (1 - (item.discount_percent || 0) / 100);
        recalcOrderTotals();
        renderBill();
        renderProducts();
        fetch('{{ route("pos.item.update", [":id", ":item"]) }}'.replace(':id', currentOrder.id).replace(':item', itemId), {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ quantity: item.quantity, discount_percent: item.discount_percent || 0 })
        }).catch(function() { toast('Failed to update quantity', 'error'); });
        delete qtyLock[itemId];
    }

    async function setQty(itemId, rawValue) {
        if (qtyLock[itemId]) return;
        const item = currentOrder.items.find(function(i) { return i.id === itemId; });
        if (!item) return;

        let newQty = Math.max(1, parseInt(rawValue) || 1);
        const diff = newQty - item.quantity;

        // Enforce stock cap when increasing
        if (diff > 0 && stockCache.hasOwnProperty(item.product_id)) {
            if (stockCache[item.product_id] < diff) {
                newQty = item.quantity + Math.max(0, stockCache[item.product_id]);
                if (newQty === item.quantity) {
                    toast('No more stock available for this item', 'error');
                    renderBill();
                    return;
                }
                toast('Quantity limited to available stock', 'error');
            }
        }

        qtyLock[itemId] = true;
        if (stockCache.hasOwnProperty(item.product_id)) {
            stockCache[item.product_id] -= (newQty - item.quantity);
            stockCache[item.product_id] = Math.max(0, stockCache[item.product_id]);
        }
        item.quantity = newQty;
        item.subtotal = item.unit_price * newQty * (1 - (item.discount_percent || 0) / 100);
        recalcOrderTotals();
        renderBill();
        renderProducts();
        fetch('{{ route("pos.item.update", [":id", ":item"]) }}'.replace(':id', currentOrder.id).replace(':item', itemId), {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ quantity: newQty, discount_percent: item.discount_percent || 0 })
        }).catch(function() { toast('Failed to update quantity', 'error'); });
        delete qtyLock[itemId];
    }

    function toggleDiscountRow(itemId) {
        if (openDiscountRows.has(itemId)) {
            openDiscountRows.delete(itemId);
        } else {
            openDiscountRows.add(itemId);
        }
        renderBill();
        // Auto-focus the input when opening
        if (openDiscountRows.has(itemId)) {
            const inp = document.getElementById('disc-' + itemId);
            if (inp) { inp.focus(); inp.select(); }
        }
    }

    async function applyItemDiscount(itemId) {
        const item = currentOrder.items.find(function(i) { return i.id === itemId; });
        if (!item) return;
        const input = document.getElementById('disc-' + itemId);
        const percent = Math.min(100, Math.max(0, parseFloat(input ? input.value : 0) || 0));

        item.discount_percent = percent;
        item.subtotal = item.unit_price * item.quantity * (1 - percent / 100);
        openDiscountRows.delete(itemId);
        recalcOrderTotals();
        renderBill();

        fetch('{{ route("pos.item.update", [":id", ":item"]) }}'.replace(':id', currentOrder.id).replace(':item', itemId), {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ quantity: item.quantity, discount_percent: percent })
        }).catch(function() { toast('Failed to apply discount', 'error'); });
    }

    async function clearItemDiscount(itemId) {
        const item = currentOrder.items.find(function(i) { return i.id === itemId; });
        if (!item) return;
        item.discount_percent = 0;
        item.subtotal = item.unit_price * item.quantity;
        openDiscountRows.delete(itemId);
        recalcOrderTotals();
        renderBill();

        fetch('{{ route("pos.item.update", [":id", ":item"]) }}'.replace(':id', currentOrder.id).replace(':item', itemId), {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ quantity: item.quantity, discount_percent: 0 })
        }).catch(function() { toast('Failed to clear discount', 'error'); });
    }

    async function removeItem(itemId) {
        const removedItem = currentOrder.items.find(function(i) { return i.id === itemId; });
        if (removedItem && stockCache.hasOwnProperty(removedItem.product_id)) {
            stockCache[removedItem.product_id] += removedItem.quantity;
        }
        currentOrder.items = currentOrder.items.filter(function(i) { return i.id !== itemId; });
        recalcOrderTotals();
        renderBill();
        renderProducts();
        fetch('{{ route("pos.item.remove", [":id", ":item"]) }}'.replace(':id', currentOrder.id).replace(':item', itemId), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).catch(function() { toast('Failed to remove item', 'error'); });
    }

    function recalcOrderTotals() {
        if (!currentOrder) return;
        const subtotal = currentOrder.items.reduce(function(s, i) { return s + i.subtotal; }, 0);
        currentOrder.subtotal = subtotal;
        currentOrder.total    = subtotal;
        currentOrder.discount_amount = 0;
    }

    // ═══════════════════════════════════════════
    // BILL PANEL RENDER
    // ═══════════════════════════════════════════

    function renderTableView() {
        if (!currentTable && !currentOrder) {
            document.getElementById('selectedTableLabel').innerHTML =
                '<i class="fas fa-arrow-left" style="font-size:11px; margin-right:4px;"></i>Select a table or create takeaway order';
            document.getElementById('customerInfoToggle').style.display = 'none';
            document.getElementById('activeOrderBanner').style.display   = 'none';
            return;
        }

        if (!currentTable && currentOrder) {
            const displayType = currentOrder.order_type ? 
                                currentOrder.order_type.charAt(0).toUpperCase() + currentOrder.order_type.slice(1) : 
                                'Takeaway';
                                
            document.getElementById('selectedTableLabel').innerHTML =
                '🛍 <strong>' + displayType + ' Order</strong> — ' + (currentOrder.order_number || '—');
            document.getElementById('customerInfoToggle').style.display = 'flex';
            document.getElementById('activeOrderBanner').style.display   = 'flex';
            document.getElementById('activeOrderText').textContent = displayType + ' — adding items';
            return;
        }

        const sectionLabel = currentTable.section === 'vip' ? '🟣 VIP' : '🍽';
        document.getElementById('selectedTableLabel').innerHTML =
            sectionLabel + ' <strong>Table ' + currentTable.table_number + '</strong> — ' + escapeHtml(currentTable.name);
        document.getElementById('customerInfoToggle').style.display = 'flex';
        document.getElementById('activeOrderBanner').style.display   = 'flex';
        document.getElementById('activeOrderText').textContent = 'Adding to Table ' + currentTable.table_number;

        if (currentOrder) {
            document.getElementById('customerName').value  = currentOrder.customer_name  || '';
            document.getElementById('customerPhone').value = currentOrder.customer_phone || '';
        }
    }

    function renderBill() {
        const billEl = document.getElementById('billItems');
        if (!currentOrder || !currentOrder.items) {
            billEl.style.display = 'block';
            billEl.innerHTML = '<div style="text-align:center; padding:48px 0; color:#cbd5e1;"><i class="fas fa-utensils" style="font-size:36px; margin-bottom:12px; display:block;"></i><p style="font-size:13px; margin:0;">Select a table or create takeaway order</p></div>';
            setBottomControls(false);
            updateCloseButtonVisibility(false);
            return;
        }

        const hasItems = currentOrder.items && currentOrder.items.length > 0;

        if (!hasItems) {
            billEl.style.display = 'block';
            billEl.innerHTML =
                '<p style="text-align:center; color:#94a3b8; font-size:13px; padding:32px 0;"><i class="fas fa-plus-circle" style="display:block; font-size:24px; margin-bottom:8px;"></i>No items yet — tap a product</p>';
        } else {
            billEl.style.display = 'grid';
            billEl.style.gridTemplateColumns = 'repeat(2, 1fr)';
            billEl.style.gap = '8px';
            billEl.style.alignItems = 'start';
            billEl.innerHTML = currentOrder.items.map(function(item) {
                const discPercent   = item.discount_percent || 0;
                const discRowOpen   = item.id && openDiscountRows.has(item.id);
                const atStockLimit  = stockCache.hasOwnProperty(item.product_id) && stockCache[item.product_id] <= 0;

                // Discount badge next to product name
                const discBadge = discPercent > 0
                    ? '<span style="font-size:9px; background:#fef3c7; color:#92400e; border-radius:4px; padding:1px 5px; font-weight:700; white-space:nowrap; flex-shrink:0;">-' + discPercent + '%</span>'
                    : '';

                // Editable quantity input (replaces static span)
                const qtyControl = item.id
                    ? '<input type="number" min="1" value="' + item.quantity + '" '
                      + 'style="width:38px; text-align:center; border:1.5px solid #e2e8f0; border-radius:6px; font-size:13px; font-weight:800; color:#0f172a; padding:2px 0; outline:none; background:#fff;" '
                      + 'onblur="setQty(' + item.id + ', this.value)" '
                      + 'onkeydown="if(event.key===\'Enter\'){this.blur();event.preventDefault();}" '
                      + 'onclick="this.select();event.stopPropagation();" />'
                    : '<span style="min-width:22px; text-align:center; font-size:13px; font-weight:800; color:#0f172a;">' + item.quantity + '</span>';

                const decBtn = item.id
                    ? '<button type="button" class="qty-btn" onclick="decreaseQty(' + item.id + ')">−</button>'
                    : '<button type="button" class="qty-btn" style="opacity:0.4;" disabled>−</button>';
                const incBtn = item.id
                    ? '<button type="button" class="qty-btn" onclick="increaseQty(' + item.id + ')"' + (atStockLimit ? ' disabled title="No more stock" style="opacity:0.4; cursor:not-allowed;"' : '') + '>+</button>'
                    : '<button type="button" class="qty-btn" style="opacity:0.4;" disabled>+</button>';

                const stockLeft = stockCache.hasOwnProperty(item.product_id)
                    ? '<div style="font-size:9px; color:#94a3b8; text-align:center; margin-top:2px;">' + stockCache[item.product_id] + ' left</div>'
                    : '';

                const noteHtml = item.kitchen_notes
                    ? '<p style="font-size:10px; color:#f59e0b; margin:2px 0 0;"><i class="fas fa-note-sticky" style="margin-right:3px;"></i>' + escapeHtml(item.kitchen_notes) + '</p>'
                    : '';

                const removeBtn = item.id
                    ? '<button type="button" onclick="removeItem(' + item.id + ')" title="Remove" style="font-size:11px; color:#ef4444; background:none; border:none; cursor:pointer; padding:2px 3px; line-height:1;"><i class="fas fa-trash"></i></button>'
                    : '';

                // Discount toggle button (amber when active, grey when not)
                const discBtnStyle = discPercent > 0
                    ? 'background:#fef3c7; color:#92400e; border:1px solid #fde68a;'
                    : 'background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;';
                const discBtn = item.id
                    ? '<button type="button" onclick="toggleDiscountRow(' + item.id + ')" title="Discount" '
                      + 'style="font-size:9px; ' + discBtnStyle + ' border-radius:5px; padding:2px 6px; cursor:pointer; font-weight:700; line-height:1.3;">% off</button>'
                    : '';

                // Inline discount input row (only when open)
                const discRowHtml = (discRowOpen && item.id)
                    ? '<div style="display:flex; align-items:center; gap:6px; margin-top:6px; padding:7px 10px; background:#fffbeb; border-radius:8px; border:1px solid #fde68a; flex-wrap:wrap;">'
                      + '<span style="font-size:11px; font-weight:600; color:#92400e;">Discount:</span>'
                      + '<input type="number" id="disc-' + item.id + '" value="' + discPercent + '" min="0" max="100" step="1" placeholder="0" '
                      + 'style="width:52px; font-size:12px; font-weight:700; border:1.5px solid #fde68a; border-radius:6px; padding:3px 6px; outline:none; text-align:center; background:#fff;" '
                      + 'onkeydown="if(event.key===\'Enter\'){applyItemDiscount(' + item.id + ');event.preventDefault();}" />'
                      + '<span style="font-size:12px; color:#92400e; font-weight:700;">%</span>'
                      + '<button type="button" onclick="applyItemDiscount(' + item.id + ')" '
                      + 'style="font-size:11px; background:#16a34a; color:#fff; border:none; border-radius:6px; padding:4px 10px; cursor:pointer; font-weight:700;">✓ Apply</button>'
                      + (discPercent > 0
                          ? '<button type="button" onclick="clearItemDiscount(' + item.id + ')" style="font-size:11px; background:#e2e8f0; color:#374151; border:none; border-radius:6px; padding:4px 8px; cursor:pointer; font-weight:600;">Clear</button>'
                          : '')
                      + '</div>'
                    : '';

                let thumbHtml = '';
                if (item.image) {
                    thumbHtml = '<div style="width:30px; height:30px; border-radius:7px; overflow:hidden; flex-shrink:0; background:#f1f5f9;">'
                        + '<img src="/storage/' + item.image + '" alt="' + escapeHtml(item.product_name) + '" style="width:100%; height:100%; object-fit:cover;">'
                        + '</div>';
                }

                return '<div style="background:#fff; border:1px solid #eef2f7; border-radius:10px; padding:8px;">'
                    // Header: thumb + name
                    + '<div style="display:flex; align-items:center; gap:6px;">'
                    + thumbHtml
                    + '<div style="flex:1; min-width:0;">'
                    + '<div style="display:flex; align-items:center; gap:4px; overflow:hidden;">'
                    + '<p style="font-size:13px; font-weight:700; color:#0f172a; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + escapeHtml(item.product_name) + '</p>'
                    + discBadge
                    + '</div>'
                    + noteHtml
                    + '</div>'
                    + '</div>'
                    // Bottom: qty controls + price/actions
                    + '<div style="display:flex; align-items:flex-end; justify-content:space-between; gap:6px; margin-top:7px;">'
                    + '<div style="display:flex; flex-direction:column; align-items:flex-start; flex-shrink:0;">'
                    + '<div style="display:flex; align-items:center; gap:4px;">'
                    + decBtn + qtyControl + incBtn
                    + '</div>'
                    + stockLeft
                    + '</div>'
                    + '<div style="display:flex; flex-direction:column; align-items:flex-end; gap:2px; min-width:0;">'
                    + '<p style="font-size:13px; font-weight:800; color:#0f172a; margin:0; white-space:nowrap;">Rs. ' + item.subtotal.toFixed(2) + '</p>'
                    + '<div style="display:flex; align-items:center; gap:6px;">' + discBtn + removeBtn + '</div>'
                    + '</div>'
                    + '</div>'
                    // Discount input row (toggleable)
                    + discRowHtml
                    + '</div>';
            }).join('');
        }

        // Totals
        const subtotal = currentOrder.subtotal || 0;
        const discount = calcDiscount(subtotal);
        const total    = Math.max(0, subtotal - discount);

        document.getElementById('subtotalDisplay').textContent = 'Rs. ' + subtotal.toFixed(2);
        document.getElementById('totalDisplay').textContent    = 'Rs. ' + total.toFixed(2);

        setBottomControls(hasItems);
        updateCloseButtonVisibility(hasItems);
        updateChange();
        scrollBillToBottom();
    }

    function updateCloseButtonVisibility(hasItems) {
        const closeBtn = document.getElementById('closeOrderBtn');
        if (closeBtn) {
            closeBtn.style.display = hasItems ? 'none' : 'flex';
        }
    }

    function setBottomControls(hasItems) {
        document.getElementById('paymentToggle').style.display      = hasItems ? 'flex' : 'none';
        document.getElementById('paymentSection').style.display     = hasItems ? 'block' : 'none';
        document.getElementById('waiterPayRow').style.display       = hasItems ? 'flex' : 'none';
        document.getElementById('holdBtn').style.display            = hasItems ? 'block' : 'none';
        if (!hasItems) setPaymentExpanded(false);
    }

    function setPaymentExpanded(expanded) {
        const body    = document.getElementById('paymentBody');
        const chevron = document.getElementById('paymentChevron');
        if (!body) return;
        body.style.display = expanded ? 'block' : 'none';
        if (chevron) chevron.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
    }

    function togglePaymentSection() {
        const body = document.getElementById('paymentBody');
        setPaymentExpanded(body.style.display === 'none');
    }

    function toggleCustomerInfo() {
        const section = document.getElementById('customerInfoSection');
        const toggle = document.getElementById('customerInfoToggle');
        const chevron = document.getElementById('customerInfoChevron');
        const isOpen = section.style.display !== 'none';

        section.style.display = isOpen ? 'none' : 'block';
        toggle.style.background = isOpen ? 'none' : '#f0fdf4';
        chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    }


    function scrollBillToBottom() {
        const wrapper = document.getElementById('billItemsWrapper');
        wrapper.scrollTop = wrapper.scrollHeight;
    }

    // ═══════════════════════════════════════════
    // CUSTOMER INFO
    // ═══════════════════════════════════════════

    async function saveCustomerInfo() {
        if (!currentOrder || !currentOrder.id) return;
        const name  = document.getElementById('customerName').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();
        if (currentOrder.customer_name === name && currentOrder.customer_phone === phone) return;
        await fetch('{{ route("pos.order.customer", ":id") }}'.replace(':id', currentOrder.id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_name: name, customer_phone: phone })
        });
        currentOrder.customer_name  = name;
        currentOrder.customer_phone = phone;
    }

    // ═══════════════════════════════════════════
    // PAYMENT
    // ═══════════════════════════════════════════

    function selectPaymentMethod(method) {
        selectedPaymentMethod = method;
        document.querySelectorAll('.pay-method-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.method === method);
        });
        document.getElementById('cashSection').style.display = method === 'cash' ? 'flex' : 'none';
        document.getElementById('splitSection').style.display = method === 'split' ? 'block' : 'none';
        if (method !== 'cash') document.getElementById('changeDisplay').textContent = 'Rs. 0.00';
        if (method === 'split') updateSplitTotal();
        const _payLabels  = { cash: 'Cash', card: 'Card', bank_transfer: 'Bank', split: 'Split' };
        const _paySummary = document.getElementById('paymentMethodSummary');
        if (_paySummary) _paySummary.textContent = (_payLabels[method] || method);
    }

    function updateSplitTotal() {
        const amount1 = parseFloat(document.getElementById('splitAmount1').value) || 0;
        const amount2 = parseFloat(document.getElementById('splitAmount2').value) || 0;
        const total = amount1 + amount2;
        document.getElementById('splitTotalDisplay').textContent = 'Rs. ' + total.toFixed(2);
    }

    function calcDiscount(subtotal) {
        const type  = document.getElementById('discountType').value;
        const value = parseFloat(document.getElementById('discountValue').value) || 0;
        if (type === 'percentage') return (subtotal * value) / 100;
        if (type === 'fixed')      return value;
        return 0;
    }

    function recalcTotal() {
        if (!currentOrder) return;
        const subtotal = currentOrder.subtotal || 0;
        const discount = calcDiscount(subtotal);
        document.getElementById('totalDisplay').textContent = 'Rs. ' + Math.max(0, subtotal - discount).toFixed(2);
        updateChange();
    }

    function updateChange() {
        if (selectedPaymentMethod !== 'cash') return;
        const subtotal = currentOrder ? (currentOrder.subtotal || 0) : 0;
        const discount = calcDiscount(subtotal);
        const total    = Math.max(0, subtotal - discount);
        const paid     = parseFloat(document.getElementById('amountPaid').value) || 0;
        const change   = Math.max(0, paid - total);
        const el       = document.getElementById('changeDisplay');
        el.textContent = 'Rs. ' + change.toFixed(2);
        el.style.color = change > 0 ? '#16a34a' : '#94a3b8';
    }

    async function initiatePayment() {
        if (!currentOrder || !currentOrder.id || !currentOrder.items || !currentOrder.items.length) {
            toast('No items in order', 'error'); return;
        }

        // Reveal payment details so the cashier can confirm method / cash amount
        setPaymentExpanded(true);

        // Cash requires an explicit amount entered
        if (selectedPaymentMethod === 'cash') {
            const cashEntered = parseFloat(document.getElementById('amountPaid').value);
            if (!cashEntered || cashEntered <= 0) {
                toast('Please enter the cash amount received', 'error');
                document.getElementById('amountPaid').focus();
                return;
            }
        }

        // Split payment validation
        if (selectedPaymentMethod === 'split') {
            const amount1 = parseFloat(document.getElementById('splitAmount1').value) || 0;
            const amount2 = parseFloat(document.getElementById('splitAmount2').value) || 0;
            const subtotal = currentOrder.subtotal || 0;
            const discountVal = calcDiscount(subtotal);
            const total = Math.max(0, subtotal - discountVal);
            const splitTotal = amount1 + amount2;

            if (amount1 <= 0 || splitTotal === 0) {
                toast('Please enter amounts for split payment', 'error');
                return;
            }
            if (Math.abs(splitTotal - total) > 0.01) {
                toast(`Split total (Rs. ${splitTotal.toFixed(2)}) must equal bill (Rs. ${total.toFixed(2)})`, 'error');
                return;
            }
        }

        await saveCustomerInfo();

        const subtotal    = currentOrder.subtotal || 0;
        const discountVal = calcDiscount(subtotal);
        const total       = Math.max(0, subtotal - discountVal);
        let amountPaid    = total;
        let paymentData   = {
            payment_method: selectedPaymentMethod,
            amount_paid:    amountPaid,
            discount_type:  document.getElementById('discountType').value || null,
            discount_value: parseFloat(document.getElementById('discountValue').value) || 0,
        };

        if (selectedPaymentMethod === 'cash') {
            amountPaid = parseFloat(document.getElementById('amountPaid').value);
            paymentData.amount_paid = amountPaid;
        } else if (selectedPaymentMethod === 'split') {
            paymentData.split_method1 = document.getElementById('splitMethod1').value;
            paymentData.split_amount1 = parseFloat(document.getElementById('splitAmount1').value);
            paymentData.split_method2 = document.getElementById('splitMethod2').value;
            paymentData.split_amount2 = parseFloat(document.getElementById('splitAmount2').value);
        }

        const res = await fetch('{{ route("pos.order.pay", ":id") }}'.replace(':id', currentOrder.id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify(paymentData)
        });
        if (!res.ok) {
            toast('Payment failed — server error', 'error');
            return;
        }
        const data = await res.json();
        if (data.success) {
            showPaidBill(data);
            await loadTables();          // refreshes tables panel (table now shows as Available)
            toast('Payment received — table closed!', 'success');
        } else {
            toast(data.error || 'Payment failed', 'error');
        }
    }

    function showPaidBill(d) {
        const methodLabel = { cash:'Cash', card:'Card', bank_transfer:'Bank Transfer', mixed:'Mixed' };
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-GB') + ', ' + now.toLocaleTimeString('en-GB');

        // ── Update these values to match your restaurant ──
        const CO_NAME    = 'Suasa Family Restaurant';
        const CO_CONTACT = '071 979 9799';
        const CO_ADDRESS = '583 Avissawella Road, mulleriyawa';
        const CO_EMAIL   = 'info@suasafamily.com';

        const itemRows = d.items.map(function(i) {
            const discLabel = i.discount_percent > 0 ? ' (-' + i.discount_percent + '%)' : '';
            return '<tr>'
                + '<td style="padding:3px 0; vertical-align:top; width:62%;">' + escapeHtml(i.product_name)
                + '<br><span style="font-size:10px;">1 x Rs.' + i.unit_price.toFixed(2) + discLabel + '</span></td>'
                + '<td style="text-align:center; padding:3px 0; vertical-align:top; width:10%;">' + i.quantity + '</td>'
                + '<td style="text-align:right; padding:3px 0; vertical-align:top; width:28%;">Rs.' + i.subtotal.toFixed(2) + '</td>'
                + '</tr>';
        }).join('');

        // ── HEADER: Logo + Company Details ──
        const html =
            '<div style="text-align:center; padding-bottom:8px;">'
            + '<img src="/images/logo.jpeg" style="max-width:150px; max-height:150px; margin-bottom:6px; display:block; margin-left:auto; margin-right:auto;" />'
            + '<div style="font-size:14px; letter-spacing:1px; color:#000; font-weight:bold;">' + CO_NAME + '</div>'
            + '<div style="font-size:11px; color:#000;">' + CO_CONTACT + '</div>'
            + '<div style="font-size:11px; color:#000;">' + CO_EMAIL + '</div>'
            + '<div style="font-size:11px; color:#000;">' + CO_ADDRESS + '</div>'
            + '</div>'

            // ── RECEIPT METADATA ──
            + '<div style="border-top:2px solid #000; border-bottom:2px solid #000; padding:6px 0; margin-bottom:8px;">'
            + '<div style="text-align:center; font-size:13px; letter-spacing:3px; color:#000; margin-bottom:5px;">RECEIPT</div>'
            + '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:11px; color:#000; width:100%; table-layout:fixed;">'
            + '<tr><td style="width:35%;">Order</td><td style="text-align:right; width:65%; word-break:break-all;">' + d.order_number + '</td></tr>'
            + '<tr><td>Type</td><td style="text-align:right;">T-' + d.table_number + (d.table_name ? ' ' + escapeHtml(d.table_name) : '') + '</td></tr>'
            + (d.customer_name  ? '<tr><td>Customer</td><td style="text-align:right;">' + escapeHtml(d.customer_name) + '</td></tr>' : '')
            + (d.customer_phone ? '<tr><td>Phone</td><td style="text-align:right;">' + d.customer_phone + '</td></tr>' : '')
            + '<tr><td>Date</td><td style="text-align:right;">' + dateStr + '</td></tr>'
            + '</table>'
            + '</div>'

            // ── ITEM TABLE ──
            + '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:12px; color:#000; width:100%; table-layout:fixed;">'
            + '<thead><tr style="border-bottom:1px dashed #000;">'
            + '<th style="text-align:left; padding-bottom:4px; font-size:11px; width:62%;">ITEM</th>'
            + '<th style="text-align:center; padding-bottom:4px; font-size:11px; width:10%;">QTY</th>'
            + '<th style="text-align:right; padding-bottom:4px; font-size:11px; width:28%;">AMOUNT</th>'
            + '</tr></thead>'
            + '<tbody>' + itemRows + '</tbody>'
            + '</table>'

            // ── SUMMARY ──
            + '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:12px; color:#000; border-top:1px dashed #000; margin-top:4px; width:100%; table-layout:fixed;">'
            + '<tr><td style="width:65%;">Subtotal</td><td style="text-align:right; width:35%;">Rs.' + d.subtotal.toFixed(2) + '</td></tr>'
            + (d.discount_amount > 0 ? '<tr><td>Discount</td><td style="text-align:right;">-Rs.' + d.discount_amount.toFixed(2) + '</td></tr>' : '')
            + '<tr style="border-top:1px solid #000; font-size:14px;"><td style="padding-top:4px;">TOTAL</td><td style="text-align:right; padding-top:4px;">Rs.' + d.total.toFixed(2) + '</td></tr>'
            + '</table>'

            // ── PAYMENT DETAILS ──
            + '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:12px; color:#000; border-top:1px dashed #000; margin-top:6px; width:100%; table-layout:fixed;">'
            + '<tr><td style="width:65%;">Paid (' + (methodLabel[d.payment_method] || d.payment_method) + ')</td><td style="text-align:right; width:35%;">Rs.' + d.amount_paid.toFixed(2) + '</td></tr>'
            + (d.change_amount > 0 ? '<tr><td>Change</td><td style="text-align:right;">Rs.' + d.change_amount.toFixed(2) + '</td></tr>' : '')
            + '</table>'

            // ── FOOTER ──
            + '<div style="text-align:center; font-size:11px; margin-top:8px; color:#000; border-top:1px dashed #000; padding-top:6px;">Thank you for dining with us!<br>We look forward to seeing you again.<br>Powered By JAAN Network (PVT) Ltd</div>';

        currentBillContent = html;
        printReceipt(html);
        resetOrder();
    }

    // ═══════════════════════════════════════════
    // BILL
    // ═══════════════════════════════════════════

    async function printBill() {
        if (!currentOrder || !currentOrder.id) return;
        await saveCustomerInfo();
        const res  = await fetch('{{ route("pos.order.waiter_bill", ":id") }}'.replace(':id', currentOrder.id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();
        if (!data.success) { toast('Could not generate bill', 'error'); return; }

        const now = new Date();
        const dateStr = now.toLocaleDateString('en-GB') + ', ' + now.toLocaleTimeString('en-GB');

        // ── Update these values to match your restaurant ──
        const CO_NAME    = 'Suasa Family Restaurant';
        const CO_CONTACT = '071 979 9799';
        const CO_ADDRESS = '583 Avissawella Road, mulleriyawa';
        const CO_EMAIL   = 'info@suasafamily.com';

        const itemRows = data.items.map(function(i) {
            return '<tr>'
                + '<td style="padding:3px 0; vertical-align:top; width:62%;">' + escapeHtml(i.product_name)
                + '<br><span style="font-size:10px;">1 x Rs.' + i.unit_price.toFixed(2) + '</span>'
                + (i.kitchen_notes ? '<br><span style="font-size:10px; font-style:italic;">Note: ' + escapeHtml(i.kitchen_notes) + '</span>' : '')
                + '</td>'
                + '<td style="text-align:center; padding:3px 0; vertical-align:top; width:10%;">' + i.quantity + '</td>'
                + '<td style="text-align:right; padding:3px 0; vertical-align:top; width:28%;">Rs.' + i.subtotal.toFixed(2) + '</td>'
                + '</tr>';
        }).join('');

        // ── HEADER: Logo + Company Details ──
        const html =
            '<div style="text-align:center; padding-bottom:8px;">'
            + '<img src="/images/logo.jpeg" style="max-width:80px; max-height:80px; margin-bottom:6px; display:block; margin-left:auto; margin-right:auto;" />'
            + '<div style="font-size:14px; letter-spacing:1px; color:#000; font-weight:bold;">' + CO_NAME + '</div>'
            + '<div style="font-size:11px; color:#000;">' + CO_CONTACT + '</div>'
            + '<div style="font-size:11px; color:#000;">' + CO_EMAIL + '</div>'
            + '<div style="font-size:11px; color:#000;">' + CO_ADDRESS + '</div>'
            + '</div>'

            // ── RECEIPT METADATA ──
            + '<div style="border-top:2px solid #000; border-bottom:2px solid #000; padding:6px 0; margin-bottom:8px;">'
            + '<div style="text-align:center; font-size:13px; letter-spacing:3px; color:#000; margin-bottom:5px;">WAITER BILL</div>'
            + '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:11px; color:#000; width:100%; table-layout:fixed;">'
            + '<tr><td style="width:35%;">Order</td><td style="text-align:right; width:65%; word-break:break-all;">' + data.order_number + '</td></tr>'
            + '<tr><td>Type</td><td style="text-align:right;">T-' + data.table_number + '</td></tr>'
            + (data.customer_name  ? '<tr><td>Customer</td><td style="text-align:right;">' + escapeHtml(data.customer_name) + '</td></tr>' : '')
            + (data.customer_phone ? '<tr><td>Phone</td><td style="text-align:right;">' + data.customer_phone + '</td></tr>' : '')
            + '<tr><td>Date</td><td style="text-align:right;">' + dateStr + '</td></tr>'
            + '</table>'
            + '</div>'

            // ── ITEM TABLE ──
            + '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:12px; color:#000; width:100%; table-layout:fixed;">'
            + '<thead><tr style="border-bottom:1px dashed #000;">'
            + '<th style="text-align:left; padding-bottom:4px; font-size:11px; width:62%;">ITEM</th>'
            + '<th style="text-align:center; padding-bottom:4px; font-size:11px; width:10%;">QTY</th>'
            + '<th style="text-align:right; padding-bottom:4px; font-size:11px; width:28%;">AMOUNT</th>'
            + '</tr></thead>'
            + '<tbody>' + itemRows + '</tbody>'
            + '</table>'

            // ── SUMMARY ──
            + '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:12px; color:#000; border-top:1px dashed #000; margin-top:4px; width:100%; table-layout:fixed;">'
            + '<tr><td style="width:65%;">Subtotal</td><td style="text-align:right; width:35%;">Rs.' + data.subtotal.toFixed(2) + '</td></tr>'
            + '<tr style="border-top:1px solid #000; font-size:14px;"><td style="padding-top:4px;">TOTAL</td><td style="text-align:right; padding-top:4px;">Rs.' + data.total.toFixed(2) + '</td></tr>'
            + '</table>'

            // ── FOOTER ──
            + '<div style="text-align:center; font-size:11px; margin-top:8px; color:#000; border-top:1px dashed #000; padding-top:6px;">This is not a payment receipt</div>';

        printReceipt(html);
        toast('Waiter bill printed', 'success');
    }


    // ═══════════════════════════════════════════
    // KOT
    // ═══════════════════════════════════════════

    async function printKot() {
        if (!currentOrder || !currentOrder.id) { toast('No active order', 'error'); return; }
        const res  = await fetch('{{ route("pos.order.kot", ":id") }}'.replace(':id', currentOrder.id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();

        if (!data.success) {
            toast(data.message || 'KOT already printed', 'warning');
            return;
        }

        if (!data.items || !Array.isArray(data.items)) {
            toast('No items to print', 'warning');
            return;
        }

        // Update local stock cache for finished goods (so POS reflects new quantities)
        if (Array.isArray(data.product_changes) && data.product_changes.length > 0) {
            data.product_changes.forEach(function(ch) {
                if (ch.product_id && stockCache.hasOwnProperty(ch.product_id)) {
                    stockCache[ch.product_id] = Math.max(0, (stockCache[ch.product_id] || 0) - ch.quantity);
                }
            });
            renderProducts();
        }

        const tableNum = currentTable ? currentTable.table_number : '—';
        printReceipt(buildKotHtml(data, tableNum));
    }

    async function printKotForTable(orderId) {
        const res  = await fetch('{{ route("pos.order.kot", ":id") }}'.replace(':id', orderId), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();

        if (!data.success) {
            toast(data.message || 'KOT already printed', 'warning');
            return;
        }

        if (!data.items || !Array.isArray(data.items)) {
            toast('No new items to print', 'warning');
            return;
        }

        // Update local stock cache for finished goods when printing for a table
        if (Array.isArray(data.product_changes) && data.product_changes.length > 0) {
            data.product_changes.forEach(function(ch) {
                if (ch.product_id && stockCache.hasOwnProperty(ch.product_id)) {
                    stockCache[ch.product_id] = Math.max(0, (stockCache[ch.product_id] || 0) - ch.quantity);
                }
            });
            renderProducts();
        }

        document.getElementById('kotOrderNumber').textContent = 'Order #' + data.order_number;
        document.getElementById('kotTableNumber').textContent = 'Table ' + (data.table_number || '—');
        renderKotItems(data.items);
        currentKotContent = buildKotHtml(data, data.table_number || '—');
        openModal('kotModal');
    }

    function renderKotItems(items) {
        document.getElementById('kotItems').innerHTML = items.map(function(item) {
            return '<div style="display:flex; justify-content:space-between; align-items:flex-start; padding:8px 0; border-bottom:1px dashed #e2e8f0;">'
                + '<div>'
                + '<p style="font-size:14px; font-weight:800; margin:0; color:#0f172a;">' + escapeHtml(item.product_name) + '</p>'
                + (item.kitchen_notes ? '<p style="font-size:11px; color:#f59e0b; margin:3px 0 0;"><i class="fas fa-note-sticky" style="margin-right:3px;"></i>' + escapeHtml(item.kitchen_notes) + '</p>' : '')
                + '</div>'
                + '<span style="font-size:18px; font-weight:900; color:#dc2626; margin-left:12px;">×' + item.quantity + '</span>'
                + '</div>';
        }).join('');
    }

    function buildKotHtml(data, tableNum) {
        return '<div style="text-align:center; font-weight:900; font-size:16px; border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:10px; color:#000;">KITCHEN ORDER</div>'
            + '<div style="font-size:13px; font-weight:800; color:#000;">Order: ' + data.order_number + '</div>'
            + '<div style="font-size:14px; font-weight:900; margin:4px 0; color:#000;">Table ' + tableNum + '</div>'
            + '<div style="font-size:10px; color:#000; margin-bottom:10px;">' + new Date().toLocaleString() + '</div>'
            + '<div style="border-top:1px solid #000; padding-top:10px;">'
            + data.items.map(function(i) {
                return '<div style="display:flex; justify-content:space-between; font-size:13px; font-weight:700; margin:8px 0; border-bottom:1px dashed #000; padding-bottom:6px; color:#000;">'
                    + '<span>' + escapeHtml(i.product_name) + '</span>'
                    + '<span style="font-size:16px; font-weight:900;">×' + i.quantity + '</span>'
                    + '</div>'
                    + (i.kitchen_notes ? '<div style="font-size:11px; color:#000; margin-top:-4px; margin-bottom:6px;">Note: ' + escapeHtml(i.kitchen_notes) + '</div>' : '');
            }).join('')
            + '</div>';
    }

    function printKotContent() {
        printReceipt(currentKotContent);
        closeModal('kotModal');
    }

    function printBillContent() {
        printReceipt(currentBillContent);
    }

    // ═══════════════════════════════════════════
    // HELD ORDERS
    // ═══════════════════════════════════════════

    async function holdCurrentOrder() {
        if (!currentOrder || !currentOrder.id) return;
        await fetch('{{ route("pos.order.hold", ":id") }}'.replace(':id', currentOrder.id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        toast('Order held');
        resetOrder();
        await loadTables();
        loadHeldOrders(true);
    }

    async function loadHeldOrders(showModal = false) {
        try {
            const res    = await fetch('{{ route("pos.held") }}');
            if (!res.ok) { toast('Failed to load held orders', 'error'); return; }
            const orders = await res.json();
            const badge  = document.getElementById('heldCount');
            badge.textContent = orders.length;
            badge.style.background = orders.length > 0 ? '#f59e0b' : '#94a3b8';

            const list = document.getElementById('heldOrdersList');
            if (orders.length === 0) {
                list.innerHTML = '<p style="text-align:center; color:#94a3b8; padding:32px 0; font-size:13px;">No held orders</p>';
            } else {
                list.innerHTML = orders.map(function(o) {
                    return '<div onclick="resumeOrder(' + o.id + ')" '
                        + 'style="padding:14px; border:1.5px solid #e2e8f0; border-radius:12px; cursor:pointer; transition:all 0.15s; background:#fff;" '
                        + 'onmouseover="this.style.borderColor=\'#dc2626\'; this.style.background=\'#fef2f2\';" '
                        + 'onmouseout="this.style.borderColor=\'#e2e8f0\'; this.style.background=\'#fff\';">'
                        + '<div style="display:flex; justify-content:space-between; align-items:flex-start;">'
                        + '<div>'
                        + '<p style="font-size:13px; font-weight:800; color:#0f172a; margin:0;">' + o.order_number + '</p>'
                        + '<p style="font-size:12px; color:#64748b; margin:3px 0 0;">Table ' + (o.table_number || '—') + ' &nbsp;&middot;&nbsp; ' + o.items_count + ' item' + (o.items_count !== 1 ? 's' : '') + '</p>'
                        + '</div>'
                        + '<span style="font-size:14px; font-weight:900; color:#dc2626;">Rs. ' + o.total.toFixed(2) + '</span>'
                        + '</div></div>';
                }).join('');
            }
            if (showModal) {
                openModal('heldOrdersModal');
            }
        } catch (e) {
            console.error('Load held orders error:', e);
            toast('Error loading held orders', 'error');
        }
    }

    async function resumeOrder(orderId) {
        const res    = await fetch('{{ route("pos.order.show", ":id") }}'.replace(':id', orderId));
        currentOrder = await res.json();
        currentTable = allTables.find(function(t) { return t.id === currentOrder.table_id; }) || null;
        renderTableView();
        renderBill();
        closeModal('heldOrdersModal');
        await loadTables();
        toast('Order resumed');
    }

    async function closeCurrentOrder() {
        if (!currentOrder || !currentOrder.id) return;
        if (currentOrder.items && currentOrder.items.length > 0) {
            if (!confirm('This order has items. Close anyway and discard all items?')) return;
        }

        // Call backend to cancel the order and free the table
        try {
            const res = await fetch('{{ route("pos.order.close_table", ":id") }}'.replace(':id', currentOrder.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if (!res.ok) {
                toast('Failed to close table', 'error');
                return;
            }
        } catch (e) {
            console.error('Close order error:', e);
            toast('Error closing table', 'error');
            return;
        }

        // Restore stock cache for items being discarded
        if (currentOrder && currentOrder.items) {
            currentOrder.items.forEach(function(item) {
                if (item.product_id && stockCache.hasOwnProperty(item.product_id)) {
                    stockCache[item.product_id] += item.quantity;
                }
            });
        }
        resetOrder();
        renderProducts();
        await loadTables();
        toast('Table deselected', 'success');
    }

    // ═══════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════

    function resetOrder() {
        currentOrder = null;
        currentTable = null;
        selectedPaymentMethod = 'cash';

        const billEl = document.getElementById('billItems');
        billEl.style.display = 'block';
        billEl.innerHTML = '<div style="text-align:center; padding:48px 0; color:#cbd5e1;"><i class="fas fa-utensils" style="font-size:36px; margin-bottom:12px; display:block;"></i><p style="font-size:13px; margin:0;">Select a table or create takeaway order</p></div>';
        document.getElementById('selectedTableLabel').innerHTML = '<i class="fas fa-arrow-left" style="font-size:11px; margin-right:4px;"></i>Select a table or create takeaway order';
        document.getElementById('customerInfoToggle').style.display     = 'none';
        document.getElementById('customerInfoSection').style.display    = 'none';
        document.getElementById('activeOrderBanner').style.display      = 'none';
        document.getElementById('paymentToggle').style.display          = 'none';
        document.getElementById('paymentSection').style.display         = 'none';
        document.getElementById('waiterPayRow').style.display           = 'none';
        document.getElementById('holdBtn').style.display                = 'none';
        setPaymentExpanded(false);
        const _paySummary = document.getElementById('paymentMethodSummary');
        if (_paySummary) _paySummary.textContent = 'Cash';
        const _confirmLiveBtn = document.getElementById('confirmLiveBillBtn');
        if (_confirmLiveBtn) _confirmLiveBtn.style.display = 'none';
        document.getElementById('customerName').value   = '';
        document.getElementById('customerPhone').value  = '';
        document.getElementById('discountType').value   = '';
        document.getElementById('discountValue').value  = '';
        document.getElementById('amountPaid').value     = '';
        document.getElementById('changeDisplay').textContent  = 'Rs. 0.00';
        document.getElementById('subtotalDisplay').textContent = 'Rs. 0.00';
        document.getElementById('totalDisplay').textContent    = 'Rs. 0.00';
        document.querySelectorAll('.pay-method-btn').forEach(function(b) {
            b.classList.toggle('active', b.dataset.method === 'cash');
        });
        document.getElementById('cashSection').style.display = 'flex';
        document.querySelectorAll('.table-card.expanded').forEach(function(c) { c.classList.remove('expanded'); });
        document.querySelectorAll('.table-card.selected').forEach(function(c) {
            c.classList.remove('selected', 'occupied');
            c.classList.add('available');
        });
    }

    function printReceipt(html) {
        const w = window.open('', '', 'width=400,height=700,toolbar=0,menubar=0,scrollbars=1');
        w.document.write(
            '<!DOCTYPE html><html><head><style>'
            + '@page { size: 80mm auto; margin: 2mm 6mm; }'
            + '* { box-sizing: border-box; font-weight: bold !important; }'
            + 'body { font-family: \'Courier New\', monospace; width: 100%; margin: 0; padding: 0; font-size: 12px; }'
            + 'table { width: 100%; border-collapse: collapse; table-layout: fixed; }'
            + 'td, th { word-break: break-word; overflow-wrap: break-word; }'
            + '</style></head><body>' + html + '</body></html>'
        );
        w.document.close();
        w.focus();
        w.print();
        setTimeout(function() { w.close(); }, 1200);
    }

    function openModal(id)  { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    function showShiftModal() { openModal('shiftModal'); }

    function showLoading() { document.body.style.cursor = 'wait'; }
    function hideLoading() { document.body.style.cursor = 'default'; }

    function toast(message, type) {
        type = type || '';
        const el = document.getElementById('toast');
        el.textContent = message;
        el.className   = 'show' + (type ? ' ' + type : '');
        clearTimeout(el._t);
        el._t = setTimeout(function() { el.className = ''; }, 2800);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function escapeJs(str) {
        if (!str) return '';
        return String(str).replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'\\"');
    }

    function setupEventListeners() {
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const cat = document.querySelector('#categoriesContainer .cat-pill.active');
            loadProducts(e.target.value, cat ? parseInt(cat.getAttribute('data-category')) : 0);
        });
        document.getElementById('discountValue').addEventListener('input', recalcTotal);
        document.getElementById('discountType').addEventListener('change', recalcTotal);

        // Block letters/alphabets on all number inputs (including dynamically created ones)
        var ALLOWED_KEYS = [8,9,13,27,46,35,36,37,38,39,40]; // backspace,tab,enter,esc,del,home,end,arrows
        document.addEventListener('keydown', function(e) {
            if (e.target.type !== 'number') return;
            if (ALLOWED_KEYS.includes(e.keyCode)) return;
            if ((e.ctrlKey || e.metaKey) && [65,67,86,88,90].includes(e.keyCode)) return;
            if ((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) return;
            if (e.keyCode === 190 || e.keyCode === 110) return; // decimal point
            e.preventDefault();
        });

        // Close modal on backdrop click
        document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) overlay.classList.remove('open');
            });
        });
    }

    window.addEventListener('load', initPos);

    // ═══════════════════════════════════════════
    // QR CODE SCANNER
    // ═══════════════════════════════════════════

    let qrScanner = null;
    let qrStream = null;

    function openQrScanner() {
        openModal('qrScannerModal');
        initQrScanner();
    }

    function closeQrScanner() {
        closeModal('qrScannerModal');
        stopQrScanner();
        resetQrScanner();
    }

    function stopQrScanner() {
        if (qrStream) {
            qrStream.getTracks().forEach(track => track.stop());
            qrStream = null;
        }
    }

    function resetQrScanner() {
        document.getElementById('scannedQrData').value = '';
        document.getElementById('qrScanResult').style.display = 'none';
        document.getElementById('confirmQrBtn').style.display = 'none';
        document.getElementById('qrLoadingState').style.display = 'block';
        document.getElementById('qrVideo').style.display = 'none';
        document.getElementById('qrResultDetails').innerHTML = '';
    }

    async function initQrScanner() {
        resetQrScanner();
        try {
            const video = document.getElementById('qrVideo');
            const canvas = document.getElementById('qrCanvas');
            const ctx = canvas.getContext('2d');

            // Request camera permission
            qrStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });

            video.srcObject = qrStream;
            video.style.display = 'block';
            document.getElementById('qrLoadingState').style.display = 'none';

            // Start scanning
            const scanQrCode = () => {
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    try {
                        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height, {
                            inversionAttempts: 'dontInvert',
                        });

                        if (code) {
                            const qrData = code.data;
                            document.getElementById('scannedQrData').value = qrData;

                            // Try to parse and display
                            try {
                                const data = JSON.parse(qrData);
                                if (data.type === 'table' && data.table_id) {
                                    displayQrResult(data);
                                    stopQrScanner();
                                }
                            } catch (e) {
                                console.warn('QR data is not JSON, treating as raw:', qrData);
                                displayQrResult({ type: 'unknown', raw_data: qrData });
                                stopQrScanner();
                            }
                        }
                    } catch (err) {
                        // jsQR not found yet, continue scanning
                    }
                }
                if (qrStream) {
                    requestAnimationFrame(scanQrCode);
                }
            };

            // Load jsQR library
            if (typeof jsQR === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
                script.onload = scanQrCode;
                document.head.appendChild(script);
            } else {
                scanQrCode();
            }
        } catch (err) {
            toast('Camera access denied or not available: ' + err.message, 'error');
            closeQrScanner();
        }
    }

    function displayQrResult(data) {
        const resultDiv = document.getElementById('qrScanResult');
        const detailsDiv = document.getElementById('qrResultDetails');
        const confirmBtn = document.getElementById('confirmQrBtn');

        if (data.type === 'table') {
            detailsDiv.innerHTML = `
                <strong>Table ${data.table_number}</strong><br>
                ID: ${data.table_id}
            `;
            confirmBtn.style.display = 'block';
        } else {
            detailsDiv.innerHTML = `Raw Data: ${data.raw_data}`;
            confirmBtn.style.display = 'none';
        }

        resultDiv.style.display = 'block';
    }

    async function confirmQrScan() {
        const qrData = document.getElementById('scannedQrData').value;
        if (!qrData) {
            toast('No QR code scanned', 'error');
            return;
        }

        showLoading();
        try {
            const response = await fetch('{{ route("pos.scan.qr") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ qr_data: qrData })
            });

            const result = await response.json();
            hideLoading();

            if (!result.success) {
                toast(result.message || 'Failed to scan QR code', 'error');
                return;
            }

            const table = result.table;
            toast(`Table ${table.table_number} loaded successfully`, 'success');
            closeQrScanner();

            if (result.order && result.order.has_active_order) {
                // Load existing order
                await viewTableOrder(result.order.id);
            } else {
                // Start new order for this table
                await startNewOrder(table.id);
            }
        } catch (error) {
            hideLoading();
            console.error('QR scan error:', error);
            toast('Error processing QR code: ' + error.message, 'error');
        }
    }

    // ── Numeric-only guard for amount / quantity inputs ──
    document.addEventListener('DOMContentLoaded', function () {
        var CTRL_KEYS = [8, 9, 13, 27, 46, 35, 36, 37, 38, 39, 40];
        var DIGITS    = function (k) { return (k >= 48 && k <= 57) || (k >= 96 && k <= 105); };
        var SHORTCUT  = function (e) { return (e.ctrlKey || e.metaKey) && [65, 67, 86, 88, 90].includes(e.keyCode); };
        document.querySelectorAll('input[type="number"]').forEach(function (input) {
            input.addEventListener('keydown', function (e) {
                if (CTRL_KEYS.includes(e.keyCode) || SHORTCUT(e) || DIGITS(e.keyCode)) return;
                if (e.keyCode === 190 || e.keyCode === 110) return; // decimal point
                if (e.keyCode === 189 || e.keyCode === 109) return; // minus
                e.preventDefault();
            });
        });
    });
</script>
</body>
</html>
