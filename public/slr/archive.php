<?php
/**
 * Document Archive Management Page
 * View and manage archived documents (SLR, etc.)
 */

// Centralized initialization
require_once '../../public/init.php';

// Enforce role-based access control
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

// Initialize services
$documentArchive = new DocumentArchiveService();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validateRequest()) {
        $_SESSION['error'] = 'Invalid security token.';
        header('Location: archive.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete' && isset($_POST['archive_id'])) {
        $deleteFile = isset($_POST['delete_file']) && $_POST['delete_file'] === '1';
        
        if ($documentArchive->deleteArchivedDocument($_POST['archive_id'], $deleteFile)) {
            $_SESSION['success'] = 'Document deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete document: ' . $documentArchive->getErrorMessage();
        }
        
        header('Location: archive.php');
        exit;
    }
    
    if ($action === 'update_status' && isset($_POST['archive_id'], $_POST['status'])) {
        if ($documentArchive->updateDocumentStatus($_POST['archive_id'], $_POST['status'])) {
            $_SESSION['success'] = 'Document status updated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update status: ' . $documentArchive->getErrorMessage();
        }
        
        header('Location: archive.php');
        exit;
    }
}

// Get filters
$filters = [];
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

if ($typeFilter) {
    $filters['document_type'] = $typeFilter;
}
if ($statusFilter) {
    $filters['status'] = $statusFilter;
}
if ($dateFrom) {
    $filters['date_from'] = $dateFrom;
}
if ($dateTo) {
    $filters['date_to'] = $dateTo;
}

$archivedDocuments = $documentArchive->getArchivedDocuments($filters);

// Filter by search term if provided
if ($searchTerm && !empty($archivedDocuments)) {
    $archivedDocuments = array_filter($archivedDocuments, function($doc) use ($searchTerm) {
        $clientName = strtolower($doc['client_name'] ?? '');
        $docNumber = strtolower($doc['document_number'] ?? '');
        $fileName = strtolower($doc['file_name'] ?? '');
        $search = strtolower($searchTerm);
        
        return strpos($clientName, $search) !== false || 
               strpos($docNumber, $search) !== false ||
               strpos($fileName, $search) !== false;
    });
}

// Get statistics
$statistics = $documentArchive->getDocumentStatistics();

$pageTitle = 'Document Archive';
include_once BASE_PATH . '/templates/layout/header.php';
?>

<main class="main-content">
  <div class="content-wrapper">
    <div class="container-fluid mt-4">
      <!-- Statistics Cards -->
      <div class="row mb-4">
        <?php 
        $totalDocs = 0;
        $totalSize = 0;
        $totalDownloads = 0;
        foreach ($statistics as $stat) {
            $totalDocs += $stat['count'];
            $totalSize += $stat['total_size'];
            $totalDownloads += $stat['total_downloads'];
        }
        ?>
        
        <div class="col-md-3">
          <div class="card bg-primary text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="card-title">Total Documents</h6>
                  <h3><?php echo number_format($totalDocs); ?></h3>
                </div>
                <div class="align-self-center">
                  <i data-feather="file-text" size="32"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-3">
          <div class="card bg-success text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="card-title">Storage Used</h6>
                  <h3><?php echo $totalSize > 0 ? number_format($totalSize / 1024 / 1024, 1) . ' MB' : '0 MB'; ?></h3>
                </div>
                <div class="align-self-center">
                  <i data-feather="hard-drive" size="32"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-3">
          <div class="card bg-info text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="card-title">Total Downloads</h6>
                  <h3><?php echo number_format($totalDownloads); ?></h3>
                </div>
                <div class="align-self-center">
                  <i data-feather="download" size="32"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-3">
          <div class="card bg-warning text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="card-title">Document Types</h6>
                  <h3><?php echo count(array_unique(array_column($statistics, 'document_type'))); ?></h3>
                </div>
                <div class="align-self-center">
                  <i data-feather="layers" size="32"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header bg-dark text-white">
              <h4 class="mb-0">
                <i data-feather="archive"></i> Document Archive
              </h4>
              <small>Manage generated documents and their storage</small>
            </div>
            <div class="card-body">
              <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                  <?php 
                  echo htmlspecialchars($_SESSION['error']);
                  unset($_SESSION['error']);
                  ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>
              
              <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                  <?php 
                  echo htmlspecialchars($_SESSION['success']);
                  unset($_SESSION['success']);
                  ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>
              
              <!-- Action Buttons -->
              <div class="row mb-3">
                <div class="col-md-12">
                  <div class="d-flex gap-2 flex-wrap">
                    <a href="../slr/" class="btn btn-primary">
                      <i data-feather="file-plus"></i> Generate New SLR
                    </a>
                    <a href="../slr/bulk.php" class="btn btn-success">
                      <i data-feather="layers"></i> Bulk Generate
                    </a>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                      <i data-feather="trash-2"></i> Cleanup Old Files
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Filters -->
              <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                  <label class="form-label">Search</label>
                  <input type="text" name="search" class="form-control" 
                         placeholder="Client name, document number, or filename"
                         value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Type</label>
                  <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="SLR" <?php echo $typeFilter === 'SLR' ? 'selected' : ''; ?>>SLR</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Status</label>
                  <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    <option value="deleted" <?php echo $statusFilter === 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">From Date</label>
                  <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label">To Date</label>
                  <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary">
                    <i data-feather="search"></i>
                  </button>
                </div>
              </form>
              
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead class="table-dark">
                    <tr>
                      <th>Document</th>
                      <th>Type</th>
                      <th>Loan ID</th>
                      <th>Client</th>
                      <th>File Size</th>
                      <th>Generated</th>
                      <th>Downloads</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($archivedDocuments)): ?>
                      <?php foreach ($archivedDocuments as $doc): ?>
                        <tr>
                          <td>
                            <strong><?php echo htmlspecialchars($doc['document_number']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($doc['file_name']); ?></small>
                          </td>
                          <td>
                            <span class="badge bg-info"><?php echo htmlspecialchars($doc['document_type']); ?></span>
                          </td>
                          <td><?php echo $doc['loan_id']; ?></td>
                          <td><?php echo htmlspecialchars($doc['client_name'] ?? 'N/A'); ?></td>
                          <td><?php echo number_format($doc['file_size'] / 1024, 1); ?> KB</td>
                          <td>
                            <?php echo date('M d, Y', strtotime($doc['generated_at'])); ?><br>
                            <small class="text-muted">by <?php echo htmlspecialchars($doc['generated_by_username']); ?></small>
                          </td>
                          <td>
                            <?php echo $doc['download_count']; ?>
                            <?php if ($doc['last_downloaded_at']): ?>
                              <br><small class="text-muted">
                                Last: <?php echo date('M d', strtotime($doc['last_downloaded_at'])); ?>
                              </small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="badge bg-<?php 
                                echo $doc['status'] === 'active' ? 'success' : 
                                    ($doc['status'] === 'archived' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($doc['status']); ?>
                            </span>
                          </td>
                          <td>
                            <div class="btn-group btn-group-sm">
                              <a href="download.php?id=<?php echo $doc['id']; ?>" 
                                 class="btn btn-outline-primary" title="Download">
                                <i data-feather="download"></i>
                              </a>
                              <button type="button" class="btn btn-outline-warning" 
                                      data-bs-toggle="modal" data-bs-target="#statusModal"
                                      data-id="<?php echo $doc['id']; ?>"
                                      data-status="<?php echo $doc['status']; ?>"
                                      title="Change Status">
                                <i data-feather="edit"></i>
                              </button>
                              <button type="button" class="btn btn-outline-danger" 
                                      data-bs-toggle="modal" data-bs-target="#deleteModal"
                                      data-id="<?php echo $doc['id']; ?>"
                                      data-name="<?php echo htmlspecialchars($doc['document_number']); ?>"
                                      title="Delete">
                                <i data-feather="trash-2"></i>
                              </button>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                          <i data-feather="inbox" class="mb-2"></i>
                          <p>No archived documents found.</p>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              
              <div class="mt-3">
                <p class="text-muted">
                  <strong>Total:</strong> <?php echo count($archivedDocuments); ?> document(s) found
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="archive_id" id="statusArchiveId">
        
        <div class="modal-header">
          <h5 class="modal-title">Update Document Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="statusSelect" class="form-select" required>
              <option value="active">Active</option>
              <option value="archived">Archived</option>
              <option value="deleted">Deleted</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="archive_id" id="deleteArchiveId">
        
        <div class="modal-header">
          <h5 class="modal-title">Delete Document</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete document <strong id="deleteDocumentName"></strong>?</p>
          
          <div class="form-check">
            <input type="checkbox" name="delete_file" value="1" class="form-check-input" id="deleteFileCheck">
            <label class="form-check-label" for="deleteFileCheck">
              Also delete the physical file from storage
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete Document</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Cleanup Modal -->
<div class="modal fade" id="cleanupModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cleanup Old Documents</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>This feature will be available in a future update.</p>
        <p>It will allow you to automatically remove documents older than a specified number of days.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status modal handling
    const statusModal = document.getElementById('statusModal');
    statusModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const archiveId = button.getAttribute('data-id');
        const currentStatus = button.getAttribute('data-status');
        
        document.getElementById('statusArchiveId').value = archiveId;
        document.getElementById('statusSelect').value = currentStatus;
    });
    
    // Delete modal handling
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const archiveId = button.getAttribute('data-id');
        const documentName = button.getAttribute('data-name');
        
        document.getElementById('deleteArchiveId').value = archiveId;
        document.getElementById('deleteDocumentName').textContent = documentName;
    });
});
</script>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>