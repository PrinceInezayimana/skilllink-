# MTN Mobile Money Payment Gate Implementation Guide

## Overview
This document explains how the new payment gate feature works and how to integrate it into your SkillLink application.

---

## Feature Workflow

### For Employers
1. **Post a Job**: Employer posts a job as usual.
2. **Applicants Apply**: Students apply to the job.
3. **Threshold Reached**: When the job reaches **10 applicants**, the system triggers the payment requirement.
4. **View Applicants**: The "View Applicants" button changes to "Pay to View Applicants" on the dashboard.
5. **Payment Page**: Employer clicks the button and is redirected to `/employer/pay_job.php`.
6. **Manual Transfer**: Employer transfers **10,000 RWF** (or $9.59) to **0788833101** via MTN Mobile Money.
7. **Confirmation**: Employer enters their phone number on the payment page to confirm the transfer.
8. **Access Granted**: After confirmation, the employer can view all applicants and manage their applications.

---

## Database Changes

### New Table: `payments`
```sql
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 9.59,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `rwf_amount` int(11) NOT NULL DEFAULT 10000,
  `phone_number` varchar(20) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending', 'completed', 'failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `employer_id` (`employer_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**To apply this migration:**
```bash
mysql -u root -p skilllink < database/add_payments.sql
```

---

## Modified Files

### 1. `/public/employer/dashboard.php`
**Changes:**
- Added query to fetch payment status for each job.
- Modified the "View Applicants" button to conditionally display:
  - **"Pay to View Applicants"** (amber button) if applicants ≥ 10 AND no payment recorded.
  - **"View Applicants"** (green button) if applicants < 10 OR payment already completed.

**Key Code:**
```php
$stmt = $conn->prepare("SELECT jobs.*, (SELECT COUNT(*) FROM applications WHERE applications.job_id=jobs.id) AS app_cnt, (SELECT status FROM payments WHERE payments.job_id=jobs.id AND payments.status='completed' LIMIT 1) AS payment_status FROM jobs WHERE employer_id=? ORDER BY created_at DESC");
```

### 2. `/public/employer/applicants.php`
**Changes:**
- Added server-side check to enforce payment requirement.
- If applicants ≥ 10 and no payment exists, redirects to `pay_job.php`.

**Key Code:**
```php
if ($app_cnt >= 10) {
    $stmt = $conn->prepare("SELECT * FROM payments WHERE job_id=? AND status='completed'");
    $stmt->bind_param("i", $job_id); $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$payment) {
        header("Location: pay_job.php?job_id=$job_id"); exit();
    }
}
```

### 3. `/public/employer/pay_job.php` (NEW)
**Purpose:** Payment confirmation page.

**Features:**
- Displays the payment amount (10,000 FRW / $9.59).
- Shows the target MTN number (0788833101).
- Provides a form for the employer to enter their phone number.
- Records the payment in the database upon confirmation.
- Redirects back to applicants page after successful confirmation.

---

## Real-Time Integration

The existing real-time system (`realtime.php` and `realtime.js`) already broadcasts applicant counts to employers. This feature leverages that to dynamically update the UI without page refresh.

**How it works:**
1. When a new application is submitted, the applicant count is updated in real-time.
2. If the count reaches 10, the frontend can optionally trigger a UI update to reflect the payment requirement.
3. The employer sees the "Pay to View Applicants" button appear dynamically.

---

## Security Considerations

1. **Ownership Verification**: All endpoints verify that the employer owns the job before allowing access.
2. **Server-Side Enforcement**: The payment check is enforced at the server level, not just the frontend.
3. **Phone Number Validation**: Consider adding phone number format validation for Rwanda (+250 or 07xx format).
4. **Transaction Logging**: All payment records are logged with timestamps for audit trails.

---

## Future Enhancements

### 1. Automated Payment Verification
Instead of manual confirmation, integrate with:
- **Flutterwave** (supports MTN MoMo in Rwanda)
- **Paypack** (local Rwanda payment gateway)
- **MTN MoMo Open API** (direct integration with MTN)

### 2. Email Notifications
Send confirmation emails to employers after payment is recorded.

### 3. Refund Policy
Implement a refund mechanism if an employer disputes a payment.

### 4. Payment History Dashboard
Add a page where employers can view all their payments and transaction history.

### 5. Tiered Pricing
Offer discounts for bulk payments or recurring subscriptions.

---

## Testing Checklist

- [ ] Create a job as an employer.
- [ ] Have multiple students apply (at least 10).
- [ ] Verify the button changes to "Pay to View Applicants" on the dashboard.
- [ ] Click the button and verify it redirects to `pay_job.php`.
- [ ] Enter a phone number and submit the form.
- [ ] Verify the payment is recorded in the database.
- [ ] Verify the button changes back to "View Applicants" after payment.
- [ ] Verify direct access to applicants page is blocked without payment.
- [ ] Test with multiple jobs to ensure payment status is tracked per job.

---

## Support & Questions

For questions or issues, refer to the main `analysis_report.md` file or contact the development team.

---

*Last Updated: March 29, 2026*
