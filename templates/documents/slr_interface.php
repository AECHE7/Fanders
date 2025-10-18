<!-- SLR Document Generation Interface Template -->
<div class="container-fluid">
    <div class="row">
        <!-- Single Loan SLR -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-pdf"></i> Generate SLR for Single Loan
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Generate a Statement of Loan Repayment document for a specific loan.</p>
                    <form action="<?= APP_URL ?>/public/documents/slr.php?action=generate" method="post">
                        <?= $csrf->getTokenField() ?>
                        <div class="mb-3">
                            <label for="loan_id" class="form-label">Select Active Loan</label>
                            <select class="form-select" id="loan_id" name="loan_id" required>
                                <option value="">Choose a loan...</option>
                                <?php foreach ($activeLoans as $loan): ?>
                                    <option value="<?= $loan['id'] ?>" data-client="<?= htmlspecialchars($loan['client_name']) ?>">
                                        #<?= $loan['id'] ?> - <?= htmlspecialchars($loan['client_name']) ?>
                                        (₱<?= number_format($loan['total_loan_amount'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Only active loans are available for SLR generation.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download"></i> Generate & Download SLR
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Client SLR -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> Generate SLR for All Client Loans
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Generate SLR documents for all loans of a specific client.</p>
                    <form action="<?= APP_URL ?>/public/documents/slr.php?action=client" method="post">
                        <?= $csrf->getTokenField() ?>
                        <div class="mb-3">
                            <label for="client_id" class="form-label">Select Client</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Choose a client...</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>">
                                        <?= htmlspecialchars($client['name']) ?> (ID: <?= $client['id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Generates SLRs for all active and completed loans of the selected client.</div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-download"></i> Generate Client SLRs
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk SLR Generation -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-file-archive"></i> Bulk SLR Generation
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Generate SLR documents for multiple loans at once. This creates a ZIP file containing all selected SLRs for efficient batch processing.</p>
                    <form action="<?= APP_URL ?>/public/documents/slr.php?action=bulk" method="post" id="bulkSLRForm">
                        <?= $csrf->getTokenField() ?>
                        <div class="mb-3">
                            <label class="form-label">Select Loans for Bulk Generation</label>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAllLoans()">Select All</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllLoans()">Clear All</button>
                                </div>
                                <?php foreach ($activeLoans as $loan): ?>
                                    <div class="form-check">
                                        <input class="form-check-input bulk-loan-checkbox" type="checkbox" name="loan_ids[]" value="<?= $loan['id'] ?>" id="loan_<?= $loan['id'] ?>">
                                        <label class="form-check-label" for="loan_<?= $loan['id'] ?>">
                                            <strong>#<?= $loan['id'] ?></strong> -
                                            <?= htmlspecialchars($loan['client_name']) ?>
                                            <span class="text-muted">(₱<?= number_format($loan['total_loan_amount'], 2) ?>)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">
                                <span id="selected-count">0</span> loans selected
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning" id="bulkSubmitBtn" disabled>
                            <i class="fas fa-file-archive"></i> Generate Bulk SLR Documents (ZIP)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- SLR Information Panel -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> About SLR Documents
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>What is an SLR?</h6>
                            <p class="mb-2">SLR stands for <strong>Statement of Loan Repayment</strong>. It is an official document that provides a comprehensive overview of a client's loan repayment status.</p>

                            <h6>When to Generate SLR?</h6>
                            <ul class="mb-2">
                                <li>Client requests loan status documentation</li>
                                <li>End of loan term for record keeping</li>
                                <li>Audit and compliance requirements</li>
                                <li>Client transfers or account reviews</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>What Information is Included?</h6>
                            <ul class="mb-2">
                                <li>Loan details (amount, term, dates)</li>
                                <li>Client information</li>
                                <li>Complete payment history</li>
                                <li>Outstanding balance</li>
                                <li>Loan breakdown (principal, interest, insurance)</li>
                            </ul>

                            <h6>Document Formats</h6>
                            <p class="mb-0">SLRs are generated as PDF documents for professional presentation and long-term archival.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update selected count and button state
function updateBulkSelection() {
    const checkboxes = document.querySelectorAll('.bulk-loan-checkbox');
    const checkedBoxes = document.querySelectorAll('.bulk-loan-checkbox:checked');
    const selectedCount = checkedBoxes.length;
    const submitBtn = document.getElementById('bulkSubmitBtn');

    document.getElementById('selected-count').textContent = selectedCount;
    submitBtn.disabled = selectedCount === 0;
}

// Select all loans
function selectAllLoans() {
    const checkboxes = document.querySelectorAll('.bulk-loan-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = true);
    updateBulkSelection();
}

// Clear all loans
function clearAllLoans() {
    const checkboxes = document.querySelectorAll('.bulk-loan-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    updateBulkSelection();
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Update count when checkboxes change
    document.querySelectorAll('.bulk-loan-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkSelection);
    });

    // Initialize count on page load
    updateBulkSelection();
});
</script>
