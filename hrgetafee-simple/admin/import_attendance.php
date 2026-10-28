<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

require_login();
check_role(1);

$admin = get_employee_info($conn, $_SESSION['employee_id']);
$message = '';
$error = '';
$preview_data = [];
$file_uploaded = false;
$uploaded_file_path = '';

function normalize_header_name($value) {
    $value = trim((string)$value);
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value); // remove BOM
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '', $value);
    return $value;
}

function normalize_date_value($value) {
    $value = trim((string)$value);
    if ($value === '') return null;

    $value = str_replace('/', '-', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    $ts = strtotime($value);
    if ($ts === false) {
        return null;
    }

    return date('Y-m-d', $ts);
}

function normalize_time_value($value) {
    $value = trim((string)$value);
    if ($value === '') return null;

    $value = preg_replace('/\s+/', ' ', $value);

    if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
        return $value;
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return null;
    }

    return date('H:i:s', $ts);
}

function normalize_datetime_value($date_value, $time_value) {
    $date_value = normalize_date_value($date_value);
    $time_value = normalize_time_value($time_value);

    if (!$date_value || !$time_value) {
        return null;
    }

    $combined = $date_value . ' ' . $time_value;
    $ts = strtotime($combined);

    if ($ts === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $ts);
}

function normalize_status_value($status) {
    $status = strtolower(trim((string)$status));
    $status = str_replace(' ', '_', $status);

    $allowed = ['present', 'absent', 'late'];

    if (in_array($status, $allowed, true)) {
        return $status;
    }

    if ($status === 'on_time') return 'present';
    if ($status === 'p') return 'present';
    if ($status === 'a') return 'absent';
    if ($status === 'l') return 'late';

    return 'present';
}

function read_import_rows($file_path, $ext) {
    $rows = [];

    if (strtolower($ext) === 'csv') {
        $handle = fopen($file_path, 'r');
        if (!$handle) return $rows;

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return $rows;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $header) {
                $assoc[$header] = $row[$index] ?? '';
            }
            $rows[] = $assoc;
        }

        fclose($handle);
        return $rows;
    }

    if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file_path);
            $sheet = $spreadsheet->getActiveSheet();

            $allRows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = $cell->getValue();
                }
                $allRows[] = $cells;
            }

            if (empty($allRows)) return [];

            $headers = [];
            foreach ($allRows[0] as $index => $value) {
                $headers[$index] = trim((string)$value);
            }

            for ($i = 1; $i < count($allRows); $i++) {
                $row = $allRows[$i];
                $assoc = [];
                foreach ($headers as $index => $header) {
                    $assoc[$header] = $row[$index] ?? '';
                }

                if (array_filter($assoc, function ($v) {
                    return trim((string)$v) !== '';
                })) {
                    $rows[] = $assoc;
                }
            }

            return $rows;
        } catch (Exception $e) {
            return [];
        }
    }

    return [];
}

// Create import log table if missing
$check_table = $conn->query("SHOW TABLES LIKE 'biometric_import_logs'");
if ($check_table->num_rows === 0) {
    $conn->query("CREATE TABLE biometric_import_logs (
        import_id INT PRIMARY KEY AUTO_INCREMENT,
        imported_by INT NOT NULL,
        import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        filename VARCHAR(255),
        total_records INT,
        successful_records INT,
        failed_records INT,
        notes TEXT
    )");
}

$import_dir = __DIR__ . '/../uploads/attendance_imports';
if (!is_dir($import_dir)) {
    mkdir($import_dir, 0777, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['attendance_file'])) {
    $csrf = $_POST['csrf_token'] ?? '';

    if (empty($csrf) || !verifyCSRFToken($csrf)) {
        $error = 'Invalid form token. Please reload the page.';
    } else {
        $file = $_FILES['attendance_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload error.';
        } elseif ($file['size'] > 5000000) {
            $error = 'File size too large. Maximum is 5MB.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
                $error = 'Only CSV and Excel files are allowed.';
            } else {
                $safe_name = preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($file['name']));
                $stored_name = time() . '_' . $safe_name;
                $stored_path = $import_dir . '/' . $stored_name;

                if (move_uploaded_file($file['tmp_name'], $stored_path)) {
                    $uploaded_file_path = $stored_path;

                    $emp_col = sanitizeInput($_POST['emp_col'] ?? '');
                    $date_col = sanitizeInput($_POST['date_col'] ?? '');
                    $time_in_col = sanitizeInput($_POST['time_in_col'] ?? '');
                    $time_out_col = sanitizeInput($_POST['time_out_col'] ?? '');
                    $status_col = sanitizeInput($_POST['status_col'] ?? '');
                    $mode = sanitizeInput($_POST['mode'] ?? 'preview');

                    if (!$emp_col || !$date_col || !$time_in_col) {
                        $error = 'Please map Date, Employee, and Time in/out (AM).';
                    } else {
                        $rows = read_import_rows($stored_path, $ext);

                        if (empty($rows)) {
                            $error = 'No readable rows were found in the file.';
                        } else {
                            $headers = array_keys($rows[0]);

                            $emp_key = null;
                            $date_key = null;
                            $time_in_key = null;
                            $time_out_key = null;
                            $status_key = null;

                            foreach ($headers as $header) {
                                $header_norm = normalize_header_name($header);

                                $emp_norm = normalize_header_name($emp_col);
                                $date_norm = normalize_header_name($date_col);
                                $time_in_norm = normalize_header_name($time_in_col);
                                $time_out_norm = $time_out_col ? normalize_header_name($time_out_col) : '';
                                $status_norm = $status_col ? normalize_header_name($status_col) : '';

                                if ($header_norm === $emp_norm) {
                                    $emp_key = $header;
                                }
                                if ($header_norm === $date_norm) {
                                    $date_key = $header;
                                }
                                if ($header_norm === $time_in_norm) {
                                    $time_in_key = $header;
                                }
                                if ($time_out_col && $header_norm === $time_out_norm) {
                                    $time_out_key = $header;
                                }
                                if ($status_col && $header_norm === $status_norm) {
                                    $status_key = $header;
                                }
                            }

                            if (!$emp_key || !$date_key || !$time_in_key) {
                                $error = 'The selected columns were not found in the uploaded file.';
                            } else {
                                if ($mode === 'preview') {
                                    $preview_data = [];
                                    $count = 0;

                                    foreach ($rows as $row) {
                                        if ($count >= 20) break;

                                        $employee_value = trim((string)($row[$emp_key] ?? ''));
                                        $date_value = trim((string)($row[$date_key] ?? ''));
                                        $time_in_value = trim((string)($row[$time_in_key] ?? ''));
                                        $time_out_value = $time_out_key ? trim((string)($row[$time_out_key] ?? '')) : '';
                                        $status_value = $status_key ? trim((string)($row[$status_key] ?? '')) : 'present';

                                        $employee_exists = '✗';

                                        if ($employee_value !== '') {
                                            $emp_stmt = $conn->prepare("SELECT employee_id FROM employees WHERE employee_code = ? OR CONCAT(first_name, ' ', last_name) = ? LIMIT 1");
                                            $emp_stmt->bind_param('ss', $employee_value, $employee_value);
                                            $emp_stmt->execute();
                                            $emp_result = $emp_stmt->get_result();
                                            $emp_row = $emp_result->fetch_assoc();
                                            $emp_stmt->close();

                                            if ($emp_row) {
                                                $employee_exists = '✓';
                                            }
                                        }

                                        $preview_data[] = [
                                            'employee_value' => $employee_value,
                                            'employee_exists' => $employee_exists,
                                            'date' => $date_value,
                                            'time_in' => $time_in_value,
                                            'time_out' => $time_out_value,
                                            'status' => normalize_status_value($status_value)
                                        ];

                                        $count++;
                                    }

                                    $file_uploaded = true;
                                    $message = 'Preview loaded successfully. Please review the data and click “Confirm & Import”.';
                                } elseif ($mode === 'import') {
                                    $successful = 0;
                                    $failed = 0;
                                    $failed_messages = [];

                                    foreach ($rows as $row) {
                                        $employee_value = trim((string)($row[$emp_key] ?? ''));
                                        $date_value = trim((string)($row[$date_key] ?? ''));
                                        $time_in_value = trim((string)($row[$time_in_key] ?? ''));
                                        $time_out_value = $time_out_key ? trim((string)($row[$time_out_key] ?? '')) : '';
                                        $status_value = $status_key ? trim((string)($row[$status_key] ?? '')) : 'present';

                                        if ($employee_value === '' || $date_value === '' || $time_in_value === '') {
                                            $failed++;
                                            $failed_messages[] = 'Missing required data in a row';
                                            continue;
                                        }

                                        $employee_id = null;

                                        $emp_stmt = $conn->prepare("SELECT employee_id FROM employees WHERE employee_code = ? OR CONCAT(first_name, ' ', last_name) = ? LIMIT 1");
                                        $emp_stmt->bind_param('ss', $employee_value, $employee_value);
                                        $emp_stmt->execute();
                                        $emp_result = $emp_stmt->get_result();
                                        $emp_row = $emp_result->fetch_assoc();
                                        $emp_stmt->close();

                                        if (!$emp_row) {
                                            $failed++;
                                            $failed_messages[] = "Employee not found: $employee_value";
                                            continue;
                                        }

                                        $employee_id = (int)$emp_row['employee_id'];
                                        $attendance_date = normalize_date_value($date_value);

                                        if (!$attendance_date) {
                                            $failed++;
                                            $failed_messages[] = "Invalid date for employee: $employee_value";
                                            continue;
                                        }

                                        $clock_in = normalize_datetime_value($date_value, $time_in_value);
                                        $clock_out = ($time_out_value !== '') ? normalize_datetime_value($date_value, $time_out_value) : null;

                                        if (!$clock_in) {
                                            $failed++;
                                            $failed_messages[] = "Invalid time in value for employee: $employee_value";
                                            continue;
                                        }

                                        $status = normalize_status_value($status_value);

                                        $check_stmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE employee_id = ? AND attendance_date = ? LIMIT 1");
                                        $check_stmt->bind_param('is', $employee_id, $attendance_date);
                                        $check_stmt->execute();
                                        $check_result = $check_stmt->get_result();
                                        $existing = $check_result->fetch_assoc();
                                        $check_stmt->close();

                                        if ($existing) {
                                            $update_stmt = $conn->prepare("UPDATE attendance SET clock_in = ?, clock_out = ?, status = ?, remarks = ? WHERE employee_id = ? AND attendance_date = ?");
                                            $remarks = 'Imported from Excel attendance file';
                                            $update_stmt->bind_param('ssssis', $clock_in, $clock_out, $status, $remarks, $employee_id, $attendance_date);
                                            if ($update_stmt->execute()) {
                                                $successful++;
                                            } else {
                                                $failed++;
                                                $failed_messages[] = "Update failed for employee: $employee_value";
                                            }
                                            $update_stmt->close();
                                        } else {
                                            $insert_stmt = $conn->prepare("INSERT INTO attendance (employee_id, clock_in, clock_out, attendance_date, status, remarks) VALUES (?, ?, ?, ?, ?, ?)");
                                            $remarks = 'Imported from Excel attendance file';
                                            $insert_stmt->bind_param('isssss', $employee_id, $clock_in, $clock_out, $attendance_date, $status, $remarks);
                                            if ($insert_stmt->execute()) {
                                                $successful++;
                                            } else {
                                                $failed++;
                                                $failed_messages[] = "Insert failed for employee: $employee_value";
                                            }
                                            $insert_stmt->close();
                                        }
                                    }

                                    $filename = basename($file['name']);
                                    $total = $successful + $failed;
                                    $notes = implode('; ', array_slice($failed_messages, 0, 5));

                                    $log_stmt = $conn->prepare("INSERT INTO biometric_import_logs (imported_by, filename, total_records, successful_records, failed_records, notes) VALUES (?, ?, ?, ?, ?, ?)");
                                    $log_stmt->bind_param('isiiis', $_SESSION['user_id'], $filename, $total, $successful, $failed, $notes);
                                    $log_stmt->execute();
                                    $log_stmt->close();

                                    $message = "✓ Import completed successfully. Successful: $successful, Failed: $failed";
                                    logSecurityEvent($conn, $_SESSION['user_id'], 'BIOMETRIC_IMPORT', "Imported Excel attendance records: $successful successful, $failed failed");

                                    $preview_data = [];
                                    $file_uploaded = false;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Import history
$import_history = [];
$hist_stmt = $conn->prepare("SELECT import_id, imported_by, import_date, filename, total_records, successful_records, failed_records FROM biometric_import_logs ORDER BY import_date DESC LIMIT 20");
if ($hist_stmt) {
    $hist_stmt->execute();
    $hist_result = $hist_stmt->get_result();
    $import_history = $hist_result->fetch_all(MYSQLI_ASSOC);
    $hist_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Excel Import - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f3f3f5;
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #5f67e8, #7e5ee7);
            color: white;
            padding: 18px 24px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .page-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }

        .page-header .userbox {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .page-header .logout-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        .main-panel {
            background: #ffffff;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 20px;
        }

        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            background: #4b5563;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 7px;
            font-weight: 600;
            text-decoration: none;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 18px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-left-color: #10b981;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: #ef4444;
        }

        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border-left-color: #3b82f6;
        }

        .upload-box {
            background: #fafafa;
            border: 2px dashed #3b82f6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .upload-box h3 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 28px;
            color: #1f2937;
        }

        .file-field {
            margin-bottom: 15px;
        }

        .file-field label {
            display: block;
            font-weight: 700;
            margin-bottom: 6px;
            color: #1f2937;
        }

        .file-field input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            font-size: 14px;
        }

        .file-help {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
        }

        .mapping-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 14px;
        }

        .mapping-group {
            display: flex;
            flex-direction: column;
        }

        .mapping-group label {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .mapping-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            font-size: 14px;
        }

        .action-buttons {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #1f2937;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
        }

        .btn-success {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        .preview-table th,
        .preview-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .preview-table th {
            background: #f8fafc;
            color: #1f2937;
        }

        .valid {
            color: #10b981;
            font-weight: 700;
        }

        .invalid {
            color: #ef4444;
            font-weight: 700;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            background: white;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .history-table th {
            background: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .muted {
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .mapping-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>📤 Attendance Excel Import</h2>
            <div class="userbox">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin['first_name']); ?></strong></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="main-panel">
            <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="alert alert-info">
                <strong>Instructions:</strong> Upload a CSV or Excel attendance file. Map the columns to the system fields, preview the data, and confirm the import.
            </div>

            <div class="upload-box">
                <h3>📁 Step 1: Upload File</h3>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="mode" value="preview">

                    <div class="file-field">
                        <label for="attendance_file">Select CSV/Excel File *</label>
                        <input type="file" name="attendance_file" id="attendance_file" accept=".csv,.xlsx,.xls" required>
                        <div class="file-help">Supported formats: CSV, Excel (.xlsx, .xls) | Max size: 5MB</div>
                    </div>

                    <h3 style="margin-top: 24px;">📋 Step 2: Map Columns</h3>
                    <p class="muted">Use the exact columns from your attendance Excel template:</p>

                    <div class="mapping-grid">
                        <div class="mapping-group">
                            <label for="date_col">Date Column *</label>
                            <select name="date_col" id="date_col" required>
                                <option value="">-- Select Column --</option>
                                <option value="Date">Date</option>
                                <option value="date">date</option>
                                <option value="attendance_date">attendance_date</option>
                            </select>
                        </div>

                        <div class="mapping-group">
                            <label for="emp_col">Employee Column *</label>
                            <select name="emp_col" id="emp_col" required>
                                <option value="">-- Select Column --</option>
                                <option value="employee">employee</option>
                                <option value="employee_code">employee_code</option>
                                <option value="emp_code">emp_code</option>
                                <option value="code">code</option>
                            </select>
                        </div>

                        <div class="mapping-group">
                            <label for="time_in_col">Time in/out (AM) Column *</label>
                            <select name="time_in_col" id="time_in_col" required>
                                <option value="">-- Select Column --</option>
                                <option value="Time in/out (AM)">Time in/out (AM)</option>
                                <option value="time_in_am">time_in_am</option>
                                <option value="time_in">time_in</option>
                                <option value="clock_in">clock_in</option>
                            </select>
                        </div>

                        <div class="mapping-group">
                            <label for="time_out_col">Time in/out (PM) Column (Optional)</label>
                            <select name="time_out_col" id="time_out_col">
                                <option value="">-- Select Column --</option>
                                <option value="Time in/out (PM)">Time in/out (PM)</option>
                                <option value="time_in_pm">time_in_pm</option>
                                <option value="time_out">time_out</option>
                                <option value="clock_out">clock_out</option>
                            </select>
                        </div>

                        <div class="mapping-group">
                            <label for="status_col">Status Column (Optional)</label>
                            <select name="status_col" id="status_col">
                                <option value="">-- Select Column --</option>
                                <option value="status">status</option>
                                <option value="attendance_status">attendance_status</option>
                            </select>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn-primary">📤 Upload & Preview</button>
                    </div>
                </form>
            </div>

            <?php if (!empty($preview_data)): ?>
                <div style="background: white; border-radius: 10px; border: 1px solid #e5e7eb; padding: 20px; margin-bottom: 20px;">
                    <h3>👀 Step 3: Preview Data</h3>
                    <p class="muted">Review the rows below before importing. ✓ means the employee exists in the system.</p>

                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Exists</th>
                                <th>Date</th>
                                <th>AM Time</th>
                                <th>PM Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_value']); ?></td>
                                    <td>
                                        <span class="<?php echo $row['employee_exists'] === '✓' ? 'valid' : 'invalid'; ?>">
                                            <?php echo $row['employee_exists']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['time_in']); ?></td>
                                    <td><?php echo htmlspecialchars($row['time_out'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="mode" value="import">
                        <input type="hidden" name="emp_col" value="<?php echo htmlspecialchars($_POST['emp_col'] ?? ''); ?>">
                        <input type="hidden" name="date_col" value="<?php echo htmlspecialchars($_POST['date_col'] ?? ''); ?>">
                        <input type="hidden" name="time_in_col" value="<?php echo htmlspecialchars($_POST['time_in_col'] ?? ''); ?>">
                        <input type="hidden" name="time_out_col" value="<?php echo htmlspecialchars($_POST['time_out_col'] ?? ''); ?>">
                        <input type="hidden" name="status_col" value="<?php echo htmlspecialchars($_POST['status_col'] ?? ''); ?>">
                        <input type="hidden" name="attendance_file" value="<?php echo htmlspecialchars($uploaded_file_path); ?>">

                        <div class="action-buttons">
                            <button type="submit" class="btn-success" onclick="return confirm('This will import all valid attendance records. Continue?');">
                                ✓ Confirm & Import
                            </button>
                            <a href="import_attendance.php" class="btn-secondary">← Start Over</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div style="background: white; border-radius: 10px; border: 1px solid #e5e7eb; padding: 20px;">
                <h3>📊 Import History</h3>

                <?php if (!empty($import_history)): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Filename</th>
                                <th>Imported By</th>
                                <th>Total</th>
                                <th>Success</th>
                                <th>Failed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($import_history as $log): ?>
                                <tr>
                                    <td><?php echo format_datetime($log['import_date']); ?></td>
                                    <td><?php echo htmlspecialchars($log['filename']); ?></td>
                                    <td>
                                        <?php
                                            $user_stmt = $conn->prepare("SELECT CONCAT(e.first_name, ' ', e.last_name) as name FROM users u JOIN employees e ON u.employee_id = e.employee_id WHERE u.user_id = ?");
                                            if ($user_stmt) {
                                                $user_stmt->bind_param('i', $log['imported_by']);
                                                $user_stmt->execute();
                                                $user_result = $user_stmt->get_result();
                                                $user_row = $user_result->fetch_assoc();
                                                $user_stmt->close();
                                                echo htmlspecialchars($user_row['name'] ?? 'Unknown');
                                            } else {
                                                echo 'Unknown';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo (int)$log['total_records']; ?></td>
                                    <td><span class="badge badge-success"><?php echo (int)$log['successful_records']; ?></span></td>
                                    <td><span class="badge badge-danger"><?php echo (int)$log['failed_records']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="muted" style="text-align:center; padding:20px;">No imports yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>