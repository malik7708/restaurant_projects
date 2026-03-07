<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Home - FoodieHub';

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
                        <?php if ($current_table): ?>
                            <div class="table-badge">
                                <i class="fas fa-utensils"></i> Table <?php echo htmlspecialchars($current_table['table_number']); ?>
                            </div>
                        <?php endif; ?>
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
                    <?php if ($current_table): ?>
                        <div class="table-badge">
                            <i class="fas fa-utensils"></i> Table <?php echo htmlspecialchars($current_table['table_number']); ?>
                        </div>
                    <?php endif; ?>
                    <h1>Welcome to FoodieHub</h1>
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
        <h2>Welcome to FoodieHub</h2>
        <p>Where every meal is a celebration of flavor and quality. Explore our menu, call your waiter, and enjoy an unforgettable dining experience.</p>
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
                    <img src="../assets/images/hero-bg.jpg" alt="FoodieHub Restaurant">
                    <div class="image-badge">
                        <i class="fas fa-star"></i> Award Winning
                    </div>
                </div>
            </div>

            <!-- Right: Content -->
            <div class="about-content">
                <div class="section-label">About Us</div>
                <h2>About FoodieHub</h2>
                <p>At FoodieHub, we believe that great food brings people together. Our restaurant has been serving delicious meals made from the finest ingredients for over a decade. We take pride in our culinary expertise and commitment to exceptional service.</p>

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

<!-- Call Waiter Button (only show if table is selected) -->
<?php if ($current_table): ?>
    <div class="call-waiter-container">
        <button id="call-waiter-btn" class="call-waiter-btn" onclick="callWaiter()">
            <i class="fas fa-bell"></i>
            <span>Call Waiter</span>
        </button>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Banner Slider JavaScript -->
<script>
    let currentBannerIndex = 0;
    let bannerAutoPlayInterval;

    function initBannerSlider() {
        const slides = document.querySelectorAll('.banner-slide');
        if (slides.length > 1) {
            startBannerAutoPlay();
        }
    }

    function showBanner(index) {
        const slides = document.querySelectorAll('.banner-slide');
        const indicators = document.querySelectorAll('.banner-indicators .indicator');

        if (index >= slides.length) {
            currentBannerIndex = 0;
        } else if (index < 0) {
            currentBannerIndex = slides.length - 1;
        } else {
            currentBannerIndex = index;
        }

        slides.forEach((slide, i) => {
            slide.classList.remove('active');
            if (i === currentBannerIndex) {
                slide.classList.add('active');
            }
        });

        indicators.forEach((indicator, i) => {
            indicator.classList.remove('active');
            if (i === currentBannerIndex) {
                indicator.classList.add('active');
            }
        });
    }

    function nextBanner() {
        resetBannerAutoPlay();
        showBanner(currentBannerIndex + 1);
    }

    function prevBanner() {
        resetBannerAutoPlay();
        showBanner(currentBannerIndex - 1);
    }

    function goToBanner(index) {
        resetBannerAutoPlay();
        showBanner(index);
    }

    function startBannerAutoPlay() {
        bannerAutoPlayInterval = setInterval(() => {
            showBanner(currentBannerIndex + 1);
        }, 5000); // Change banner every 5 seconds
    }

    function resetBannerAutoPlay() {
        clearInterval(bannerAutoPlayInterval);
        startBannerAutoPlay();
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') prevBanner();
        if (e.key === 'ArrowRight') nextBanner();
    });

    // Initialize banner slider when page loads
    document.addEventListener('DOMContentLoaded', initBannerSlider);

    // Watch Demo Functionality
    let demoInterval;
    let demoStep = 0;
    const demoScreens = [
        'welcome',
        'menu',
        'cart',
        'profile'
    ];

    function startDemo() {
        const floatingElements = document.querySelector('.floating-elements');
        const showcaseElements = document.querySelector('.showcase-elements');

        // Reset to first screen
        demoStep = 0;
        updateDemoScreen();

        // Show floating elements and side elements
        floatingElements.style.opacity = '1';
        showcaseElements.style.opacity = '1';

        // Start demo animation
        demoInterval = setInterval(() => {
            demoStep = (demoStep + 1) % demoScreens.length;
            updateDemoScreen();
        }, 3000); // Change every 3 seconds

        // Stop after full cycle
        setTimeout(() => {
            clearInterval(demoInterval);
            // Reset to welcome screen
            demoStep = 0;
            updateDemoScreen();
            floatingElements.style.opacity = '0';
            showcaseElements.style.opacity = '0';
        }, 12000); // Stop after 12 seconds (4 screens * 3s)
    }

    function updateDemoScreen() {
        const appContent = document.querySelector('.app-content');
        const navItems = document.querySelectorAll('.nav-item');

        // Update navigation active state
        navItems.forEach((item, index) => {
            item.classList.remove('active');
            if (index === demoStep) {
                item.classList.add('active');
            }
        });

        // Update content based on step
        switch (demoScreens[demoStep]) {
            case 'welcome':
                appContent.innerHTML = `
                    <div class="welcome-screen">
                        <div class="qr-success">
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>QR Code Scanned!</h3>
                            <p>Ready to start your dining experience</p>
                        </div>
                        <div class="action-cards">
                            <div class="action-card primary">
                                <div class="card-icon">
                                    <i class="fas fa-list"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Browse Menu</h4>
                                    <p>Explore our delicious dishes</p>
                                </div>
                                <div class="card-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </div>
                            <div class="action-card secondary">
                                <div class="card-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Call Waiter</h4>
                                    <p>Need assistance? We're here</p>
                                </div>
                                <div class="card-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'menu':
                appContent.innerHTML = `
                    <div class="menu-screen">
                        <h3>Our Menu</h3>
                        <div class="menu-items">
                            <div class="menu-item">
                                <div class="item-info">
                                    <h4>Grilled Chicken</h4>
                                    <p>Juicy grilled chicken with herbs</p>
                                    <span class="price">Rs 450</span>
                                </div>
                                <button class="add-btn">+</button>
                            </div>
                            <div class="menu-item">
                                <div class="item-info">
                                    <h4>Pasta Alfredo</h4>
                                    <p>Creamy pasta with parmesan</p>
                                    <span class="price">Rs 380</span>
                                </div>
                                <button class="add-btn">+</button>
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'cart':
                appContent.innerHTML = `
                    <div class="cart-screen">
                        <h3>Your Cart</h3>
                        <div class="cart-items">
                            <div class="cart-item">
                                <span>Grilled Chicken</span>
                                <span>Rs 450</span>
                                <div class="quantity">
                                    <button>-</button>
                                    <span>1</span>
                                    <button>+</button>
                                </div>
                            </div>
                        </div>
                        <div class="cart-total">
                            <strong>Total: Rs 450</strong>
                        </div>
                        <button class="checkout-btn">Checkout</button>
                    </div>
                `;
                break;
            case 'profile':
                appContent.innerHTML = `
                    <div class="profile-screen">
                        <h3>Your Profile</h3>
                        <div class="profile-info">
                            <p><strong>Name:</strong> John Doe</p>
                            <p><strong>Email:</strong> john@example.com</p>
                            <p><strong>Table:</strong> A-12</p>
                        </div>
                        <button class="logout-btn">Logout</button>
                    </div>
                `;
                break;
        }
    }

    // Call Waiter Functionality
    function callWaiter() {
        const btn = document.getElementById('call-waiter-btn');
        const originalText = btn.innerHTML;

        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Calling...</span>';

        // Send AJAX request to call waiter
        fetch('call_waiter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    table_id: <?php echo isset($_SESSION['table_id']) ? $_SESSION['table_id'] : 'null'; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    btn.innerHTML = '<i class="fas fa-check"></i><span>Called!</span>';
                    btn.classList.add('success');

                    // Reset button after 3 seconds
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        btn.classList.remove('success');
                    }, 3000);
                } else {
                    // Show error
                    alert('Error: ' + (data.message || 'Failed to call waiter'));
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
    }
</script>