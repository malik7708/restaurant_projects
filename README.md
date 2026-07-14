# DigitalDine - Smart QR Dining System

A complete restaurant website upgraded with Smart QR Dining System, built with PHP, MySQL, HTML, CSS, and JavaScript. Features professional banner slider, menu management, order system, and now table-based QR ordering with waiter call functionality.

## 🚀 **SMART QR DINING SYSTEM** **[NEW]**

### 📱 **QR Code Table Access**

- **Dynamic QR codes** generated for each table
- **Table number storage** in session when QR scanned
- **Table badge display** on homepage hero section
- **Automatic table validation** and session management

### 🪑 **Table Management**

- **Admin panel** for managing restaurant tables
- **Add/Edit/Delete tables** with unique table numbers
- **Activate/Deactivate tables** for maintenance
- **QR code generation** with direct links
- **Real-time QR preview** using QR Server API

### 📊 **Smart Dashboard**

- **Orders grouped by table** with revenue tracking
- **Table statistics** (total, active, inactive tables)
- **Pending waiter calls** counter
- **Table-wise order summary** with revenue per table

### 🔔 **Waiter Call System**

- **Call waiter button** on customer homepage (table-specific)
- **Real-time call notifications** in admin panel
- **Call status management** (pending/completed)
- **Call history** with timestamps

### 🛒 **Enhanced Order System**

- **Table association** with every order
- **Table-specific order tracking**
- **Modified checkout** to save table information
- **Order history** linked to specific tables

## ✨ **ENHANCED FEATURES** (EXISTING)

## ✨ **ENHANCED FEATURES** (NEW)

### 🖼️ **Professional Banner Slider** **[ENHANCED]**

- **Auto-rotating banners** with smooth transitions **[NEW]**
- **3 sample banners** included (Burgers, Pizzas, Desserts) **[NEW]**
- **Manual navigation** with previous/next buttons **[NEW]**
- **Indicator dots** for quick banner selection **[NEW]**
- **Keyboard navigation** (arrow keys to switch banners) **[NEW]**
- **Responsive design** that works on mobile and desktop **[ENHANCED]**

### 📖 **Enhanced About Section** **[ENHANCED]**

- **Eye-catching layout** with side-by-side image and content **[NEW]**
- **Achievement badge** ("Award Winning") **[NEW]**
- **Highlighted features** with icons and descriptions **[NEW]**
- **Professional call-to-action buttons** **[NEW]**
- **Smooth hover animations** **[ENHANCED]**

### 🛠️ **Banner Management System** **[NEW]**

- **Admin panel** to add, edit, and delete banners **[NEW]**
- **Image upload** functionality with validation **[NEW]**
- **Display order** control for banner sequence **[NEW]**
- **Active/Inactive** toggle for quick control **[NEW]**
- **Responsive admin interface** with preview **[NEW]**

### 🗄️ **Database Enhancements** **[ENHANCED]**

- **Banners table** for storing banner configurations **[NEW]**
- **Display order** field for custom banner sequencing **[NEW]**
- **Image management** with metadata storage **[NEW]**
- **Tables table** for QR dining system **[NEW]**
- **Waiter_calls table** for call management **[NEW]**
- **Orders table** enhanced with table_id **[NEW]**

## Features

- **Frontend**: Responsive HTML, CSS, JavaScript
- **Backend**: PHP with MySQL database
- **Admin Panel**: Login/logout, banner management, menu management, order/reservation management, **table management**, **waiter calls**
- **Customer Features**: Menu browsing, shopping cart, checkout, reservations, contact form, **QR table access**, **waiter calling**
- **Smart QR System**: **Table-based ordering**, **QR code generation**, **real-time waiter calls**, **table revenue tracking**
- **Security**: Password hashing, input validation, SQL injection prevention, PDO prepared statements

## Project Structure

```
restaurant_project/
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet **[ENHANCED]** (+300 lines)
│   ├── js/
│   │   └── script.js       # Main JavaScript file
│   └── images/             # Images (logos, food photos, etc.) **[ENHANCED]** (banner images)
├── config/
│   └── config.php          # Configuration (site name, database, settings)
├── includes/
│   ├── header.php          # Common header
│   ├── footer.php          # Common footer
│   ├── hero.php            # Reusable hero section include
│   └── db.php              # Database connection
├── admin/
│   ├── index.php           # Admin dashboard **[ENHANCED]** (QR dining stats)
│   ├── login.php           # Admin login page
│   ├── logout.php          # Admin logout
│   ├── manage_banners.php  # Manage home page banners
│   ├── manage_menu.php     # Add/Edit/Delete menu items
│   ├── manage_orders.php   # View orders/reservations
│   ├── manage_tables.php   # **[NEW]** Manage restaurant tables & QR codes
│   └── manage_waiter_calls.php # **[NEW]** Handle waiter call requests
├── customer/
│   ├── index.php           # Home page **[ENHANCED]** (QR table badge + waiter call)
│   ├── menu.php            # Menu page
│   ├── cart.php            # Shopping cart
│   ├── checkout.php        # Checkout page **[ENHANCED]** (table association)
│   ├── reservation.php     # Reservation form
│   ├── contact.php         # Contact form
│   ├── about.php           # About page
│   └── call_waiter.php     # **[NEW]** AJAX endpoint for waiter calls
├── tools/                  # Utility scripts for development
├── database_setup.sql      # Database setup script **[ENHANCED]** (tables, waiter_calls, orders)
└── index.php               # Root redirect to customer home
```

## Setup Instructions

### 1. Install XAMPP

- Download and install XAMPP from https://www.apachefriends.org/
- Start Apache and MySQL services

### 2. Database Setup

- Open phpMyAdmin (http://localhost/phpmyadmin)
- Create a new database named `restaurant_db`
- Import the `database_setup.sql` file
- Default admin credentials: `admin` / `admin123`

### 3. Project Setup

- Copy the `restaurant_project` folder to `C:\xampp\htdocs\`
- The project will be accessible at `http://localhost/restaurant_project/customer/`

### 4. Configuration

- Edit `config/config.php` to customize:
  - Site name and URLs
  - Database credentials
  - Restaurant name, email, phone, address
  - Operating hours
  - Tax rate, reservation settings
  - Feature flags (email notifications, loyalty program, etc.)
- All configuration constants are centralized in this file for easy maintenance

### 5. Add Images (Optional)

Place the following images in the `assets/images/` folder:

- `hero-bg.jpg` - Hero section background
- `menu-bg.jpg` - Menu page background
- `cart-bg.jpg` - Cart page background
- `checkout-bg.jpg` - Checkout page background
- `reservation-bg.jpg` - Reservation page background
- `contact-bg.jpg` - Contact page background
- `about-bg.jpg` - About page background
- `restaurant-interior.jpg` - Restaurant interior photo
- `chef.jpg` - Chef photo
- `chef-maria.jpg` - Chef Maria photo
- `manager-john.jpg` - Manager photo
- `sommelier-sarah.jpg` - Sommelier photo
- `pizza1.jpg`, `burger1.jpg`, `salad1.jpg`, `pasta1.jpg`, `cake1.jpg` - Menu item images

### 6. Banner Management

Once the project is set up:

1. Go to Admin Panel: `http://localhost/restaurant_project/admin/login.php`
2. Login with admin credentials: `admin` / `admin123`
3. Click on **"Manage Banners"** in the navigation menu
4. Add new banners with custom titles, descriptions, and images
5. Set the display order for banner rotation
6. Toggle banners active/inactive as needed
7. Upload custom images (recommended size: 1920x600px)

### 📱 **QR Dining System Setup & Usage** **[NEW]**

#### Admin Setup:

1. Go to **"Manage Tables"** in the admin navigation
2. Add tables with unique table numbers (e.g., T01, T02, VIP1)
3. Generate QR codes for each table
4. Print QR codes and place at physical tables
5. Monitor waiter calls in **"Waiter Calls"** section

#### Customer Usage:

1. **Scan QR Code** at table: `http://localhost/restaurant_project/customer/index.php?table=T02`
2. **Table badge appears** on homepage showing current table
3. **Browse menu and order** as usual
4. **Call waiter** using the floating button when needed
5. **Orders automatically linked** to table number

#### Waiter Call System:

- Customers can call waiters instantly from their table
- Admin sees real-time call notifications
- Mark calls as completed when service is provided
- Track call history and response times

## Usage

### Admin Access

- URL: `http://localhost/restaurant_project/admin/login.php`
- Username: `admin`
- Password: `admin123`

### Customer Access

- Home: `http://localhost/restaurant_project/customer/`
- Menu: `http://localhost/restaurant_project/customer/menu.php`
- Cart: `http://localhost/restaurant_project/customer/cart.php`
- Checkout: `http://localhost/restaurant_project/customer/checkout.php`
- Reservation: `http://localhost/restaurant_project/customer/reservation.php`
- Contact: `http://localhost/restaurant_project/customer/contact.php`
- About: `http://localhost/restaurant_project/customer/about.php`

## Features Overview

### Admin Features

- Secure login with hashed passwords
- Dashboard with statistics
- **Banner management** (add, edit, delete, upload images) **[NEW]**
- Menu item management (CRUD operations)
- Order management with status updates
- Reservation management

### Customer Features

- Browse menu items
- Add items to cart (localStorage)
- Shopping cart with quantity management
- Secure checkout with form validation
- Table reservations
- Contact form
- Responsive design for mobile and desktop

## Security Features

- Password hashing with `password_hash()`
- Prepared statements to prevent SQL injection
- Input validation and sanitization
- Session management for admin authentication
- CSRF protection considerations

## Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7+
- **Database**: MySQL
- **Styling**: Custom CSS with responsive design
- **Icons**: Font Awesome
- **Fonts**: Google Fonts (Poppins)

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge

## Notes

- Cart functionality uses localStorage (client-side)
- Orders are stored in database with JSON-encoded cart data
- Email functionality would need to be implemented for contact form
- Image uploads for menu items would need to be implemented
- Consider implementing user registration for enhanced features

## License

This project is for educational purposes. Feel free to modify and use as needed.
