<?php
/**
 * Handle approval and rejection of borrow and return requests
 */

require_once '../../app/config/config.php';

ob_start();

function autoload($className) {
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

spl_autoload_register('autoload');

$session = new Session();
$auth = new AuthService();

if (!$auth->isLoggedIn() || !$auth->hasRole(['super-admin', 'admin'])) {
    $session->setFlash('error', 'You must be logged in as an admin to access this page.');
    header('Location: ' . APP_URL . '/public/auth/login.php');
    exit;
}

$transactionService = new TransactionService();
$csrf = new CSRF();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if (!$transactionId || !in_array($action, ['approve', 'reject'])) {
            $error = 'Invalid request.';
        } else {
            $transaction = $transactionService->getTransactionById($transactionId);
            if (!$transaction) {
                $error = 'Transaction not found.';
            } else {
                if ($action === 'approve') {
                    if ($transaction['status'] === 'borrowing') {
                        // Approve borrow request
                        $result = $transactionService->approveBorrowRequest($transactionId);
                    } elseif ($transaction['status'] === 'returning') {
                        // Approve return request
                        $result = $transactionService->approveReturnRequest($transactionId);
                    } else {
                        $error = 'Invalid transaction status for approval.';
                    }
                    if (isset($result) && $result) {
                        $success = 'Transaction approved successfully.';
                    } else {
                        $error = $transactionService->getErrorMessage() ?: 'Failed to approve transaction.';
                    }
                } elseif ($action === 'reject') {
                    // Reject request by updating status to 'rejected'
                    $updateData = [
                        'status' => 'rejected',
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    if ($transactionService->updateTransaction($transactionId, $updateData)) {
                        $success = 'Transaction request rejected.';
                    } else {
                        $error = $transactionService->getErrorMessage() ?: 'Failed to reject transaction.';
                    }
                }
            }
        }
    }
}

// Get all pending approval transactions with new statuses
$pendingTransactions = $transactionService->getTransactionsByStatuses(['borrowing', 'returning']);

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Record Borrow and Return Book</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (empty($pendingTransactions)): ?>
        <p>No pending records at this time.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Book Title</th>
                        <th>Borrower</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingTransactions as $transaction): ?>
                        <tr>
                            <td><?= htmlspecialchars($transaction['id']) ?></td>
                            <td><?= htmlspecialchars($transaction['book_title']) ?></td>
                            <td><?= htmlspecialchars($transaction['borrower_name']) ?></td>
                            <td><?= htmlspecialchars($transaction['borrow_date']) ?></td>
                            <td><?= htmlspecialchars($transaction['due_date']) ?></td>
                            <td>
                                <?php if ($transaction['status'] == 'borrowing'): ?>
                                    <span class="badge bg-warning text-dark">Borrowing</span>
                                <?php elseif ($transaction['status'] == 'returning'): ?>
                                    <span class="badge bg-warning text-dark">Returning</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($transaction['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-success btn-sm btn-approve-request" 
                                        data-transaction-id="<?= htmlspecialchars($transaction['id']) ?>"
                                        data-book-title="<?= htmlspecialchars($transaction['book_title']) ?>"
                                        data-borrower-name="<?= htmlspecialchars($transaction['borrower_name']) ?>">
                                    Record
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    </div>
</main>

<!-- Approve Request Modal -->
<div class="modal fade" id="approveRequestModal" tabindex="-1" aria-labelledby="approveRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approveRequestModalLabel">
                    <i data-feather="check-circle"></i> Confirm Payment Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You are about to record payment for:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Transaction ID:</strong> <span id="modalTransactionId"></span></li>
                    <li><strong>Book:</strong> <span id="modalBookTitle"></span></li>
                    <li><strong>Borrower:</strong> <span id="modalBorrowerName"></span></li>
                </ul>
                <div class="alert alert-info mt-3">
                    <i data-feather="info"></i> This will approve and record the payment request.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" id="approveRequestForm" style="display:inline;">
                    <input type="hidden" name="transaction_id" id="formTransactionId">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->getToken()) ?>">
                    <button type="submit" name="action" value="approve" class="btn btn-success">
                        <i data-feather="check-circle"></i> Confirm Record
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const approveButtons = document.querySelectorAll('.btn-approve-request');
    const modal = new bootstrap.Modal(document.getElementById('approveRequestModal'));
    
    approveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const transactionId = this.getAttribute('data-transaction-id');
            const bookTitle = this.getAttribute('data-book-title');
            const borrowerName = this.getAttribute('data-borrower-name');
            
            document.getElementById('modalTransactionId').textContent = transactionId;
            document.getElementById('modalBookTitle').textContent = bookTitle;
            document.getElementById('modalBorrowerName').textContent = borrowerName;
            document.getElementById('formTransactionId').value = transactionId;
            
            modal.show();
        });
    });
});
</script>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
