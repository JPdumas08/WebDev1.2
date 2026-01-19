<?php
require_once 'db.php'; 

// Fetch all active (non-archived) products from the `products` table
try {
    $stmt = $pdo->query('SELECT product_id, product_name, product_price, product_image, category, product_stock FROM products WHERE is_archived = 0 ORDER BY product_id ASC');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

    http_response_code(500);
    echo '<p>Error fetching products: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

// Render products as HTML (simple card grid). Consumers can also include this file and use $products directly.
function render_products_grid(array $products) {
    if (empty($products)) {
        echo '<p class="text-muted">No products found.</p>';
        return;
    }

    echo '<div class="row">';
    foreach ($products as $p) {
        // Ensure product image path is escaped and present
        $img = !empty($p['product_image']) ? htmlspecialchars($p['product_image']) : 'image/placeholder.png';
        $name = htmlspecialchars($p['product_name']);
        $price = htmlspecialchars(number_format((float)$p['product_price'], 2));

        echo '<div class="col-md-4 mb-4">';
        echo '  <div class="card h-100">';
        echo '    <img src="' . $img . '" class="card-img-top" alt="' . $name . '" style="height:250px;object-fit:cover;">';
        echo '    <div class="card-body d-flex flex-column">';
        echo '      <h5 class="card-title">' . $name . '</h5>';
        echo '      <p class="card-text mt-auto">₱' . $price . '</p>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    echo '</div>';
}


if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    ?><!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Products</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
      <div class="container py-5">
        <h2 class="mb-4">Products</h2>
        <?php render_products_grid($products); ?>
      </div>
    </body>
    </html>
    <?php
}


return;
