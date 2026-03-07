<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Shopping Cart - FoodieHub';

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Get current table info from session
$current_table = null;
if (isset($_SESSION['table_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, table_number FROM tables WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['table_id']]);
        $current_table = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Table fetch error: " . $e->getMessage());
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
$hero_title = 'Your Shopping Cart';
$hero_subtitle = 'Review your items and proceed to checkout.';
$hero_image = '/restaurant_project/assets/images/cart-bg.jpeg';
$hero_cta_text = 'Proceed to Checkout';
$hero_cta_href = 'cart.php#cart-items-container';
include __DIR__ . '/../includes/hero.php';
?>

<!-- Main Cart Section -->
<section class="section cart-container">
    <div class="container">
        <!-- Cart Layout -->
        <div class="cart-wrapper">
            <!-- Left: Cart Items -->
            <div class="cart-items-section">
                <div id="cart-items-container">
                    <!-- Items loaded by JavaScript -->
                </div>
            </div>

            <!-- Right: Order Summary -->
            <div class="order-summary-section" id="summary-panel">
                <div class="summary-card">
                    <h2>Order Summary</h2>

                    <!-- Table Information -->
                    <?php if ($current_table): ?>
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                            <p style="margin: 0 0 0.3rem 0; font-size: 0.9rem; opacity: 0.9;">Table Number</p>
                            <h3 style="margin: 0; font-size: 1.8rem; font-weight: bold;"><?php echo htmlspecialchars($current_table['table_number']); ?></h3>
                        </div>
                    <?php endif; ?>

                    <div class="summary-breakdown">
                        <div class="summary-line">
                            <span>Subtotal:</span>
                            <span class="price" id="subtotal-price">Rs 0</span>
                        </div>
                        <div class="summary-line">
                            <span>Discount:</span>
                            <span class="price savings" id="discount-price">Rs 0</span>
                        </div>
                        <div class="summary-line">
                            <span>Tax (8%):</span>
                            <span class="price" id="tax-price">Rs 0</span>
                        </div>
                        <div class="summary-line total">
                            <span>Total:</span>
                            <span class="price-total" id="total-price">Rs 0</span>
                        </div>
                    </div>

                    <a href="checkout.php" id="checkout-btn" class="btn-checkout" style="display: none;">
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </a>
                    <button id="clear-btn" class="btn-clear" onclick="clearAllCart()" style="display: none;">
                        <i class="fas fa-trash-alt"></i> Clear Cart
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty Cart Message -->
        <div id="empty-state" class="empty-state" style="display: none;">
            <div class="empty-content">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your Cart is Empty</h2>
                <p>Start adding delicious items from our menu!</p>
                <a href="menu.php" class="btn-menu">
                    <i class="fas fa-utensils"></i> Browse Menu
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Continue Shopping Section -->
<section class="section" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
    <div class="container" style="text-align: center;">
        <h3 style="color: #333; margin-bottom: 1.5rem;">Find More Favorites</h3>
        <a href="menu.php" class="btn-continue">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    // Load cart on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadCart();
    });

    function loadCart() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const container = document.getElementById('cart-items-container');
        const summary = document.getElementById('summary-panel');
        const emptyState = document.getElementById('empty-state');
        const checkoutBtn = document.getElementById('checkout-btn');
        const clearBtn = document.getElementById('clear-btn');

        // Clear container
        container.innerHTML = '';

        // Check if cart is empty
        if (cart.length === 0) {
            summary.style.display = 'none';
            emptyState.style.display = 'block';
            updateCartBadge(); // Update cart count even when empty
            return;
        }

        // Show cart
        summary.style.display = 'block';
        emptyState.style.display = 'none';
        checkoutBtn.style.display = 'block';
        clearBtn.style.display = 'block';

        let subtotal = 0;
        let totalDiscount = 0;

        // Render each item
        cart.forEach((item, index) => {
            const discount = item.discount || 0;
            // TEMP: Force discount for testing
            const testDiscount = discount > 0 ? discount : 10; // Force 10% if no discount
            const discountAmount = (item.price * testDiscount) / 100;
            const priceAfterDiscount = item.price - discountAmount;
            const itemTotal = priceAfterDiscount * item.quantity;
            const itemDiscountTotal = discountAmount * item.quantity;

            subtotal += item.price * item.quantity;
            totalDiscount += itemDiscountTotal;

            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.innerHTML = `
                <div class="item-image">
                    <img src="../assets/images/${item.image || 'placeholder.jpg'}" 
                         alt="${item.name}" 
                         onerror="this.src='../assets/images/placeholder.jpg'">
                </div>
                <div class="item-details">
                    <h3>${item.name}</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        ${testDiscount > 0 ? 
                            `<p style="text-decoration: line-through; color: #999; font-size: 0.9rem;">Rs ${item.price.toLocaleString()}</p>
                             <p class="item-price" style="color: #28a745; font-weight: bold;">Rs ${priceAfterDiscount.toLocaleString()}</p>` :
                            `<p class="item-price">Rs ${item.price.toLocaleString()}</p>`
                        }
                        ${testDiscount > 0 ? `<span class="discount-badge">-${testDiscount.toFixed(0)}%</span>` : ''}
                    </div>
                </div>
                <div class="item-quantity">
                    <button class="qty-btn" onclick="updateQty(${index}, -1)">−</button>
                    <input type="number" value="${item.quantity}" readonly>
                    <button class="qty-btn" onclick="updateQty(${index}, 1)">+</button>
                </div>
                <div class="item-total">
                    <div>Rs ${itemTotal.toLocaleString()}</div>
                </div>
                <button class="btn-remove" onclick="removeItem(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(cartItem);
        });

        // Update summary
        const afterDiscount = subtotal - totalDiscount;
        const tax = afterDiscount * 0.08;
        const total = afterDiscount + tax;

        document.getElementById('subtotal-price').textContent = `Rs ${subtotal.toLocaleString()}`;
        document.getElementById('discount-price').textContent = `Rs ${totalDiscount.toLocaleString()}`;
        document.getElementById('tax-price').textContent = `Rs ${tax.toLocaleString()}`;
        document.getElementById('total-price').textContent = `Rs ${total.toLocaleString()}`;

        updateCartBadge();
    }

    function updateQty(index, change) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        if (cart[index]) {
            cart[index].quantity += change;
            if (cart[index].quantity <= 0) {
                cart.splice(index, 1);
            }
            localStorage.setItem('cart', JSON.stringify(cart));
            loadCart();
        }
    }

    function removeItem(index) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        cart.splice(index, 1);
        localStorage.setItem('cart', JSON.stringify(cart));
        loadCart();
        showAlert('Item removed', 'info');
    }

    function clearAllCart() {
        if (confirm('Clear your entire cart?')) {
            localStorage.removeItem('cart');
            loadCart();
            showAlert('Cart cleared', 'warning');
        }
    }

    function updateCartBadge() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const count = cart.reduce((sum, item) => sum + item.quantity, 0);
        const badge = document.getElementById('cart-count');
        if (badge) badge.textContent = count;
    }

    function showAlert(msg, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = msg;
        alert.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: ${type === 'warning' ? '#ff9800' : '#2196F3'};
            color: white;
            border-radius: 8px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }
</script>

<style>
    .cart-wrapper {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .cart-items-section {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    #cart-items-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .cart-item {
        display: grid;
        grid-template-columns: 100px 1fr auto auto auto;
        gap: 1.5rem;
        align-items: center;
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .cart-item:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }

    .item-image img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
    }

    .item-details {
        position: relative;
    }

    .item-details h3 {
        margin: 0;
        color: #333;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }

    .item-price {
        color: #667eea;
        font-weight: 600;
        font-size: 1rem;
        margin: 0;
    }

    .discount-badge {
        display: inline-block;
        background: #4caf50;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
        margin-top: 0.5rem;
    }

    .item-quantity {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #f5f5f5;
        padding: 0.5rem;
        border-radius: 8px;
    }

    .qty-btn {
        background: white;
        border: 1px solid #ddd;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1.1rem;
        transition: all 0.2s;
    }

    .qty-btn:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .item-quantity input {
        width: 50px;
        text-align: center;
        border: none;
        background: transparent;
        font-weight: bold;
        font-size: 1rem;
    }

    .item-total {
        text-align: right;
        min-width: 100px;
    }

    .item-total div {
        font-weight: 700;
        color: #667eea;
        font-size: 1.1rem;
    }

    .btn-remove {
        background: #ff5252;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.1rem;
        transition: all 0.3s;
    }

    .btn-remove:hover {
        background: #ff1744;
        transform: scale(1.1);
    }

    /* Order Summary */
    .order-summary-section {
        position: sticky;
        top: 100px;
        height: fit-content;
    }

    .summary-card {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }

    .summary-card h2 {
        margin-top: 0;
        color: #333;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
    }

    .summary-breakdown {
        margin-bottom: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    .summary-line {
        display: flex;
        justify-content: space-between;
        font-size: 0.95rem;
        color: #666;
    }

    .summary-line.total {
        font-size: 1.2rem;
        font-weight: bold;
        color: #333;
        padding-top: 0.8rem;
        border-top: 2px solid #f0f0f0;
        margin-top: 0.5rem;
    }

    .price {
        font-weight: 600;
        color: #333;
    }

    .price.savings {
        color: #4caf50;
    }

    .price-total {
        color: #667eea;
        font-weight: 700;
        font-size: 1.3rem;
    }

    .btn-checkout {
        display: block;
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-bottom: 0.8rem;
    }

    .btn-checkout:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    }

    .btn-clear {
        display: block;
        width: 100%;
        padding: 1rem;
        background: #ff5252;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-clear:hover {
        background: #ff1744;
        transform: translateY(-2px);
    }

    /* Empty State */
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
    }

    .empty-content {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 3rem;
        border-radius: 16px;
    }

    .empty-content i {
        font-size: 4rem;
        color: #667eea;
        margin-bottom: 1rem;
        opacity: 0.8;
    }

    .empty-content h2 {
        color: #333;
        margin-bottom: 0.5rem;
    }

    .empty-content p {
        color: #666;
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }

    .btn-menu {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-menu:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    }

    .btn-continue {
        display: inline-block;
        background: white;
        color: #667eea;
        padding: 0.8rem 2rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        border: 2px solid white;
        transition: all 0.3s;
    }

    .btn-continue:hover {
        background: transparent;
        color: white;
        border-color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .cart-wrapper {
            grid-template-columns: 1fr;
        }

        .cart-item {
            grid-template-columns: 80px 1fr;
        }

        .item-quantity,
        .item-total,
        .btn-remove {
            grid-column: 2;
            justify-self: start;
        }

        .order-summary-section {
            position: static;
        }

        .empty-content {
            padding: 2rem;
        }

        .empty-content i {
            font-size: 3rem;
        }
    }

    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
</style>