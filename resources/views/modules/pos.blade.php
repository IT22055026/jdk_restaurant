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
        .pos-grid { display: grid; grid-template-columns: 1fr 760px; flex: 1; min-height: 0; }

        /* ── Panels ── */
        .menu-panel    { background: #f8fafc; display: flex; flex-direction: column; overflow: hidden; }
        .bill-panel    { background: #fff; border-left: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; }

        /* ── Category pills ── */
        .cat-pill {
            padding: 9px 18px; border-radius: 20px; font-size: 13px; font-weight: 600;
            border: 2px solid #e2e8f0; cursor: pointer; white-space: nowrap; transition: all 0.15s;
            background: #fff; color: #64748b;
        }
        .cat-pill:hover { border-color: #2563eb; color: #2563eb; }
        .cat-pill.active { background: #2563eb; color: #fff; border-color: #2563eb; }

        /* ── Product cards ── */
        .product-card {
            background: #fff; border: 2px solid #e2e8f0; border-radius: 12px;
            padding: 18px 14px; cursor: pointer; transition: all 0.18s; text-align: center;
        }
        .product-card:hover { border-color: #2563eb; box-shadow: 0 4px 16px rgba(37,99,235,0.15); transform: translateY(-2px); }
        .product-card:active { transform: scale(0.97); }

        /* ── Bill items ── */
        .bill-item {
            display: flex; align-items: center; padding: 10px 0;
            border-bottom: 1px solid #f1f5f9; gap: 8px;
        }
        .qty-btn {
            width: 32px; height: 32px; border: 1.5px solid #e2e8f0; border-radius: 8px;
            background: #f8fafc; cursor: pointer; font-size: 13px; font-weight: bold;
            display: flex; align-items: center; justify-content: center; transition: all 0.12s;
            color: #374151;
        }
        .qty-btn:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

        /* ── Payment method buttons ── */
        .pay-method-btn {
            flex: 1; padding: 10px 4px; border: 2px solid #e2e8f0; border-radius: 10px;
            font-size: 12px; font-weight: 700; cursor: pointer; text-align: center;
            background: #fff; transition: all 0.15s; color: #64748b;
        }
        .pay-method-btn:hover { border-color: #3b82f6; color: #3b82f6; }
        .pay-method-btn.active { border-color: #2563eb; background: #eff6ff; color: #2563eb; }

        /* While Split is selected, the split-amount fields need the room —
           shrink the method icons down to a single compact row each instead
           of stacked icon+label, so the bottom Hold/Pay buttons don't get
           pushed out of view. */
        #paymentBody.split-active .pay-method-btn {
            padding: 6px 4px; font-size: 10px;
            display: flex; align-items: center; justify-content: center; gap: 4px;
        }
        #paymentBody.split-active .pay-method-btn i {
            display: inline-block !important; font-size: 12px !important; margin-bottom: 0 !important;
        }
        /* Whatever's expanded here (method buttons + cash/split fields) is
           capped and scrolls internally, rather than growing past the panel
           and pushing the Hold/Pay row below the visible area — the bottom
           controls must always stay reachable regardless of screen height.
           A viewport-relative cap (rather than a fixed px guess) keeps this
           safe on short windows without being unnecessarily cramped on tall
           ones. */
        #paymentBody { max-height: 26vh; overflow-y: auto; }

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
        .btn-primary   { background: #2563eb; color: #fff; border: none; border-radius: 10px; padding: 13px 18px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 14px; }
        .btn-primary:hover   { background: #1d4ed8; }
        .btn-secondary { background: #f1f5f9; color: #374151; border: none; border-radius: 10px; padding: 10px 16px; font-weight: 600; cursor: pointer; transition: background 0.15s; font-size: 13px; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-green   { background: #16a34a; color: #fff; border: none; border-radius: 10px; padding: 13px 18px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 14px; }
        .btn-green:hover   { background: #15803d; }
        .btn-blue    { background: #2563eb; color: #fff; border: none; border-radius: 10px; padding: 13px 18px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 14px; }
        .btn-blue:hover    { background: #1d4ed8; }
        .btn-orange  { background: #ea580c; color: #fff; border: none; border-radius: 10px; padding: 10px 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 12px; }
        .btn-orange:hover  { background: #c2410c; }
        .btn-purple  { background: #7c3aed; color: #fff; border: none; border-radius: 10px; padding: 10px 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; font-size: 12px; }
        .btn-purple:hover  { background: #6d28d9; }

        /* ── Keyboard focus visibility (cashiers work mouse-free) ── */
        .product-card:focus-visible,
        .cat-pill:focus-visible,
        .qty-btn:focus-visible,
        .pay-method-btn:focus-visible,
        button:focus-visible,
        a:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 3px solid #2563eb !important;
            outline-offset: 2px !important;
        }
        html.dark-mode *:focus-visible { outline-color: #6d94ff; }
        .product-card[aria-disabled="true"] { cursor: not-allowed; }
        kbd {
            background: #f1f5f9; border: 1px solid #cbd5e1; border-bottom-width: 2px;
            border-radius: 4px; padding: 1px 5px; font-size: 9px; font-family: inherit;
            color: #475569; font-weight: 700;
        }
        html.dark-mode kbd { background: #141b30; border-color: #26314d; color: #9aa7c2; }

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
        #toast.error   { background: #1e3a8a; }
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
        html.dark-mode .bill-panel { background: #10162a; border-color: #1f2942; }
        html.dark-mode .menu-panel { background: #0d1220; }

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
        html.dark-mode #selectedTokenLabel { color: #9aa7c2 !important; }

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

<div class="pos-grid" style="margin-top: 64px; height: calc(100vh - 64px);">

    <!-- ════════════════════════════════════════
         COLUMN 1 — MENU PANEL
    ════════════════════════════════════════ -->
    <div class="menu-panel">

        <!-- Toolbar -->
        <div style="padding:16px; background:#fff; border-bottom:1px solid #e2e8f0; flex-shrink:0;">
            <!-- Categories -->
            <div style="display:flex; gap:8px; overflow-x:auto; padding-bottom:8px;" id="categoriesContainer">
                <button class="cat-pill" id="offersPill" onclick="toggleOffersMode()" style="background:#fdf4ff; color:#a21caf; border-color:#e9d5ff;">
                    <i class="fas fa-gift" style="margin-right:4px;"></i>Offers
                </button>
                <button class="cat-pill" id="includeItemsPill" onclick="toggleIncludeItemsMode()" style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;">
                    <i class="fas fa-flask" style="margin-right:4px;"></i>Include Items
                </button>
                <button class="cat-pill active" data-category="0" onclick="selectCategory(0, this)">All</button>
            </div>
            <!-- Search Bar -->
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                <input type="text" id="searchInput" placeholder="Search by product name…"
                       style="width:100%; padding:11px 14px 11px 38px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; background:#f8fafc;"
                       onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <!-- Keyboard-only cashier workflow reference -->
            <div style="margin-top:7px; display:flex; align-items:center; gap:9px; flex-wrap:wrap; color:#94a3b8; font-size:10px;">
                <span><i class="fas fa-keyboard" style="margin-right:3px;"></i>Keyboard:</span>
                <span><kbd>Tab</kbd> Switch panel</span>
                <span><kbd>&#8593;&#8595;&#8592;&#8594;</kbd> Move within it</span>
                <span><kbd>&crarr;</kbd> Select</span>
                <span><kbd>F2</kbd> New</span>
                <span><kbd>F3</kbd> Search</span>
                <span><kbd>F4</kbd> Hold</span>
                <span><kbd>F8</kbd> Discard</span>
                <span><kbd>F9</kbd> Pay</span>
                <span><kbd>Esc</kbd> Close</span>
            </div>
        </div>

        <!-- Active order indicator -->
        <div id="activeOrderBanner" style="display:none; background:linear-gradient(90deg,#eff6ff,#fff1f1); border-bottom:1px solid #fecaca; padding:8px 16px; flex-shrink:0;">
            <span style="font-size:12px; font-weight:600; color:#2563eb; flex:1;">
                <i class="fas fa-circle-dot" style="margin-right:4px;"></i>
                <span id="activeOrderText">Token —</span>
                <span style="color:#374151; font-weight:500; margin-left:4px;">tap a product to add it</span>
            </span>
            <button id="closeOrderBtn" onclick="closeCurrentOrder(); event.stopPropagation();" style="background:none; border:none; color:#2563eb; cursor:pointer; font-size:18px; padding:0 8px; width:32px; height:32px; display:flex; align-items:center; justify-content:center; transition:all 0.2s; flex-shrink:0;">
                <i class="fas fa-times-circle" style="font-size:18px;"></i>
            </button>
        </div>

        <!-- Products grid -->
        <div style="flex:1; overflow-y:auto; padding:16px;">
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:18px;" id="productsContainer">
                <p style="grid-column:1/-1; text-align:center; color:#94a3b8; padding:48px 0; font-size:13px;">Loading products…</p>
            </div>
        </div>

    </div>

    <!-- ════════════════════════════════════════
         COLUMN 2 — BILL PANEL
    ════════════════════════════════════════ -->
    <div class="bill-panel">

        <!-- Zone 1: Header with Table Info -->
        <div id="orderHeaderPanel" style="padding:12px 16px; border-bottom:1px solid #e2e8f0; flex-shrink:0; background:#fff;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                <h3 style="font-size:17px; font-weight:800; color:#0f172a; margin:0;">
                    <i class="fas fa-receipt" style="color:#2563eb; margin-right:6px;"></i>Order
                </h3>
                <button onclick="createNewOrder()" class="btn-primary" style="padding:9px 16px; font-size:12px; font-weight:700;">
                    <i class="fas fa-plus" style="margin-right:6px;"></i>New Order
                </button>
            </div>
            <div id="selectedTokenLabel" style="font-size:14px; font-weight:700; color:#64748b;">
                <i class="fas fa-arrow-left" style="font-size:10px; margin-right:4px;"></i>Tap New Order to begin
            </div>
            <div id="tokenNumberRow" style="display:none; align-items:center; gap:8px; margin-top:8px;">
                <label style="font-size:11px; font-weight:800; color:#ea580c; text-transform:uppercase; letter-spacing:0.04em; flex-shrink:0;">
                    <i class="fas fa-ticket" style="margin-right:3px;"></i>Token #
                </label>
                <input type="number" id="tokenNumberInput" placeholder="Enter physical token #" min="1"
                       style="flex:1; min-width:0; font-size:14px; font-weight:800; border:1.5px solid #fdba74; border-radius:8px; padding:6px 10px; outline:none; background:#fff7ed; color:#9a3412;"
                       onblur="saveTokenNumber()" onkeydown="if(event.key==='Enter'){this.blur();event.preventDefault();}">
            </div>
        </div>

        <!-- Zone 2: Expandable Customer Info -->
        <div id="customerPanel" style="padding:0; border-bottom:1px solid #e2e8f0; flex-shrink:0; background:#f8fafc;">
            <button id="customerInfoToggle" onclick="toggleCustomerInfo()" style="width:100%; padding:10px 16px; background:none; border:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; text-align:left;">
                <div style="display:flex; align-items:center;">
                    <i class="fas fa-user-circle" style="color:#1d4ed8; margin-right:6px; font-size:13px;"></i>
                    <span style="font-size:12px; font-weight:700; color:#1d4ed8; text-transform:uppercase;">Customer</span>
                </div>
                <i class="fas fa-chevron-down" id="customerInfoChevron" style="font-size:11px; color:#64748b;"></i>
            </button>
            <div id="customerInfoSection" style="display:none; padding:8px 16px; border-top:1px solid #e2e8f0;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                    <input type="text" id="customerName" placeholder="Name"
                           style="font-size:13px; border:1.5px solid #bfdbfe; border-radius:8px; padding:9px 10px; background:#fff; outline:none; width:100%;"
                           onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#bfdbfe'; saveCustomerInfo()">
                    <input type="text" id="customerPhone" placeholder="Phone"
                           style="font-size:13px; border:1.5px solid #bfdbfe; border-radius:8px; padding:9px 10px; background:#fff; outline:none; width:100%;"
                           onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#bfdbfe'; saveCustomerInfo()">
                </div>
            </div>
        </div>

        <!-- Zone 3: Order items (scrollable) - MAIN AREA -->
        <div style="flex:1; overflow-y:auto; padding:12px 16px; background:#fafafa;" id="billItemsWrapper">
            <div id="billItems">
                <div style="text-align:center; padding:48px 0; color:#cbd5e1;">
                    <i class="fas fa-utensils" style="font-size:36px; margin-bottom:12px; display:block;"></i>
                    <p style="font-size:12px; margin:0;">Start a new order, then add items</p>
                </div>
            </div>
        </div>

        <!-- Zone 4: Fixed bottom controls -->
        <div id="bottomControlsPanel" style="border-top:1px solid #e2e8f0; padding:14px 18px; background:#fff; flex-shrink:0; display:flex; flex-direction:column; gap:8px;">

            <!-- Give a free item as a discount (instead of reducing the bill amount) -->
            <button type="button" id="freeItemToggle" onclick="openFreeItemModal()" style="display:none; width:100%; text-align:left; background:#f0fdf4; border:1px dashed #86efac; border-radius:8px; padding:7px 10px; font-size:12px; font-weight:700; color:#166534; cursor:pointer;">
                <i class="fas fa-gift" style="margin-right:5px;"></i>Give a Free Item (as discount)
            </button>

            <!-- Totals + Payment summary — single 4-column row -->
            <div style="display:grid; grid-template-columns: 1fr 1.25fr 1fr 1.1fr; gap:8px; align-items:start;">
                <!-- Subtotal -->
                <div>
                    <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#94a3b8; margin-bottom:3px;">Subtotal</div>
                    <div id="subtotalDisplay" style="font-size:15px; font-weight:600; color:#374151;">Rs. 0.00</div>
                </div>
                <!-- Discount -->
                <div>
                    <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#94a3b8; margin-bottom:3px;">Discount</div>
                    <div style="display:flex; gap:4px;">
                        <select id="discountType" onchange="recalcTotal()"
                                style="flex:1; min-width:0; font-size:12px; border:1px solid #e2e8f0; border-radius:6px; padding:5px 6px; background:#f8fafc; outline:none; cursor:pointer;">
                            <option value="">None</option>
                            <option value="percentage">%</option>
                            <option value="fixed">Rs</option>
                        </select>
                        <input type="number" id="discountValue" placeholder="0" min="0" oninput="recalcTotal()"
                               style="width:48px; font-size:12px; border:1px solid #e2e8f0; border-radius:6px; padding:5px 6px; outline:none; background:#f8fafc;">
                    </div>
                </div>
                <!-- Total -->
                <div>
                    <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#2563eb; margin-bottom:3px;">Total</div>
                    <div id="totalDisplay" style="font-size:20px; font-weight:800; color:#2563eb;">Rs. 0.00</div>
                </div>
                <!-- Payment toggle (hidden until items exist) -->
                <button type="button" id="paymentToggle" onclick="togglePaymentSection()" style="display:none; flex-direction:column; align-items:flex-start; gap:2px; background:none; border:none; padding:0; cursor:pointer; text-align:left;">
                    <span style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#94a3b8;">Payment</span>
                    <span style="font-size:14px; font-weight:700; color:#2563eb; display:flex; align-items:center; gap:4px;">
                        <span id="paymentMethodSummary">Cash</span>
                        <i class="fas fa-chevron-down" id="paymentChevron" style="font-size:10px; color:#64748b; transition:transform 0.15s;"></i>
                    </span>
                </button>
            </div>

            <!-- Payment details (collapsible, hidden until items exist) -->
            <div id="paymentSection" style="display:none;">
                <div id="paymentBody" style="display:none; padding-top:8px; margin-top:2px; border-top:1px solid #e2e8f0;">
                <div style="display:flex; gap:5px; margin-bottom:5px;">
                    <button class="pay-method-btn active" data-method="cash" onclick="selectPaymentMethod('cash')" style="flex:1; padding:10px 6px; font-size:12px;">
                        <i class="fas fa-money-bill-wave" style="display:block; font-size:16px; margin-bottom:3px;"></i>Cash
                    </button>
                    <button class="pay-method-btn" data-method="card" onclick="selectPaymentMethod('card')" style="flex:1; padding:10px 6px; font-size:12px;">
                        <i class="fas fa-credit-card" style="display:block; font-size:16px; margin-bottom:3px;"></i>Card
                    </button>
                    <button class="pay-method-btn" data-method="bank_transfer" onclick="selectPaymentMethod('bank_transfer')" style="flex:1; padding:10px 6px; font-size:12px;">
                        <i class="fas fa-university" style="display:block; font-size:16px; margin-bottom:3px;"></i>Bank
                    </button>
                </div>
                <div style="display:flex; gap:5px; margin-bottom:8px;">
                    <button class="pay-method-btn" data-method="pickme" onclick="selectPaymentMethod('pickme')" style="flex:1; padding:10px 6px; font-size:12px;">
                        <i class="fas fa-taxi" style="display:block; font-size:16px; margin-bottom:3px;"></i>PickMe
                    </button>
                    <button class="pay-method-btn" data-method="uber" onclick="selectPaymentMethod('uber')" style="flex:1; padding:10px 6px; font-size:12px;">
                        <i class="fab fa-uber" style="display:block; font-size:16px; margin-bottom:3px;"></i>Uber
                    </button>
                    <button class="pay-method-btn" data-method="split" onclick="selectPaymentMethod('split')" style="flex:1; padding:10px 6px; font-size:12px;">
                        <i class="fas fa-code-branch" style="display:block; font-size:16px; margin-bottom:3px;"></i>Split
                    </button>
                </div>
                <!-- Cash amount input -->
                <div id="cashSection" style="display:flex; gap:6px;">
                    <div style="flex:1;">
                        <label style="font-size:10px; font-weight:600; color:#64748b; display:block; margin-bottom:3px;">Paid</label>
                        <input type="number" id="amountPaid" placeholder="0.00" min="0" oninput="updateChange()"
                               style="width:100%; font-size:14px; font-weight:700; border:1px solid #e2e8f0; border-radius:6px; padding:8px 10px; outline:none;"
                               onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:10px; font-weight:600; color:#64748b; display:block; margin-bottom:3px;">Change</label>
                        <div id="changeDisplay" style="font-size:14px; font-weight:700; color:#16a34a; padding:8px 10px; background:#f0fdf4; border-radius:6px; border:1px solid #bbf7d0; text-align:center;">Rs. 0.00</div>
                    </div>
                </div>

                <!-- Split Payment inputs -->
                <div id="splitSection" style="display:none; border-top:1px solid #e2e8f0; padding-top:12px;">
                    <div style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:10px;">Split Payment</div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:10px;">
                        <div>
                            <label for="splitMethod1" style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Method 1</label>
                            <select id="splitMethod1" onchange="updateSplitTotal()" style="width:100%; font-size:14px; border:1.5px solid #e2e8f0; border-radius:8px; padding:9px 10px; outline:none;">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank">Bank</option>
                            </select>
                        </div>
                        <div>
                            <label for="splitAmount1" style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Amount</label>
                            <input type="number" id="splitAmount1" placeholder="0.00" min="0" oninput="updateSplitTotal()"
                                   style="width:100%; font-size:15px; font-weight:700; border:1.5px solid #e2e8f0; border-radius:8px; padding:9px 10px; outline:none;">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:10px;">
                        <div>
                            <label for="splitMethod2" style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Method 2</label>
                            <select id="splitMethod2" onchange="updateSplitTotal()" style="width:100%; font-size:14px; border:1.5px solid #e2e8f0; border-radius:8px; padding:9px 10px; outline:none;">
                                <option value="">-- Select --</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank">Bank</option>
                            </select>
                        </div>
                        <div>
                            <label for="splitAmount2" style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Amount</label>
                            <input type="number" id="splitAmount2" placeholder="0.00" min="0" oninput="updateSplitTotal()"
                                   style="width:100%; font-size:15px; font-weight:700; border:1.5px solid #e2e8f0; border-radius:8px; padding:9px 10px; outline:none;">
                        </div>
                    </div>
                    <div style="background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:8px; padding:10px 12px; text-align:center;">
                        <div style="font-size:11px; color:#64748b; margin-bottom:4px; font-weight:600;">Total Paid</div>
                        <div id="splitTotalDisplay" style="font-size:18px; font-weight:800; color:#16a34a;">Rs. 0.00</div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div id="orderControls" style="display:flex; flex-direction:column; gap:6px;">

                <!-- Pay: prints the KOT (to kitchen) and the bill (to customer) in one action -->
                <div id="waiterPayRow" style="display:none; gap:6px; display:flex;">
                    <button onclick="holdCurrentOrder()" id="holdBtn" class="btn-secondary" style="padding:13px 14px; font-size:13px; white-space:nowrap;" title="Park this bill and resume it later from Sales Report">
                        <i class="fas fa-pause-circle" style="margin-right:4px;"></i>Hold
                    </button>
                    <button onclick="initiatePayment()" id="payBtn" class="btn-green" style="flex:1; padding:13px 8px; font-size:14px;">
                        <i class="fas fa-check-circle" style="margin-right:3px;"></i>Pay
                    </button>
                </div>

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
            <button onclick="printBillContent()" data-primary="true" class="btn-primary" style="flex:1;"><i class="fas fa-print" style="margin-right:4px;"></i>Print</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: Give Free Item (discount, not a bill reduction)
══════════════════════════════════════════════════ -->
<div id="freeItemModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 420px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0;"><i class="fas fa-gift" style="color:#16a34a; margin-right:6px;"></i>Give a Free Item</h2>
            <button onclick="closeModal('freeItemModal')" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p style="font-size:12px; color:#64748b; margin:0 0 12px;">
            Adds the item to the bill at its normal price, then marks it 100% off so it's added to stock/kitchen as usual but shows as <strong style="color:#16a34a;">FREE</strong> on the bill instead of reducing the total amount.
        </p>
        <label style="font-size:11px; font-weight:700; color:#374151; display:block; margin-bottom:4px;">Item</label>
        <select id="freeItemProduct" onchange="onFreeItemSelectionChange()" style="width:100%; font-size:13px; border:1.5px solid #e2e8f0; border-radius:8px; padding:9px 10px; outline:none; background:#f8fafc; margin-bottom:10px;">
            <option value="">Select an item…</option>
        </select>
        <label id="freeItemQtyLabel" style="font-size:11px; font-weight:700; color:#374151; display:block; margin-bottom:4px;">Quantity</label>
        <input type="number" id="freeItemQty" value="1" min="1" style="width:100%; font-size:13px; border:1.5px solid #e2e8f0; border-radius:8px; padding:9px 10px; outline:none; background:#f8fafc;">
        <div style="display:flex; gap:8px; margin-top:16px;">
            <button onclick="closeModal('freeItemModal')" class="btn-secondary" style="flex:1;">Cancel</button>
            <button onclick="submitFreeItem()" data-primary="true" style="flex:1; background:#16a34a; color:#fff; border:none; border-radius:10px; padding:10px 16px; font-weight:700; cursor:pointer;"><i class="fas fa-gift" style="margin-right:5px;"></i>Add as Free</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: Add Include Item (paid — buy one directly, e.g. "extra mayonnaise")
══════════════════════════════════════════════════ -->
<div id="includeItemModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 380px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0;"><i class="fas fa-flask" style="color:#1d4ed8; margin-right:6px;"></i><span id="includeItemModalTitle">Add Item</span></h2>
            <button onclick="closeModal('includeItemModal')" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p id="includeItemModalHint" style="font-size:12px; color:#64748b; margin:0 0 12px;"></p>
        <label id="includeItemQtyLabel" style="font-size:11px; font-weight:700; color:#374151; display:block; margin-bottom:4px;">Quantity</label>
        <input type="number" id="includeItemQty" value="1" min="1" step="1"
               style="width:100%; font-size:15px; font-weight:700; border:1.5px solid #bfdbfe; border-radius:8px; padding:9px 10px; outline:none; background:#eff6ff; text-align:center;"
               onkeydown="if(event.key==='Enter'){submitIncludeItem();event.preventDefault();event.stopPropagation();}">
        <div style="display:flex; gap:8px; margin-top:16px;">
            <button onclick="closeModal('includeItemModal')" class="btn-secondary" style="flex:1;">Cancel</button>
            <button onclick="submitIncludeItem()" data-primary="true" style="flex:1; background:#1d4ed8; color:#fff; border:none; border-radius:10px; padding:10px 16px; font-weight:700; cursor:pointer;"><i class="fas fa-plus" style="margin-right:5px;"></i>Add to Bill</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: Discard Bill
══════════════════════════════════════════════════ -->
<div id="discardReasonModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 420px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0;"><i class="fas fa-ban" style="color:#dc2626; margin-right:6px;"></i>Discard Bill</h2>
            <button onclick="closeModal('discardReasonModal')" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p style="font-size:13px; color:#64748b; margin:0 0 10px;">This bill will be cancelled and recorded for audit. Please enter a reason.</p>
        <textarea id="discardReasonInput" rows="3" placeholder="Reason for discarding this bill…"
                  style="width:100%; font-size:13px; border:1.5px solid #e2e8f0; border-radius:8px; padding:10px; outline:none; resize:none;"></textarea>
        <div style="display:flex; gap:8px; margin-top:14px;">
            <button onclick="closeModal('discardReasonModal')" class="btn-secondary" style="flex:1;">Cancel</button>
            <button onclick="submitDiscardOrder()" data-primary="true" style="flex:1; background:#dc2626; color:#fff; border:none; border-radius:10px; padding:10px 16px; font-weight:700; cursor:pointer;">Discard Bill</button>
        </div>
    </div>
</div>

<!-- Shift Not Started Modal -->
<div id="shiftModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 400px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 48px; margin-bottom: 12px;">
                <i class="fas fa-exclamation-circle" style="color: #2563eb;"></i>
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
            <a href="{{ route('shifts.index') }}" style="flex: 1; padding: 10px; border: none; border-radius: 8px; background: #2563eb; color: #fff; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-arrow-right" style="margin-right: 6px;"></i> Go to Shifts
            </a>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast"></div>

<script>
    // ── State ──
    let currentOrder  = null;
    let allProducts   = [];
    let allIngredients = []; // raw/included items (not sellable menu Products) — for the Free Item picker
    let allCategories = @json($categories);
    let selectedPaymentMethod = 'cash';
    let currentBillContent    = '';
    let stockCache            = {}; // { productId: remainingQty } for finished-goods (independent stock)
    let ingredientStockCache  = {}; // { ingredientId: remainingRawQty } for recipe-tracked products
    let productRecipes        = {}; // { productId: [{ingredient_id, quantity_per_unit}] } for recipe-tracked products
    let openDiscountRows      = new Set(); // item IDs whose discount input row is open
    let qtyLock               = {}; // { itemId: true } — prevents overlapping qty updates
    let lastFocusedBeforeModal = null; // element to restore focus to once a modal closes

    // Recipe-tracked products (e.g. two combos that both use the same Paratha Roti)
    // share one pool of ingredient stock. getStock() derives a product's available
    // quantity from that shared pool; adjustStock() is the single place that deducts
    // or restores it so every sibling product reflects the change immediately.
    function getStock(productId) {
        const recipe = productRecipes[productId];
        if (recipe && recipe.length > 0) {
            let unitsPossible = Infinity;
            recipe.forEach(function(r) {
                const remaining = ingredientStockCache.hasOwnProperty(r.ingredient_id) ? ingredientStockCache[r.ingredient_id] : 0;
                const possible = r.quantity_per_unit > 0 ? Math.floor(remaining / r.quantity_per_unit) : Infinity;
                if (possible < unitsPossible) unitsPossible = possible;
            });
            return Math.max(0, unitsPossible === Infinity ? 0 : unitsPossible);
        }
        return stockCache.hasOwnProperty(productId) ? stockCache[productId] : null;
    }

    function adjustStock(productId, deltaUnits) {
        const recipe = productRecipes[productId];
        if (recipe && recipe.length > 0) {
            recipe.forEach(function(r) {
                if (ingredientStockCache.hasOwnProperty(r.ingredient_id)) {
                    ingredientStockCache[r.ingredient_id] -= r.quantity_per_unit * deltaUnits;
                }
            });
        } else if (stockCache.hasOwnProperty(productId)) {
            stockCache[productId] = Math.max(0, stockCache[productId] - deltaUnits);
        }
    }

    // ═══════════════════════════════════════════
    // KEYBOARD NAVIGATION (cashiers work mouse-free)
    //
    // The screen is a handful of panels (categories, product grid, order
    // header, customer, bill items, bottom controls). Tab moves between
    // panels and only ever stops once per panel — a "roving tabindex": every
    // control inside a panel is tabindex="-1" except the one currently
    // "active", which is tabindex="0". Arrow keys move that active pointer
    // around *inside* the current panel; Enter/Space activates whatever is
    // focused (native browser behavior for real <button>/<input> elements).
    // ═══════════════════════════════════════════

    const ROVING_SELECTOR = 'button, input, select, a[href], .product-card[tabindex]';

    function rovingItems(panel) {
        return Array.from(panel.querySelectorAll(ROVING_SELECTOR)).filter(function(el) {
            return !el.disabled && el.getAttribute('aria-disabled') !== 'true' && el.offsetParent !== null;
        });
    }

    // Make exactly one item in `panel` the page's Tab stop: `preferredEl` if
    // it's still present, else whichever was already active, else the first
    // item. Only actually moves keyboard focus there when `restoreFocus` is
    // true — re-renders happen constantly (including from background syncs),
    // and must never steal focus from a cashier working somewhere else.
    function ensureRovingDefault(panel, preferredEl, restoreFocus) {
        const items = rovingItems(panel);
        if (!items.length) return null;
        const current = items.find(function(el) { return el.getAttribute('tabindex') === '0'; });
        const active = (preferredEl && items.includes(preferredEl)) ? preferredEl : (current || items[0]);
        items.forEach(function(el) { el.setAttribute('tabindex', el === active ? '0' : '-1'); });
        if (restoreFocus) active.focus();
        return active;
    }

    // Whichever item last actually received focus — by click, arrow-nav, or
    // programmatic focus() — becomes the panel's sole Tab stop from then on.
    function registerRovingSync(panel) {
        panel.addEventListener('focusin', function(e) {
            const items = rovingItems(panel);
            if (!items.includes(e.target)) return;
            items.forEach(function(el) { el.setAttribute('tabindex', el === e.target ? '0' : '-1'); });
        });
    }

    // Generic arrow-key roving for panels that are a simple list of controls
    // (as opposed to the product grid / bill items, which are 2-D and have
    // their own column-aware handlers). Up/down always move the roving
    // pointer; left/right only do on controls where that key has no native
    // meaning — a text input's caret and a number input's spinner always win.
    function registerLinearRoving(panel) {
        registerRovingSync(panel);
        panel.addEventListener('keydown', function(e) {
            const items = rovingItems(panel);
            const idx = items.indexOf(e.target);
            if (idx === -1) return;

            // Text/number inputs: up/down has no native meaning for a single-line
            // value (these are all money/quantity fields — cashiers type the
            // amount rather than spin to it), so it's free to move the roving
            // pointer; left/right always stays native for the text caret.
            // Selects: the reverse — up/down natively cycles options, so only
            // left/right is free to roam.
            const tag = e.target.tagName;
            const isInput  = tag === 'INPUT';
            const isSelect = tag === 'SELECT';
            const canUpDown    = !isSelect;
            const canLeftRight = !isInput;

            let dir = 0;
            if (canUpDown && e.key === 'ArrowDown') dir = 1;
            else if (canUpDown && e.key === 'ArrowUp') dir = -1;
            else if (canLeftRight && e.key === 'ArrowRight') dir = 1;
            else if (canLeftRight && e.key === 'ArrowLeft') dir = -1;
            else if (canLeftRight && e.key === 'Home') { e.preventDefault(); items[0].focus(); return; }
            else if (canLeftRight && e.key === 'End') { e.preventDefault(); items[items.length - 1].focus(); return; }
            else return;

            if (!dir) return;
            const next = items[idx + dir];
            if (next) { e.preventDefault(); next.focus(); }
        });
    }

    // Grid items wrap based on container width (auto-fill), so the number of
    // columns isn't fixed — derive it by counting how many cards share the
    // first card's row (their offsetTop).
    function gridColumnCount(cards) {
        if (cards.length < 2) return 1;
        const firstTop = cards[0].offsetTop;
        let count = 0;
        for (const card of cards) {
            if (card.offsetTop === firstTop) count++;
            else break;
        }
        return count || 1;
    }

    // Re-rendering the product/offer grid replaces every card's DOM node,
    // which would otherwise drop keyboard focus back to <body>. Remember
    // which product/offer had focus (if any) before rebuilding, so the
    // equivalent new card can both become the panel's roving default and, if
    // focus was actually there, get it back (a second Enter on the same item
    // then bumps its quantity again — handy for "same item x3").
    function captureGridFocus(container) {
        const el = document.activeElement;
        if (!container.contains(el)) return { hadFocus: false };
        return {
            hadFocus: true,
            productId: el.dataset.productId || null,
            offerId: el.dataset.offerId || null,
            ingredientId: el.dataset.ingredientId || null,
        };
    }

    function restoreGridFocus(container, captured) {
        let preferredEl = null;
        if (captured && captured.productId) {
            preferredEl = container.querySelector('.product-card[data-product-id="' + captured.productId + '"]');
        } else if (captured && captured.offerId) {
            preferredEl = container.querySelector('.product-card[data-offer-id="' + captured.offerId + '"]');
        } else if (captured && captured.ingredientId) {
            preferredEl = container.querySelector('.product-card[data-ingredient-id="' + captured.ingredientId + '"]');
        }
        ensureRovingDefault(container, preferredEl, !!(captured && captured.hadFocus));
    }

    // ── Bootstrap ──
    async function initPos() {
        loadCategories();
        await loadProducts();
        loadIngredients();
        setupEventListeners();
        updateShiftStatus();
        setInterval(updateShiftStatus, 15000); // Update every 15 seconds

        // Establish each panel's single Tab stop before any order exists —
        // renderOrderHeader()/setBottomControls() only run once an order is
        // created or resumed, but a couple of these panels' controls (e.g.
        // the discount fields) are visible from the very first paint.
        ensureRovingDefault(document.getElementById('orderHeaderPanel'), null, false);
        ensureRovingDefault(document.getElementById('customerPanel'), null, false);
        ensureRovingDefault(document.getElementById('bottomControlsPanel'), null, false);

        const resumeId = new URLSearchParams(window.location.search).get('resume');
        if (resumeId) {
            history.replaceState({}, '', window.location.pathname);
            await resumeExistingOrder(resumeId);
        }

        document.getElementById('searchInput').focus();
    }

    // Brings a bill that was never paid (e.g. left over from a previous
    // session) back into the active order panel so it can actually be
    // finished, instead of sitting stuck as "pending" forever — reached via
    // the "Resume & Pay" link on the Sales Report page.
    async function resumeExistingOrder(orderId) {
        showLoading();
        try {
            const res = await fetch('/pos/order/' + orderId);
            if (!res.ok) {
                hideLoading();
                toast('Could not load that order', 'error');
                return;
            }
            const data = await res.json();
            if (data.status === 'completed' || data.status === 'cancelled') {
                hideLoading();
                toast('That bill is already ' + data.status + ' — nothing to resume', 'error');
                return;
            }

            currentOrder = data;
            renderOrderHeader();
            renderBill();
            hideLoading();
            toast('Resumed order ' + (data.order_number || '') + ' — finish billing below', 'success');
        } catch (e) {
            console.error('resumeExistingOrder error:', e);
            hideLoading();
            toast('Failed to resume order', 'error');
        }
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

    async function createNewOrder() {
        showLoading();
        try {
            const res = await fetch('{{ route("pos.order.create") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            });

            if (!res.ok) {
                hideLoading();
                console.error('Order creation HTTP error. Status:', res.status, 'Text:', res.statusText);
                let errorMessage = 'Failed to create order';

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
                toast('Failed to create order: Invalid response from server', 'error');
                return false;
            }

            currentOrder = {
                id: data.order_id,
                order_number: data.order_number,
                token_number: data.token_number,
                token_date: data.token_date,
                items: [],
                subtotal: 0,
                total: 0,
                discount_amount: 0,
                live_bill_enabled: false,
                customer_name: null,
                customer_phone: null,
            };

            renderOrderHeader();
            renderBill();
            hideLoading();

            toast('New order started — enter the token number and add items', 'success');
            return true;
        } catch (e) {
            console.error('createNewOrder error:', e);
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
            // A filtered search only returns a subset of products, but ingredient
            // reservations made against products outside that subset (e.g. added
            // before the search) must keep counting — so refresh whatever's in this
            // response instead of wiping caches for products we can no longer see.
            allProducts.forEach(function(p) {
                if (Array.isArray(p.recipe) && p.recipe.length > 0) {
                    productRecipes[p.id] = p.recipe;
                    p.recipe.forEach(function(r) {
                        ingredientStockCache[r.ingredient_id] = r.ingredient_stock;
                    });
                    delete stockCache[p.id];
                } else if (!p.is_unlimited_stock) {
                    stockCache[p.id] = p.quantity;
                    delete productRecipes[p.id];
                } else {
                    delete stockCache[p.id];
                    delete productRecipes[p.id];
                }
            });
            // loadProducts() re-fetches fresh baselines from the server (e.g. on every
            // search keystroke), which doesn't know about items already sitting unprinted
            // in the current cart. Re-apply that reservation on top of the fresh baseline
            // so searching mid-order never makes reserved stock look available again.
            if (currentOrder && Array.isArray(currentOrder.items)) {
                currentOrder.items.forEach(function(item) {
                    if (item.product_id) adjustStock(item.product_id, item.quantity);
                });
            }
            renderProducts();
        } catch (e) {
            console.error('Load products error:', e);
            toast('Error loading products', 'error');
        }
    }

    // Raw/included items (not sellable menu Products) — fetched once up
    // front purely to populate the Free Item picker alongside menu items.
    async function loadIngredients() {
        try {
            const res = await fetch('{{ route("pos.ingredients") }}');
            if (!res.ok) return;
            allIngredients = await res.json();
        } catch (e) {
            console.error('Load ingredients error:', e);
        }
    }

    function loadCategories() {
        const container = document.getElementById('categoriesContainer');
        container.innerHTML = '<button class="cat-pill" id="offersPill" onclick="toggleOffersMode()" style="background:#fdf4ff; color:#a21caf; border-color:#e9d5ff;"><i class="fas fa-gift" style="margin-right:4px;"></i>Offers</button>'
            + '<button class="cat-pill" id="includeItemsPill" onclick="toggleIncludeItemsMode()" style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;"><i class="fas fa-flask" style="margin-right:4px;"></i>Include Items</button>'
            + '<button class="cat-pill active" data-category="0" onclick="selectCategory(0, this)">All</button>';
        allCategories.forEach(function(cat) {
            const btn = document.createElement('button');
            btn.className = 'cat-pill';
            btn.textContent = cat.name;
            btn.setAttribute('data-category', cat.id);
            btn.onclick = function() { selectCategory(cat.id, btn); };
            container.appendChild(btn);
        });
        ensureRovingDefault(container, container.querySelector('.cat-pill.active'), false);
    }

    function selectCategory(id, btn) {
        offersMode = false;
        includeItemsMode = false;
        document.getElementById('offersPill').classList.remove('active');
        document.getElementById('includeItemsPill').classList.remove('active');
        document.querySelectorAll('#categoriesContainer .cat-pill').forEach(function(b) { b.classList.remove('active'); });
        if (btn) btn.classList.add('active');
        loadProducts(document.getElementById('searchInput').value, id);
    }

    let offersMode = false;
    let allOffers = [];
    let includeItemsMode = false;

    function toggleOffersMode() {
        offersMode = !offersMode;
        includeItemsMode = false;
        document.querySelectorAll('#categoriesContainer .cat-pill').forEach(function(b) { b.classList.remove('active'); });
        if (offersMode) {
            document.getElementById('offersPill').classList.add('active');
            loadOffers();
        } else {
            document.querySelector('#categoriesContainer .cat-pill[data-category="0"]').classList.add('active');
            renderProducts();
        }
    }

    // "Include Items" — sell a raw/included item directly (e.g. "extra
    // mayonnaise") rather than through one of the sellable menu Products.
    // Shown as its own section, never mixed into the regular product grid.
    async function toggleIncludeItemsMode() {
        includeItemsMode = !includeItemsMode;
        offersMode = false;
        document.querySelectorAll('#categoriesContainer .cat-pill').forEach(function(b) { b.classList.remove('active'); });
        if (includeItemsMode) {
            document.getElementById('includeItemsPill').classList.add('active');
            // Re-fetch rather than trust the initPos() prefetch — that one's
            // fire-and-forget for the Free Item modal's benefit and may not
            // have resolved yet if this is clicked right after page load.
            await loadIngredients();
            renderIncludeItems();
        } else {
            document.querySelector('#categoriesContainer .cat-pill[data-category="0"]').classList.add('active');
            renderProducts();
        }
    }

    function renderIncludeItems() {
        const container = document.getElementById('productsContainer');
        const captured = captureGridFocus(container);
        if (!allIngredients.length) {
            container.innerHTML = '<p style="grid-column:1/-1; text-align:center; color:#94a3b8; padding:48px 0; font-size:13px;"><i class="fas fa-flask" style="font-size:28px; display:block; margin-bottom:10px;"></i>No included items available</p>';
            restoreGridFocus(container, captured);
            return;
        }
        container.innerHTML = allIngredients.map(function(i) {
            const hasPrice = i.selling_price !== null && i.selling_price > 0;
            const outOfStock = i.quantity <= 0;
            const disabled = !hasPrice || outOfStock;
            const priceLabel = hasPrice
                ? 'Rs. ' + i.selling_price.toFixed(2) + ' / ' + escapeHtml(i.unit)
                : 'No price set';
            const stockLabel = outOfStock
                ? '<p style="font-size:10px; color:#ef4444; margin:2px 0 0; font-weight:700;">Out of Stock</p>'
                : '<p style="font-size:10px; color:#64748b; margin:2px 0 0;">' + i.quantity.toFixed(2) + ' ' + escapeHtml(i.unit) + ' left</p>';
            const cardExtra = disabled
                ? 'tabindex="-1" aria-disabled="true" style="opacity:0.5; cursor:not-allowed; pointer-events:none;"'
                : 'tabindex="-1" role="button" aria-label="Add ' + escapeHtml(i.name) + '" onclick="openIncludeItemModal(' + i.id + ', \'' + escapeJs(i.name) + '\', \'' + escapeJs(i.unit) + '\', ' + i.selling_price + ', ' + i.quantity + ')"';

            return '<div class="product-card" data-ingredient-id="' + i.id + '" ' + cardExtra + '>'
                + '<div style="height:130px; background:linear-gradient(135deg,#eff6ff,#dbeafe); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:12px;">'
                + '<i class="fas fa-flask" style="color:#1d4ed8; font-size:28px;"></i>'
                + '</div>'
                + '<p style="font-size:14px; font-weight:700; color:#0f172a; margin:0 0 5px; line-height:1.3; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">' + escapeHtml(i.name) + '</p>'
                + '<p style="font-size:14px; font-weight:900; color:#1d4ed8; margin:0;">' + priceLabel + '</p>'
                + stockLabel
                + '</div>';
        }).join('');
        restoreGridFocus(container, captured);
    }

    function openIncludeItemModal(ingredientId, name, unit, sellingPrice, stock) {
        const hasActiveShift = {{ $activeShift ? 'true' : 'false' }};
        if (!hasActiveShift) {
            showShiftModal();
            return;
        }

        document.getElementById('includeItemModalTitle').textContent = name;
        document.getElementById('includeItemModalHint').textContent =
            'Rs. ' + sellingPrice.toFixed(2) + ' per ' + unit + ' — ' + stock.toFixed(2) + ' ' + unit + ' in stock.';
        document.getElementById('includeItemQtyLabel').textContent = 'Quantity (' + unit + ')';
        const qtyInput = document.getElementById('includeItemQty');
        qtyInput.value = 1;
        qtyInput.dataset.ingredientId = ingredientId;
        qtyInput.dataset.name = name;
        openModal('includeItemModal');
    }

    async function submitIncludeItem() {
        const qtyInput = document.getElementById('includeItemQty');
        const ingredientId = parseInt(qtyInput.dataset.ingredientId);
        const name = qtyInput.dataset.name || 'Item';
        const qty = Math.max(1, parseInt(qtyInput.value) || 1);
        if (!ingredientId) return;

        if (!currentOrder || !currentOrder.id) {
            const created = await createNewOrder();
            if (!created) {
                toast('Failed to create order. Please try again.', 'error');
                return;
            }
        }

        try {
            const res = await fetch('{{ route("pos.item.add", ":id") }}'.replace(':id', currentOrder.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ ingredient_id: ingredientId, quantity: qty })
            });
            const data = await res.json();
            if (!data.success) {
                toast(data.message || 'Failed to add item', 'error');
                return;
            }

            closeModal('includeItemModal');
            await syncOrder();
            renderBill();
            toast(name + ' added to order', 'success');
        } catch (e) {
            console.error('Add include item error:', e);
            toast('Failed to add item', 'error');
        }
    }

    async function loadOffers() {
        const container = document.getElementById('productsContainer');
        container.innerHTML = '<p style="grid-column:1/-1; text-align:center; color:#94a3b8; padding:48px 0; font-size:13px;">Loading offers…</p>';
        try {
            const res = await fetch('{{ route("pos.offers") }}');
            allOffers = await res.json();
            renderOffers();
        } catch (e) {
            console.error('Load offers error:', e);
            toast('Error loading offers', 'error');
        }
    }

    function renderOffers() {
        const container = document.getElementById('productsContainer');
        const captured = captureGridFocus(container);
        if (!allOffers.length) {
            container.innerHTML = '<p style="grid-column:1/-1; text-align:center; color:#94a3b8; padding:48px 0; font-size:13px;"><i class="fas fa-gift" style="font-size:28px; display:block; margin-bottom:10px;"></i>No active offers</p>';
            restoreGridFocus(container, captured);
            return;
        }
        container.innerHTML = allOffers.map(function(o) {
            const includes = (o.includes || []).join(', ');
            const imageHtml = o.image
                ? '<img src="/storage/' + o.image + '" alt="' + escapeHtml(o.name) + '" style="width:100%; height:100%; object-fit:cover;">'
                : '<i class="fas fa-gift" style="color:#a21caf; font-size:28px;"></i>';
            return '<div class="product-card" tabindex="-1" role="button" data-offer-id="' + o.id + '" aria-label="Add offer ' + escapeHtml(o.name) + '" onclick="addOfferToOrder(' + o.id + ', \'' + escapeJs(o.name) + '\')">'
                + '<div style="height:130px; background:linear-gradient(135deg,#fdf4ff,#fae8ff); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:12px; overflow:hidden;">'
                + imageHtml
                + '</div>'
                + '<p style="font-size:14px; font-weight:700; color:#0f172a; margin:0 0 5px; line-height:1.3;">' + escapeHtml(o.name) + '</p>'
                + '<p style="font-size:16px; font-weight:900; color:#a21caf; margin:0;">Rs. ' + o.price.toFixed(2) + '</p>'
                + (includes ? '<p style="font-size:10px; color:#64748b; margin:4px 0 0; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">Includes: ' + escapeHtml(includes) + '</p>' : '')
                + '</div>';
        }).join('');
        restoreGridFocus(container, captured);
    }

    async function addOfferToOrder(offerId, offerName) {
        const hasActiveShift = {{ $activeShift ? 'true' : 'false' }};
        if (!hasActiveShift) {
            showShiftModal();
            return;
        }

        if (!currentOrder || !currentOrder.id) {
            const created = await createNewOrder();
            if (!created) {
                toast('Failed to create order. Please try again.', 'error');
                return;
            }
        }

        try {
            const res = await fetch('{{ route("pos.offer.add", ":id") }}'.replace(':id', currentOrder.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ offer_id: offerId, quantity: 1 })
            });
            const data = await res.json();
            if (data.success) {
                await syncOrder();
                toast(offerName + ' added to order', 'success');
            } else {
                toast(data.message || 'Failed to add offer', 'error');
            }
        } catch (e) {
            console.error('Add offer error:', e);
            toast('Failed to add offer to order', 'error');
        }
    }

    function renderProducts() {
        const container = document.getElementById('productsContainer');
        const captured = captureGridFocus(container);
        if (allProducts.length === 0) {
            container.innerHTML = '<p style="grid-column:1/-1; text-align:center; color:#94a3b8; padding:48px 0; font-size:13px;"><i class="fas fa-search" style="font-size:28px; display:block; margin-bottom:10px;"></i>No products found</p>';
            restoreGridFocus(container, captured);
            return;
        }
        container.innerHTML = allProducts.map(function(p) {
            let imageHtml = '';
            if (p.image) {
                imageHtml = '<img src="/storage/' + p.image + '" alt="' + escapeHtml(p.name) + '" '
                    + 'style="width:100%; height:100%; object-fit:cover;">';
            } else {
                imageHtml = '<i class="fas fa-utensils" style="color:#2563eb; font-size:18px;"></i>';
            }

            const availableQty = p.is_unlimited_stock ? null : getStock(p.id);
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
                ? 'tabindex="-1" aria-disabled="true" style="opacity:0.5; cursor:not-allowed; pointer-events:none;"'
                : 'tabindex="-1" role="button" aria-label="Add ' + escapeHtml(p.name) + '" onclick="addProductToOrder(' + p.id + ', \'' + escapeJs(p.name) + '\', ' + p.price + ')"';

            return '<div class="product-card" data-product-id="' + p.id + '" ' + cardExtra + '>'
                + '<div style="height:130px; background:linear-gradient(135deg,#eff6ff,#fee2e2); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:12px; overflow:hidden; position:relative;">'
                + imageHtml
                + '</div>'
                + '<p style="font-size:14px; font-weight:700; color:#0f172a; margin:0 0 5px; line-height:1.3; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">' + escapeHtml(p.name) + '</p>'
                + '<p style="font-size:16px; font-weight:900; color:#2563eb; margin:0;">Rs. ' + p.price.toFixed(2) + '</p>'
                + stockBadge
                + '</div>';
        }).join('');
        restoreGridFocus(container, captured);
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
            const created = await createNewOrder();
            if (!created) {
                toast('Failed to create order. Please try again.', 'error');
                return;
            }
            // Small delay to ensure order is created
            await new Promise(resolve => setTimeout(resolve, 100));
        }

        // Verify order is valid before adding items
        if (!currentOrder || !currentOrder.id || !Array.isArray(currentOrder.items)) {
            toast('No active order. Please create an order first.', 'error');
            return;
        }

        // Check stock before adding
        const availBeforeAdd = getStock(productId);
        if (availBeforeAdd !== null && availBeforeAdd <= 0) {
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
        adjustStock(productId, 1);
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
                adjustStock(productId, -1);
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

        const availForIncrease = getStock(item.product_id);
        if (availForIncrease !== null && availForIncrease <= 0) {
            toast('No more stock available for this item', 'error');
            return;
        }

        qtyLock[itemId] = true;
        adjustStock(item.product_id, 1);
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
        adjustStock(item.product_id, -1);
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
        // This fires on every blur — including tabbing away without editing
        // anything — so a no-op guard isn't just an optimization: without it,
        // every Tab press off this field re-renders the whole bill (and
        // fires a save request) for nothing, which stomps on the browser's
        // in-flight focus transition to whatever's next.
        if (newQty === item.quantity) return;
        const diff = newQty - item.quantity;

        // Enforce stock cap when increasing
        const availForSet = getStock(item.product_id);
        if (diff > 0 && availForSet !== null) {
            if (availForSet < diff) {
                newQty = item.quantity + Math.max(0, availForSet);
                if (newQty === item.quantity) {
                    toast('No more stock available for this item', 'error');
                    renderBill();
                    return;
                }
                toast('Quantity limited to available stock', 'error');
            }
        }

        qtyLock[itemId] = true;
        adjustStock(item.product_id, newQty - item.quantity);
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
        if (removedItem) {
            adjustStock(removedItem.product_id, -removedItem.quantity);
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

    function renderOrderHeader() {
        if (!currentOrder) {
            document.getElementById('selectedTokenLabel').innerHTML =
                '<i class="fas fa-arrow-left" style="font-size:11px; margin-right:4px;"></i>Tap New Order to begin';
            document.getElementById('customerInfoToggle').style.display = 'none';
            document.getElementById('activeOrderBanner').style.display   = 'none';
            document.getElementById('tokenNumberRow').style.display = 'none';
            ensureRovingDefault(document.getElementById('orderHeaderPanel'), null, false);
            ensureRovingDefault(document.getElementById('customerPanel'), null, false);
            return;
        }

        const tokenLabel = currentOrder.token_number
            ? 'Token #' + String(currentOrder.token_number).padStart(2, '0')
            : 'No token yet';
        document.getElementById('selectedTokenLabel').innerHTML =
            '🎫 <strong>' + tokenLabel + '</strong> — ' + (currentOrder.order_number || '—');
        document.getElementById('customerInfoToggle').style.display = 'flex';
        document.getElementById('activeOrderBanner').style.display   = 'flex';
        document.getElementById('activeOrderText').textContent = tokenLabel + ' — adding items';

        document.getElementById('customerName').value  = currentOrder.customer_name  || '';
        document.getElementById('customerPhone').value = currentOrder.customer_phone || '';

        document.getElementById('tokenNumberRow').style.display = 'flex';
        document.getElementById('tokenNumberInput').value = currentOrder.token_number || '';

        ensureRovingDefault(document.getElementById('orderHeaderPanel'), null, false);
        ensureRovingDefault(document.getElementById('customerPanel'), null, false);
    }

    async function saveTokenNumber() {
        if (!currentOrder || !currentOrder.id) return;
        const input = document.getElementById('tokenNumberInput');
        const value = parseInt(input.value);
        if (!value || value < 1) return;
        if (value === currentOrder.token_number) return;

        try {
            const res = await fetch('{{ route("pos.order.token_number", ":id") }}'.replace(':id', currentOrder.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ token_number: value })
            });
            const data = await res.json();
            if (data.success) {
                currentOrder.token_number = data.token_number;
                renderOrderHeader();
                toast('Token #' + String(data.token_number).padStart(2, '0') + ' set', 'success');
            } else {
                input.value = currentOrder.token_number || '';
                toast(data.message || 'Failed to set token number', 'error');
            }
        } catch (e) {
            console.error('Save token error:', e);
            toast('Failed to set token number', 'error');
        }
    }

    function renderBill() {
        const billEl = document.getElementById('billItems');
        // Rebuilding innerHTML destroys every control's DOM node, dropping
        // keyboard focus back to <body> — remember which item/role was
        // focused (if any) so it can be restored on the freshly built nodes.
        const focusedControl = document.activeElement.closest && document.activeElement.closest('[data-role]');
        const hadFocus = billEl.contains(document.activeElement);
        const focusedCard = focusedControl && focusedControl.closest('.bill-item-card');
        const focusMemo = hadFocus ? { itemId: focusedCard ? focusedCard.dataset.itemId : null, role: focusedControl ? focusedControl.dataset.role : null } : null;

        if (!currentOrder || !currentOrder.items) {
            billEl.style.display = 'block';
            billEl.innerHTML = '<div style="text-align:center; padding:48px 0; color:#cbd5e1;"><i class="fas fa-utensils" style="font-size:36px; margin-bottom:12px; display:block;"></i><p style="font-size:13px; margin:0;">Tap New Order to begin</p></div>';
            setBottomControls(false);
            updateCloseButtonVisibility(false);
            ensureRovingDefault(billEl, null, false);
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
            billEl.innerHTML = currentOrder.items.map(function(item, idx) {
                const discPercent   = item.discount_percent || 0;
                const isFreeItem    = discPercent >= 100;
                const discRowOpen   = item.id && openDiscountRows.has(item.id);
                const itemAvailStock = getStock(item.product_id);
                const atStockLimit  = itemAvailStock !== null && itemAvailStock <= 0;

                // Discount badge next to product name — a full (100%) discount is
                // shown as a distinct "FREE gift" rather than a plain "-100%" line
                // discount, since it represents giving an item away, not shaving
                // money off the bill.
                const discBadge = isFreeItem
                    ? '<span style="font-size:9px; background:#dcfce7; color:#166534; border-radius:4px; padding:1px 5px; font-weight:700; white-space:nowrap; flex-shrink:0;"><i class="fas fa-gift"></i> FREE</span>'
                    : (discPercent > 0
                        ? '<span style="font-size:9px; background:#fef3c7; color:#92400e; border-radius:4px; padding:1px 5px; font-weight:700; white-space:nowrap; flex-shrink:0;">-' + discPercent + '%</span>'
                        : '');

                // Editable quantity input (replaces static span)
                const qtyControl = item.id
                    ? '<input type="number" min="1" value="' + item.quantity + '" data-role="qty" '
                      + 'style="width:46px; text-align:center; border:1.5px solid #e2e8f0; border-radius:8px; font-size:15px; font-weight:800; color:#0f172a; padding:2px 0; outline:none; background:#fff;" '
                      + 'onblur="setQty(' + item.id + ', this.value)" '
                      + 'onkeydown="if(event.key===\'Enter\'){this.blur();event.preventDefault();}" '
                      + 'onclick="this.select();event.stopPropagation();" />'
                    : '<span style="min-width:22px; text-align:center; font-size:13px; font-weight:800; color:#0f172a;">' + item.quantity + '</span>';

                const decBtn = item.id
                    ? '<button type="button" class="qty-btn" data-role="dec" onclick="decreaseQty(' + item.id + ')">−</button>'
                    : '<button type="button" class="qty-btn" style="opacity:0.4;" disabled>−</button>';
                const incBtn = item.id
                    ? '<button type="button" class="qty-btn" data-role="inc" onclick="increaseQty(' + item.id + ')"' + (atStockLimit ? ' disabled title="No more stock" style="opacity:0.4; cursor:not-allowed;"' : '') + '>+</button>'
                    : '<button type="button" class="qty-btn" style="opacity:0.4;" disabled>+</button>';

                const stockLeft = itemAvailStock !== null
                    ? '<div style="font-size:9px; color:#94a3b8; text-align:center; margin-top:2px;">' + itemAvailStock + ' left</div>'
                    : '';

                const noteHtml = item.kitchen_notes
                    ? '<p style="font-size:10px; color:#f59e0b; margin:2px 0 0;"><i class="fas fa-note-sticky" style="margin-right:3px;"></i>' + escapeHtml(item.kitchen_notes) + '</p>'
                    : '';

                const removeBtn = item.id
                    ? '<button type="button" data-role="remove" onclick="removeItem(' + item.id + ')" title="Remove" style="font-size:11px; color:#ef4444; background:none; border:none; cursor:pointer; padding:2px 3px; line-height:1;"><i class="fas fa-trash"></i></button>'
                    : '';

                // Discount toggle button (green when free, amber when partially discounted, grey when not)
                const discBtnStyle = isFreeItem
                    ? 'background:#dcfce7; color:#166534; border:1px solid #86efac;'
                    : (discPercent > 0
                        ? 'background:#fef3c7; color:#92400e; border:1px solid #fde68a;'
                        : 'background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;');
                const discBtn = item.id
                    ? '<button type="button" data-role="disc" onclick="toggleDiscountRow(' + item.id + ')" title="Discount" '
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
                    thumbHtml = '<div style="width:44px; height:44px; border-radius:9px; overflow:hidden; flex-shrink:0; background:#f1f5f9;">'
                        + '<img src="/storage/' + item.image + '" alt="' + escapeHtml(item.product_name) + '" style="width:100%; height:100%; object-fit:cover;">'
                        + '</div>';
                }

                return '<div class="bill-item-card" data-item-id="' + (item.id || ('tmp-' + idx)) + '" style="background:#fff; border:1px solid #eef2f7; border-radius:10px; padding:8px;">'
                    // Header: thumb + name
                    + '<div style="display:flex; align-items:center; gap:6px;">'
                    + thumbHtml
                    + '<div style="flex:1; min-width:0;">'
                    + '<div style="display:flex; align-items:center; gap:4px; overflow:hidden;">'
                    + '<p style="font-size:15px; font-weight:700; color:#0f172a; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + escapeHtml(item.product_name) + '</p>'
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
                    + (isFreeItem
                        ? '<p style="font-size:15px; font-weight:800; margin:0; white-space:nowrap;"><span style="text-decoration:line-through; color:#94a3b8; font-size:11px; font-weight:600; margin-right:4px;">Rs. ' + (item.unit_price * item.quantity).toFixed(2) + '</span><span style="color:#16a34a;">FREE</span></p>'
                        : '<p style="font-size:15px; font-weight:800; color:#0f172a; margin:0; white-space:nowrap;">Rs. ' + item.subtotal.toFixed(2) + '</p>')
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

        // Each product is its own Tab stop (so Tab moves product-to-product,
        // "one by one") defaulting to its qty control — arrow keys adjust
        // that product's quantity directly, wherever the focus is within its
        // row. Every OTHER card just needs its tabindex bookkeeping fixed up;
        // only the one that actually had focus before this re-render gets it
        // back.
        Array.from(billEl.querySelectorAll('.bill-item-card')).forEach(function(card) {
            const isFocusedCard = !!(focusMemo && focusMemo.itemId === card.dataset.itemId);
            const preferredEl = (isFocusedCard && focusMemo.role)
                ? card.querySelector('[data-role="' + focusMemo.role + '"]')
                : card.querySelector('[data-role="qty"]');
            ensureRovingDefault(card, preferredEl, hadFocus && isFocusedCard);
        });
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
        document.getElementById('freeItemToggle').style.display     = (currentOrder && currentOrder.id) ? 'block' : 'none';
        if (!hasItems) setPaymentExpanded(false);
        ensureRovingDefault(document.getElementById('bottomControlsPanel'), null, false);
    }

    function setPaymentExpanded(expanded) {
        const body    = document.getElementById('paymentBody');
        const chevron = document.getElementById('paymentChevron');
        if (!body) return;
        body.style.display = expanded ? 'block' : 'none';
        if (chevron) chevron.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
        ensureRovingDefault(document.getElementById('bottomControlsPanel'), null, false);
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
        ensureRovingDefault(document.getElementById('customerPanel'), null, false);
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
        // Split's own fields need the vertical room — shrink the method
        // icons down so the Hold/Pay row below never gets pushed off-screen.
        document.getElementById('paymentBody').classList.toggle('split-active', method === 'split');
        if (method !== 'cash') document.getElementById('changeDisplay').textContent = 'Rs. 0.00';
        if (method === 'split') updateSplitTotal();
        const _payLabels  = { cash: 'Cash', card: 'Card', bank_transfer: 'Bank', pickme: 'PickMe', uber: 'Uber', split: 'Split' };
        const _paySummary = document.getElementById('paymentMethodSummary');
        if (_paySummary) _paySummary.textContent = (_payLabels[method] || method);
        ensureRovingDefault(document.getElementById('bottomControlsPanel'), null, false);
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

        // PickMe/Uber orders are delivery orders with no physical token handed
        // out at the counter, so they're exempt from the token requirement.
        const tokenExempt = selectedPaymentMethod === 'pickme' || selectedPaymentMethod === 'uber';

        if (!currentOrder.token_number && !tokenExempt) {
            toast('Enter the token number before completing the bill', 'error');
            document.getElementById('tokenNumberInput').focus();
            return;
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
            // "split" is a UI-only concept — the bill was paid via a mix of
            // methods, which the backend records as payment_method "mixed"
            // (the split_method*/split_amount* fields below carry the detail).
            payment_method: selectedPaymentMethod === 'split' ? 'mixed' : selectedPaymentMethod,
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
            toast('Payment received!', 'success');
        } else {
            toast(data.message || 'Payment failed', 'error');
        }
    }

    function showPaidBill(d) {
        const methodLabel = { cash:'Cash', card:'Card', bank_transfer:'Bank Transfer', mixed:'Mixed', pickme:'PickMe', uber:'Uber' };
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-GB') + ', ' + now.toLocaleTimeString('en-GB');

        // ── Update these values to match your restaurant ──
        const CO_NAME    = "Cafe' Kdj - BBQ";
        const CO_TAGLINE = 'Fusion Food Court';
        const CO_CONTACT = '07777-04555';
        const CO_ADDRESS = '#9, Galle Road, Dehiwala';

        const itemRows = d.items.map(function(i) {
            const isFreeItem = (i.discount_percent || 0) >= 100;
            const discLabel = isFreeItem ? ' (FREE GIFT)' : (i.discount_percent > 0 ? ' (-' + i.discount_percent + '%)' : '');
            const amountCell = isFreeItem
                ? '<span style="text-decoration:line-through;">Rs.' + (i.unit_price * i.quantity).toFixed(2) + '</span> <strong>FREE</strong>'
                : 'Rs.' + i.subtotal.toFixed(2);
            return '<tr>'
                + '<td style="padding:3px 0; vertical-align:top; width:62%;">' + escapeHtml(i.product_name)
                + '<br><span style="font-size:10px;">1 x Rs.' + i.unit_price.toFixed(2) + discLabel + '</span></td>'
                + '<td style="text-align:center; padding:3px 0; vertical-align:top; width:10%;">' + i.quantity + '</td>'
                + '<td style="text-align:right; padding:3px 0; vertical-align:top; width:28%;">' + amountCell + '</td>'
                + '</tr>';
        }).join('');

        // ── HEADER: Logo + Company Details ──
        const html =
            '<div style="text-align:center; padding-bottom:8px;">'
            + '<img src="/images/KDJ_logo.png" style="max-width:150px; max-height:150px; margin-bottom:6px; display:block; margin-left:auto; margin-right:auto;" />'
            + '<div style="font-size:14px; letter-spacing:1px; color:#000; font-weight:bold;">' + CO_NAME + '</div>'
            + '<div style="font-size:11px; color:#000;">' + CO_TAGLINE + '</div>'
            + '<div style="font-size:11px; color:#000;">' + CO_ADDRESS + '</div>'
            + '<div style="font-size:11px; color:#000;">' + CO_CONTACT + '</div>'
            + '</div>'

            // ── RECEIPT METADATA ──
            + '<div style="border-top:2px solid #000; border-bottom:2px solid #000; padding:6px 0; margin-bottom:8px;">'
            + '<div style="text-align:center; font-size:13px; letter-spacing:3px; color:#000; margin-bottom:5px;">RECEIPT</div>'
            + '<table width="100%" cellspacing="0" cellpadding="2" style="font-size:11px; color:#000; width:100%; table-layout:fixed;">'
            + (d.customer_name  ? '<tr><td style="width:35%;">Customer</td><td style="text-align:right; width:65%;">' + escapeHtml(d.customer_name) + '</td></tr>' : '')
            + (d.customer_phone ? '<tr><td>Phone</td><td style="text-align:right;">' + d.customer_phone + '</td></tr>' : '')
            + '<tr><td style="width:35%;">Date</td><td style="text-align:right; width:65%;">' + dateStr + '</td></tr>'
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

            // ── TOKEN NUMBER (shown after the total, not with the order metadata) ──
            + (d.token_number
                ? '<div style="text-align:center; border-top:2px dashed #000; border-bottom:2px dashed #000; padding:8px 0; margin-top:8px;">'
                    + '<div style="font-size:11px; letter-spacing:3px; color:#000;">TOKEN NUMBER</div>'
                    + '<div style="font-size:48px; font-weight:900; color:#000; line-height:1.2;">#' + String(d.token_number).padStart(2, '0') + '</div>'
                    + '</div>'
                : '')

            // ── FOOTER ──
            + '<div style="text-align:center; font-size:11px; margin-top:8px; color:#000; border-top:1px dashed #000; padding-top:6px;">Thank you for dining with us!<br>We look forward to seeing you again.<br>Powered By JAAN Network (PVT) Ltd</div>';

        currentBillContent = html;

        // One "Pay" action produces two printouts: the KOT for the kitchen
        // (only if there are actual kitchen items on this bill) and the bill
        // for the customer. Both go through a single print job (one dialog,
        // one click) with a page-break between them so the printer cuts
        // between the two instead of stapling them into one receipt — see
        // printReceipt() for how the page break is inserted.
        const printJobs = [];
        if (Array.isArray(d.kot_items) && d.kot_items.length > 0) {
            printJobs.push(buildTokenHtml({
                token_number: d.token_number,
                order_number: d.order_number,
                payment_method: d.payment_method,
                items: d.kot_items,
            }));
        }
        printJobs.push(html);
        printReceipt(printJobs);
        resetOrder();
    }

    // ═══════════════════════════════════════════
    // TOKEN (kitchen ticket) — built and printed automatically as part of
    // Pay (see showPaidBill()); there's no standalone print action anymore.
    // ═══════════════════════════════════════════

    function buildTokenHtml(data) {
        const deliveryLabels = { pickme: 'PICKME DELIVERY', uber: 'UBER DELIVERY' };
        const heading = data.token_number
            ? '#' + String(data.token_number).padStart(2, '0')
            : (deliveryLabels[data.payment_method] || 'NO TOKEN');

        return '<div style="text-align:center;">'
            + '<img src="/images/KDJ_logo.png" style="max-width:70px; max-height:70px; margin-bottom:4px; display:inline-block;" />'
            + '</div>'
            + '<div style="text-align:center; font-weight:900; font-size:' + (data.token_number ? '32px' : '20px') + '; border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:10px; color:#000;">' + heading + '</div>'
            + '<div style="font-size:13px; font-weight:800; color:#000;">Order: ' + data.order_number + '</div>'
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

    function printBillContent() {
        printReceipt(currentBillContent);
    }

    function openFreeItemModal() {
        if (!currentOrder || !currentOrder.id) return;

        const select = document.getElementById('freeItemProduct');
        const productOptions = allProducts.map(function(p) {
            return '<option value="product:' + p.id + '" data-name="' + escapeHtml(p.name) + '">' + escapeHtml(p.name) + ' (Rs. ' + p.price.toFixed(2) + ')</option>';
        }).join('');
        // Raw/included items aren't sellable menu Products, so they have no
        // retail price to show — the unit (g/ml/pcs) is the useful bit here.
        const ingredientOptions = allIngredients.map(function(i) {
            return '<option value="ingredient:' + i.id + '" data-name="' + escapeHtml(i.name) + '" data-unit="' + escapeHtml(i.unit) + '">' + escapeHtml(i.name) + ' (' + escapeHtml(i.unit) + ')</option>';
        }).join('');

        select.innerHTML = '<option value="">Select an item…</option>'
            + (productOptions ? '<optgroup label="Menu Items">' + productOptions + '</optgroup>' : '')
            + (ingredientOptions ? '<optgroup label="Included Items">' + ingredientOptions + '</optgroup>' : '');
        document.getElementById('freeItemQty').value = 1;
        document.getElementById('freeItemQtyLabel').textContent = 'Quantity';

        openModal('freeItemModal');
    }

    // Included items are measured, not counted — once one is picked, swap
    // the generic "Quantity" label for "Quantity (unit)" so the cashier
    // knows what number to type (e.g. 250 for ml, not "how many").
    function onFreeItemSelectionChange() {
        const select = document.getElementById('freeItemProduct');
        const opt = select.options[select.selectedIndex];
        const unit = opt ? opt.dataset.unit : null;
        document.getElementById('freeItemQtyLabel').textContent = unit ? ('Quantity (' + unit + ')') : 'Quantity';
    }

    async function submitFreeItem() {
        const select = document.getElementById('freeItemProduct');
        const qty = Math.max(1, parseInt(document.getElementById('freeItemQty').value) || 1);

        if (!select.value) {
            toast('Select an item to give for free', 'error');
            return;
        }
        if (!currentOrder || !currentOrder.id) return;

        const [type, idStr] = select.value.split(':');
        const payload = { quantity: qty, is_free: true };
        if (type === 'ingredient') payload.ingredient_id = parseInt(idStr);
        else payload.product_id = parseInt(idStr);

        try {
            const res = await fetch('{{ route("pos.item.add", ":id") }}'.replace(':id', currentOrder.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!data.success) {
                toast(data.message || 'Failed to add free item', 'error');
                return;
            }

            closeModal('freeItemModal');
            await syncOrder();
            renderBill();
            const name = select.options[select.selectedIndex].dataset.name || 'Item';
            toast(name + ' added as a free item', 'success');
        } catch (e) {
            console.error('Add free item error:', e);
            toast('Failed to add free item', 'error');
        }
    }

    function closeCurrentOrder() {
        if (!currentOrder || !currentOrder.id) return;
        document.getElementById('discardReasonInput').value = '';
        openModal('discardReasonModal');
    }

    async function submitDiscardOrder() {
        if (!currentOrder || !currentOrder.id) return;

        const reason = document.getElementById('discardReasonInput').value.trim();
        if (!reason) {
            toast('Please enter a reason for discarding this bill', 'error');
            return;
        }

        // Call backend to cancel the order
        try {
            const res = await fetch('{{ route("pos.order.cancel", ":id") }}'.replace(':id', currentOrder.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ reason: reason })
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                toast(data.message || 'Failed to discard bill', 'error');
                return;
            }
        } catch (e) {
            console.error('Discard order error:', e);
            toast('Error discarding bill', 'error');
            return;
        }

        // Restore stock cache for items being discarded
        if (currentOrder && currentOrder.items) {
            currentOrder.items.forEach(function(item) {
                if (item.product_id) {
                    adjustStock(item.product_id, -item.quantity);
                }
            });
        }
        closeModal('discardReasonModal');
        resetOrder();
        renderProducts();
        toast('Bill discarded', 'success');
    }

    // Parks the current bill (status -> hold) so it shows up as pending on the
    // Sales Report page and can be brought back later via its "Resume & Pay"
    // link. Unlike discard, the items stay reserved — the cached product
    // stock counts are intentionally left untouched.
    async function holdCurrentOrder() {
        if (!currentOrder || !currentOrder.id) return;

        if (!currentOrder.items || currentOrder.items.length === 0) {
            toast('Add at least one item before holding this bill', 'error');
            return;
        }

        try {
            const res = await fetch('{{ route("pos.order.hold", ":id") }}'.replace(':id', currentOrder.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({})
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                toast(data.message || 'Failed to hold bill', 'error');
                return;
            }
        } catch (e) {
            console.error('Hold order error:', e);
            toast('Error holding bill', 'error');
            return;
        }

        resetOrder();
        renderProducts();
        toast('Bill held — resume it anytime from Sales Report', 'success');
    }

    // ═══════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════

    function resetOrder() {
        currentOrder = null;
        selectedPaymentMethod = 'cash';

        const billEl = document.getElementById('billItems');
        billEl.style.display = 'block';
        billEl.innerHTML = '<div style="text-align:center; padding:48px 0; color:#cbd5e1;"><i class="fas fa-utensils" style="font-size:36px; margin-bottom:12px; display:block;"></i><p style="font-size:13px; margin:0;">Tap New Order to begin</p></div>';
        document.getElementById('selectedTokenLabel').innerHTML = '<i class="fas fa-arrow-left" style="font-size:11px; margin-right:4px;"></i>Tap New Order to begin';
        document.getElementById('customerInfoToggle').style.display     = 'none';
        document.getElementById('customerInfoSection').style.display    = 'none';
        document.getElementById('activeOrderBanner').style.display      = 'none';
        document.getElementById('tokenNumberRow').style.display         = 'none';
        document.getElementById('tokenNumberInput').value               = '';
        document.getElementById('paymentToggle').style.display          = 'none';
        document.getElementById('paymentSection').style.display         = 'none';
        document.getElementById('waiterPayRow').style.display           = 'none';
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
        document.getElementById('paymentBody').classList.remove('split-active');

        // Hand keyboard focus back to search so a cashier can start the next
        // bill (typing a product name auto-creates a new order) without
        // reaching for the mouse.
        const search = document.getElementById('searchInput');
        if (search) search.focus();
    }

    // Accepts either a single receipt's HTML, or an array of receipts that
    // should all print as ONE job — one popup, one print-dialog confirmation
    // — with a page break between each so the printer still cuts the paper
    // between them instead of running them together on one strip.
    function printReceipt(htmlOrPages) {
        const pages = Array.isArray(htmlOrPages) ? htmlOrPages : [htmlOrPages];
        const body = pages.map(function(pageHtml, idx) {
            const isLast = idx === pages.length - 1;
            return '<div' + (isLast ? '' : ' style="page-break-after: always;"') + '>' + pageHtml + '</div>';
        }).join('');

        const w = window.open('', '', 'width=400,height=700,toolbar=0,menubar=0,scrollbars=1');
        w.document.write(
            '<!DOCTYPE html><html><head><style>'
            // 80mm = a standard 3" thermal receipt roll; keep the side margins
            // tight so the printable area actually uses the paper width instead
            // of wasting it, while still leaving enough room to avoid clipping
            // on printers whose own driver reserves a sliver of its own.
            + '@page { size: 80mm auto; margin: 2mm 3mm; }'
            + '* { box-sizing: border-box; font-weight: bold !important; }'
            + 'body { font-family: \'Courier New\', monospace; width: 100%; margin: 0; padding: 0; font-size: 12px; }'
            + 'table { width: 100%; border-collapse: collapse; table-layout: fixed; }'
            + 'td, th { word-break: break-word; overflow-wrap: break-word; }'
            + '</style></head><body>' + body + '</body></html>'
        );
        w.document.close();
        w.focus();

        // Printing immediately after document.write() can fire before the logo
        // <img> has actually loaded, so the printed/PDF output shows a blank
        // space where the logo should be. Wait for every image in the popup to
        // finish loading (or fail) first, with a hard timeout so a bad image
        // URL can never block printing altogether.
        let printed = false;
        const doPrint = function() {
            if (printed) return;
            printed = true;
            w.print();
            setTimeout(function() { w.close(); }, 1200);
        };
        const images = w.document.images;
        if (images.length === 0) {
            doPrint();
        } else {
            let pending = images.length;
            const onOneDone = function() {
                pending--;
                if (pending <= 0) doPrint();
            };
            Array.prototype.forEach.call(images, function(img) {
                if (img.complete) {
                    onOneDone();
                } else {
                    img.addEventListener('load', onOneDone);
                    img.addEventListener('error', onOneDone);
                }
            });
            setTimeout(doPrint, 1500); // safety net
        }
    }

    function openModal(id) {
        const overlay = document.getElementById(id);
        lastFocusedBeforeModal = document.activeElement;
        overlay.classList.add('open');
        const box = overlay.querySelector('.modal-box');
        // Land on the first fillable field so a cashier can start typing right
        // away; only fall back to a button/link when the modal has none (e.g.
        // the "no active shift" prompt).
        const field = box.querySelector('input, textarea, select');
        const target = field || box.querySelector('button, a[href]');
        if (target) target.focus();
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
        if (lastFocusedBeforeModal && document.body.contains(lastFocusedBeforeModal)) {
            lastFocusedBeforeModal.focus();
        }
        lastFocusedBeforeModal = null;
    }

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

        // ── Search box: Enter quick-adds when the filter narrowed to one match.
        // Moving into the product grid itself is Tab's job, not an arrow key's —
        // this panel stays self-contained. ──
        document.getElementById('searchInput').addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const visible = offersMode ? allOffers : allProducts.filter(function(p) {
                return p.is_unlimited_stock || getStock(p.id) > 0;
            });
            if (visible.length === 1) {
                const only = visible[0];
                if (offersMode) addOfferToOrder(only.id, only.name);
                else addProductToOrder(only.id, only.name, only.price);
            }
        });

        // ── Product/offer grid panel: arrow keys move the roving card, Enter/Space adds ──
        registerRovingSync(document.getElementById('productsContainer'));
        document.getElementById('productsContainer').addEventListener('keydown', function(e) {
            const card = e.target.closest('.product-card');
            if (!card || card.getAttribute('aria-disabled') === 'true') return;

            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                // A card's onclick may open a modal (e.g. the Include Items
                // quantity prompt). Without stopping propagation here, this
                // same keydown keeps bubbling to the document-level modal
                // handler, which sees a now-open modal and a non-button
                // target and immediately "submits" it — the modal would
                // flash open and close before you could type a quantity.
                e.stopPropagation();
                card.click();
                return;
            }

            const cards = rovingItems(this);
            const idx = cards.indexOf(card);
            if (idx === -1) return;
            const cols = gridColumnCount(cards);
            let nextIdx;
            if (e.key === 'ArrowRight') nextIdx = idx + 1;
            else if (e.key === 'ArrowLeft') nextIdx = idx - 1;
            else if (e.key === 'ArrowDown') nextIdx = idx + cols;
            else if (e.key === 'ArrowUp') nextIdx = idx - cols;
            else if (e.key === 'Home') nextIdx = 0;
            else if (e.key === 'End') nextIdx = cards.length - 1;
            else return;

            e.preventDefault();
            if (cards[nextIdx]) cards[nextIdx].focus();
        });

        // ── Categories / order header / customer / bottom-controls panels: a
        // simple list of controls each — the generic linear roving handler
        // covers all of them. ──
        registerLinearRoving(document.getElementById('categoriesContainer'));
        registerLinearRoving(document.getElementById('orderHeaderPanel'));
        registerLinearRoving(document.getElementById('customerPanel'));
        registerLinearRoving(document.getElementById('bottomControlsPanel'));

        // ── Bill items panel: each PRODUCT is its own Tab stop (Tab moves
        // product-to-product, defaulting to its qty control), so the roving
        // sync here is scoped per-card rather than to the whole panel — a
        // focused control only affects tabindex within its own card.
        document.getElementById('billItems').addEventListener('focusin', function(e) {
            const control = e.target.closest('[data-role]');
            const card = control && control.closest('.bill-item-card');
            if (!card) return;
            rovingItems(card).forEach(function(el) { el.setAttribute('tabindex', el === control ? '0' : '-1'); });
        });

        // Up/down always adjusts that product's quantity, whichever of its
        // controls currently has focus — the fast path a cashier reaches for
        // most. Left/right cycles between dec/qty/inc/%off/remove within the
        // same product (the qty <input>'s own left/right stays native, for
        // the text caret).
        document.getElementById('billItems').addEventListener('keydown', function(e) {
            const control = e.target.closest('[data-role]');
            if (!control) return;
            const card = control.closest('.bill-item-card');
            if (!card) return;

            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                e.preventDefault();
                const itemId = card.dataset.itemId;
                if (!itemId || itemId.indexOf('tmp-') === 0) return; // no server id yet
                if (e.key === 'ArrowUp') increaseQty(parseInt(itemId, 10));
                else decreaseQty(parseInt(itemId, 10));
                return;
            }

            if (control.tagName !== 'INPUT' && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
                e.preventDefault();
                const ROLE_ORDER = ['dec', 'qty', 'inc', 'disc', 'remove'];
                const dir = e.key === 'ArrowRight' ? 1 : -1;
                for (let i = ROLE_ORDER.indexOf(control.dataset.role) + dir; i >= 0 && i < ROLE_ORDER.length; i += dir) {
                    const target = card.querySelector('[data-role="' + ROLE_ORDER[i] + '"]');
                    if (target && !target.disabled) { target.focus(); break; }
                }
            }
        });

        // ── Modal keyboard behavior: Escape closes, Enter submits, Tab is trapped inside ──
        document.addEventListener('keydown', function(e) {
            const openOverlay = document.querySelector('.modal-overlay.open');
            if (!openOverlay) return;

            if (e.key === 'Escape') {
                e.preventDefault();
                closeModal(openOverlay.id);
                return;
            }

            const tag = e.target.tagName;
            if (e.key === 'Enter' && tag !== 'TEXTAREA' && tag !== 'BUTTON' && tag !== 'A') {
                const primary = openOverlay.querySelector('[data-primary="true"]');
                if (primary) {
                    e.preventDefault();
                    primary.click();
                    return;
                }
            }

            if (e.key === 'Tab') {
                const box = openOverlay.querySelector('.modal-box');
                const focusables = Array.from(box.querySelectorAll('input, textarea, select, button, a[href]'))
                    .filter(function(el) { return !el.disabled && el.offsetParent !== null; });
                if (!focusables.length) return;
                const first = focusables[0];
                const last = focusables[focusables.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });

        // ── Global function-key shortcuts for a mouse-free cashier workflow ──
        document.addEventListener('keydown', function(e) {
            if (document.querySelector('.modal-overlay.open')) return; // Escape/Enter/Tab handled above
            if (e.key === 'F2') {
                e.preventDefault();
                createNewOrder();
            } else if (e.key === 'F3') {
                e.preventDefault();
                const search = document.getElementById('searchInput');
                search.focus();
                search.select();
            } else if (e.key === 'F4') {
                e.preventDefault();
                holdCurrentOrder();
            } else if (e.key === 'F8') {
                e.preventDefault();
                closeCurrentOrder();
            } else if (e.key === 'F9') {
                e.preventDefault();
                initiatePayment();
            }
        });

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
                if (e.target === overlay) closeModal(overlay.id);
            });
        });
    }

    window.addEventListener('load', initPos);

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
