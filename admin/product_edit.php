<?php
/**
 * Admin Product Edit Form
 * Add or edit products with validation
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/image_utils.php';

$page_title = 'Edit Product';
$product_id = (int)($_GET['id'] ?? 0);
$product = null;
$errors = [];

// Fetch existing product if editing
if ($product_id > 0) {
    try {
        $sql = "SELECT * FROM products WHERE product_id = :pid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pid' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            header('Location: products.php');
            exit();
        }
    } catch (Exception $e) {
        error_log("Product fetch error: " . $e->getMessage());
        header('Location: products.php');
        exit();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category = trim($_POST['category'] ?? '');

    // Validation
    if (!$name) $errors[] = 'Product name is required';
    if ($price <= 0) $errors[] = 'Price must be greater than 0';
    if ($stock < 0) $errors[] = 'Stock cannot be negative';

    // Handle image upload
    $image = $product['product_image'] ?? ''; // Keep existing image by default
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        // New file uploaded
        $uploaded_file = $_FILES['product_image'];
        
        // Validate the uploaded image
        $validation = validate_image_upload($uploaded_file, 5242880); // 5MB max
        if (!$validation['valid']) {
            $errors[] = 'Image upload error: ' . $validation['error'];
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = __DIR__ . '/../image/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $original_name = $uploaded_file['name'];
            $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $sanitized_name = sanitize_filename($name);
            $filename = $sanitized_name . '_' . time() . '.' . $extension;
            $target_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($uploaded_file['tmp_name'], $target_path)) {
                // Delete old image if it exists and is different
                if ($product_id > 0 && !empty($product['product_image']) && $product['product_image'] !== 'image/' . $filename) {
                    $old_image_path = __DIR__ . '/../' . $product['product_image'];
                    if (file_exists($old_image_path) && strpos($old_image_path, 'placeholder') === false) {
                        @unlink($old_image_path);
                    }
                }
                
                // Store relative path in database
                $image = 'image/' . $filename;
            } else {
                $errors[] = 'Failed to save uploaded image. Please try again.';
            }
        }
    } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Upload error occurred
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $error_code = $_FILES['product_image']['error'];
        $errors[] = 'Image upload error: ' . ($upload_errors[$error_code] ?? 'Unknown error');
    } elseif ($product_id === 0 && empty($image)) {
        // New product requires an image
        $errors[] = 'Product image is required for new products';
    }

    if (empty($errors)) {
        try {
            if ($product_id > 0) {
                // Update
                $update_sql = "UPDATE products SET product_name = :name, product_description = :desc, 
                              product_price = :price, product_stock = :stock, product_image = :image,
                              category = :category
                              WHERE product_id = :pid";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([
                    ':name' => $name,
                    ':desc' => $description,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':image' => $image,
                    ':category' => $category ?: null,
                    ':pid' => $product_id
                ]);
            } else {
                // Insert
                $insert_sql = "INSERT INTO products (product_name, product_description, product_price, product_stock, product_image, category)
                              VALUES (:name, :desc, :price, :stock, :image, :category)";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([
                    ':name' => $name,
                    ':desc' => $description,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':image' => $image,
                    ':category' => $category ?: null
                ]);
            }
            
            $_SESSION['message'] = $product_id > 0 ? 'Product updated successfully' : 'Product added successfully';
            header('Location: products.php');
            exit();
        } catch (Exception $e) {
            error_log("Product save error: " . $e->getMessage());
            $errors[] = 'Error saving product. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><?php echo $product_id > 0 ? 'Edit Product' : 'Add Product'; ?></h1>
    <div class="page-actions">
        <a href="products.php" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="admin-card" style="background: #FDE2E4; border-color: #F5C6CB; color: #842029; margin-bottom: 1.5rem;">
        <ul style="margin: 0; padding-left: 1.5rem;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-card" style="max-width: 600px;">
    <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="form-group">
            <label class="form-label">Product Name *</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['product_name'] ?? ''); ?>" required>
            <div class="invalid-feedback">Product name is required.</div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($product['product_description'] ?? ''); ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Price (₱) *</label>
                <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $product['product_price'] ?? ''; ?>" required>
                <div class="invalid-feedback">Price is required.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Stock *</label>
                <input type="number" name="stock" class="form-control" value="<?php echo $product['product_stock'] ?? ''; ?>" required>
                <div class="invalid-feedback">Stock is required.</div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category" class="form-control">
                <option value="">Select Category</option>
                <option value="Bracelet" <?php echo (isset($product['category']) && $product['category'] === 'Bracelet') ? 'selected' : ''; ?>>Bracelet</option>
                <option value="Earrings" <?php echo (isset($product['category']) && $product['category'] === 'Earrings') ? 'selected' : ''; ?>>Earrings</option>
                <option value="Necklace" <?php echo (isset($product['category']) && $product['category'] === 'Necklace') ? 'selected' : ''; ?>>Necklace</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Product Image <?php echo $product_id === 0 ? '*' : ''; ?></label>
            
            <?php if ($product_id > 0 && !empty($product['product_image'])): ?>
                <div style="margin-bottom: 1rem;">
                    <p class="text-muted small mb-2">Current Image:</p>
                    <img src="<?php echo htmlspecialchars(get_admin_image_path($product['product_image'])); ?>" 
                         alt="Current product image" 
                         style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border);"
                         onerror="this.src='../image/placeholder.png'">
                </div>
            <?php endif; ?>
            
            <input type="file" 
                   name="product_image" 
                   id="product_image" 
                   class="form-control" 
                   accept="image/jpeg,image/png,image/gif,image/webp"
                   <?php echo $product_id === 0 ? 'required' : ''; ?>>
            <small class="text-muted">
                <?php if ($product_id > 0): ?>
                    Leave empty to keep current image. 
                <?php endif; ?>
                Accepted formats: JPG, PNG, GIF, WEBP (Max 5MB)
            </small>
            <div id="imagePreview" style="margin-top: 1rem; display: none;">
                <p class="text-muted small mb-2">Preview:</p>
                <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border);">
            </div>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">Save Product</button>
            <a href="products.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
// Image preview functionality
document.getElementById('product_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

// Custom validation for product edit form
document.querySelector('form').addEventListener('submit', function(e) {
    let isValid = true;
    const form = this;
    
    // Clear previous validation
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    
    // Validate product name
    const name = form.querySelector('[name="name"]');
    if (!name.value.trim()) {
        name.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validate price
    const price = form.querySelector('[name="price"]');
    if (!price.value || parseFloat(price.value) <= 0) {
        price.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validate stock
    const stock = form.querySelector('[name="stock"]');
    if (!stock.value || parseInt(stock.value) < 0) {
        stock.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validate image (only required for new products)
    const imageInput = form.querySelector('[name="product_image"]');
    const isNewProduct = <?php echo $product_id === 0 ? 'true' : 'false'; ?>;
    if (isNewProduct && (!imageInput.files || imageInput.files.length === 0)) {
        imageInput.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validate file type if file is selected
    if (imageInput.files && imageInput.files.length > 0) {
        const file = imageInput.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            imageInput.classList.add('is-invalid');
            isValid = false;
            alert('Please select a valid image file (JPG, PNG, GIF, or WEBP)');
        }
        
        // Validate file size (5MB max)
        if (file.size > 5242880) {
            imageInput.classList.add('is-invalid');
            isValid = false;
            alert('Image file size must be less than 5MB');
        }
    }
    
    if (!isValid) {
        e.preventDefault();
        const firstError = form.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
