<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Menu - FoodieHub';

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

        if ($current_table) {
            $_SESSION['table_number'] = $current_table['table_number'];
        } else {
            unset($_SESSION['table_id'], $_SESSION['table_number']);
        }
    } catch (PDOException $e) {
        unset($_SESSION['table_id'], $_SESSION['table_number']);
    }
}

// Get all menu items
try {
    $stmt = $pdo->query("SELECT * FROM menu_items");
    $menu_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $menu_items = [];
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
$hero_title = 'Our Menu';
$hero_subtitle = 'Explore our delicious selection of dishes, carefully crafted for your enjoyment.';
$hero_image = '/restaurant_project/assets/images/menu1-bg.jpeg';
$hero_cta_text = 'Order Now';
$hero_cta_href = 'menu.php#menu';
include __DIR__ . '/../includes/hero.php';
?>

<!-- Menu Items -->
<section id="menu" class="section animate-on-scroll">
    <div class="container">
        <!-- Table Information Badge -->
        <?php if ($current_table): ?>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-utensils" style="font-size: 1.5rem;"></i>
                    <div>
                        <p style="margin: 0; font-size: 0.9rem; opacity: 0.9;">Table Number</p>
                        <h3 style="margin: 0; font-size: 1.5rem; font-weight: bold;"><?php echo htmlspecialchars($current_table['table_number']); ?></h3>
                    </div>
                </div>
                <button onclick="clearTableSession()" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; font-size: 0.85rem;">Reset Table</button>
            </div>
        <?php endif; ?>

        <!-- Menu Section Title -->
        <h2 style="text-align: center; font-size: 2rem; margin-bottom: 0.5rem;">Our Menu</h2>
        <p style="text-align: center; color: #666; margin-bottom: 2rem;">Browse our delicious selection and add items to your cart</p>

        <?php if (!empty($menu_items)): ?>
            <div class="menu-grid stagger-animation">
                <?php foreach ($menu_items as $item): ?>
                    <div class="menu-card">
                        <?php if ($item['image']): ?>
                            <img src="../assets/images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                            <div style="width: 100%; height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 3rem;">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        <div class="menu-card-content">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <?php if ($item['discount'] > 0): ?>
                                    <div>
                                        <div class="price" style="text-decoration: line-through; color: #999; font-size: 0.9rem;">Rs <?php echo number_format($item['price'], 0); ?></div>
                                        <div class="price" style="color: #28a745; font-weight: bold;">Rs <?php echo number_format($item['price'] * (100 - $item['discount']) / 100, 0); ?></div>
                                    </div>
                                    <div style="background: #dc3545; color: white; padding: 0.5rem 0.75rem; border-radius: 5px; font-size: 0.85rem; font-weight: bold;">
                                        -<?php echo number_format($item['discount'], 0); ?>%
                                    </div>
                                <?php else: ?>
                                    <div class="price">Rs <?php echo number_format($item['price'], 0); ?></div>
                                <?php endif; ?>
                            </div>
                            <button class="btn add-to-cart-btn"
                                data-id="<?php echo $item['id']; ?>"
                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                data-price="<?php echo $item['price']; ?>"
                                data-discount="<?php echo $item['discount']; ?>"
                                data-image="<?php echo htmlspecialchars($item['image'] ?? ''); ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; font-size: 1.2rem; color: #666;">No menu items available at the moment. Please check back later.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Call to Action -->
<section class="section" style="background: #f8f9fa; text-align: center;">
    <div class="container">
        <h2>Ready to Order?</h2>
        <p>Browse our menu and add your favorite items to the cart, then proceed to checkout.</p>
        <div style="margin-top: 2rem;">
            <a href="cart.php" class="btn"><i class="fas fa-shopping-cart"></i> View Cart</a>
            <a href="reservation.php" class="btn" style="background: #28a745; margin-left: 1rem;"><i class="fas fa-calendar-alt"></i> Make Reservation</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    function clearTableSession() {
        if (confirm('Are you sure you want to clear the current table? You will need to scan a QR code again.')) {
            fetch('clear_table.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(err => console.error('Error:', err));
        }
    }
</script>