<?php
if (empty($penalties)) {
    echo '<p>No penalties found matching the selected criteria.</p>';
} else {
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Book Title</th>
                    <th>Borrow Date</th>
                    <th>Days Overdue</th>
                    <th>Penalty Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            foreach ($penalties as $penalty) {
                ?>
                <tr>
                    <td><?= htmlspecialchars($i) ?></td>
                    <td><?= htmlspecialchars($penalty['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($penalty['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($penalty['book_title'] ?? '') ?></td>
                    <td><?= isset($penalty['borrow_date']) && $penalty['borrow_date'] ? date('Y-m-d', strtotime($penalty['borrow_date'])) : 'N/A' ?></td>
                    <td><?= htmlspecialchars($penalty['days_overdue'] ?? '0') ?></td>
                    <td>₱<?= isset($penalty['penalty_amount']) ? number_format($penalty['penalty_amount'], 2) : '0.00' ?></td>
                    <td><?= isset($penalty['status']) && $penalty['status'] == 1 ? 'Paid' : 'Unpaid' ?></td>
                </tr>
                <?php
                $i++;
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
