<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Checkout - FoodieHub';

$errors = [];
$success = '';

// Handle QR code table parameter
if (isset($_GET['table']) && !empty($_GET['table'])) {
    $table_number = trim($_GET['table']);

    // Validate table exists and is active
    try {
        $stmt = $pdo->prepare("SELECT id, table_number FROM tables WHERE table_number = ? AND status = 'active'");
        $stmt->execute([$table_number]);
        $table = $stmt->fetch();

        if ($table) {
            $_SESSION['table_id'] = $table['id'];
            $_SESSION['table_number'] = $table['table_number'];
        }
    } catch (PDOException $e) {
        // Log error but continue
        error_log("Table validation error: " . $e->getMessage());
    }
}

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

// Redirect if no table selected (only table orders allowed)
if (!$current_table) {
    header('Location: index.php#qr-scanner-section');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an AJAX request
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $cart_data = json_decode($_POST['cart_data'] ?? '[]', true);

    // Validation
    if (empty($customer_name)) {
        $errors[] = 'Customer name is required';
    }

    if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required';
    }

    if (empty($customer_phone)) {
        $errors[] = 'Phone number is required';
    }

    if (empty($cart_data)) {
        $errors[] = 'Your cart is empty';
    }

    if (empty($errors)) {
        // Calculate total with discounts
        $subtotal = 0;
        $total_discount = 0;

        foreach ($cart_data as $item) {
            $item_price = $item['price'] * $item['quantity'];
            $discount = isset($item['discount']) ? $item['discount'] : 0;
            $discount_amount = $item_price * ($discount / 100);

            $subtotal += $item_price;
            $total_discount += $discount_amount;
        }

        // Calculate after discount and with tax
        $after_discount = $subtotal - $total_discount;
        $total_price = $after_discount * 1.08; // Add 8% tax

        try {
            // Insert order into database
            $table_id = isset($_SESSION['table_id']) ? $_SESSION['table_id'] : null;
            $stmt = $pdo->prepare("INSERT INTO orders (table_id, customer_name, customer_email, customer_phone, customer_address, items, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $table_id,
                $customer_name,
                $customer_email,
                $customer_phone,
                null, // No delivery address for table orders
                json_encode($cart_data),
                $total_price
            ]);

            $order_id = $pdo->lastInsertId();

            if ($isAjax) {
                // Return JSON response for AJAX
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'order_id' => $order_id,
                    'total' => $total_price,
                    'message' => 'Order placed successfully!'
                ]);
                exit;
            } else {
                $success = 'Order placed successfully! Order ID: ' . $order_id;
            }
        } catch (PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $error_message
                ]);
                exit;
            } else {
                $errors[] = $error_message;
            }
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => implode(', ', $errors)
            ]);
            exit;
        }
    }
}

?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
$hero_title = 'Checkout';
$hero_subtitle = "Complete your order and we'll prepare it right away.";
$hero_image = '/restaurant_project/assets/images/checkout-bg.jpeg';
$hero_cta_text = 'Place Your Order';
$hero_cta_href = 'checkout.php#checkout-form';
include __DIR__ . '/../includes/hero.php';
?>

<!-- Checkout Content -->
<section class="section">
    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <br><br>
                <a href="index.php" class="bfucktn">Return to Home</a>
            </div>
        <?php else: ?>
            <div class="checkout-container">
                <!-- Order Summary -->
                <div class="order-summary">
                    <h3>Order Summary</h3>

                    <!-- Table Information -->
                    <?php if ($current_table): ?>
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                            <p style="margin: 0 0 0.3rem 0; font-size: 0.9rem; opacity: 0.9;">Table Number</p>
                            <h3 style="margin: 0; font-size: 1.8rem; font-weight: bold;"><?php echo htmlspecialchars($current_table['table_number']); ?></h3>
                        </div>
                    <?php endif; ?>

                    <div class="order-items" id="order-items">
                        <p>Loading cart items...</p>
                    </div>
                    <div class="order-total">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="subtotal">Rs 0</span>
                        </div>
                        <div class="summary-row">
                            <span>Discount:</span>
                            <span id="discount">Rs 0</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (8%):</span>
                            <span id="tax">Rs 0</span>
                        </div>
                        <div class="summary-row total-row">
                            <span>Total:</span>
                            <span id="total">Rs 0</span>
                        </div>
                    </div>

                </div>

                <!-- Customer Information Form -->
                <div class="checkout-form">
                    <h3>Customer Information</h3>
                    <form id="checkout-form">
                        <div class="form-group">
                            <label for="customer_name">Full Name *</label>
                            <input type="text" id="customer_name" name="customer_name" required value="Test Customer">
                        </div>

                        <div class="form-group">
                            <label for="customer_email">Email Address *</label>
                            <input type="email" id="customer_email" name="customer_email" required value="test@example.com">
                        </div>

                        <div class="form-group">
                            <label for="customer_phone">Phone Number *</label>
                            <input type="tel" id="customer_phone" name="customer_phone" required value="1234567890">
                        </div>

                        <button type="submit" class="btn place-order-btn" id="place-order-btn" style="width: 100%;">
                            <i class="fas fa-credit-card"></i> Place Order
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Order Success Modal -->
<div id="order-success-modal" class="modal">
    <div class="modal-content success-modal">
        <div class="modal-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Order Confirmed!</h2>
            <p>Your order has been placed successfully</p>
        </div>
        <div class="modal-body">
            <div class="order-details">
                <div class="detail-row">
                    <span>Order ID:</span>
                    <strong id="modal-order-id">#0000</strong>
                </div>
                <div class="detail-row">
                    <span>Estimated Preparation Time:</span>
                    <strong>15-20 minutes</strong>
                </div>
                <div class="detail-row">
                    <span>Total Amount:</span>
                    <strong id="modal-total-amount">Rs 0</strong>
                </div>
            </div>
            <div class="order-notice">
                <i class="fas fa-info-circle"></i>
                <p>You will receive an SMS confirmation with your order details. Your order will be served at your table.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeOrderModal()">Continue Shopping</button>
            <button class="btn" onclick="trackOrder()">Track Order</button>
        </div>
    </div>
</div>

<!-- Order Error Modal -->
<div id="order-error-modal" class="modal">
    <div class="modal-content error-modal">
        <div class="modal-header">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Order Failed</h2>
            <p>There was an error placing your order</p>
        </div>
        <div class="modal-body">
            <p id="error-message">Please try again or contact customer support.</p>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="closeErrorModal()">Try Again</button>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadCartForCheckout();

        // Handle form submission with AJAX
        const form = document.getElementById('checkout-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const cart = JSON.parse(localStorage.getItem('cart')) || [];
                if (cart.length === 0) {
                    showErrorModal('Your cart is empty!');
                    return;
                }

                // Get form data
                const formData = new URLSearchParams();
                formData.append('customer_name', document.getElementById('customer_name').value);
                formData.append('customer_email', document.getElementById('customer_email').value);
                formData.append('customer_phone', document.getElementById('customer_phone').value);
                formData.append('cart_data', JSON.stringify(cart));

                // Show loading state
                const submitBtn = document.getElementById('place-order-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;

                // Send AJAX request
                fetch('checkout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData.toString()
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Parsed JSON data:', data);
                        if (data.success) {
                            // Clear cart
                            localStorage.removeItem('cart');
                            updateCartCount();

                            // Show success modal
                            showOrderSuccessModal(data.order_id, data.total);
                        } else {
                            // Show error modal
                            showErrorModal(data.message || 'An error occurred while placing your order.');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        console.error('Error details:', error.message);
                        showErrorModal('Network error. Please check your connection and try again.');
                    })
                    .finally(() => {
                        // Reset button
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });
        }
    });

    function loadCartForCheckout() {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        // TEMP: Add test items if cart is empty for testing
        if (cart.length === 0) {
            cart = [{
                    id: 2,
                    name: 'Chicken Burger',
                    price: 999,
                    discount: 10,
                    image: 'burger1.jpg',
                    quantity: 1
                },
                {
                    id: 4,
                    name: 'Pasta Carbonara',
                    price: 1499,
                    discount: 5,
                    image: 'pasta1.jpg',
                    quantity: 1
                }
            ];
            localStorage.setItem('cart', JSON.stringify(cart));
            console.log('Test items added to cart for testing');
        }

        const orderItems = document.getElementById('order-items');
        const subtotalElement = document.getElementById('subtotal');
        const discountElement = document.getElementById('discount');
        const taxElement = document.getElementById('tax');
        const totalElement = document.getElementById('total');

        if (!orderItems) return;

        if (cart.length === 0) {
            orderItems.innerHTML = '<p>Your cart is empty. <a href="menu.php">Go back to menu</a></p>';
            const placeBtn = document.getElementById('place-order-btn');
            if (placeBtn) placeBtn.style.display = 'none';
            return;
        }

        let itemsHtml = '';
        let subtotal = 0;
        let totalDiscount = 0;

        cart.forEach(item => {
            const discount = item.discount || 0;
            // TEMP: Force discount for testing
            const testDiscount = discount > 0 ? discount : 10; // Force 10% if no discount
            const discountAmount = item.price * (testDiscount / 100);
            const priceAfterDiscount = item.price - discountAmount;
            const itemTotal = priceAfterDiscount * item.quantity;
            const itemDiscountTotal = discountAmount * item.quantity;

            subtotal += item.price * item.quantity;
            totalDiscount += itemDiscountTotal;

            const discountBadge = discount > 0 ? `<div style="color: #28a745; font-weight: bold; font-size: 0.9rem;"><i class="fas fa-tag"></i> Save Rs ${itemDiscountTotal.toLocaleString()}</div>` : '';
            itemsHtml += `
                    <div class="checkout-item">
                        <img src="../assets/images/${item.image || 'placeholder.jpg'}" alt="${item.name}" onerror="this.src='../assets/images/placeholder.jpg'">
                        <div class="item-info">
                            <h4>${item.name}</h4>
                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                ${testDiscount > 0 ?
                                    `<p>Quantity: ${item.quantity} × <span style="text-decoration: line-through; color: #999;">Rs ${item.price.toLocaleString()}</span> <span style="color: #28a745; font-weight: bold;">Rs ${priceAfterDiscount.toLocaleString()}</span></p>` :
                                    `<p>Quantity: ${item.quantity} × Rs ${item.price.toLocaleString()}</p>`
                                }
                                ${discountBadge}
                            </div>
                        </div>
                        <div class="item-price">Rs ${itemTotal.toLocaleString()}</div>
                    </div>
                `;
        });

        orderItems.innerHTML = itemsHtml;

        const afterDiscount = subtotal - totalDiscount;
        const tax = afterDiscount * 0.08;
        const total = afterDiscount + tax;

        if (subtotalElement) subtotalElement.textContent = `Rs ${subtotal.toLocaleString()}`;
        if (discountElement) discountElement.textContent = `Rs ${totalDiscount.toLocaleString()} (Saved!)`;
        if (taxElement) taxElement.textContent = `Rs ${tax.toLocaleString()}`;
        if (totalElement) totalElement.textContent = `Rs ${total.toLocaleString()}`;
    }

    // Modal functions
    function showOrderSuccessModal(orderId, total) {
        document.getElementById('modal-order-id').textContent = `#${orderId.toString().padStart(4, '0')}`;
        document.getElementById('modal-total-amount').textContent = `Rs ${total.toLocaleString()}`;
        document.getElementById('order-success-modal').style.display = 'flex';
    }

    function showErrorModal(message) {
        document.getElementById('error-message').textContent = message;
        document.getElementById('order-error-modal').style.display = 'flex';
    }

    function closeOrderModal() {
        document.getElementById('order-success-modal').style.display = 'none';
        window.location.href = 'index.php';
    }

    function closeErrorModal() {
        document.getElementById('order-error-modal').style.display = 'none';
    }

    function trackOrder() {
        // For now, just close modal and go to home
        closeOrderModal();
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const successModal = document.getElementById('order-success-modal');
        const errorModal = document.getElementById('order-error-modal');

        if (event.target === successModal) {
            closeOrderModal();
        }
        if (event.target === errorModal) {
            closeErrorModal();
        }
    }

    // Cart functions
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = cartCount;
        }
    }
</script>
<style>
    .checkout-container {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
    }

    .order-summary {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        height: fit-content;
    }

    .checkout-form {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .checkout-item {
        display: flex;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #e1e5e9;
    }

    .checkout-item:last-child {
        border-bottom: none;
    }

    .checkout-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        margin-right: 1rem;
    }

    .item-info h4 {
        margin-bottom: 0.5rem;
        color: #333;
    }

    .item-info p {
        color: #666;
        font-size: 0.9rem;
    }

    .item-price {
        font-weight: 600;
        color: #667eea;
    }

    .order-total {
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 2px solid #e1e5e9;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .total-row {
        font-weight: bold;
        font-size: 1.1rem;
        color: #667eea;
    }

    .order-now-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .order-now-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }

    @media (max-width: 768px) {
        .checkout-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Modal Styles -->
<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        animation: fadeIn 0.3s ease-out;
    }

    .modal-content {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        text-align: center;
        padding: 2rem 2rem 1rem;
        border-bottom: 1px solid #e1e5e9;
    }

    .success-icon {
        font-size: 4rem;
        color: #28a745;
        margin-bottom: 1rem;
    }

    .error-icon {
        font-size: 4rem;
        color: #dc3545;
        margin-bottom: 1rem;
    }

    .modal-header h2 {
        margin: 0.5rem 0;
        color: #333;
        font-size: 1.5rem;
    }

    .modal-header p {
        color: #666;
        margin: 0;
        font-size: 1rem;
    }

    .modal-body {
        padding: 2rem;
    }

    .order-details {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        font-size: 1rem;
    }

    .detail-row:last-child {
        margin-bottom: 0;
    }

    .detail-row span:first-child {
        color: #666;
    }

    .detail-row strong {
        color: #333;
        font-weight: 600;
    }

    .order-notice {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        background: #e3f2fd;
        border: 1px solid #bbdefb;
        border-radius: 8px;
        padding: 1rem;
        color: #1976d2;
    }

    .order-notice i {
        font-size: 1.2rem;
        margin-top: 0.1rem;
    }

    .modal-footer {
        padding: 1.5rem 2rem 2rem;
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .btn {
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
    }

    .place-order-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .place-order-btn:hover {
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }

    .place-order-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }

    @media (max-width: 480px) {
        .modal-content {
            width: 95%;
            margin: 1rem;
        }

        .modal-footer {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>