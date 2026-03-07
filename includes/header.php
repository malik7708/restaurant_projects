<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($page_title) ? $page_title : 'Restaurant'; ?></title>
    <link rel="stylesheet" href="/restaurant_project/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Magic Particles Background -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="nav-brand">
                    <a href="/restaurant_project/customer/index.php"><i class="fas fa-utensils"></i> DigitalDine</a>
                </div>
                <ul class="nav-menu" id="main-nav" role="menu">
                    <li><a href="/restaurant_project/customer/index.php">Home</a></li>
                    <li><a href="/restaurant_project/customer/menu.php">Menu</a></li>
                    <li><a href="/restaurant_project/customer/about.php">About</a></li>
                    <!-- Reservation removed from navbar per request -->
                    <li><a href="/restaurant_project/customer/contact.php">Contact</a></li>
                    <li><a href="/restaurant_project/customer/cart.php"><i class="fas fa-shopping-cart"></i> Cart <span id="cart-count">0</span></a></li>
                    <li><a href="/restaurant_project/admin/login.php">Admin Login</a></li>
               </ul>
                <button class="hamburger" aria-label="Toggle navigation" aria-expanded="false" aria-controls="main-nav">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>