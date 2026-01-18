<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

// Validate order exists and belongs to user
if ($order_id <= 0) {
    header('Location: order_history.php');
    exit();
}

$order_sql = "SELECT * FROM orders WHERE order_id = :oid AND user_id = :uid";
$order_stmt = $pdo->prepare($order_sql);
$order_stmt->execute([':oid' => $order_id, ':uid' => $user_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: order_history.php');
    exit();
}

// Get user email for display
$user_sql = "SELECT email_address FROM users WHERE user_id = :uid";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([':uid' => $user_id]);
$user_result = $user_stmt->fetch(PDO::FETCH_ASSOC);
$user_email = $user_result ? $user_result['email_address'] : '';

// Generate transaction reference number
$transaction_id = 'BNK' . date('YmdHis') . mt_rand(1000, 9999);

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    // Update order payment status to paid
    $update_sql = "UPDATE orders SET payment_status = 'paid' WHERE order_id = :oid AND user_id = :uid";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([':oid' => $order_id, ':uid' => $user_id]);
    
    // Update payment record status
    $update_payment_sql = "UPDATE payments SET status = 'paid' WHERE order_id = :oid";
    $update_payment_stmt = $pdo->prepare($update_payment_sql);
    $update_payment_stmt->execute([':oid' => $order_id]);
    
    // Redirect to order confirmation page
    header('Location: order_confirmation.php?order_id=' . $order_id . '&transaction_id=' . $transaction_id . '&payment_method=bank_transfer');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Transfer Payment - Jeweluxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Back Button -->
                <div class="mb-4">
                    <button class="btn btn-outline-secondary" onclick="window.history.back();" type="button">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                </div>

                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;">
                                <i class="fas fa-university"></i>
                            </div>
                            <h2 class="mb-2">Bank Transfer Payment</h2>
                            <p class="text-muted">Complete your payment via bank transfer</p>
                        </div>

                        <!-- Order Summary -->
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Order #:</span>
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>₱<?php echo number_format($order['subtotal'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span>₱<?php echo number_format($order['shipping_cost'], 2); ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total Amount:</strong>
                                    <strong class="text-primary">₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Instructions -->
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading mb-3">
                                <i class="fas fa-info-circle"></i> Payment Instructions
                            </h5>
                            <ol class="mb-0">
                                <li class="mb-2">Visit your <strong>bank branch</strong> or use <strong>online banking</strong></li>
                                <li class="mb-2">Transfer to <strong>BDO Unibank</strong></li>
                                <li class="mb-2">Account Name: <code style="background: white; padding: 5px 10px; border-radius: 4px;">Jeweluxe Store</code></li>
                                <li class="mb-2">Account Number: <code style="background: white; padding: 5px 10px; border-radius: 4px;">1234-5678-9012</code></li>
                                <li class="mb-2">Enter amount: <strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></li>
                                <li class="mb-2">Use reference: <code style="background: white; padding: 5px 10px; border-radius: 4px;"><?php echo htmlspecialchars($order['order_number']); ?></code></li>
                                <li>Keep your transaction receipt and confirm below</li>
                            </ol>
                        </div>

                        <!-- Bank Details Box -->
                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">Transfer To This Account</h6>
                            </div>
                            <div class="card-body text-center py-4">
                                <div class="mb-3" style="font-size: 2rem;">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="mb-3">
                                    <h5 class="mb-1">BDO Unibank</h5>
                                    <p class="text-muted mb-0">Bank Name</p>
                                </div>
                                <div class="mb-3">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control text-center" value="Jeweluxe Store" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('Jeweluxe Store')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Account Name</small>
                                </div>
                                <div class="mb-3">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control text-center fw-bold" value="1234-5678-9012" readonly style="font-size: 1.1rem;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('1234-5678-9012')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Account Number</small>
                                </div>
                                <div class="alert alert-warning mb-0" style="font-size: 0.9rem;">
                                    <strong>Amount to Transfer:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Confirmation -->
                        <form method="POST" action="bank_transfer_payment.php?order_id=<?php echo $order_id; ?>" id="paymentForm">
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmPayment" name="payment_sent" required>
                                    <label class="form-check-label" for="confirmPayment">
                                        I have transferred ₱<?php echo number_format($order['total_amount'], 2); ?> to BDO Unibank account 1234-5678-9012 with reference <?php echo htmlspecialchars($order['order_number']); ?>
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mb-3">
                                <button type="button" class="btn btn-success btn-lg" id="confirmPaymentBtn">
                                    <i class="fas fa-check-circle"></i> Confirm Payment & Generate Receipt
                                </button>
                            </div>

                            <div class="text-center">
                                <p class="text-muted mb-0" style="font-size: 0.9rem;">
                                    Once confirmed, you'll receive a receipt. Your order will be marked as paid.
                                </p>
                            </div>
                        </form>

                        <!-- Help Section -->
                        <div class="card bg-light border-0 mt-4">
                            <div class="card-body">
                                <h6 class="card-title">Need Help?</h6>
                                <p class="small text-muted mb-2">If you encounter any issues with your bank transfer:</p>
                                <ul class="small text-muted mb-0">
                                    <li>Double-check the account name and number before transferring</li>
                                    <li>Ensure you have sufficient funds in your account</li>
                                    <li>Make sure the reference matches your Order #</li>
                                    <li>Keep your transaction receipt for verification</li>
                                    <li>Contact our support team if payment doesn't go through</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                ToastNotification.success('Copied to clipboard!');
            });
        }

        // Handle Bank Transfer payment confirmation with custom modal
        document.addEventListener('DOMContentLoaded', function() {
            const confirmBtn = document.getElementById('confirmPaymentBtn');
            const checkbox = document.getElementById('confirmPayment');
            const paymentForm = document.getElementById('paymentForm');
            
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Check if checkbox is checked
                    if (!checkbox || !checkbox.checked) {
                        ToastNotification.warning('Please confirm that you have completed the bank transfer before proceeding.');
                        return;
                    }
                    
                    ConfirmModal.show(
                        '🏦 Confirm Bank Transfer Payment',
                        'Have you completed the bank transfer? Please confirm only after transferring the payment.',
                        function() {
                            if (paymentForm) {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'confirm_payment';
                                input.value = '1';
                                paymentForm.appendChild(input);
                                
                                // Show loading state
                                confirmBtn.disabled = true;
                                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing Payment...';
                                
                                // Submit the form
                                paymentForm.submit();
                            } else {
                                ToastNotification.error('Form not found. Please refresh and try again.');
                            }
                        }
                    );
                });
            }
        });
    </script>
</body>
</html>
