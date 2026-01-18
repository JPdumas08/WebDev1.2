<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

// Ensure philippine locations reference table exists and is seeded (lightweight seed)
$pdo->exec("CREATE TABLE IF NOT EXISTS phil_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    province VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    INDEX idx_province (province),
    INDEX idx_province_city (province, city)
)");

$seedData = [
    // NCR
    ['Metro Manila', 'Manila'],
    ['Metro Manila', 'Quezon City'],
    ['Metro Manila', 'Makati'],
    ['Metro Manila', 'Taguig'],
    ['Metro Manila', 'Pasig'],
    ['Metro Manila', 'Pasay'],
    ['Metro Manila', 'Mandaluyong'],
    ['Metro Manila', 'Marikina'],
    ['Metro Manila', 'Caloocan'],
    ['Metro Manila', 'Parañaque'],
    ['Metro Manila', 'Las Piñas'],
    ['Metro Manila', 'Valenzuela'],
    // Central Luzon
    ['Bulacan', 'Malolos'],
    ['Bulacan', 'Meycauayan'],
    ['Bulacan', 'San Jose del Monte'],
    ['Bulacan', 'Baliuag'],
    ['Bulacan', 'Plaridel'],
    ['Pampanga', 'San Fernando'],
    ['Pampanga', 'Angeles'],
    ['Pampanga', 'Mabalacat'],
    ['Pampanga', 'Apalit'],
    ['Nueva Ecija', 'Cabanatuan'],
    ['Nueva Ecija', 'Gapan'],
    ['Nueva Ecija', 'San Jose'],
    ['Nueva Ecija', 'Palayan'],
    ['Tarlac', 'Tarlac City'],
    ['Tarlac', 'Concepcion'],
    ['Tarlac', 'Gerona'],
    ['Tarlac', 'Capas'],
    // CALABARZON
    ['Cavite', 'Dasmariñas'],
    ['Cavite', 'Bacoor'],
    ['Cavite', 'Imus'],
    ['Cavite', 'General Trias'],
    ['Cavite', 'Trece Martires'],
    ['Cavite', 'Silang'],
    ['Laguna', 'Calamba'],
    ['Laguna', 'Santa Rosa'],
    ['Laguna', 'Biñan'],
    ['Laguna', 'San Pablo'],
    ['Laguna', 'Los Baños'],
    ['Laguna', 'San Pedro City'],
    ['Batangas', 'Batangas City'],
    ['Batangas', 'Lipa'],
    ['Batangas', 'Tanauan'],
    ['Batangas', 'Santo Tomas'],
    ['Batangas', 'Nasugbu'],
    ['Rizal', 'Antipolo'],
    ['Rizal', 'Cainta'],
    ['Rizal', 'Taytay'],
    ['Rizal', 'Binangonan'],
    ['Rizal', 'Rodriguez'],
    ['Quezon', 'Lucena'],
    ['Quezon', 'Tayabas'],
    ['Quezon', 'Sariaya'],
    ['Quezon', 'Candelaria'],
    // Visayas
    ['Cebu', 'Cebu City'],
    ['Cebu', 'Lapu-Lapu City'],
    ['Cebu', 'Mandaue'],
    ['Cebu', 'Talisay'],
    ['Cebu', 'Carcar'],
    ['Iloilo', 'Iloilo City'],
    ['Iloilo', 'Oton'],
    ['Iloilo', 'Pavia'],
    ['Iloilo', 'Passi'],
    ['Iloilo', 'Cabatuan'],
    ['Negros Occidental', 'Bacolod'],
    ['Negros Occidental', 'Bago'],
    ['Negros Occidental', 'Talisay'],
    ['Negros Occidental', 'Silay'],
    ['Negros Occidental', 'Kabankalan'],
    // Mindanao
    ['Davao del Sur', 'Davao City'],
    ['Davao del Sur', 'Digos'],
    ['Zamboanga del Sur', 'Zamboanga City'],
    ['Zamboanga del Sur', 'Pagadian'],
    ['Misamis Oriental', 'Cagayan de Oro'],
    ['Misamis Oriental', 'El Salvador'],
    ['Misamis Oriental', 'Opol'],
    ['South Cotabato', 'General Santos'],
    ['South Cotabato', 'Koronadal'],
    // Cordillera / Ilocos
    ['Benguet', 'Baguio City'],
    ['Benguet', 'La Trinidad'],
    ['Benguet', 'Itogon'],
    ['Ilocos Norte', 'Laoag'],
    ['Ilocos Norte', 'Batac'],
    ['Ilocos Sur', 'Vigan'],
    ['Ilocos Sur', 'Candon'],
    ['Pangasinan', 'Dagupan'],
    ['Pangasinan', 'Urdaneta'],
    ['Pangasinan', 'San Carlos'],
    ['Pangasinan', 'Alaminos'],
    ['Pangasinan', 'Lingayen']
];

$stmtSeedExists = $pdo->prepare("SELECT 1 FROM phil_locations WHERE province = :province AND city = :city LIMIT 1");
$stmtSeedInsert = $pdo->prepare("INSERT INTO phil_locations (province, city) VALUES (:province, :city)");
foreach ($seedData as $row) {
    $stmtSeedExists->execute([':province' => $row[0], ':city' => $row[1]]);
    if (!$stmtSeedExists->fetchColumn()) {
        $stmtSeedInsert->execute([':province' => $row[0], ':city' => $row[1]]);
    }
}

// Build province -> cities map from reference table
$locations = $pdo->query("SELECT province, city FROM phil_locations ORDER BY province, city")
    ->fetchAll(PDO::FETCH_ASSOC);
$provinceCities = [];
foreach ($locations as $loc) {
    $province = $loc['province'];
    $city = $loc['city'];
    if (!isset($provinceCities[$province])) {
        $provinceCities[$province] = [];
    }
    $provinceCities[$province][] = $city;
}

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?redirect=address');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Check for success redirect
if (isset($_GET['success'])) {
    $success_message = 'Address added successfully.';
}

function normalize_space(string $v): string {
    return trim(preg_replace('/\s+/', ' ', $v));
}

// Check for redirect message from checkout
if (isset($_SESSION['checkout_redirect_message'])) {
    $error_message = $_SESSION['checkout_redirect_message'];
    unset($_SESSION['checkout_redirect_message']);
}

// Handle add address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $full_name = normalize_space($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address_line1 = normalize_space($_POST['address_line1'] ?? '');
    $address_line2 = normalize_space($_POST['address_line2'] ?? '');
    $city = normalize_space($_POST['city'] ?? '');
    $province = normalize_space($_POST['state'] ?? ''); // UI label says State/Province
    $postal_code = trim($_POST['postal_code'] ?? '');
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    $errors = [];

    // Full Name: required, min 2 chars, letters & spaces only
    if ($full_name === '' || mb_strlen($full_name) < 2 || !preg_match('/^[A-Za-z ]+$/', $full_name)) {
        $errors[] = 'Full Name is required and must contain only letters and spaces (min 2 characters).';
    }

    // Phone: PH mobile 09XXXXXXXXX or +639XXXXXXXXX
    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $phone)) {
        $errors[] = 'Phone Number must be a valid PH mobile (09XXXXXXXXX or +639XXXXXXXXX).';
    }

    // Address Line 1: required, min length 5
    if ($address_line1 === '' || mb_strlen($address_line1) < 5) {
        $errors[] = 'Address Line 1 must be at least 5 characters (complete street address).';
    }

    // Province: required and must be in predefined list
    $provinceKey = null;
    foreach ($provinceCities as $prov => $cities) {
        if (strcasecmp($prov, $province) === 0) {
            $provinceKey = $prov;
            break;
        }
    }
    if (!$provinceKey) {
        $errors[] = 'Please select a valid Philippine province.';
    }

    // City: required and must belong to province
    if ($city === '') {
        $errors[] = 'City/Municipality is required.';
    } elseif ($provinceKey) {
        $validCity = false;
        foreach ($provinceCities[$provinceKey] as $allowedCity) {
            if (strcasecmp($allowedCity, $city) === 0) {
                $validCity = true;
                // Normalize to canonical casing
                $city = $allowedCity;
                break;
            }
        }
        if (!$validCity) {
            $errors[] = 'City/Municipality must belong to the selected province.';
        }
    }

    // Postal Code: exactly 4 digits
    if (!preg_match('/^\d{4}$/', $postal_code)) {
        $errors[] = 'Postal Code must be exactly 4 digits.';
    }

    if ($errors) {
        $error_message = implode(' ', $errors);
    } else {
        // If this is set as default, unset other defaults
        if ($is_default) {
            $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = :uid")->execute([':uid' => $user_id]);
        }
        
        $insert_sql = "INSERT INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, postal_code, is_default) 
                       VALUES (:uid, :fname, :phone, :addr1, :addr2, :city, :state, :postal, :def)";
        $stmt = $pdo->prepare($insert_sql);
        
        if ($stmt->execute([
            ':uid' => $user_id,
            ':fname' => $full_name,
            ':phone' => $phone,
            ':addr1' => $address_line1,
            ':addr2' => $address_line2,
            ':city' => $city,
            ':state' => $provinceKey ?? $province,
            ':postal' => $postal_code,
            ':def' => $is_default
        ])) {
            // Redirect to prevent duplicate form submission on page reload
            header('Location: address.php?success=1');
            exit();
        } else {
            $error_message = 'Failed to add address.';
        }
    }
}

// Handle set default
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_default'])) {
    $address_id = (int) $_POST['address_id'];
    $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = :uid")->execute([':uid' => $user_id]);
    $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE address_id = :aid AND user_id = :uid")
        ->execute([':aid' => $address_id, ':uid' => $user_id]);
    $success_message = 'Default address updated.';
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    $address_id = (int) $_POST['address_id'];
    $pdo->prepare("DELETE FROM addresses WHERE address_id = :aid AND user_id = :uid")
        ->execute([':aid' => $address_id, ':uid' => $user_id]);
    $success_message = 'Address deleted successfully.';
}

// Fetch addresses
$addresses_sql = "SELECT * FROM addresses WHERE user_id = :uid ORDER BY is_default DESC, created_at DESC";
$stmt = $pdo->prepare($addresses_sql);
$stmt->execute([':uid' => $user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$pageTitle = 'My Addresses - Jeweluxe';
include 'includes/header.php';
?>
<link rel="stylesheet" href="styles.css">
<body class="order-history-page">

    <section class="orders-hero">
        <div class="container-xl">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light btn-sm" onclick="window.history.back();" type="button" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1 class="mb-0 text-white">My Addresses</h1>
            </div>
        </div>
    </section>

    <div class="orders-wrapper py-5">
        <div class="container-xl">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Saved Addresses</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                            + Add New Address
                        </button>
                    </div>

                    <?php if (empty($addresses)): ?>
                        <div class="card shadow-sm border-0 rounded-4">
                            <div class="card-body text-center py-5">
                                <div class="mb-3" style="font-size: 3rem;">📍</div>
                                <h5 class="mb-2">No addresses saved</h5>
                                <p class="text-muted mb-3">Add your shipping address to make checkout faster.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                    Add Address
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($addresses as $address): ?>
                                <div class="col-md-6">
                                    <div class="card shadow-sm border-0 rounded-4 h-100 <?php echo $address['is_default'] ? 'border-primary' : ''; ?>" style="<?php echo $address['is_default'] ? 'border: 2px solid #0d6efd !important;' : ''; ?>">
                                        <div class="card-body">
                                            <?php if ($address['is_default']): ?>
                                                <span class="badge bg-primary mb-2">Default</span>
                                            <?php endif; ?>
                                            <h6 class="mb-2"><?php echo htmlspecialchars($address['full_name']); ?></h6>
                                            <p class="mb-1 small"><?php echo htmlspecialchars($address['phone']); ?></p>
                                            <p class="mb-2 small text-muted">
                                                <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                                <?php if ($address['address_line2']): ?>
                                                    <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                                <?php echo htmlspecialchars($address['country']); ?>
                                            </p>
                                            <div class="d-flex gap-2 mt-3">
                                                <?php if (!$address['is_default']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                                        <button type="submit" name="set_default" class="btn btn-sm btn-outline-primary">Set as Default</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="d-inline delete-address-form">
                                                    <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-address-btn">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body">
                            <h6 class="mb-3">💡 Address Tips</h6>
                            <ul class="small text-muted">
                                <li>Mark your most-used address as default</li>
                                <li>Include apartment/unit numbers</li>
                                <li>Provide accurate phone number</li>
                                <li>Double-check postal codes</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addAddressForm">
                    <div class="modal-body">
                        <!-- Validation Errors Alert -->
                        <div id="validationErrorsAlert" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
                            <strong>Please fix the following errors:</strong>
                            <ul id="validationErrorsList" class="mb-0 mt-2"></ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="address_line1" class="form-label">Address Line 1 *</label>
                            <input type="text" class="form-control" id="address_line1" name="address_line1" placeholder="Street address" required>
                        </div>
                        <div class="mb-3">
                            <label for="address_line2" class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" id="address_line2" name="address_line2" placeholder="Apt, suite, unit, etc.">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">Province *</label>
                                <select class="form-select" id="state" name="state" required>
                                    <option value="">Select province</option>
                                    <?php foreach ($provinceCities as $prov => $cities): ?>
                                        <option value="<?php echo htmlspecialchars($prov); ?>"><?php echo htmlspecialchars($prov); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <select class="form-select" id="city" name="city" required disabled>
                                    <option value="">Select city/municipality</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="postal_code" class="form-label">Postal Code *</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">
                                Set as default address
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_address" class="btn btn-primary">Save Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        // Province -> cities map from DB
        const provinceCities = <?php echo json_encode($provinceCities); ?>;

        // Validate form inputs and display errors in modal
        function validateAddressForm() {
            const errors = [];
            const fullName = document.getElementById('full_name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address1 = document.getElementById('address_line1').value.trim();
            const province = document.getElementById('state').value.trim();
            const city = document.getElementById('city').value.trim();
            const postalCode = document.getElementById('postal_code').value.trim();

            // Full Name validation: required, min 2 chars, letters & spaces only
            if (!fullName || fullName.length < 2) {
                errors.push('Full Name is required and must be at least 2 characters.');
            } else if (!/^[A-Za-z ]+$/.test(fullName)) {
                errors.push('Full Name must contain only letters and spaces.');
            }

            // Phone validation: PH mobile 09XXXXXXXXX or +639XXXXXXXXX
            if (!phone || !/^(09\d{9}|\+639\d{9})$/.test(phone)) {
                errors.push('Phone Number must be a valid PH mobile (09XXXXXXXXX or +639XXXXXXXXX).');
            }

            // Address Line 1 validation: required, min 5 chars
            if (!address1 || address1.length < 5) {
                errors.push('Address Line 1 must be at least 5 characters (complete street address).');
            }

            // Province validation: required
            if (!province) {
                errors.push('Please select a valid Philippine province.');
            }

            // City validation: required
            if (!city) {
                errors.push('City/Municipality is required.');
            }

            // Postal Code validation: exactly 4 digits
            if (!postalCode || !/^\d{4}$/.test(postalCode)) {
                errors.push('Postal Code must be exactly 4 digits.');
            }

            return errors;
        }

        // Display validation errors in modal
        function showValidationErrors(errors) {
            const alertDiv = document.getElementById('validationErrorsAlert');
            const errorsList = document.getElementById('validationErrorsList');
            
            if (errors.length > 0) {
                errorsList.innerHTML = errors.map(error => `<li>${error}</li>`).join('');
                alertDiv.style.display = 'block';
                alertDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return false;
            } else {
                alertDiv.style.display = 'none';
                return true;
            }
        }

        // Populate city options based on selected province
        function populateCities(provinceValue) {
            const citySelect = document.getElementById('city');
            citySelect.innerHTML = '<option value="">Select city/municipality</option>';
            const cities = provinceCities[provinceValue] || [];
            cities.forEach(city => {
                const opt = document.createElement('option');
                opt.value = city;
                opt.textContent = city;
                citySelect.appendChild(opt);
            });
            citySelect.disabled = cities.length === 0;
        }

        // Handle address delete with custom confirmation and wire up dropdown linkage
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('state');
            const citySelect = document.getElementById('city');
            const addAddressForm = document.getElementById('addAddressForm');

            provinceSelect.addEventListener('change', function() {
                populateCities(this.value);
            });

            const addAddressModal = document.getElementById('addAddressModal');
            addAddressModal.addEventListener('show.bs.modal', function() {
                provinceSelect.value = '';
                citySelect.innerHTML = '<option value="">Select city/municipality</option>';
                citySelect.disabled = true;
                document.getElementById('validationErrorsAlert').style.display = 'none';
            });

            // Handle form submission with client-side validation
            addAddressForm.addEventListener('submit', function(e) {
                const errors = validateAddressForm();
                if (!showValidationErrors(errors)) {
                    e.preventDefault();
                }
            });

            document.querySelectorAll('.delete-address-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const form = this.closest('.delete-address-form');
                    const addressId = form.querySelector('input[name="address_id"]').value;
                    
                    ConfirmModal.show(
                        '⚠️ Delete Address',
                        'Are you sure you want to delete this address?',
                        function() {
                            const formData = new FormData();
                            formData.append('address_id', addressId);
                            formData.append('delete_address', '1');
                            
                            fetch('', {
                                method: 'POST',
                                body: formData
                            }).then(() => {
                                ToastNotification.success('Address deleted successfully.');
                                setTimeout(() => location.reload(), 1500);
                            }).catch(error => {
                                ToastNotification.error('Error deleting address.');
                                console.error('Error:', error);
                            });
                        }
                    );
                });
            });
        });
    </script>