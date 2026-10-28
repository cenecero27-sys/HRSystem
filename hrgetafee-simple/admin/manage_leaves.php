<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/soft_delete_helper.php';

require_login();
check_role(1);

$admin = get_employee_info($conn, $_SESSION['employee_id']);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $action_type = $_POST['action_type'] ?? '';

    if ($action_type === 'manage_leave_request') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (empty($csrf) || !verifyCSRFToken($csrf)) {
            $error = 'Invalid form token.';
        } else {
            $leave_id = intval($_POST['leave_id'] ?? 0);
            $action = sanitizeInput($_POST['action'] ?? '');
            $denial_reason = sanitizeInput($_POST['denial_reason'] ?? '');
            $staff_note = sanitizeInput($_POST['staff_note'] ?? '');

            if ($leave_id <= 0 || !in_array($action, ['approve', 'deny'])) {
                $error = 'Invalid request.';
            } else {
                if ($action === 'deny' && trim($denial_reason) === '') {
                    $error = 'Reason for denial is required when denying a leave request.';
                } else {
                    $status = $action === 'approve' ? 'approved' : 'denied';
                    $admin_id = $_SESSION['user_id'];

                    $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, staff_note = ?, denial_reason = ?, approved_by = ?, approved_date = NOW() WHERE leave_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("sssii", $status, $staff_note, $denial_reason, $admin_id, $leave_id);
                        if ($stmt->execute()) {
                            $message = $action === 'approve' ? '✓ Leave request approved.' : '✓ Leave request denied.';
                            logSecurityEvent($conn, $_SESSION['user_id'], $action === 'approve' ? 'APPROVE_LEAVE' : 'DENY_LEAVE', "Leave ID: $leave_id - Action: $action");
                            header('Location: manage_leaves.php');
                            exit;
                        } else {
                            $error = 'Failed to update leave request.';
                        }
                        $stmt->close();
                    } else {
                        $error = 'Database error.';
                    }
                }
            }
        }
    }

    elseif ($action_type === 'add_leave_type') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (empty($csrf) || !verifyCSRFToken($csrf)) {
            $error = 'Invalid form token.';
        } else {
            $leave_type_name = sanitizeInput($_POST['leave_type_name'] ?? '');
            $max_days = intval($_POST['max_days'] ?? 0);
            $description = sanitizeInput($_POST['description'] ?? '');

            if (empty($leave_type_name) || $max_days <= 0) {
                $error = 'Leave type name and max days are required.';
            } else {
                $check_stmt = $conn->prepare("SELECT leave_type_id FROM leave_types WHERE LOWER(leave_type_name) = LOWER(?) AND is_deleted = FALSE LIMIT 1");
                if ($check_stmt) {
                    $check_stmt->bind_param('s', $leave_type_name);
                    $check_stmt->execute();
                    $check_stmt->store_result();

                    if ($check_stmt->num_rows > 0) {
                        $error = 'This leave type already exists.';
                        $check_stmt->close();
                    } else {
                        $check_stmt->close();

                        $stmt = $conn->prepare("INSERT INTO leave_types (leave_type_name, max_days_per_year, description, created_at) VALUES (?, ?, ?, NOW())");
                        if ($stmt) {
                            $stmt->bind_param("sis", $leave_type_name, $max_days, $description);
                            if ($stmt->execute()) {
                                $message = '✓ Leave type added successfully';
                                logSecurityEvent($conn, $_SESSION['user_id'], 'ADD_LEAVE_TYPE', "Added leave type: $leave_type_name");
                                header('Location: manage_leaves.php');
                                exit;
                            } else {
                                $error = 'Error adding leave type: ' . htmlspecialchars($stmt->error);
                            }
                            $stmt->close();
                        } else {
                            $error = 'Database error.';
                        }
                    }
                } else {
                    $error = 'Database error.';
                }
            }
        }
    }

    elseif ($action_type === 'delete_leave_type') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (empty($csrf) || !verifyCSRFToken($csrf)) {
            $error = 'Invalid form token.';
        } else {
            $delete_id = intval($_POST['leave_type_id'] ?? 0);

            if ($delete_id <= 0) {
                $error = 'Invalid leave type.';
            } else {
                $verify_stmt = $conn->prepare("SELECT leave_type_id, leave_type_name, max_days_per_year, description FROM leave_types WHERE leave_type_id = ? AND is_deleted = FALSE LIMIT 1");
                if ($verify_stmt) {
                    $verify_stmt->bind_param('i', $delete_id);
                    $verify_stmt->execute();
                    $verify_res = $verify_stmt->get_result();
                    $leave_data = $verify_res->fetch_assoc();
                    $verify_stmt->close();

                    if ($leave_data) {
                        $result = softDeleteItem($conn, 'leave_type', $delete_id, $leave_data['leave_type_name'], $leave_data, $_SESSION['user_id']);

                        if ($result['success']) {
                            $message = '✓ Leave type moved to recycle bin.';
                            logSecurityEvent($conn, $_SESSION['user_id'], 'DELETE_LEAVE_TYPE', "Moved to bin: {$leave_data['leave_type_name']}");
                        } else {
                            $error = $result['error'];
                        }
                    } else {
                        $error = 'Leave type not found.';
                    }
                } else {
                    $error = 'Database error.';
                }
            }
        }
    }
}

$leave_types = [];
$stmt = $conn->prepare("SELECT leave_type_id, leave_type_name, max_days_per_year, description FROM leave_types WHERE is_deleted = FALSE ORDER BY leave_type_name");
if ($stmt) {
    $stmt->execute();
    $leave_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$pending_leaves = [];
$stmt = $conn->prepare(
    "SELECT lr.*, e.first_name, e.last_name, e.employee_code, lt.leave_type_name
     FROM leave_requests lr
     JOIN employees e ON e.employee_id = lr.employee_id
     JOIN leave_types lt ON lt.leave_type_id = lr.leave_type_id
     WHERE lr.status = 'pending'
     ORDER BY lr.created_at DESC"
);
if ($stmt) {
    $stmt->execute();
    $pending_leaves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$completed_leaves = [];
$stmt = $conn->prepare(
    "SELECT lr.*, e.first_name, e.last_name, e.employee_code, lt.leave_type_name
     FROM leave_requests lr
     JOIN employees e ON e.employee_id = lr.employee_id
     JOIN leave_types lt ON lt.leave_type_id = lr.leave_type_id
     WHERE lr.status IN ('approved', 'denied')
     ORDER BY lr.approved_date DESC, lr.created_at DESC
     LIMIT 100"
);
if ($stmt) {
    $stmt->execute();
    $completed_leaves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f5f7fb; }
        .card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f8fafc; }
        .btn { display: inline-block; padding: 8px 12px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; font-weight: 600; font-size: 13px; }
        .btn-primary { background: #667eea; color: white; }
        .btn-secondary { background: #e5e7eb; color: #1f2937; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-info { background: #3b82f6; color: white; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-denied { background: #fee2e2; color: #991b1b; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        textarea { min-height: 70px; }
        .document-link { display: inline-block; background: #dbeafe; color: #1e40af; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; }
        .document-link:hover { background: #bfdbfe; }
        .required-asterisk { color: #ef4444; }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; width: min(1000px, 92vw); max-height: 90vh; border-radius: 12px; overflow: hidden; box-shadow: 0 16px 50px rgba(0,0,0,0.25); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        .modal-header h3 { margin: 0; font-size: 20px; }
        .modal-close { cursor: pointer; font-size: 28px; color: #666; line-height: 1; }
        .modal-close:hover { color: #111; }
        .modal-body { padding: 20px; background: #fff; }
        .modal-preview-box { border: 1px solid #e5e7eb; background: #f9fafb; height: 520px; border-radius: 10px; overflow: hidden; }
        .modal-preview-box iframe { width: 100%; height: 100%; border: 0; background: white; }
        .modal-actions { margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap; }
        .muted { color: #6b7280; font-size: 13px; }

        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .modal-preview-box { height: 420px; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Leave Management</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($admin['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">← Back to Dashboard</a>

        <div class="card">
            <h3>⏳ Pending Leave Requests (<?php echo count($pending_leaves); ?>)</h3>

            <?php if (empty($pending_leaves)): ?>
                <p style="color: #666;">No pending leave requests.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Dates</th>
                            <th>Days</th>
                            <th>Supporting Documents</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_leaves as $leave): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></strong><br>
                                    <code><?php echo htmlspecialchars($leave['employee_code']); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                <td>
                                    <?php echo format_date($leave['start_date']); ?> to <?php echo format_date($leave['end_date']); ?>
                                </td>
                                <td><?php echo (int)$leave['number_of_days']; ?></td>
                                <td>
                                    <?php if (!empty($leave['signature_path']) && file_exists($leave['signature_path'])): ?>
                                        <button type="button" class="document-link" onclick="openDocumentModal(<?php echo (int)$leave['leave_id']; ?>, '<?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($leave['leave_type_name'], ENT_QUOTES); ?>')">📄 View</button>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 13px;">Not uploaded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary" onclick="openApprovalModal(<?php echo (int)$leave['leave_id']; ?>)">Review</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>📋 Completed Leave History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Denial Reason</th>
                        <th>Approved Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($completed_leaves)): ?>
                        <tr><td colspan="6" style="padding: 16px; color: #999;">No completed leave requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($completed_leaves as $leave): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                <td><?php echo format_date($leave['start_date']); ?> to <?php echo format_date($leave['end_date']); ?></td>
                                <td>
                                    <?php
                                    if ($leave['status'] === 'approved') echo '<span class="badge badge-approved">Approved</span>';
                                    elseif ($leave['status'] === 'denied') echo '<span class="badge badge-denied">Denied</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($leave['status'] === 'denied' && !empty($leave['denial_reason'])) {
                                        echo '<span style="color: #666; font-size: 13px;">' . htmlspecialchars($leave['denial_reason']) . '</span>';
                                    } else {
                                        echo '<span style="color: #999; font-size: 13px;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($leave['approved_date'] ? date('M d, Y', strtotime($leave['approved_date'])) : '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>➕ Add Leave Type</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action_type" value="add_leave_type">

                <div class="form-row">
                    <div>
                        <label>Leave Type Name <span class="required-asterisk">*</span></label>
                        <input type="text" name="leave_type_name" placeholder="e.g., Vacation Leave" required>
                    </div>
                    <div>
                        <label>Max Days Per Year <span class="required-asterisk">*</span></label>
                        <input type="number" name="max_days" min="1" placeholder="e.g., 15" required>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <label>Description</label>
                    <textarea name="description" placeholder="Describe this leave type..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 16px;">Add Leave Type</button>
            </form>
        </div>

        <div class="card">
            <h3>📋 Leave Types (<?php echo count($leave_types); ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Max Days/Year</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leave_types)): ?>
                        <tr><td colspan="4" style="padding: 16px; color: #999;">No leave types found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($leave_types as $lt): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($lt['leave_type_name']); ?></strong></td>
                                <td><?php echo (int)$lt['max_days_per_year']; ?> days</td>
                                <td><?php echo htmlspecialchars($lt['description'] ?? '-'); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action_type" value="delete_leave_type">
                                        <input type="hidden" name="leave_type_id" value="<?php echo (int)$lt['leave_type_id']; ?>">
                                        <button class="btn btn-danger" type="submit" onclick="return confirm('Move to recycle bin?')">🗑️ Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Review Leave Request</h3>
                <span class="modal-close" onclick="closeApprovalModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="approvalForm" onsubmit="return validateApprovalForm()">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action_type" value="manage_leave_request">
                    <input type="hidden" name="leave_id" id="modalLeaveId" value="">

                    <div style="margin: 20px 0;">
                        <label>HR Note (if approving)</label>
                        <textarea name="staff_note" placeholder="Optional note for approval..."></textarea>
                    </div>

                    <div style="margin: 20px 0;">
                        <label>Reason for Denial <span class="required-asterisk" id="denialRequiredLabel" style="display: none;">*</span></label>
                        <textarea name="denial_reason" id="denialReasonField" placeholder="State reason for denial (required only when denying)..."></textarea>
                        <small id="denialHint" class="muted" style="display: none;">This field is required when denying the leave request.</small>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" name="action" value="approve" class="btn btn-success" onclick="setAction('approve')">✅ Approve</button>
                        <button type="submit" name="action" value="deny" class="btn btn-danger" onclick="setAction('deny')">❌ Deny</button>
                        <button type="button" class="btn btn-secondary" onclick="closeApprovalModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="documentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="documentModalTitle">Supporting Document</h3>
                <span class="modal-close" onclick="closeDocumentModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="documentMeta" class="muted" style="margin-bottom: 12px;"></div>
                <div class="modal-preview-box">
                    <iframe id="documentPreviewFrame" src="" title="Supporting Document Preview"></iframe>
                </div>

                <div class="modal-actions">
                    <a id="documentDownloadLink" href="#" target="_blank" class="btn btn-info">⬇ Download</a>
                    <button type="button" class="btn btn-secondary" onclick="closeDocumentModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentAction = null;

        function openApprovalModal(leaveId) {
            document.getElementById('modalLeaveId').value = leaveId;
            document.getElementById('denialReasonField').value = '';
            document.getElementById('denialRequiredLabel').style.display = 'none';
            document.getElementById('denialHint').style.display = 'none';
            currentAction = null;
            document.getElementById('approvalModal').classList.add('active');
        }

        function closeApprovalModal() {
            document.getElementById('approvalModal').classList.remove('active');
            currentAction = null;
        }

        function setAction(action) {
            currentAction = action;
        }

        function validateApprovalForm() {
            if (currentAction === 'deny') {
                const denialReason = document.getElementById('denialReasonField').value.trim();
                if (!denialReason) {
                    alert('❌ Please provide a reason for denial.');
                    return false;
                }
            }
            return true;
        }

        document.querySelectorAll('button[name="action"]').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.value === 'deny') {
                    document.getElementById('denialRequiredLabel').style.display = 'inline';
                    document.getElementById('denialHint').style.display = 'block';
                    document.getElementById('denialReasonField').focus();
                } else {
                    document.getElementById('denialRequiredLabel').style.display = 'none';
                    document.getElementById('denialHint').style.display = 'none';
                }
            });
        });

        function openDocumentModal(leaveId, employeeName, leaveTypeName) {
            const previewUrl = '../admin/download_signature.php?leave_id=' + leaveId;
            const title = employeeName + ' - ' + leaveTypeName;

            document.getElementById('documentModalTitle').textContent = title;
            document.getElementById('documentMeta').textContent = 'Supporting document preview for ' + employeeName + ' (' + leaveTypeName + ')';
            document.getElementById('documentPreviewFrame').src = previewUrl;
            document.getElementById('documentDownloadLink').href = previewUrl;

            document.getElementById('documentModal').classList.add('active');
        }

        function closeDocumentModal() {
            document.getElementById('documentModal').classList.remove('active');
            document.getElementById('documentPreviewFrame').src = '';
        }

        window.onclick = function(event) {
            const approvalModal = document.getElementById('approvalModal');
            const documentModal = document.getElementById('documentModal');

            if (event.target === approvalModal) {
                closeApprovalModal();
            }

            if (event.target === documentModal) {
                closeDocumentModal();
            }
        }
    </script>
</body>
</html>