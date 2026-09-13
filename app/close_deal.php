<?php
/**
 * NexaCRM - Atomic Close-Won & Invoice Generation (ACID Transaction)
 * 
 * ACADEMIC HIGHLIGHT (CSE311):
 * - Strict Two-Phase Locking via SELECT ... FOR UPDATE.
 * - Guarantees ATOMICITY: Updating deal status, logging history, and generating invoice
 *   succeed completely or roll back together.
 * - Prevents race conditions if two managers try to close the same deal concurrently.
 */

require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: deals.php");
    exit;
}

$dealId = (int)($_POST['deal_id'] ?? 0);
$userId = (int)($_POST['user_id'] ?? 1);

if ($dealId <= 0) {
    header("Location: deals.php?error=" . urlencode("Invalid deal ID."));
    exit;
}

try {
    // ------------------------------------------------------------------------
    // 1. START ATOMIC TRANSACTION
    // ------------------------------------------------------------------------
    $pdo->beginTransaction();

    // ------------------------------------------------------------------------
    // 2. PESSIMISTIC ROW-LEVEL LOCK (Strict 2PL)
    // The "FOR UPDATE" clause locks this specific deal row.
    // ------------------------------------------------------------------------
    $stmt = $pdo->prepare("SELECT id, title, value, stage FROM deals WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $dealId]);
    $deal = $stmt->fetch();

    if (!$deal) {
        $pdo->rollBack();
        header("Location: deals.php?error=" . urlencode("Deal not found."));
        exit;
    }

    if ($deal['stage'] === 'closed_won') {
        $pdo->rollBack();
        header("Location: deal_view.php?id=$dealId&error=" . urlencode("Deal is already Closed as Won. Cannot duplicate invoice."));
        exit;
    }

    $oldStage = $deal['stage'];
    $dealValue = (float)$deal['value'];

    // ------------------------------------------------------------------------
    // 3. UPDATE DEAL STAGE TO 'closed_won'
    // ------------------------------------------------------------------------
    $updStmt = $pdo->prepare("UPDATE deals SET stage = 'closed_won', closed_at = NOW() WHERE id = :id");
    $updStmt->execute([':id' => $dealId]);

    // ------------------------------------------------------------------------
    // 4. INSERT INTO STAGE HISTORY AUDIT TRAIL
    // ------------------------------------------------------------------------
    $histStmt = $pdo->prepare("
        INSERT INTO deal_stage_history (deal_id, from_stage, to_stage, changed_by_user_id)
        VALUES (:deal_id, :from_stage, 'closed_won', :user_id)
    ");
    $histStmt->execute([
        ':deal_id'    => $dealId,
        ':from_stage' => $oldStage,
        ':user_id'    => $userId
    ]);

    // ------------------------------------------------------------------------
    // 5. ATOMICALLY AUTO-GENERATE BILLING INVOICE (5% Tax)
    // ------------------------------------------------------------------------
    $taxRate = 0.05;
    $taxAmount = round($dealValue * $taxRate, 2);
    $totalAmount = $dealValue + $taxAmount;
    $invoiceNum = 'INV-' . date('Ymd') . '-' . str_pad($dealId, 4, '0', STR_PAD_LEFT);
    $dueDate = date('Y-m-d', strtotime('+30 days'));

    $invStmt = $pdo->prepare("
        INSERT INTO invoices (deal_id, invoice_number, subtotal, tax_amount, total_amount, status, due_date)
        VALUES (:deal_id, :invoice_number, :subtotal, :tax_amount, :total_amount, 'unpaid', :due_date)
    ");
    $invStmt->execute([
        ':deal_id'        => $dealId,
        ':invoice_number' => $invoiceNum,
        ':subtotal'       => $dealValue,
        ':tax_amount'     => $taxAmount,
        ':total_amount'   => $totalAmount,
        ':due_date'       => $dueDate
    ]);

    // ------------------------------------------------------------------------
    // 6. COMMIT TRANSACTION
    // ------------------------------------------------------------------------
    $pdo->commit();

    header("Location: deal_view.php?id=$dealId&success=1");
    exit;

} catch (Exception $e) {
    // ------------------------------------------------------------------------
    // ERROR RECOVERY: ROLLBACK ON EXCEPTION
    // ------------------------------------------------------------------------
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: deal_view.php?id=$dealId&error=" . urlencode($e->getMessage()));
    exit;
}
