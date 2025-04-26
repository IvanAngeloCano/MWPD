<?php
// approval_page.php - Recreated Approval Page
session_start();
require_once 'db.php'; // Update to your DB connection file

// Only allow logged-in users with appropriate roles (customize as needed)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch approval record by ID
$approval_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($approval_id <= 0) {
    die('Invalid approval ID.');
}

try {
    $stmt = $pdo->prepare('SELECT * FROM approvals WHERE id = ?');
    $stmt->execute([$approval_id]);
    $approval = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$approval) {
        die('Approval record not found.');
    }
} catch (Exception $e) {
    error_log('DB Error: ' . $e->getMessage());
    die('Error fetching approval record.');
}

// Handle approval/denial POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'] ?? '';
    $comments = $_POST['comments'] ?? '';
    if (!in_array($decision, ['approved', 'denied'])) {
        $error = 'Invalid decision.';
    } else {
        try {
            $update = $pdo->prepare('UPDATE approvals SET status=?, approved_by=?, comments=?, updated_at=NOW() WHERE id=?');
            $update->execute([
                $decision,
                $_SESSION['user_id'],
                $comments,
                $approval_id
            ]);
            header('Location: pending_approvals.php?msg=updated');
            exit();
        } catch (Exception $e) {
            error_log('Update Error: ' . $e->getMessage());
            $error = 'Failed to update approval.';
        }
    }
}

function h($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Details</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .container { max-width: 600px; margin: 0 auto; }
        .badge { padding: 0.3em 0.8em; border-radius: 0.5em; color: #fff; }
        .badge-pending { background: #f0ad4e; }
        .badge-approved { background: #5cb85c; }
        .badge-denied { background: #d9534f; }
        .form-group { margin-bottom: 1em; }
        label { font-weight: bold; }
        textarea { width: 100%; min-height: 60px; }
        .btn { padding: 0.5em 1.2em; border: none; border-radius: 0.3em; cursor: pointer; }
        .btn-approve { background: #5cb85c; color: #fff; }
        .btn-deny { background: #d9534f; color: #fff; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .error { color: #d9534f; margin-bottom: 1em; }
    </style>
</head>
<body>
<div class="container">
    <h2>Approval Details</h2>
    <?php if (isset($error)): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>
    <table>
        <tr><th>ID</th><td><?= h($approval['id']) ?></td></tr>
        <tr><th>Direct Hire ID</th><td><?= h($approval['direct_hire_id']) ?></td></tr>
        <tr><th>Document ID</th><td><?= h($approval['document_id']) ?></td></tr>
        <tr><th>Submitted By</th><td><?= h($approval['submitted_by']) ?></td></tr>
        <tr><th>Approved By</th><td><?= h($approval['approved_by']) ?></td></tr>
        <tr><th>Status</th>
            <td>
                <?php
                    $status = $approval['status'];
                    $badge = 'badge-pending';
                    if ($status === 'approved') $badge = 'badge-approved';
                    elseif ($status === 'denied') $badge = 'badge-denied';
                ?>
                <span class="badge <?= $badge ?>"><?= h(ucfirst($status)) ?></span>
            </td>
        </tr>
        <tr><th>Comments</th><td><?= nl2br(h($approval['comments'])) ?></td></tr>
        <tr><th>Created At</th><td><?= h($approval['created_at']) ?></td></tr>
        <tr><th>Updated At</th><td><?= h($approval['updated_at']) ?></td></tr>
    </table>
    <?php if ($approval['status'] === 'pending'): ?>
    <form method="post" style="margin-top:2em;">
        <div class="form-group">
            <label for="comments">Comments (optional):</label>
            <textarea name="comments" id="comments"></textarea>
        </div>
        <button type="submit" name="decision" value="approved" class="btn btn-approve">Approve</button>
        <button type="submit" name="decision" value="denied" class="btn btn-deny">Deny</button>
    </form>
    <?php endif; ?>
    <p><a href="pending_approvals.php">&larr; Back to Pending Approvals</a></p>
</div>
</body>
</html>
