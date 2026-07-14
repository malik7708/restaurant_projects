<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Home - DigitalDine';

// Handle QR code table parameter
$table_error = '';
if (isset($_GET['table']) && !empty($_GET['table'])) {
    $table_number = trim($_GET['table']);

    try {
        $stmt = $pdo->prepare("SELECT id, table_number, status FROM tables WHERE table_number = ?");
        $stmt->execute([$table_number]);
        $table = $stmt->fetch();

        if ($table) {
            if ($table['status'] === 'active') {
                $_SESSION['table_id'] = $table['id'];
                $_SESSION['table_number'] = $table['table_number'];
            } else {
                unset($_SESSION['table_id'], $_SESSION['table_number']);
                $table_error = 'Table ' . htmlspecialchars($table['table_number']) . ' is currently reserved. Please choose another table or ask our staff for assistance.';
            }
        } else {
            unset($_SESSION['table_id'], $_SESSION['table_number']);
            $table_error = 'Table not found. Please scan a valid QR code.';
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

        // Update session if table still exists
        if ($current_table) {
            $_SESSION['table_number'] = $current_table['table_number'];
        } else {
            // Clear session if table no longer exists
            unset($_SESSION['table_id'], $_SESSION['table_number']);
        }
    } catch (PDOException $e) {
        unset($_SESSION['table_id'], $_SESSION['table_number']);
    }
}

// Get banners
try {
    $stmt = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY display_order ASC");
    $banners = $stmt->fetchAll();
} catch (PDOException $e) {
    $banners = [];
}

// Get featured menu items
try {
    $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY id DESC LIMIT 6");
    $featured_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $featured_items = [];
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Banner Slider -->
<section class="banner-slider-section">
    <div class="banner-slider">
        <?php if (!empty($banners)): ?>
            <?php foreach ($banners as $index => $banner): ?>
                <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('../assets/images/<?php echo htmlspecialchars($banner['image'] ?? 'placeholder.jpg'); ?>');">
                    <div class="banner-overlay"></div>
                    <div class="banner-content">
                        <h1><?php echo htmlspecialchars($banner['title']); ?></h1>
                        <?php if ($banner['description']): ?>
                            <p><?php echo htmlspecialchars($banner['description']); ?></p>
                        <?php endif; ?>
                        <?php if ($banner['link']): ?>
                            <a href="<?php echo htmlspecialchars($banner['link']); ?>" class="btn banner-btn">
                                <i class="fas fa-arrow-right"></i> Explore Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="banner-slide active" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="banner-content">
                    <h1>Welcome to DigitalDine</h1>
                    <p>Experience the finest dining with our carefully crafted dishes</p>
                    <a href="menu.php" class="btn banner-btn"><i class="fas fa-arrow-right"></i> View Menu</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Banner Navigation -->
    <?php if (count($banners) > 1): ?>
        <div class="banner-nav">
            <button class="banner-nav-btn prev" onclick="prevBanner()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="banner-indicators">
                <?php foreach ($banners as $index => $banner): ?>
                    <button class="indicator <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToBanner(<?php echo $index; ?>)" title="<?php echo htmlspecialchars($banner['title']); ?>"></button>
                <?php endforeach; ?>
            </div>
            <button class="banner-nav-btn next" onclick="nextBanner()">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    <?php endif; ?>
</section>
<!-- Welcome Section -->
<section class="section welcome-section animate-on-scroll">
    <div class="container">
        <h2>Welcome to DigitalDine</h2>
        <p>Where every meal is a celebration of flavor and quality. Explore our menu, track your order, and enjoy an unforgettable dining experience.</p>
        <a href="menu.php" class="btn btn-primary">
            <i class="fas fa-list"></i> Browse Menu
        </a>
        <a href="index.php#qr-scanner-section" class="btn btn-secondary">
            <i class="fas fa-calendar-alt"></i> Reserve Your Table
        </a>
    </div>
</section>
<!-- QR Scanner Section -->
<section class="qr-scanner-section">
    <div class="container">
        <div class="qr-scanner-container">
            <div class="qr-scanner-content">
                <div class="qr-scanner-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h2>Scan QR Code to Reserve your Table</h2>
                <img src="../assets/images/menu1-bg.jpeg" alt="QR Code Example" style="width: 550px; height: 250px; border-radius: 10px; display: block; margin: 1rem auto;">
                <p>Point your camera at the QR code on your table to begin your dining experience</p>

                <button id="scan-qr-btn" class="btn btn-primary qr-scan-btn" onclick="startQRScan()">
                    <i class="fas fa-camera"></i> Scan QR Code
                </button>
                <div id="qr-reader" style="display: none;"></div>
                <div id="qr-result" style="display: none;"></div>
            </div>
            <div class="qr-scanner-visual">
                <div class="phone-mockup" id="phoneDevice">
                    <!-- Phone Notch -->
                    <div class="phone-notch"></div>

                    <!-- Phone Status Bar -->
                    <div class="phone-status-bar">
                        <div class="status-left">
                            <span class="status-time">9:41</span>
                        </div>
                        <div class="status-right">
                            <i class="fas fa-signal"></i>
                            <i class="fas fa-wifi"></i>
                            <i class="fas fa-battery-full"></i>
                        </div>
                    </div>

                    <!-- Phone Screen Content -->
                    <div class="phone-screen">
                        <!-- QR Scanner View -->
                        <div class="scanner-view">
                            <div class="scanner-header">
                                <h3>DigitalDine Scanner</h3>
                                <p>Point camera at table QR code</p>
                            </div>

                            <div class="scanner-viewport">
                                <div class="scan-animation">
                                    <div class="scan-line"></div>
                                    <div class="scan-corners">
                                        <div class="corner top-left"></div>
                                        <div class="corner top-right"></div>
                                        <div class="corner bottom-left"></div>
                                        <div class="corner bottom-right"></div>
                                    </div>
                                </div>
                                <div class="qr-placeholder">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                            </div>

                            <div class="scanner-footer">
                                <button class="phone-btn" onclick="toggleFlash()"><i class="fas fa-flash"></i></button>
                                <button class="phone-btn active" onclick="toggleCamera()"><i class="fas fa-camera"></i></button>
                                <button class="phone-btn" onclick="toggleGallery()"><i class="fas fa-image"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Phone Home Indicator -->
                    <div class="phone-home-indicator"></div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Featured Menu Section -->
<section class="section animate-on-scroll">
    <div class="container">
        <p>Discover our signature dishes</p>
        <h2>Featured Menu</h2>

        <?php if (!empty($featured_items)): ?>
            <div class="menu-grid stagger-animation">
                <?php foreach ($featured_items as $item): ?>
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
            <div style="text-align: center; margin-top: 3rem;">
                <a href="menu.php" class="btn">View Full Menu</a>
            </div>
        <?php else: ?>
            <p>No menu items available at the moment. Please check back later.</p>
        <?php endif; ?>
    </div>
</section>

<!-- About Section -->
<section class="section about-section animate-on-scroll">
    <div class="container">
        <div class="about-wrapper">
            <!-- Left: Image -->
            <div class="about-image">
                <div class="image-wrapper">
                    <img src="../assets/images/hero-bg.jpg" alt="DigitalDine Restaurant">
                    <div class="image-badge">
                        <i class="fas fa-star"></i> Award Winning
                    </div>
                </div>
            </div>

            <!-- Right: Content -->
            <div class="about-content">
                <div class="section-label">About Us</div>
                <h2>About DigitalDine</h2>
                <p>At DigitalDine, we believe that great food brings people together. Our restaurant has been serving delicious meals made from the finest ingredients for over a decade. We take pride in our culinary expertise and commitment to exceptional service.</p>

                <div class="about-highlights">
                    <div class="highlight-item">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h4>Premium Quality</h4>
                            <p>Only the finest ingredients sourced locally</p>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h4>Expert Chefs</h4>
                            <p>Highly trained culinary professionals</p>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h4>Perfect Ambiance</h4>
                            <p>Comfortable and welcoming atmosphere</p>
                        </div>
                    </div>
                </div>

                <p style="margin-top: 1.5rem;">Whether you're looking for a quick bite, a family dinner, or a special celebration, we have something for everyone. Our diverse menu features traditional favorites and innovative creations that will tantalize your taste buds.</p>

                <div class="about-cta">
                    <a href="about.php" class="btn btn-primary">
                        <i class="fas fa-info-circle"></i> Learn More
                    </a>
                    <a href="reservation.php" class="btn btn-secondary">
                        <i class="fas fa-calendar-alt"></i> Reserve Table
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="section">
    <div class="container">
        <h2>Why Choose DigitalDine?</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 3rem;">
            <div style="text-align: center;">
                <i class="fas fa-utensils" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
                <h3>Fresh Ingredients</h3>
                <p>We use only the freshest, highest quality ingredients in all our dishes.</p>
            </div>
            <div style="text-align: center;">
                <i class="fas fa-clock" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
                <h3>Quick Service</h3>
                <p>Fast and efficient service without compromising on quality.</p>
            </div>
            <div style="text-align: center;">
                <i class="fas fa-heart" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
                <h3>Made with Love</h3>
                <p>Every dish is prepared with care and attention to detail.</p>
            </div>
            <div style="text-align: center;">
                <i class="fas fa-star" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
                <h3>Excellent Reviews</h3>
                <p>Consistently rated 5-star by our satisfied customers.</p>
            </div>
        </div>
    </div>
</section>

<!-- Track Order Button -->
<div class="call-waiter-container" style="left:30px !important; right:auto !important;">
    <a href="javascript:void(0)" class="call-waiter-btn open-track-order-modal" onclick="openTrackOrderModal(); return false;">
        <i class="fas fa-search"></i>
        <span>Track Your Order</span>
    </a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Inline page scripts removed; central JS handles banners and demos -->