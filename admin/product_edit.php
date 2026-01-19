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
    $image = $_POST['image'] ?? ($product['product_image'] ?? '');

    // Validation
    if (!$name) $errors[] = 'Product name is required';
    if ($price <= 0) $errors[] = 'Price must be greater than 0';
    if ($stock < 0) $errors[] = 'Stock cannot be negative';
    if (!$image) $errors[] = 'Product image URL is required';

    if (empty($errors)) {
        try {
            if ($product_id > 0) {
                // Update
                $update_sql = "UPDATE products SET product_name = :name, product_description = :desc, 
                              product_price = :price, product_stock = :stock, product_image = :image
                              WHERE product_id = :pid";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([
                    ':name' => $name,
                    ':desc' => $description,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':image' => $image,
                    ':pid' => $product_id
                ]);
            } else {
                // Insert
                $insert_sql = "INSERT INTO products (product_name, product_description, product_price, product_stock, product_image)
                              VALUES (:name, :desc, :price, :stock, :image)";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([
                    ':name' => $name,
                    ':desc' => $description,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':image' => $image
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
    <form method="POST" novalidate>
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
            <label class="form-label">Image URL *</label>
            <input type="text" name="image" class="form-control" value="<?php echo htmlspecialchars($product['product_image'] ?? ''); ?>" placeholder="e.g., image/product.jpg" required>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">Save Product</button>
            <a href="products.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
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
    
    // Validate image
    const image = form.querySelector('[name="image"]');
    if (!image.value.trim()) {
        image.classList.add('is-invalid');
        isValid = false;
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
