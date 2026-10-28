<?php
session_start();
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

require_login();
check_role(3);

$employee = get_employee_info($conn, $_SESSION['employee_id']);
$employee_id = $_SESSION['employee_id'];

// Handle view/preview
if (isset($_GET['view_document'])) {
    $document_id = intval($_GET['view_document']);
    $stmt = $conn->prepare("SELECT * FROM employee_documents WHERE document_id = ? AND employee_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ii', $document_id, $employee_id);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($doc && file_exists($doc['file_path'])) {
            // Get file extension
            $file_ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
            
            // For images and PDFs, display directly
            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'pdf'])) {
                $mime_types = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'pdf' => 'application/pdf'
                ];
                header('Content-Type: ' . ($mime_types[$file_ext] ?? 'application/octet-stream'));
                header('Content-Disposition: inline; filename="' . basename($doc['document_name']) . '"');
                readfile($doc['file_path']);
                exit;
            } else {
                // For other file types, force download
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($doc['document_name']) . '"');
                readfile($doc['file_path']);
                exit;
            }
        }
    }
}

// Handle download
if (isset($_GET['download_document'])) {
    $document_id = intval($_GET['download_document']);
    $stmt = $conn->prepare("SELECT * FROM employee_documents WHERE document_id = ? AND employee_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ii', $document_id, $employee_id);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($doc && file_exists($doc['file_path'])) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($doc['document_name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($doc['file_path']));
            readfile($doc['file_path']);
            exit;
        }
    }
}

// Get documents
$documents = [];
$stmt = $conn->prepare("SELECT * FROM employee_documents WHERE employee_id = ? AND is_active = 1 ORDER BY uploaded_at DESC");
if ($stmt) {
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - HRGetafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5568d3;
            --secondary-color: #764ba2;
            --danger-color: #ef4444;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --sidebar-width: 270px;
            --sidebar-width-collapsed: 80px;
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
            background: #f8f9fa;
            color: #2c3e50;
        }

        .employee-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .employee-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: #ecf0f1;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: width var(--transition-speed) ease;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
        }

        .employee-sidebar.collapsed {
            width: var(--sidebar-width-collapsed);
        }

        .sidebar-header {
            padding: 25px 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: relative;
            flex-shrink: 0;
        }

        .sidebar-logo {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .sidebar-logo-text {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
            display: block;
            letter-spacing: 0.5px;
        }

        .employee-sidebar.collapsed .sidebar-logo-text {
            display: none;
        }

        .sidebar-toggle {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: all var(--transition-speed) ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
            flex: 1;
            overflow-y: auto;
        }

        .sidebar-menu-section {
            margin-bottom: 5px;
        }

        .sidebar-section-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.45);
            padding: 15px 15px 8px 15px;
            letter-spacing: 1px;
        }

        .employee-sidebar.collapsed .sidebar-section-title {
            opacity: 0;
        }

        .sidebar-menu-item {
            position: relative;
        }

        .sidebar-menu-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            transition: all var(--transition-speed) ease;
            border-left: 3px solid transparent;
            font-size: 14px;
            font-weight: 500;
        }

        .sidebar-menu-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary-color);
            padding-left: 20px;
        }

        .sidebar-menu-link.active {
            background: rgba(102, 126, 234, 0.25);
            color: white;
            border-left-color: var(--primary-color);
            font-weight: 600;
        }

        .sidebar-menu-link i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar-menu-text {
            flex: 1;
            opacity: 1;
            white-space: nowrap;
            overflow: hidden;
        }

        .employee-sidebar.collapsed .sidebar-menu-text {
            opacity: 0;
            width: 0;
        }

        .sidebar-footer {
            padding: 15px;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
            margin-top: auto;
        }

        .sidebar-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }

        .employee-sidebar.collapsed .sidebar-user-info {
            opacity: 0;
        }

        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .sidebar-user-details {
            flex: 1;
            overflow: hidden;
        }

        .sidebar-user-name {
            font-size: 12px;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-role {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 2px;
        }

        .sidebar-logout-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all var(--transition-speed) ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .sidebar-logout-btn:hover {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            transform: translateY(-2px);
        }

        .employee-sidebar.collapsed .sidebar-logout-btn {
            padding: 12px 8px;
            font-size: 0;
        }

        .employee-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) ease;
            display: flex;
            flex-direction: column;
        }

        .employee-sidebar.collapsed ~ .employee-main {
            margin-left: var(--sidebar-width-collapsed);
        }

        .employee-navbar {
            background: white;
            padding: 18px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e7ff;
        }

        .navbar-left h2 {
            color: #2c3e50;
            font-size: 26px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .navbar-left i {
            color: var(--primary-color);
        }

        .employee-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .page-header p {
            color: #666;
            font-size: 14px;
        }

        .back-btn {
            display: inline-block;
            background: #e5e7eb;
            color: #1f2937;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #d1d5db;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-top: 4px solid var(--primary-color);
        }

        .card h3 {
            margin-top: 0;
            color: #1f2937;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
            margin-top: 15px;
        }

        .document-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .document-card:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
            transform: translateY(-3px);
        }

        .document-type-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .document-name {
            font-weight: 700;
            color: #1f2937;
            font-size: 14px;
            margin-bottom: 10px;
            word-break: break-word;
        }

        .document-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .document-meta strong {
            color: #374151;
        }

        .document-actions {
            display: flex;
            gap: 8px;
        }

        .btn-view {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: var(--primary-color);
            color: white;
            padding: 10px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-view:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        /* ===== MODAL STYLES ===== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            width: min(1000px, 95vw);
            max-height: 90vh;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            flex-shrink: 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #1f2937;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            color: #333;
            background: rgba(0,0,0,0.05);
            border-radius: 6px;
        }

        .modal-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .modal-preview {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
        }

        .modal-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .modal-preview iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .preview-fallback {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .preview-fallback i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
            flex-shrink: 0;
        }

        .btn-modal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-download-modal {
            background: var(--success-color);
            color: white;
        }

        .btn-download-modal:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-print-modal {
            background: var(--info-color);
            color: white;
        }

        .btn-print-modal:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-close-modal {
            background: #e5e7eb;
            color: #1f2937;
        }

        .btn-close-modal:hover {
            background: #d1d5db;
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 240px;
            }

            .employee-sidebar {
                transform: translateX(-100%);
            }

            .employee-sidebar.mobile-open {
                transform: translateX(0);
            }

            .employee-main {
                margin-left: 0;
            }

            .employee-content {
                padding: 15px;
            }

            .documents-grid {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .modal-content {
                max-height: 95vh;
            }
        }

        @media (max-width: 480px) {
            .employee-content {
                padding: 10px;
            }

            .card {
                padding: 15px;
            }

            .documents-grid {
                grid-template-columns: 1fr;
            }

            .modal-footer {
                flex-direction: column;
            }

            .btn-modal {
                width: 100%;
            }
        }

        .employee-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .employee-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .employee-content::-webkit-scrollbar {
            width: 8px;
        }

        .employee-content::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="employee-wrapper">
        <aside class="employee-sidebar" id="employeeSidebar">
            <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-briefcase"></i>
                </div>
                <span class="sidebar-logo-text">Employee Portal</span>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-menu-section">
                    <a href="dashboard.php" class="sidebar-menu-link">
                        <i class="fas fa-home"></i>
                        <span class="sidebar-menu-text">Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-menu-section">
                    <div class="sidebar-section-title">Personal</div>
                    <ul class="sidebar-menu">
                        <li class="sidebar-menu-item">
                            <a href="view_profile.php" class="sidebar-menu-link">
                                <i class="fas fa-user-circle"></i>
                                <span class="sidebar-menu-text">My Profile</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="my_documents.php" class="sidebar-menu-link active">
                                <i class="fas fa-file-alt"></i>
                                <span class="sidebar-menu-text">My Documents</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-menu-section">
                    <div class="sidebar-section-title">Attendance</div>
                    <ul class="sidebar-menu">
                        <li class="sidebar-menu-item">
                            <a href="attendance.php" class="sidebar-menu-link">
                                <i class="fas fa-calendar-check"></i>
                                <span class="sidebar-menu-text">Records</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <?php if ($employee['employment_type'] === 'Regular'): ?>
                <li class="sidebar-menu-section">
                    <div class="sidebar-section-title">Leave Management</div>
                    <ul class="sidebar-menu">
                        <li class="sidebar-menu-item">
                            <a href="apply_leave.php" class="sidebar-menu-link">
                                <i class="fas fa-file-alt"></i>
                                <span class="sidebar-menu-text">Apply for Leave</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="leave_status.php" class="sidebar-menu-link">
                                <i class="fas fa-clipboard-list"></i>
                                <span class="sidebar-menu-text">Leave Status</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="sidebar-menu-section">
                    <div class="sidebar-section-title">Payroll</div>
                    <ul class="sidebar-menu">
                        <li class="sidebar-menu-item">
                            <a href="salary_slips.php" class="sidebar-menu-link">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span class="sidebar-menu-text">My Payslips</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-menu-section">
                    <div class="sidebar-section-title">Communications</div>
                    <ul class="sidebar-menu">
                        <li class="sidebar-menu-item">
                            <a href="announcements.php" class="sidebar-menu-link">
                                <i class="fas fa-bullhorn"></i>
                                <span class="sidebar-menu-text">Announcements</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <div class="sidebar-footer">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="sidebar-user-details">
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($employee['first_name']); ?></div>
                        <div class="sidebar-user-role"><?php echo htmlspecialchars($employee['employment_type']); ?></div>
                    </div>
                </div>
                <a href="../logout.php" class="sidebar-logout-btn" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <div class="employee-main">
            <nav class="employee-navbar">
                <div class="navbar-left">
                    <h2>
                        <i class="fas fa-folder-open"></i>
                        <span>My Documents</span>
                    </h2>
                </div>
            </nav>

            <div class="employee-content">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>

                <div class="page-header">
                    <h1>My Documents & Files 📁</h1>
                    <p>All your official employee documents are securely stored here. Click to preview, download or print.</p>
                </div>

                <div class="card">
                    <h3><i class="fas fa-file-shield"></i> Official Employee Documents</h3>

                    <?php if (empty($documents)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-pdf"></i>
                            <p><strong>No Documents Yet</strong></p>
                            <p>HR will upload your official documents here.</p>
                        </div>
                    <?php else: ?>
                        <div class="documents-grid">
                            <?php foreach ($documents as $doc): ?>
                                <div class="document-card">
                                    <div class="document-type-badge">
                                        <?php
                                            $titleMap = [
                                                'credentials' => 'Credentials',
                                                '201_file' => '201 File',
                                                'contract' => 'Contract',
                                                'cos' => 'COS',
                                                'diploma' => 'Diploma / TOR',
                                                'other' => 'Other'
                                            ];
                                            echo $titleMap[$doc['document_type']] ?? ucfirst(str_replace('_', ' ', $doc['document_type']));
                                        ?>
                                    </div>
                                    
                                    <div class="document-name">
                                        <?php echo htmlspecialchars($doc['document_name']); ?>
                                    </div>

                                    <div class="document-meta">
                                        <strong>📦 Size:</strong> <?php echo number_format((float)($doc['file_size'] / 1024), 2); ?> KB<br>
                                        <strong>📅 Uploaded:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($doc['uploaded_at']))); ?>
                                    </div>

                                    <div class="document-actions">
                                        <button class="btn-view" onclick="openPreview(<?php echo (int)$doc['document_id']; ?>, '<?php echo htmlspecialchars($doc['document_name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== PREVIEW MODAL ===== -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Document Preview</h3>
                <button class="modal-close" onclick="closePreview()">&times;</button>
            </div>

            <div class="modal-body">
                <div class="modal-preview" id="modalPreview">
                    <div class="preview-fallback">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading preview...</p>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <a id="downloadLink" href="#" class="btn-modal btn-download-modal">
                    <i class="fas fa-download"></i> Download
                </a>
                <button class="btn-modal btn-print-modal" onclick="printDocument()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn-modal btn-close-modal" onclick="closePreview()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const employeeSidebar = document.getElementById('employeeSidebar');
        let currentDocId = null;

        sidebarToggle.addEventListener('click', function() {
            employeeSidebar.classList.toggle('collapsed');
        });

        function openPreview(docId, docName) {
            currentDocId = docId;
            document.getElementById('modalTitle').textContent = docName;
            document.getElementById('downloadLink').href = 'my_documents.php?download_document=' + docId;
            
            const previewDiv = document.getElementById('modalPreview');
            previewDiv.innerHTML = '<iframe src="my_documents.php?view_document=' + docId + '" style="width:100%; height:100%; border:none;"></iframe>';
            
            document.getElementById('previewModal').classList.add('active');
        }

        function closePreview() {
            document.getElementById('previewModal').classList.remove('active');
            document.getElementById('modalPreview').innerHTML = '';
            currentDocId = null;
        }

        function printDocument() {
            if (currentDocId) {
                const printWindow = window.open('my_documents.php?view_document=' + currentDocId, 'print', 'height=600,width=800');
                printWindow.addEventListener('load', function() {
                    setTimeout(() => printWindow.print(), 250);
                });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('previewModal');
            if (event.target === modal) {
                closePreview();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePreview();
            }
        });
    </script>
</body>
</html>