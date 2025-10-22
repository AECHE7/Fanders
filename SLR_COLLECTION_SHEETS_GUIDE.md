# SLR and Collection Sheets - Business Operations Guide

## 📋 **SLR (Summary of Loan Release) Documents**

### **What is SLR?**
**SLR** stands for **"Summary of Loan Release"** - an official document that confirms a client has received a loan disbursement from Fanders Microfinance.

### **Purpose & Business Function:**
- **Loan Disbursement Documentation**: Creates official proof that money was released to a client
- **Legal Compliance**: Provides signed documentation for regulatory requirements
- **Cash Blotter Integration**: Records loan releases as "outflow" in daily cash tracking
- **Client Records**: Maintains permanent record of loan transactions

### **When SLR is Generated:**
1. **Loan Approval & Disbursement**: When a loan is approved and money is released to client
2. **Client Request**: When client needs proof of loan receipt for personal records
3. **Audit Requirements**: For compliance and record-keeping purposes
4. **Transfer/Review**: When client accounts are reviewed or transferred

### **SLR Content Includes:**
- Client personal information
- Loan details (amount, terms, interest rate)
- Disbursement date and amount
- Payment schedule (17 weekly payments)
- Insurance and savings breakdown
- Signature blocks for client confirmation

### **SLR Operations in Your System:**

#### **1. Single SLR Generation:**
```
Location: /public/documents/slr.php
Process: Select loan → Generate PDF → Download
Users: Cashiers, Managers, Admins
```

#### **2. Bulk SLR Generation:**
```
Location: /public/slr/bulk.php
Process: Select multiple loans → Generate ZIP file
Users: Managers, Admins (for batch processing)
```

#### **3. Client SLR Generation:**
```
Process: Generate SLRs for all loans of a specific client
Output: Single PDF or ZIP file depending on loan count
```

---

## 📝 **Collection Sheets System**

### **What are Collection Sheets?**
**Collection Sheets** are daily reports that Account Officers use to track and submit client payments they've collected in the field.

### **Purpose & Business Function:**
- **Field Collection Tracking**: Account Officers record payments collected from clients
- **Accountability**: Creates audit trail for who collected what from whom
- **Payment Processing**: Enables bulk posting of payments by Cashiers
- **Cash Blotter Integration**: Records collections as "inflow" in daily cash tracking

### **Collection Sheet Workflow:**

#### **1. Account Officer Creates Collection Sheet:**
```
Location: /public/collection-sheets/add.php
Process:
1. Account Officer logs in
2. Creates new collection sheet for today
3. Selects client from dropdown
4. System shows client's active loans via AJAX
5. Enters payment amount (auto-filled with weekly amount)
6. Adds multiple collection items throughout the day
7. Submits sheet for Cashier approval
```

#### **2. Cashier Reviews & Approves:**
```
Location: /public/collection-sheets/approve.php
Process:
1. Cashier reviews submitted collection sheets
2. Verifies amounts and client information
3. Can approve, reject (with reason), or request changes
4. Approved sheets move to "ready for posting" status
```

#### **3. Cashier Posts Payments:**
```
Process:
1. Cashier opens approved collection sheet
2. Clicks "Post All Payments" 
3. System automatically:
   - Creates payment records in database
   - Updates loan balances
   - Creates cash blotter entries (inflow)
   - Logs all transactions for audit
   - Changes collection sheet status to "posted"
```

### **Collection Sheet Statuses:**
- **Draft**: Account Officer is still adding items
- **Submitted**: Ready for Cashier review
- **Approved**: Cashier approved, ready for payment posting
- **Posted**: Payments have been recorded in system
- **Rejected**: Cashier rejected with reason (back to Account Officer)

---

## 🔄 **How They Work Together in Daily Operations**

### **Morning Operations:**
1. **Account Officers** go to field to collect payments from clients
2. Use mobile-friendly interface to record collections on **Collection Sheets**
3. Submit completed sheets to **Cashier** for approval

### **Afternoon Operations:**
4. **Cashier** reviews all submitted **Collection Sheets**
5. Approves valid collections and posts payments to system
6. **Cash Blotter** automatically updates with payment inflows

### **Loan Disbursement Operations:**
7. When new loans are approved and disbursed:
8. **SLR documents** are generated for client signatures
9. **Cash Blotter** records loan disbursements as outflows
10. Clients receive official **SLR** as proof of loan receipt

### **Daily Cash Flow Tracking:**
- **Inflows**: Collections from Collection Sheets
- **Outflows**: Loan releases from SLR system
- **Balance**: Real-time cash position tracking

---

## 🎯 **Real-World Example Operations**

### **Example 1: Daily Collection Process**
```
Account Officer Maria:
- Visits 15 clients in the morning
- Collects ₱12,500 in weekly payments
- Records each payment on Collection Sheet via mobile
- Submits sheet at 2 PM

Cashier John:
- Reviews Maria's collection sheet at 3 PM
- Verifies amounts match expected weekly payments
- Approves and posts all 15 payments
- Cash blotter shows ₱12,500 inflow for the day
```

### **Example 2: Loan Disbursement Process**
```
New Loan Approved:
- Client: Anna Santos
- Loan Amount: ₱50,000
- Manager approves loan application

Cashier Process:
1. Generates SLR document for Anna
2. Anna signs SLR confirming receipt
3. Cash is disbursed to Anna
4. SLR records ₱50,000 outflow in cash blotter
5. Anna keeps copy of SLR as proof
```

### **Example 3: End-of-Day Reconciliation**
```
Cash Blotter Summary:
- Opening Balance: ₱100,000
- Collections (Inflow): ₱45,000 (from Collection Sheets)
- Loan Releases (Outflow): ₱30,000 (from SLR system)
- Closing Balance: ₱115,000
```

---

## 📊 **Business Benefits**

### **For Account Officers:**
- Mobile-friendly collection recording
- Automatic payment amount calculations
- Real-time loan status visibility
- Reduced paperwork and errors

### **For Cashiers:**
- Streamlined payment posting process
- Built-in validation and error checking
- Automatic cash blotter integration
- Clear audit trail for all transactions

### **For Management:**
- Real-time cash position monitoring
- Complete payment history tracking
- Regulatory compliance documentation
- Reduced manual errors and improved efficiency

### **For Clients:**
- Official SLR documentation for loan receipts
- Accurate payment tracking and receipts
- Transparent loan status information
- Professional service delivery

---

## 🔧 **System Integration Points**

1. **Collection Sheets** → **Payment Records** → **Cash Blotter Inflows**
2. **SLR Generation** → **Loan Disbursement** → **Cash Blotter Outflows**  
3. **Both Systems** → **Transaction Logs** → **Audit Trail**
4. **All Operations** → **Real-time Reporting** → **Management Dashboard**

This integrated approach ensures accurate financial tracking, regulatory compliance, and efficient microfinance operations while maintaining complete accountability and audit trails.