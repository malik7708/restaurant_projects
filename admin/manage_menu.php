<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$page_title = 'Manage Menu - DigitalDine';

$errors = [];
$success = '';

// Upload directory
$upload_dir = __DIR__ . '/../assets/images/';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        // Add new menu item
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $discount = floatval($_POST['discount'] ?? 0);
        $image = '';

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $file = $_FILES['image'];

            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.';
            } elseif ($file['size'] > $max_file_size) {
                $errors[] = 'File size exceeds 5MB limit.';
            } else {
                $filename = time() . '_' . basename($file['name']);
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $image = $filename;
                } else {
                    $errors[] = 'Failed to upload image.';
                }
            }
        }

        // Validation
        if (empty($name)) {
            $errors[] = 'Item name is required';
        }

        if ($price <= 0) {
            $errors[] = 'Price must be greater than 0';
        }

        if ($discount < 0 || $discount > 100) {
            $errors[] = 'Discount must be between 0 and 100';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, discount, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $discount, $image]);
                $success = 'Menu item added successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['edit_item'])) {
        // Edit existing menu item
        $id = intval($_POST['item_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $discount = floatval($_POST['discount'] ?? 0);
        $image = trim($_POST['old_image'] ?? '');

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $file = $_FILES['image'];

            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.';
            } elseif ($file['size'] > $max_file_size) {
                $errors[] = 'File size exceeds 5MB limit.';
            } else {
                // Delete old file if exists
                if (!empty($image)) {
                    @unlink($upload_dir . $image);
                }

                $filename = time() . '_' . basename($file['name']);
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $image = $filename;
                } else {
                    $errors[] = 'Failed to upload image.';
                }
            }
        }

        // Validation
        if (empty($name)) {
            $errors[] = 'Item name is required';
        }

        if ($price <= 0) {
            $errors[] = 'Price must be greater than 0';
        }

        if ($discount < 0 || $discount > 100) {
            $errors[] = 'Discount must be between 0 and 100';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, discount = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $discount, $image, $id]);
                $success = 'Menu item updated successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_item'])) {
        // Delete menu item
        $id = intval($_POST['item_id'] ?? 0);

        try {
            // Get image filename first
            $stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if ($item && !empty($item['image'])) {
                @unlink($upload_dir . $item['image']);
            }

            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Menu item deleted successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all menu items
try {
    $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY id DESC");
    $menu_items = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-utensils"></i> Manage Menu</h1>
            <div class="admin-actions">
                <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_banners.php"><i class="fas fa-images"></i> Manage Banners</a></li>
                <li><a href="manage_menu.php" class="active"><i class="fas fa-utensils"></i> Manage Menu</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
            </ul>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Item Form -->
        <div class="form-section">
            <h3><i class="fas fa-plus"></i> Add New Menu Item</h3>
            <form method="POST" action="" class="admin-form" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Item Name *</label>
                        <input type="text" id="name" name="name" required
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="price">Price (Rs) *</label>
                        <input type="number" id="price" name="price" step="1" min="0" required
                            value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="discount">Discount (%)</label>
                        <input type="number" id="discount" name="discount" step="0.01" min="0" max="100"
                            value="<?php echo htmlspecialchars($_POST['discount'] ?? '0'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="image">Item Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small>Accepted: JPG, PNG, GIF, WebP. Max 5MB</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="add_item" class="btn"><i class="fas fa-plus"></i> Add Item</button>
            </form>
        </div>

        <!-- Menu Items List -->
        <div class="items-section">
            <h3><i class="fas fa-list"></i> Current Menu Items</h3>
            <?php if (empty($menu_items)): ?>
                <p>No menu items found. Add your first item above.</p>
            <?php else: ?>
                <div class="items-grid">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="item-card">
                            <?php if ($item['image']): ?>
                                <img src="../assets/images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                            <?php else: ?>
                                <div class="no-image"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                <?php if ($item['discount'] > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <p class="item-price" style="text-decoration: line-through; color: #999; font-size: 0.9rem;">Rs <?php echo number_format($item['price'], 0); ?></p>
                                        <p class="item-price" style="color: #28a745; font-weight: bold;">Rs <?php echo number_format($item['price'] * (100 - $item['discount']) / 100, 0); ?></p>
                                    </div>
                                    <p style="color: #28a745; font-weight: bold;"><i class="fas fa-tag"></i> <?php echo number_format($item['discount'], 1); ?>% Off</p>
                                <?php else: ?>
                                    <p class="item-price">Rs <?php echo number_format($item['price'], 0); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-edit" onclick="editItem(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['name'])); ?>', '<?php echo addslashes(htmlspecialchars($item['description'])); ?>', <?php echo $item['price']; ?>, <?php echo $item['discount']; ?>, '<?php echo addslashes(htmlspecialchars($item['image'])); ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-delete" onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['name'])); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&amp;times;</span>
            <h3>Edit Menu Item</h3>
            <form method="POST" action="" id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="editItemId">
                <input type="hidden" name="old_image" id="editOldImage">
                <div class="form-row">
                    <div class="form-group">
                        <label for="editName">Item Name *</label>
                        <input type="text" id="editName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="editPrice">Price (Rs) *</label>
                        <input type="number" id="editPrice" name="price" step="1" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editDiscount">Discount (%)</label>
                        <input type="number" id="editDiscount" name="discount" step="0.01" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label for="editImage">Item Image</label>
                        <input type="file" id="editImage" name="image" accept="image/*">
                        <small>Accepted: JPG, PNG, GIF, WebP. Max 5MB</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editDescription">Description</label>
                    <textarea id="editDescription" name="description" rows="3"></textarea>
                </div>
                <button type="submit" name="edit_item" class="btn"><i class="fas fa-save"></i> Update Item</button>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&amp;times;</span>
            <h3>Delete Menu Item</h3>
            <p>Are you sure you want to delete "<span id="deleteItemName"></span>"? This action cannot be undone.</p>
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="item_id" id="deleteItemId">
                <button type="submit" name="delete_item" class="btn btn-delete"><i class="fas fa-trash"></i> Delete Item</button>
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <style>
        .form-section,
        .items-section {
            margin-bottom: 3rem;
        }

        .admin-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .item-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .no-image {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #ccc;
        }

        .item-details {
            padding: 1rem;
        }

        .item-details h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .item-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .item-price {
            font-weight: 600;
            color: #667eea;
            font-size: 1.1rem;
        }

        .item-actions {
            padding: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit {
            background: #28a745;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }
    </style>

    <script>
        function editItem(id, name, description, price, discount, image) {
            document.getElementById('editItemId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
            document.getElementById('editPrice').value = price;
            document.getElementById('editDiscount').value = discount;
            document.getElementById('editImage').value = '';
            document.getElementById('editOldImage').value = image;
            document.getElementById('editModal').style.display = 'block';
        }

        function deleteItem(id, name) {
            document.getElementById('deleteItemId').value = id;
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
        }
    </script>
</body>

</html>