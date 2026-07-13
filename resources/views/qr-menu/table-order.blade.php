<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suasa Family Restaurant - Table {{ $table->table_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; min-height: 100vh; }

        .app-container { display: flex; flex-direction: column; height: 100vh; }

        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 20px 16px;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; }
        .header-left p { font-size: 13px; opacity: 0.9; }

        .table-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            padding: 20px 16px;
        }

        .categories {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            margin-bottom: 24px;
            padding-bottom: 8px;
            scroll-behavior: smooth;
        }

        .categories::-webkit-scrollbar { height: 4px; }
        .categories::-webkit-scrollbar-track { background: transparent; }
        .categories::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }

        .category-btn {
            white-space: nowrap;
            padding: 10px 20px;
            border-radius: 25px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #64748b;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .category-btn:hover { border-color: #dc2626; color: #dc2626; }
        .category-btn.active { background: #dc2626; color: white; border-color: #dc2626; }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .product-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .product-card:hover {
            border-color: #dc2626;
            box-shadow: 0 12px 24px rgba(220, 38, 38, 0.15);
            transform: translateY(-4px);
        }

        .product-card:active { transform: scale(0.98); }

        .product-image {
            width: 100%;
            height: 120px;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 32px;
        }

        .product-image img { width: 100%; height: 100%; object-fit: cover; }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .product-name {
            font-weight: 700;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 16px;
            font-weight: 900;
            color: #dc2626;
        }

        .cart-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 2px solid #e2e8f0;
            padding: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            z-index: 40;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
        }

        .cart-footer.hidden { display: none; }

        .cart-info {
            flex: 1;
        }

        .cart-count {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }

        .cart-total {
            font-size: 20px;
            font-weight: 900;
            color: #dc2626;
        }

        .order-btn {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }

        .order-btn:active { transform: scale(0.98); }

        .modal { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 60; align-items: center; justify-content: center; }
        .modal.open { display: flex; }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 28px;
            max-width: 420px;
            width: 92%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 64px rgba(0,0,0,0.2);
        }

        .modal-header {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            display: block;
            margin-bottom: 6px;
        }

        .input-field {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .input-field:focus { border-color: #dc2626; }

        .order-summary {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 8px;
            color: #64748b;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            font-size: 16px;
            color: #dc2626;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-primary {
            flex: 1;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary:hover { transform: translateY(-2px); }

        .btn-secondary {
            flex: 1;
            background: #e2e8f0;
            color: #374151;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary:hover { background: #cbd5e1; }

        .success-state {
            text-align: center;
            padding: 32px 0;
        }

        .success-icon {
            font-size: 48px;
            color: #16a34a;
            margin-bottom: 16px;
        }

        .success-title {
            font-size: 18px;
            font-weight: 800;
            color: #166534;
            margin-bottom: 8px;
        }

        .success-text {
            font-size: 13px;
            color: #166534;
        }

        @media (max-width: 768px) {
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
            .header-content { flex-direction: column; gap: 12px; }
            .header-left h1 { font-size: 24px; }
        }
    </style>
</head>
<body>

<div class="app-container">
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <h1>🍽️ Suasa Family Restaurant</h1>
                <p>Order from your table</p>
            </div>
            <div class="table-badge">
                <i class="fas fa-chair"></i>
                Table {{ $table->table_number }}
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Categories -->
        <div class="categories" id="categoriesContainer">
            <button class="category-btn active" data-category="0" onclick="filterProducts(0, this)">
                All Items
            </button>
            @foreach($categories as $category)
                <button class="category-btn" data-category="{{ $category->id }}" onclick="filterProducts({{ $category->id }}, this)">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsContainer">
            @foreach($products as $product)
                <div class="product-card" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, '{{ $product->category_id }}')">
                    <div class="product-image">
                        @if($product->image)
                            <img src="/storage/{{ $product->image }}" alt="{{ $product->name }}">
                        @else
                            <i class="fas fa-utensils" style="color: #dc2626;"></i>
                        @endif
                    </div>
                    <div class="product-info">
                        <div class="product-name">{{ $product->name }}</div>
                        <div class="product-price">Rs. {{ number_format($product->price, 2) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Spacer -->
        <div style="height: 100px;"></div>
    </div>

    <!-- Cart Footer -->
    <div class="cart-footer hidden" id="cartFooter">
        <div class="cart-info">
            <div class="cart-count"><span id="cartItemCount">0</span> item<span id="itemsPlural">s</span></div>
            <div class="cart-total">Rs. <span id="cartTotalAmount">0.00</span></div>
        </div>
        <button class="order-btn" onclick="openCheckoutModal()">
            <i class="fas fa-shopping-cart"></i> Order
        </button>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal" id="checkoutModal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-clipboard-list" style="color: #dc2626; font-size: 24px;"></i>
            Complete Order - Suasa
        </div>

        <div id="checkoutForm">
            <div class="input-group">
                <label class="input-label">Your Name (Optional)</label>
                <input type="text" id="customerName" class="input-field" placeholder="Enter your name">
            </div>

            <div class="input-group">
                <label class="input-label">Phone Number (Optional)</label>
                <input type="tel" id="customerPhone" class="input-field" placeholder="Enter phone number">
            </div>

            <div class="order-summary">
                <div id="checkoutItems"></div>
                <div class="summary-total">
                    <span>Total:</span>
                    <span>Rs. <span id="checkoutTotal">0.00</span></span>
                </div>
            </div>

            <div class="modal-buttons">
                <button class="btn-secondary" onclick="closeCheckoutModal()">Cancel</button>
                <button class="btn-primary" onclick="submitOrder()">
                    <i class="fas fa-check" style="margin-right: 6px;"></i>Place Order
                </button>
            </div>
        </div>

        <div id="successState" class="success-state" style="display: none;">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <div class="success-title">Order Placed!</div>
            <div class="success-text">Your order has been sent to the kitchen. Thank you!</div>
        </div>
    </div>
</div>

<script>
    const tableId = {{ $table->id }};
    let cart = {};
    let allProducts = {!! json_encode($products) !!};

    function addToCart(productId, productName, price, categoryId) {
        if (!cart[productId]) {
            cart[productId] = {
                id: productId,
                name: productName,
                price: price,
                quantity: 0,
                categoryId: categoryId
            };
        }
        cart[productId].quantity++;
        updateCartUI();
    }

    function updateCartUI() {
        const items = Object.values(cart);
        const cartFooter = document.getElementById('cartFooter');

        if (items.length === 0) {
            cartFooter.classList.add('hidden');
            return;
        }

        cartFooter.classList.remove('hidden');

        const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
        const totalAmount = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        document.getElementById('cartItemCount').textContent = totalItems;
        document.getElementById('itemsPlural').textContent = totalItems === 1 ? '' : 's';
        document.getElementById('cartTotalAmount').textContent = totalAmount.toFixed(2);

        updateCheckoutSummary();
    }

    function updateCheckoutSummary() {
        const items = Object.values(cart);
        const checkoutItemsHtml = items.map(item =>
            `<div class="summary-item">
                <span>${item.name} × ${item.quantity}</span>
                <span style="font-weight: 700; color: #374151;">Rs. ${(item.price * item.quantity).toFixed(2)}</span>
            </div>`
        ).join('');

        document.getElementById('checkoutItems').innerHTML = checkoutItemsHtml;

        const total = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        document.getElementById('checkoutTotal').textContent = total.toFixed(2);
    }

    function openCheckoutModal() {
        updateCheckoutSummary();
        document.getElementById('checkoutModal').classList.add('open');
        document.getElementById('checkoutForm').style.display = 'block';
        document.getElementById('successState').style.display = 'none';
    }

    function closeCheckoutModal() {
        document.getElementById('checkoutModal').classList.remove('open');
        document.getElementById('customerName').value = '';
        document.getElementById('customerPhone').value = '';
    }

    async function submitOrder() {
        const items = Object.values(cart);
        if (items.length === 0) {
            alert('Please add items to your order');
            return;
        }

        const orderData = {
            customer_name: document.getElementById('customerName').value || null,
            customer_phone: document.getElementById('customerPhone').value || null,
            items: items.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                kitchen_notes: null
            }))
        };

        try {
            const response = await fetch(`/table/${tableId}/order/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (result.success) {
                document.getElementById('checkoutForm').style.display = 'none';
                document.getElementById('successState').style.display = 'block';

                const message = result.is_new_order
                    ? `Order #${result.order_number} placed successfully!`
                    : `Items added to Order #${result.order_number}!`;

                setTimeout(() => {
                    cart = {};
                    updateCartUI();
                    closeCheckoutModal();
                    alert(message);
                }, 2000);
            } else {
                alert('Error: ' + (result.message || 'Failed to place order'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error placing order: ' + error.message);
        }
    }

    function filterProducts(categoryId, btn) {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const productsContainer = document.getElementById('productsContainer');
        const filtered = categoryId === 0 ? allProducts : allProducts.filter(p => p.category_id == categoryId);

        productsContainer.innerHTML = filtered.map(product => `
            <div class="product-card" onclick="addToCart(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price}, ${product.category_id})">
                <div class="product-image">
                    ${product.image ? `<img src="/storage/${product.image}" alt="${product.name}">` : '<i class="fas fa-utensils" style="color: #dc2626; font-size: 32px;"></i>'}
                </div>
                <div class="product-info">
                    <div class="product-name">${product.name}</div>
                    <div class="product-price">Rs. ${parseFloat(product.price).toFixed(2)}</div>
                </div>
            </div>
        `).join('');
    }

    if (!document.querySelector('meta[name="csrf-token"]')) {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = '{{ csrf_token() }}';
        document.head.appendChild(meta);
    }
</script>

</body>
</html>
