<?php
/**
 * Reusable Bootstrap Confirmation Modal Component
 * 
 * Usage:
 * 1. Include this file: include_once BASE_PATH . '/templates/components/confirmation-modal.php';
 * 2. Call the function: renderConfirmationModal($config);
 * 
 * @param array $config Configuration array with the following keys:
 *   - id: Modal HTML ID (required)
 *   - title: Modal title (required)
 *   - icon: Feather icon name (optional, default: 'alert-circle')
 *   - headerClass: Header background class (optional, default: 'bg-primary')
 *   - confirmButtonClass: Confirm button class (optional, default: 'btn-primary')
 *   - confirmButtonText: Confirm button text (optional, default: 'Confirm')
 *   - cancelButtonText: Cancel button text (optional, default: 'Cancel')
 *   - bodyContent: Custom body content (optional)
 *   - showWarningSection: Show warning section (optional, default: true)
 */

function renderConfirmationModal($config) {
    // Set defaults
    $defaults = [
        'icon' => 'alert-circle',
        'headerClass' => 'bg-primary',
        'confirmButtonClass' => 'btn-primary',
        'confirmButtonText' => 'Confirm',
        'cancelButtonText' => 'Cancel',
        'showWarningSection' => true
    ];
    
    $config = array_merge($defaults, $config);
    
    // Validate required fields
    if (empty($config['id']) || empty($config['title'])) {
        throw new InvalidArgumentException('Modal ID and title are required');
    }
    
    $modalId = htmlspecialchars($config['id']);
    $title = htmlspecialchars($config['title']);
    $icon = htmlspecialchars($config['icon']);
    $headerClass = htmlspecialchars($config['headerClass']);
    $confirmButtonClass = htmlspecialchars($config['confirmButtonClass']);
    $confirmButtonText = htmlspecialchars($config['confirmButtonText']);
    $cancelButtonText = htmlspecialchars($config['cancelButtonText']);
    
    ob_start();
    ?>
    
<!-- <?= $title ?> Modal -->
<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header <?= $headerClass ?> text-white">
                <h5 class="modal-title" id="<?= $modalId ?>Label">
                    <i data-feather="<?= $icon ?>" class="me-2" style="width:20px;height:20px;"></i>
                    <?= $title ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($config['bodyContent'])): ?>
                    <?= $config['bodyContent'] ?>
                <?php else: ?>
                    <p class="mb-3">You are about to perform an action. Please confirm to continue.</p>
                    <div class="card bg-light">
                        <div class="card-body">
                            <div id="<?= $modalId ?>Details">
                                <!-- Dynamic content will be inserted here by JavaScript -->
                            </div>
                        </div>
                    </div>
                    <?php if ($config['showWarningSection']): ?>
                    <div id="<?= $modalId ?>Warning" class="alert alert-info mt-3">
                        <i data-feather="info" class="me-1" style="width:16px;height:16px;"></i>
                        Please review the details above before confirming.
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x" class="me-1" style="width:16px;height:16px;"></i>
                    <?= $cancelButtonText ?>
                </button>
                <button type="button" class="btn <?= $confirmButtonClass ?>" id="<?= $modalId ?>Confirm">
                    <i data-feather="check" class="me-1" style="width:16px;height:16px;"></i>
                    <?= $confirmButtonText ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-initialize feather icons when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    const modal<?= ucfirst($modalId) ?> = document.getElementById('<?= $modalId ?>');
    if (modal<?= ucfirst($modalId) ?>) {
        modal<?= ucfirst($modalId) ?>.addEventListener('shown.bs.modal', function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    }
});
</script>

    <?php
    return ob_get_clean();
}

/**
 * Helper function to create a simple confirmation modal
 * 
 * @param string $id Modal ID
 * @param string $title Modal title
 * @param string $type Type: 'danger', 'warning', 'success', 'info' (default: 'primary')
 * @return string Modal HTML
 */
function createSimpleConfirmationModal($id, $title, $type = 'primary') {
    $typeClasses = [
        'danger' => ['header' => 'bg-danger', 'button' => 'btn-danger', 'icon' => 'alert-triangle'],
        'warning' => ['header' => 'bg-warning', 'button' => 'btn-warning', 'icon' => 'alert-triangle'],
        'success' => ['header' => 'bg-success', 'button' => 'btn-success', 'icon' => 'check-circle'],
        'info' => ['header' => 'bg-info', 'button' => 'btn-info', 'icon' => 'info'],
        'primary' => ['header' => 'bg-primary', 'button' => 'btn-primary', 'icon' => 'alert-circle']
    ];
    
    $classes = $typeClasses[$type] ?? $typeClasses['primary'];
    
    return renderConfirmationModal([
        'id' => $id,
        'title' => $title,
        'icon' => $classes['icon'],
        'headerClass' => $classes['header'],
        'confirmButtonClass' => $classes['button']
    ]);
}

/**
 * Generate JavaScript helper for modal confirmation
 * 
 * @param string $modalId Modal ID
 * @param string $formId Form ID to submit when confirmed
 * @param array $updateCallbacks Array of JavaScript callbacks to update modal content
 * @return string JavaScript code
 */
function generateConfirmationModalJS($modalId, $formId, $updateCallbacks = []) {
    $callbacks = implode("\n    ", $updateCallbacks);
    
    return "
document.addEventListener('DOMContentLoaded', function() {
    const {$modalId}Modal = document.getElementById('{$modalId}');
    const {$modalId}Form = document.getElementById('{$formId}');
    const {$modalId}Confirm = document.getElementById('{$modalId}Confirm');
    
    if ({$modalId}Confirm && {$modalId}Form) {
        {$modalId}Confirm.addEventListener('click', function() {
            {$modalId}Form.submit();
        });
    }
    
    // Update modal content function
    window.update{$modalId}Content = function(data) {
        {$callbacks}
    };
});
";
}
?>