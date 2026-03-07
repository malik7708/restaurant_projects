<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db.php';

$page_title = 'Manage Banners - FoodieHub';

$errors = [];
$success = '';

// Handle banner operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' || $action === 'edit') {
            $banner_id = isset($_POST['banner_id']) ? intval($_POST['banner_id']) : 0;
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $link = trim($_POST['link'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $display_order = intval($_POST['display_order'] ?? 0);
            $image = $_POST['image_filename'] ?? '';

            if (empty($title)) {
                $errors[] = 'Banner title is required';
            }

            if (empty($image) && $action === 'add') {
                $errors[] = 'Banner image is required';
            }

            if (empty($errors)) {
                try {
                    if ($action === 'add') {
                        $stmt = $pdo->prepare("INSERT INTO banners (title, description, image, link, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $description, $image, $link, $is_active, $display_order]);
                        $success = 'Banner added successfully!';
                    } else {
                        $stmt = $pdo->prepare("UPDATE banners SET title = ?, description = ?, link = ?, is_active = ?, display_order = ? WHERE id = ?");
                        $stmt->execute([$title, $description, $link, $is_active, $display_order, $banner_id]);
                        $success = 'Banner updated successfully!';
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $banner_id = intval($_POST['banner_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
                $stmt->execute([$banner_id]);
                $success = 'Banner deleted successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } elseif ($action === 'upload_image') {
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
                $tmp_file = $_FILES['banner_image']['tmp_name'];
                $filename = 'banner_' . time() . '_' . basename($_FILES['banner_image']['name']);
                $target_path = '../assets/images/' . $filename;

                if (move_uploaded_file($tmp_file, $target_path)) {
                    echo json_encode(['success' => true, 'filename' => $filename]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'File upload failed']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
                exit;
            }
        }
    }
}

// Get all banners
try {
    $stmt = $pdo->query("SELECT * FROM banners ORDER BY display_order ASC");
    $banners = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$edit_banner = null;
if (isset($_GET['edit'])) {
    $banner_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$banner_id]);
    $edit_banner = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-images"></i> Manage Banners</h1>
            <div class="admin-actions">
                <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_menu.php"><i class="fas fa-utensils"></i> Manage Menu</a></li>
                <li><a href="manage_banners.php" class="active"><i class="fas fa-images"></i> Manage Banners</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
            </ul>
        </div>

        <div class="admin-content">
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
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-bottom: 3rem;">
                <!-- Add/Edit Banner Form -->
                <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3><?php echo $edit_banner ? 'Edit Banner' : 'Add New Banner'; ?></h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $edit_banner ? 'edit' : 'add'; ?>">
                        <?php if ($edit_banner): ?>
                            <input type="hidden" name="banner_id" value="<?php echo $edit_banner['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="title">Banner Title *</label>
                            <input type="text" id="title" name="title" required
                                value="<?php echo htmlspecialchars($edit_banner['title'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"
                                placeholder="Optional banner description"><?php echo htmlspecialchars($edit_banner['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="link">Link</label>
                            <input type="text" id="link" name="link"
                                placeholder="e.g., menu.php or https://example.com"
                                value="<?php echo htmlspecialchars($edit_banner['link'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" id="display_order" name="display_order" min="1"
                                value="<?php echo $edit_banner['display_order'] ?? 1; ?>">
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active"
                                    <?php echo ($edit_banner && $edit_banner['is_active']) ? 'checked' : 'checked'; ?>>
                                Active
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="banner_image">Banner Image <?php echo !$edit_banner ? '*' : '(Optional)'; ?></label>
                            <div id="image-preview" style="margin-bottom: 1rem;">
                                <?php if ($edit_banner && $edit_banner['image']): ?>
                                    <img src="../assets/images/<?php echo htmlspecialchars($edit_banner['image']); ?>"
                                        alt="Current Banner" style="max-width: 200px; border-radius: 8px;">
                                    <p style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">Current image</p>
                                <?php endif; ?>
                            </div>
                            <input type="file" id="banner_image" name="banner_image" accept="image/*"
                                onchange="uploadBannerImage();">
                            <input type="hidden" name="image_filename" id="image_filename"
                                value="<?php echo htmlspecialchars($edit_banner['image'] ?? ''); ?>">
                            <small style="color: #666;">Recommended size: 1920x600px</small>
                        </div>

                        <?php if ($edit_banner): ?>
                            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-save"></i> Update Banner
                            </button>
                            <a href="manage_banners.php" class="btn" style="width: 100%; margin-top: 0.5rem; background: #6c757d; text-align: center;">
                                <i class="fas fa-times"></i> Cancel Edit
                            </a>
                        <?php else: ?>
                            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Add Banner
                            </button>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Banners List -->
                <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>Existing Banners</h3>

                    <?php if (empty($banners)): ?>
                        <p style="color: #999;">No banners found. Create your first banner!</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($banners as $banner): ?>
                                <div style="border: 1px solid #ddd; padding: 1rem; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <?php if ($banner['image']): ?>
                                                <img src="../assets/images/<?php echo htmlspecialchars($banner['image']); ?>"
                                                    alt="<?php echo htmlspecialchars($banner['title']); ?>"
                                                    style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                            <?php endif; ?>
                                            <div>
                                                <h4 style="margin: 0 0 0.25rem 0;"><?php echo htmlspecialchars($banner['title']); ?></h4>
                                                <small style="color: #666;">
                                                    Order: <?php echo $banner['display_order']; ?>
                                                    |
                                                    Status: <?php echo $banner['is_active'] ? '<span style="color: #28a745;">Active</span>' : '<span style="color: #dc3545;">Inactive</span>'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="?edit=<?php echo $banner['id']; ?>" class="btn" style="background: #667eea; padding: 0.5rem 1rem;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this banner?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                            <button type="submit" class="btn" style="background: #dc3545; padding: 0.5rem 1rem;">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function uploadBannerImage() {
            const fileInput = document.getElementById('banner_image');
            const file = fileInput.files[0];

            if (!file) return;

            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please upload a valid image file');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('banner_image', file);

            fetch('manage_banners.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('image_filename').value = data.filename;

                        // Show preview
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            document.getElementById('image-preview').innerHTML =
                                '<img src="' + e.target.result + '" style="max-width: 200px; border-radius: 8px;">' +
                                '<p style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">Uploaded image</p>';
                        };
                        reader.readAsDataURL(file);

                        alert('Image uploaded successfully!');
                    } else {
                        alert('Error uploading image: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error uploading image');
                });
        }
    </script>
</body>

</html>