<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-badge {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 50;
        }
        .menu-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="menu-container text-white shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ config('app.name', 'Restaurant') }}</h1>
                    <p class="text-purple-100">Scan & Order</p>
                </div>
                <button id="cartBtn" class="relative bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-purple-50 transition flex items-center gap-2">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cartCount" class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm absolute -top-2 -right-2">0</span>
                    Cart
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Category Filter -->
        <div class="mb-8">
            <div class="flex gap-3 overflow-x-auto pb-4">
                <button class="category-btn px-6 py-2 rounded-full font-semibold whitespace-nowrap transition bg-purple-600 text-white" data-category="all">
                    All Items
                </button>
                @foreach($categories as $category)
                    <button class="category-btn px-6 py-2 rounded-full font-semibold whitespace-nowrap transition bg-white text-gray-700 hover:bg-purple-100" data-category="{{ $category->id }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-20" id="productsContainer">
            <!-- Products will be loaded here -->
        </div>
    </div>

    <!-- Cart Modal -->
    <div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-purple-800 text-white p-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold">Your Order</h2>
                <button id="closeCartBtn" class="text-2xl">&times;</button>
            </div>

            <div id="cartItems" class="p-6">
                <p class="text-gray-500 text-center py-8">Your cart is empty</p>
            </div>

            <div class="sticky bottom-0 bg-gray-50 border-t p-6 space-y-4">
                <div class="space-y-2">
                    <div class="flex justify-between text-lg">
                        <span>Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-purple-600">
                        <span>Total:</span>
                        <span id="total">$0.00</span>
                    </div>
                </div>
                <button id="checkoutBtn" class="w-full bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700 transition">
                    Proceed to Order
                </button>
                <button id="continueShopping" class="w-full bg-gray-200 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-300 transition">
                    Continue Shopping
                </button>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="bg-gradient-to-r from-green-600 to-green-800 text-white p-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold">Order Details</h2>
                <button id="closeCheckoutBtn" class="text-2xl">&times;</button>
            </div>

            <form id="checkoutForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Name *</label>
                    <input type="text" id="customerName" name="customer_name" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Phone Number *</label>
                    <input type="tel" id="customerPhone" name="customer_phone" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Order Type *</label>
                    <select id="orderType" name="order_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Select Order Type</option>
                        <option value="takeaway">Takeaway</option>
                        <option value="delivery">Delivery</option>
                        <option value="dine_in">Dine In</option>
                    </select>
                </div>

                <div id="summaryDiv" class="bg-gray-100 p-4 rounded-lg">
                    <h3 class="font-bold text-gray-800 mb-2">Order Summary</h3>
                    <div id="orderSummary" class="text-sm space-y-1"></div>
                    <div class="border-t mt-2 pt-2 font-bold text-gray-800">
                        <span>Total: </span>
                        <span id="checkoutTotal">$0.00</span>
                    </div>
                </div>

                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition">
                    Place Order
                </button>
            </form>
        </div>
    </div>

    <script>
        let cart = {};
        let products = {};
        let currentCategory = 'all';

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts('all');
            setupEventListeners();
        });

        function setupEventListeners() {
            // Category filter
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.category-btn').forEach(b => {
                        b.classList.remove('bg-purple-600', 'text-white');
                        b.classList.add('bg-white', 'text-gray-700', 'hover:bg-purple-100');
                    });
                    this.classList.remove('bg-white', 'text-gray-700', 'hover:bg-purple-100');
                    this.classList.add('bg-purple-600', 'text-white');
                    currentCategory = this.dataset.category;
                    loadProducts(currentCategory);
                });
            });

            // Cart buttons
            document.getElementById('cartBtn').addEventListener('click', openCart);
            document.getElementById('closeCartBtn').addEventListener('click', closeCart);
            document.getElementById('continueShopping').addEventListener('click', closeCart);
            document.getElementById('checkoutBtn').addEventListener('click', openCheckout);
            document.getElementById('closeCheckoutBtn').addEventListener('click', closeCheckout);
            document.getElementById('checkoutForm').addEventListener('submit', submitOrder);
        }

        function loadProducts(categoryId) {
            const url = categoryId === 'all'
                ? '/api/menu/category/all'
                : `/api/menu/category/${categoryId}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    displayProducts(data);
                    data.forEach(product => {
                        products[product.id] = product;
                    });
                })
                .catch(error => console.error('Error loading products:', error));
        }

        function displayProducts(productList) {
            const container = document.getElementById('productsContainer');
            container.innerHTML = '';

            if (productList.length === 0) {
                container.innerHTML = '<div class="col-span-full text-center py-12"><p class="text-gray-500 text-lg">No products available</p></div>';
                return;
            }

            productList.forEach(product => {
                const isOutOfStock = !product.is_unlimited_stock && product.quantity <= 0;
                const cartItem = cart[product.id];
                const quantity = cartItem ? cartItem.quantity : 0;

                const html = `
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                        ${product.image ? `<img src="/storage/${product.image}" alt="${product.name}" class="w-full h-48 object-cover">` : '<div class="w-full h-48 bg-gray-200 flex items-center justify-center"><i class="fas fa-image text-gray-400 text-4xl"></i></div>'}
                        <div class="p-4">
                            <h3 class="text-lg font-bold text-gray-800 mb-2">${product.name}</h3>
                            ${product.description ? `<p class="text-gray-600 text-sm mb-3">${product.description}</p>` : ''}
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-2xl font-bold text-purple-600">$${parseFloat(product.price).toFixed(2)}</span>
                                ${!product.is_unlimited_stock ? `<span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded">Stock: ${product.quantity}</span>` : ''}
                            </div>
                            ${isOutOfStock ?
                                '<div class="w-full bg-gray-300 text-gray-600 py-2 rounded-lg font-semibold text-center cursor-not-allowed">Out of Stock</div>'
                                :
                                `<div class="flex gap-2">
                                    <button class="flex-1 bg-purple-600 text-white py-2 rounded-lg font-semibold hover:bg-purple-700 transition" onclick="addToCart(${product.id})">
                                        <i class="fas fa-plus"></i> Add to Cart
                                    </button>
                                    ${quantity > 0 ? `
                                        <div class="flex items-center gap-2 bg-purple-100 px-3 rounded-lg">
                                            <button onclick="decrementCart(${product.id})" class="text-purple-600 hover:text-purple-800">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="font-bold text-purple-600">${quantity}</span>
                                            <button onclick="incrementCart(${product.id})" class="text-purple-600 hover:text-purple-800">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    ` : ''}
                                </div>`
                            }
                        </div>
                    </div>
                `;
                container.innerHTML += html;
            });
        }

        function addToCart(productId) {
            const product = products[productId];
            if (!product) return;

            if (!cart[productId]) {
                cart[productId] = {
                    name: product.name,
                    price: product.price,
                    quantity: 0
                };
            }
            cart[productId].quantity++;
            updateCartUI();
        }

        function incrementCart(productId) {
            if (cart[productId]) {
                cart[productId].quantity++;
                updateCartUI();
            }
        }

        function decrementCart(productId) {
            if (cart[productId]) {
                cart[productId].quantity--;
                if (cart[productId].quantity <= 0) {
                    delete cart[productId];
                }
                updateCartUI();
            }
        }

        function updateCartUI() {
            const count = Object.values(cart).reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cartCount').textContent = count;

            const subtotal = Object.values(cart).reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('total').textContent = '$' + subtotal.toFixed(2);
        }

        function openCart() {
            const cartItems = document.getElementById('cartItems');
            const items = Object.entries(cart);

            if (items.length === 0) {
                cartItems.innerHTML = '<p class="text-gray-500 text-center py-8">Your cart is empty</p>';
            } else {
                cartItems.innerHTML = items.map(([productId, item]) => `
                    <div class="flex justify-between items-center p-4 border-b hover:bg-gray-50">
                        <div>
                            <p class="font-semibold text-gray-800">${item.name}</p>
                            <p class="text-sm text-gray-600">$${item.price.toFixed(2)} each</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 bg-purple-100 px-3 py-1 rounded">
                                <button onclick="decrementCart(${productId})" class="text-purple-600 hover:text-purple-800 w-6 text-center">−</button>
                                <span class="font-bold text-purple-600 w-8 text-center">${item.quantity}</span>
                                <button onclick="incrementCart(${productId})" class="text-purple-600 hover:text-purple-800 w-6 text-center">+</button>
                            </div>
                            <button onclick="removeFromCart(${productId})" class="text-red-500 hover:text-red-700 w-8 text-center">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            }

            document.getElementById('cartModal').classList.remove('hidden');
            updateCartUI();
        }

        function removeFromCart(productId) {
            delete cart[productId];
            updateCartUI();
            openCart();
        }

        function closeCart() {
            document.getElementById('cartModal').classList.add('hidden');
        }

        function openCheckout() {
            if (Object.keys(cart).length === 0) {
                alert('Please add items to your cart first');
                return;
            }

            const summaryDiv = document.getElementById('orderSummary');
            const total = Object.values(cart).reduce((sum, item) => sum + (item.price * item.quantity), 0);

            summaryDiv.innerHTML = Object.entries(cart).map(([_, item]) =>
                `<div class="flex justify-between"><span>${item.name} x${item.quantity}</span><span>$${(item.price * item.quantity).toFixed(2)}</span></div>`
            ).join('');

            document.getElementById('checkoutTotal').textContent = '$' + total.toFixed(2);
            document.getElementById('cartModal').classList.add('hidden');
            document.getElementById('checkoutModal').classList.remove('hidden');
        }

        function closeCheckout() {
            document.getElementById('checkoutModal').classList.add('hidden');
        }

        function submitOrder(e) {
            e.preventDefault();

            const orderType = document.getElementById('orderType').value;
            const customerName = document.getElementById('customerName').value;
            const customerPhone = document.getElementById('customerPhone').value;

            if (!orderType || !customerName || !customerPhone) {
                alert('Please fill in all required fields');
                return;
            }

            // Create order via API
            fetch('/pos/order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    order_type: orderType,
                    customer_name: customerName,
                    customer_phone: customerPhone,
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const orderId = data.order_id;
                    addItemsToOrder(orderId);
                } else {
                    alert('Error creating order: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating order');
            });
        }

        function addItemsToOrder(orderId) {
            let completed = 0;
            const totalItems = Object.keys(cart).length;

            Object.entries(cart).forEach(([productId, item]) => {
                fetch(`/pos/order/${orderId}/item`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: item.quantity,
                    })
                })
                .then(response => response.json())
                .then(() => {
                    completed++;
                    if (completed === totalItems) {
                        showOrderSuccess(data.order_number || 'ORD-' + orderId);
                    }
                })
                .catch(error => console.error('Error adding item:', error));
            });
        }

        function showOrderSuccess(orderNumber) {
            alert(`Order placed successfully!\nOrder Number: ${orderNumber}\n\nYour order will be prepared shortly.`);
            cart = {};
            document.getElementById('checkoutModal').classList.add('hidden');
            document.getElementById('checkoutForm').reset();
            updateCartUI();
            loadProducts(currentCategory);
        }
    </script>

    <!-- CSRF Token for Laravel -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</body>
</html>
