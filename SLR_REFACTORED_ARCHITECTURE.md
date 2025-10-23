# Refactored SLR System Architecture

## Overview

The Statement of Loan Receipt (SLR) system has been completely refactored to provide better maintainability, error handling, and consistency. The new architecture follows SOLID principles and provides clear separation of concerns.

## What Changed

### Before (Legacy Architecture)
- **Multiple service classes**: `SLRService`, `SLRDocumentService`, `LoanReleaseService` with overlapping responsibilities
- **Inconsistent trigger naming**: Mixed use of `'manual'`, `'manual_request'`, `'loan_approval'`, `'auto_approval'`, `'loan_disbursement'`, `'auto_disbursement'`
- **Poor error handling**: Functions returned `false` with unclear error messages
- **Mixed concerns**: PDF generation, validation, database access, and business logic all in one class
- **Silent failures**: SLR generation errors during loan approval/disbursement were logged but not visible to users

### After (Refactored Architecture)
- **Single responsibility**: Each class has one clear purpose
- **Standardized constants**: All triggers, statuses, and access types use `SLRConstants`
- **Result objects**: Operations return `SLRResult` with clear success/failure states and error codes
- **Clean separation**: Validator, Repository, PDF Generator, and Service are separate classes
- **Comprehensive logging**: All operations log to both `slr_access_log` and `transaction_logs` with context

## New Architecture Components

### 1. Constants (`app/constants/SLRConstants.php`)

Defines all SLR-related constants:

```php
use App\Constants\SLRConstants;

// Trigger Events
SLRConstants::TRIGGER_MANUAL                    // 'manual'
SLRConstants::TRIGGER_LOAN_APPROVAL             // 'loan_approval'
SLRConstants::TRIGGER_LOAN_DISBURSEMENT         // 'loan_disbursement'

// Document Status
SLRConstants::STATUS_ACTIVE                     // 'active'
SLRConstants::STATUS_ARCHIVED                   // 'archived'
SLRConstants::STATUS_REPLACED                   // 'replaced'
SLRConstants::STATUS_VOID                       // 'void'

// Access Types
SLRConstants::ACCESS_GENERATION                 // 'generation'
SLRConstants::ACCESS_VIEW                       // 'view'
SLRConstants::ACCESS_DOWNLOAD                   // 'download'
SLRConstants::ACCESS_ARCHIVE                    // 'archive'
```

**Helper Methods**:
- `SLRConstants::getValidTriggers()` - Returns array of valid triggers
- `SLRConstants::getTriggerLabel($trigger)` - Human-readable trigger name
- `SLRConstants::getStatusBadgeClass($status)` - Bootstrap badge class for status

### 2. Result Object (`app/services/SLR/SLRResult.php`)

Replaces returning `false` with rich result objects:

```php
use App\Services\SLR\SLRResult;

// Check success
if ($result->isSuccess()) {
    $data = $result->getData();
}

// Check failure
if ($result->isFailure()) {
    $errorMessage = $result->getErrorMessage();
    $errorCode = $result->getErrorCode();  // e.g., 'LOAN_NOT_FOUND', 'INVALID_TRIGGER'
}

// Get data or default
$data = $result->getDataOr($defaultValue);

// Get data or throw
$data = $result->getDataOrThrow();  // throws Exception on failure
```

### 3. Validator (`app/services/SLR/SLRValidator.php`)

Handles all validation logic:

```php
$validator = new SLRValidator($db);

// Validate if SLR can be generated
$validationResult = $validator->canGenerateSLR($loan, $trigger);

// Get generation rule
$rule = $validator->getGenerationRule(SLRConstants::TRIGGER_LOAN_DISBURSEMENT);

// Check requirements
$requiresSig = $validator->requiresSignature($trigger);
$notifyClient = $validator->requiresClientNotification($trigger);
```

### 4. Repository (`app/services/SLR/SLRRepository.php`)

Handles all database operations:

```php
$repository = new SLRRepository($db);

// Create SLR record
$slrId = $repository->createSLR($slrData);

// Get SLR documents
$slr = $repository->getSLRById($slrId);
$slr = $repository->getSLRByLoanId($loanId);
$slrs = $repository->listSLRDocuments($filters, $limit, $offset);

// Update status
$repository->updateSLRStatus($slrId, SLRConstants::STATUS_ARCHIVED, $newPath, $reason);

// Log access
$repository->logAccess($slrId, SLRConstants::ACCESS_DOWNLOAD, $userId, $reason);
```

### 5. PDF Generator (`app/services/SLR/SLRPDFGenerator.php`)

Handles PDF document creation:

```php
$pdfGenerator = new SLRPDFGenerator();

$pdfResult = $pdfGenerator->createSLRPDF($loan, $calculationService);

if ($pdfResult->isSuccess()) {
    $pdfContent = $pdfResult->getData();
}
```

### 6. Refactored Service (`app/services/SLR/SLRServiceRefactored.php`)

Main service class that orchestrates all components:

```php
use App\Services\SLR\SLRServiceRefactored;
use App\Constants\SLRConstants;

$slrService = new SLRServiceRefactored();

// Generate SLR
$result = $slrService->generateSLR(
    $loanId, 
    $generatedBy, 
    SLRConstants::TRIGGER_LOAN_DISBURSEMENT
);

// Download SLR
$result = $slrService->downloadSLR($slrId, $userId, $reason);

// Archive SLR
$result = $slrService->archiveSLR($slrId, $userId, $reason);

// List SLRs
$slrs = $slrService->listSLRDocuments($filters, $limit, $offset);
```

### 7. Backwards-Compatible Adapter (`app/services/SLRServiceAdapter.php`)

Maintains compatibility with existing code:

```php
// Old code continues to work:
$slrService = new SLRServiceAdapter();  // or rename to SLRService
$slr = $slrService->generateSLR($loanId, $userId, 'loan_approval');  // returns array|false

// Adapter automatically:
// 1. Maps old trigger names to new constants
// 2. Converts Result objects to array|false
// 3. Sets error messages on BaseService
// 4. Logs detailed errors
```

## Migration Guide

### Step 1: Use Constants Everywhere

**Before:**
```php
$slrService->generateSLR($loanId, $userId, 'loan_disbursement');
$slr['status'] === 'active';
```

**After:**
```php
use App\Constants\SLRConstants;

$slrService->generateSLR($loanId, $userId, SLRConstants::TRIGGER_LOAN_DISBURSEMENT);
$slr['status'] === SLRConstants::STATUS_ACTIVE;
```

### Step 2: Handle Results Properly

**Before:**
```php
$slr = $slrService->generateSLR($loanId, $userId, 'manual');
if (!$slr) {
    echo "Failed: " . $slrService->getErrorMessage();
}
```

**After (using refactored service):**
```php
$result = $slrService->generateSLR($loanId, $userId, SLRConstants::TRIGGER_MANUAL);
if ($result->isFailure()) {
    error_log("SLR Generation Failed: " . $result->getErrorMessage());
    error_log("Error Code: " . $result->getErrorCode());
}
$slr = $result->getData();
```

**After (using adapter for backwards compatibility):**
```php
// No changes needed! Adapter handles conversion automatically
$slr = $slrService->generateSLR($loanId, $userId, 'manual');
if (!$slr) {
    // Error message is set on $slrService->getErrorMessage()
    // Detailed error is logged automatically
}
```

### Step 3: Update LoanService Integration

**Before (in `LoanService::disburseLoan`):**
```php
// Check if auto-generation is enabled for disbursement
$sql = "SELECT auto_generate FROM slr_generation_rules
        WHERE trigger_event = 'loan_disbursement' AND is_active = true LIMIT 1";
$stmt = $this->db->prepare($sql);
$stmt->execute();
$rule = $stmt->fetch(PDO::FETCH_ASSOC);

if ($rule && $rule['auto_generate']) {
    $slrDocument = $slrService->generateSLR($id, $_SESSION['user_id'] ?? 1, 'loan_disbursement');
    if ($slrDocument) {
        error_log('SLR generated...');
    } else {
        error_log('Failed: ' . $slrService->getErrorMessage());
    }
}
```

**After:**
```php
use App\Constants\SLRConstants;

// Validator handles rule checking internally
$result = $slrService->generateSLR(
    $id, 
    $_SESSION['user_id'] ?? 1, 
    SLRConstants::TRIGGER_LOAN_DISBURSEMENT
);

if ($result->isSuccess()) {
    $slrDoc = $result->getData();
    error_log("SLR generated: " . $slrDoc['document_number']);
} else {
    // Failure is already logged with context by the service
    // But we can add application-specific context
    error_log("Loan $id disbursed but SLR generation failed: " . $result->getErrorMessage());
}
```

### Step 4: Update Endpoints

**Before (`public/slr/generate.php`):**
```php
$slrDocument = $slrService->generateSLR($loanId, $user['id'], 'manual_request');

if ($slrDocument) {
    $session->setFlash('success', 'SLR generated!');
} else {
    $session->setFlash('error', 'Failed: ' . $slrService->getErrorMessage());
}
```

**After:**
```php
use App\Constants\SLRConstants;

$result = $slrService->generateSLR(
    $loanId, 
    $user['id'], 
    SLRConstants::TRIGGER_MANUAL
);

if ($result->isSuccess()) {
    $slrDocument = $result->getData();
    $session->setFlash('success', 'SLR document generated successfully!');
    // Redirect to download with SLR ID
    header('Location: download.php?slr_id=' . $slrDocument['id']);
} else {
    $session->setFlash('error', 'Failed to generate SLR: ' . $result->getErrorMessage());
    // Optionally log error code for debugging
    error_log("SLR generation error: " . $result->getErrorCode());
    header('Location: index.php');
}
```

## Error Codes

The refactored system provides specific error codes for better debugging:

| Error Code | Meaning | Common Causes |
|------------|---------|---------------|
| `INVALID_TRIGGER` | Invalid trigger event | Using undefined trigger string |
| `LOAN_NOT_FOUND` | Loan doesn't exist | Invalid loan ID |
| `INVALID_LOAN_STATUS` | Loan status doesn't allow SLR | Loan is not approved/active/completed |
| `NO_GENERATION_RULE` | No rule for trigger | Missing rule in `slr_generation_rules` |
| `RULE_NOT_ACTIVE` | Rule exists but disabled | Rule has `is_active = false` |
| `PRINCIPAL_TOO_LOW` | Loan amount below minimum | Rule has `min_principal_amount` constraint |
| `PRINCIPAL_TOO_HIGH` | Loan amount above maximum | Rule has `max_principal_amount` constraint |
| `ACTIVE_SLR_EXISTS` | SLR already generated | Archive existing SLR first |
| `PDF_GENERATION_ERROR` | PDF creation failed | Check PDF generator logs |
| `FILE_SAVE_ERROR` | Can't save to disk | Check filesystem permissions |
| `DB_INSERT_ERROR` | Database insert failed | Check database logs |
| `SLR_NOT_FOUND` | SLR record not found | Invalid SLR ID |
| `SLR_NOT_ACTIVE` | SLR is archived/void | Can't download inactive SLR |
| `FILE_NOT_FOUND` | PDF file missing | File deleted from storage |
| `INTEGRITY_CHECK_FAILED` | File hash mismatch | File corrupted or tampered |
| `EXCEPTION` | Unexpected error | Check exception logs |

## Logging Improvements

The refactored system provides comprehensive logging:

### 1. Detailed Operation Logs
Every SLR operation logs detailed context:
```
[2025-10-23 10:15:32] SLR: Successfully generated SLR SLR-202510-000123 for loan 123 (trigger: loan_disbursement)
[2025-10-23 10:16:45] SLR: Validation failed for loan 124: Loan status 'application' doesn't allow SLR generation
[2025-10-23 10:17:12] SLR: PDF generation failed for loan 125: Memory limit exceeded
```

### 2. Dual Audit Trail
- **slr_access_log**: SLR-specific access tracking with IP and user agent
- **transaction_logs**: General audit trail integrated with system-wide events

### 3. Error Context
Failed operations log error code and full context:
```
[2025-10-23 10:18:00] SLR: Exception during generation for loan 126: PDOException - Connection timeout
[Stack trace...]
```

## Database Schema

### Required Tables

#### slr_documents
```sql
CREATE TABLE slr_documents (
    id SERIAL PRIMARY KEY,
    loan_id INTEGER NOT NULL REFERENCES loans(id),
    document_number VARCHAR(50) UNIQUE NOT NULL,
    generated_by INTEGER NOT NULL REFERENCES users(id),
    generation_trigger VARCHAR(50) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INTEGER NOT NULL,
    content_hash VARCHAR(64),
    client_signature_required BOOLEAN DEFAULT true,
    status VARCHAR(20) DEFAULT 'active',
    download_count INTEGER DEFAULT 0,
    last_downloaded_at TIMESTAMP,
    last_downloaded_by INTEGER REFERENCES users(id),
    replacement_reason TEXT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_slr_loan_id ON slr_documents(loan_id);
CREATE INDEX idx_slr_status ON slr_documents(status);
CREATE INDEX idx_slr_generated_at ON slr_documents(generated_at);
```

#### slr_generation_rules
```sql
CREATE TABLE slr_generation_rules (
    id SERIAL PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    description TEXT,
    trigger_event VARCHAR(50) NOT NULL,
    auto_generate BOOLEAN DEFAULT false,
    require_signatures BOOLEAN DEFAULT true,
    notify_client BOOLEAN DEFAULT false,
    notify_officers BOOLEAN DEFAULT false,
    min_principal_amount DECIMAL(15,2),
    max_principal_amount DECIMAL(15,2),
    is_active BOOLEAN DEFAULT true,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_slr_rules_trigger ON slr_generation_rules(trigger_event, is_active);
```

#### slr_access_log
```sql
CREATE TABLE slr_access_log (
    id SERIAL PRIMARY KEY,
    slr_document_id INTEGER NOT NULL REFERENCES slr_documents(id),
    access_type VARCHAR(50) NOT NULL,
    accessed_by INTEGER NOT NULL REFERENCES users(id),
    access_reason TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_slr_access_document ON slr_access_log(slr_document_id);
CREATE INDEX idx_slr_access_user ON slr_access_log(accessed_by);
CREATE INDEX idx_slr_access_date ON slr_access_log(accessed_at);
```

## Configuration

### Enable Auto-Generation

Use the provided scripts or update rules directly:

```bash
# Enable SLR auto-generation on loan disbursement
php enable_auto_slr_simple.php
```

Or via SQL:
```sql
UPDATE slr_generation_rules 
SET auto_generate = true, is_active = true
WHERE trigger_event = 'loan_disbursement';
```

### Configure Constraints

Set minimum/maximum loan amounts for SLR generation:

```sql
UPDATE slr_generation_rules 
SET min_principal_amount = 5000.00,
    max_principal_amount = 100000.00
WHERE trigger_event = 'loan_disbursement';
```

## Testing

### Manual Test Flow
1. Create a client
2. Create a loan application
3. Approve the loan → Check if SLR generated (if rule enabled)
4. Disburse the loan → Check if SLR generated (if rule enabled)
5. Download SLR → Verify file integrity
6. Check `slr_access_log` and `transaction_logs` for entries

### Verification Queries

```sql
-- Check SLR for a loan
SELECT * FROM slr_documents WHERE loan_id = 123;

-- Check SLR access history
SELECT sal.*, u.name 
FROM slr_access_log sal
JOIN users u ON sal.accessed_by = u.id
WHERE slr_document_id = 456
ORDER BY accessed_at DESC;

-- Check transaction logs for SLR events
SELECT * FROM transaction_logs 
WHERE entity_type = 'system' 
  AND action LIKE 'slr_%'
ORDER BY timestamp DESC;

-- Check generation rules
SELECT * FROM slr_generation_rules 
WHERE is_active = true
ORDER BY trigger_event;
```

## Benefits of Refactored Architecture

1. **Maintainability**: Clear separation of concerns makes code easier to understand and modify
2. **Testability**: Each component can be tested independently
3. **Error Handling**: Rich error codes and messages make debugging easier
4. **Consistency**: Constants eliminate string typos and inconsistencies
5. **Logging**: Comprehensive audit trail for compliance and debugging
6. **Backwards Compatibility**: Adapter allows gradual migration
7. **Extensibility**: Easy to add new triggers, statuses, or access types

## Next Steps

1. ✅ Constants defined
2. ✅ Result object created
3. ✅ Validator, Repository, PDF Generator extracted
4. ✅ Refactored service implemented
5. ✅ Backwards-compatible adapter created
6. ⏳ Update LoanService to use constants
7. ⏳ Update endpoints to use constants
8. ⏳ Create integration tests
9. ⏳ Migrate legacy code gradually
10. ⏳ Remove deprecated services after migration complete

## Support

For questions or issues with the refactored SLR system:
1. Check error logs for detailed error codes and messages
2. Verify database tables exist and rules are configured
3. Check file system permissions for SLR storage directories
4. Review this documentation for proper usage patterns
