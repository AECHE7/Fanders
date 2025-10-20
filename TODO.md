# TODO: Implement Variable Loan Terms and PDF Agreements

## Completed Tasks
- [x] Update `templates/loans/form.php` to add a term input field (4-52 weeks)
- [x] Modify `app/services/LoanCalculationService.php` to accept variable term parameter
- [x] Update `app/services/LoanService.php` to pass term in `applyForLoan()`
- [x] Add `generateLoanAgreement()` method to `app/utilities/PDFGenerator.php`
- [x] Modify `LoanService::approveLoan()` to generate PDF after approval
- [x] Create storage directory for agreements
- [x] Update `public/loans/add.php` to handle term parameter in form processing and calculation

## Remaining Tasks
- [ ] Test form submission with variable terms
- [ ] Test PDF generation and download functionality
- [ ] Verify calculations for different terms
- [ ] Update controller to handle term parameter in loan application
- [ ] Add database field for PDF path if needed
- [ ] Update loan approval UI to pass approvedBy parameter
- [ ] Test end-to-end loan application and approval process
