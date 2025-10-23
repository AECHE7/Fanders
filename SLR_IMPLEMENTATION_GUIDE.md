# SLR System Implementation Guide

**Date:** October 23, 2025  
**System:** Fanders Microfinance Loan Management  
**Feature:** Statement of Loan Receipt (SLR) Document Management

## Overview

The SLR (Statement of Loan Receipt) system has been refactored to provide a clean, maintainable architecture for automatic and manual generation of loan receipt documents throughout the loan lifecycle.

## Architecture Improvements

### 1. **Standardized Constants** (`app/constants/SLRConstants.php`)

All SLR-related strings are now centralized in constants to prevent inconsistencies:

```php
use App\Constants\SLRConstants;

// Triggers
SLRConstants::TRIGGER_MANUAL                 // 'manual'
SLRConstants::TRIGGER_LOAN_APPROVAL          // 'loan_approval'
SLRConstants::TRIGGER_LOAN_DISBURSEMENT      // 'loan_disbursement'

// Document Status
SLRConstants::STATUS_ACTIVE                  // 'active'
SLRConstants::STATUS_ARCHIVED                // 'archived'
SLRConstants::STATUS_REPLACED                // 'replaced'
SLRConstants::STATUS_VOID                    // 'void'

// Access Types
SLRConstants::ACCESS_GENERATION              // 'generation'
SLRConstants::ACCESS_VIEW                    // 'view'
SLRConstants::ACCESS_DOWNLOAD                // 'download'
SLRConstants::ACCESS_ARCHIVE                 // 'archive'
```

### 2. **Result Pattern** (`app/services/SLR/SLRResult.php`)

Improved error handling using a Result object instead of returning `false`:

```php
$result = $slrService->generateSLR($loanId, $userId, $trigger);

if ($result->isSuccess()) {
    $document = $result->getData();
    echo "SLR generated: " . $document['document_number'];
} else {
    echo "Error: " . $result->getErrorMessage();
    $code = $result->getErrorCode(); // Optional error code
}
```

### 3. **Service Consolidation**

**Before:** Multiple overlapping services
- `SLRService` (enhanced)
- `SLRDocumentService` (legacy)
- `LoanReleaseService` (legacy)

**After:** Single unified service with adapter pattern
- `SLRService` - Core implementation (unchanged for stability)
- `SLRServiceAdapter` - Backwards-compatible facade
- `SLRServiceRefactored` - Future clean implementation

### 4. **Clean Loan Lifecycle Hooks**

**Before:** Inline rule checking and generation code scattered in approval/disbursement methods

**After:** Single clean hook method:

```php
// In LoanService
private function triggerAutoSLRGeneration($loanId, $trigger, $userId = null) {
    // Centralized logic for checking rules and generating SLRs
    // Handles errors gracefully without blocking loan operations
}
```

**Usage:**
```php
// In approveLoan()
if ($approvalSuccess) {
    $this->triggerAutoSLRGeneration($id, 'loan_approval', $approvedBy);
}

// In disburseLoan()
$this->triggerAutoSLRGeneration($id, 'loan_disbursement', $disbursedBy);
```

### 5. **Enhanced Error Logging**

All SLR operations now have defensive error handling:

```php
// SLRService::logSLRAccess() now catches and logs errors
try {
    $ok = $this->db->query($sql, $params);
    if ($ok === false) {
        error_log("[SLRService] Failed to insert into slr_access_log for SLR ID {$slrId}");
    }
} catch (Exception $e) {
    error_log("[SLRService] Exception: " . $e->getMessage());
}
```

Log format provides clear context:
- `[LoanService] ✓ SLR auto-generated for loan 123 via 'loan_disbursement': SLR-202510-000123`
- `[LoanService] ✗ Failed to auto-generate SLR for loan 456: Loan not found`
- `[SLRService] Exception when inserting slr_access_log for SLR ID 789: ...`

## Database Schema

### Tables

**slr_documents**
- Stores generated SLR PDF documents
- Tracks download counts and access
- Maintains file integrity with SHA256 hash
- Status: active, archived, replaced, void

**slr_generation_rules**
- Configures when SLRs are auto-generated
- Trigger events: manual, loan_approval, loan_disbursement
- Per-trigger configuration for auto_generate, signatures, notifications

**slr_access_log**
- Detailed audit trail of all SLR access
- Tracks generation, viewing, downloads, archiving
- Records IP, user agent, access reason

### Current Configuration

Run this to check current rules:
```sql
SELECT 
    rule_name, 
    trigger_event, 
    auto_generate, 
    is_active 
FROM slr_generation_rules 
ORDER BY id;
```

Current setup:
- `loan_approval`: auto_generate = TRUE, is_active = TRUE
- `manual_request`: auto_generate = TRUE, is_active = TRUE
- `loan_disbursement`: auto_generate = TRUE, is_active = TRUE

## When SLRs are Generated

### 1. Automatic on Loan Approval
**Trigger:** When `LoanService->approveLoan()` is called  
**Location:** `app/services/LoanService.php` line ~379  
**Rule:** `slr_generation_rules.trigger_event = 'loan_approval'`  
**Code:**
```php
$this->triggerAutoSLRGeneration($id, 'loan_approval', $approvedBy);
```

### 2. Automatic on Loan Disbursement
**Trigger:** When `LoanService->disburseLoan()` is called  
**Location:** `app/services/LoanService.php` line ~479  
**Rule:** `slr_generation_rules.trigger_event = 'loan_disbursement'`  
**Code:**
```php
$this->triggerAutoSLRGeneration($id, 'loan_disbursement', $disbursedBy);
```

### 3. Manual Generation
**Trigger:** User action from UI  
**Endpoints:**
- `public/slr/generate.php?action=generate&loan_id=X`
- `public/slr/manage.php` (POST action=generate)
- `public/documents/slr.php` (various actions)

**Code:**
```php
$slrService = new SLRServiceAdapter();
$slrDocument = $slrService->generateSLR($loanId, $user['id'], 'manual');
```

## SLR Lifecycle

```
┌─────────────────┐
│ Loan Application│
└────────┬────────┘
         │
         ▼
┌─────────────────┐     Optional Auto-Generation
│ Loan Approved   ├────────► SLR Generated (Status: active)
└────────┬────────┘
         │
         ▼
┌─────────────────┐     Default Auto-Generation
│ Loan Disbursed  ├────────► SLR Generated (Status: active)
└────────┬────────┘                 │
         │                          │
         │                          ▼
         │                  ┌──────────────┐
         │                  │ Download/View│
         │                  └──────┬───────┘
         │                         │
         ▼                         ▼
┌─────────────────┐         ┌────────────┐
│ Loan Active     │         │ Archive    │
│ (Payments...)   │         │ (archived) │
└────────┬────────┘         └────────────┘
         │
         ▼
┌─────────────────┐
│ Loan Completed  │
└─────────────────┘
```

## Logging & Audit Trail

Every SLR operation creates entries in **two** tables:

### 1. slr_access_log (SLR-specific detailed tracking)
```sql
INSERT INTO slr_access_log (
    slr_document_id, access_type, accessed_by, 
    access_reason, ip_address, user_agent
) VALUES (?, ?, ?, ?, ?, ?);
```

### 2. transaction_logs (System-wide audit)
```sql
-- Via TransactionService->logGeneric()
INSERT INTO transaction_logs (
    entity_type, entity_id, action, user_id, 
    details, ip_address, timestamp
) VALUES ('system', $slrId, 'slr_generation', ...);
```

## API Reference

### SLRServiceAdapter Methods

```php
// Generate SLR
$result = $slrService->generateSLR($loanId, $userId, $trigger);
// Returns: array|false

// Download SLR
$fileInfo = $slrService->downloadSLR($slrId, $userId, $reason);
// Returns: array with file_path, file_name, file_size, content_type

// Get SLR by loan
$slr = $slrService->getSLRByLoanId($loanId);
// Returns: array|null

// List SLRs with filters
$documents = $slrService->listSLRDocuments($filters, $limit, $offset);
// Returns: array

// Archive SLR
$success = $slrService->archiveSLR($slrId, $userId, $reason);
// Returns: bool
```

### Filters for listSLRDocuments

```php
$filters = [
    'loan_id' => 123,
    'status' => 'active',        // active, archived, replaced, void
    'client_id' => 456,
    'generated_by' => 789,
    'date_from' => '2025-10-01',
    'date_to' => '2025-10-31',
];
```

## Configuration

### Enable/Disable Auto-Generation

**Via SQL:**
```sql
-- Enable auto-generation on disbursement
UPDATE slr_generation_rules 
SET auto_generate = true, is_active = true 
WHERE trigger_event = 'loan_disbursement';

-- Disable auto-generation on approval
UPDATE slr_generation_rules 
SET auto_generate = false 
WHERE trigger_event = 'loan_approval';
```

**Via PHP Scripts:**
```bash
# Enable auto-generation on disbursement
php enable_auto_slr_disbursement.php

# Or use the simple version (no framework deps)
php enable_auto_slr_simple.php
```

### Verify Schema

```bash
# Run schema verification and migration
php verify_slr_schema.php

# Or using Docker
docker run --rm -i \
  -e PGPASSWORD="your_password" \
  postgres:15-alpine \
  psql -h "your_host" -p 6543 -U "your_user" -d "postgres" \
  < database/migrations/verify_slr_schema.sql
```

## Troubleshooting

### SLRs Not Auto-Generating

1. **Check generation rules:**
   ```sql
   SELECT * FROM slr_generation_rules 
   WHERE trigger_event IN ('loan_approval', 'loan_disbursement');
   ```
   Ensure `auto_generate = true` and `is_active = true`

2. **Check PHP error logs:**
   ```bash
   tail -f /var/log/php_errors.log | grep "SLR"
   ```
   Look for `[LoanService]` or `[SLRService]` prefixed messages

3. **Verify loan status:**
   - SLRs on approval: loan must transition from 'application' → 'approved'
   - SLRs on disbursement: loan must transition from 'approved' → 'active'

### SLR Access Logs Not Populating

Check `SLRService::logSLRAccess()` for error messages:
```bash
grep "slr_access_log" /var/log/php_errors.log
```

### File Integrity Errors

If you see "SLR file integrity check failed":
- File was modified on disk after generation
- `content_hash` mismatch detected
- Solution: Archive the old SLR and regenerate

## Migration Checklist

- [x] Create SLRConstants class
- [x] Create SLRResult class
- [x] Create SLRServiceRefactored wrapper
- [x] Create SLRServiceAdapter for backwards compatibility
- [x] Update LoanService to use adapter
- [x] Add triggerAutoSLRGeneration() hook method
- [x] Refactor approval method to use hook
- [x] Refactor disbursement method to use hook
- [x] Add defensive logging in SLRService::logSLRAccess()
- [x] Update public endpoints to use adapter
- [x] Create DB schema migration
- [x] Verify DB tables and rules
- [ ] Update remaining test scripts
- [ ] Create integration test suite
- [ ] Consolidate public endpoints into single controller

## Testing

### Manual Test Flow

1. **Create a client**
2. **Create a loan application**
3. **Approve the loan** → Check for SLR in `slr_documents`
4. **Disburse the loan** → Check for SLR (should be the same or new based on rules)
5. **Download the SLR** → Verify entry in `slr_access_log`
6. **Check transaction_logs** → Verify all operations logged

### Verify in Database

```sql
-- Check SLRs for a loan
SELECT * FROM slr_documents WHERE loan_id = 123;

-- Check access logs
SELECT * FROM slr_access_log WHERE slr_document_id IN 
  (SELECT id FROM slr_documents WHERE loan_id = 123);

-- Check transaction logs
SELECT * FROM transaction_logs 
WHERE entity_type = 'system' 
  AND action LIKE 'slr_%' 
  AND entity_id IN (SELECT id FROM slr_documents WHERE loan_id = 123);
```

## Future Enhancements

1. **Unified Controller:** Consolidate `generate.php`, `manage.php`, `slr.php` into single REST controller
2. **Native SLRServiceRefactored:** Fully implement without wrapping legacy service
3. **Webhook Notifications:** Notify clients when SLR is ready
4. **Digital Signatures:** Integrate e-signature for client/officer signatures
5. **Batch Generation:** Generate SLRs for multiple loans at once
6. **Template Customization:** Allow per-branch SLR template customization

## Support

For issues or questions:
- Check error logs: `grep "SLR" /var/log/php_errors.log`
- Verify database configuration: `php verify_slr_schema.php`
- Review generation rules: Check `slr_generation_rules` table

---

**Last Updated:** October 23, 2025  
**Version:** 2.0 (Refactored Architecture)
