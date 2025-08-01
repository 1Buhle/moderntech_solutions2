<?php
ob_start();

// --- Database Connection Setup ---
$host = 'localhost';
$dbName = 'moderntech_hr';
$username = 'root';
$password = 'CubanaKing@2016';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbName;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$conn = null;
$phpError = null;
$attendanceData = [];
$response = ['success' => false, 'message' => ''];

try {
    $conn = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Log the error for debugging, but show a user-friendly message
    error_log("Database Connection Error in AttendanceView.php: " . $e->getMessage());
    $phpError = "Failed to connect to the database. Please check your connection settings or try again later.";
}

// --- PHP Functions for CRUD Operations ---

/**
 * Adds a new attendance record.
 * @param PDO $conn The PDO database connection object.
 * @param int $employeeId The ID of the employee.
 * @param string $date The date of attendance (YYYY-MM-DD).
 * @param string $status The attendance status ('Present' or 'Absent').
 * @return array An associative array with 'success' (bool) and 'message' (string).
 */
function addAttendance(PDO $conn, $employeeId, $date, $status) {
    try {
        // Check if an attendance record for this employee and date already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = :employee_id AND date = :date");
        $stmt->execute([':employee_id' => $employeeId, ':date' => $date]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => "Attendance for Employee ID {$employeeId} on {$date} already exists. Please update the existing record instead of adding a new one."];
        }

        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (:employee_id, :date, :status)");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':date' => $date,
            ':status' => $status
        ]);
        return ['success' => true, 'message' => 'Attendance added successfully.'];
    } catch (PDOException $e) {
        error_log("Error adding attendance: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add attendance: ' . $e->getMessage()];
    }
}

/**
 * Updates an existing attendance record.
 * @param PDO $conn The PDO database connection object.
 * @param int $id The ID of the attendance record.
 * @param int $employeeId The ID of the employee (for verification).
 * @param string $date The new date of attendance (YYYY-MM-DD).
 * @param string $status The new attendance status ('Present' or 'Absent').
 * @return array An associative array with 'success' (bool) and 'message' (string).
 */
function updateAttendance(PDO $conn, $id, $employeeId, $date, $status) {
    try {
        $stmt = $conn->prepare("UPDATE attendance SET employee_id = :employee_id, date = :date, status = :status WHERE id = :id");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':date' => $date,
            ':status' => $status,
            ':id' => $id
        ]);
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Attendance record updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'No attendance record found with the given ID, or no changes were made.'];
        }
    } catch (PDOException $e) {
        error_log("Error updating attendance: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update attendance: ' . $e->getMessage()];
    }
}

/**
 * Deletes an attendance record.
 * @param PDO $conn The PDO database connection object.
 * @param int $id The ID of the attendance record to delete.
 * @return array An associative array with 'success' (bool) and 'message' (string).
 */
function deleteAttendance(PDO $conn, $id) {
    try {
        $stmt = $conn->prepare("DELETE FROM attendance WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Attendance record deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'No attendance record found with the given ID.'];
        }
    } catch (PDOException $e) {
        error_log("Error deleting attendance: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete attendance: ' . $e->getMessage()];
    }
}

/**
 * Adds a new leave request.
 * @param PDO $conn The PDO database connection object.
 * @param int $employeeId The ID of the employee.
 * @param string $startDate The start date of the leave (YYYY-MM-DD).
 * @param string|null $endDate The end date of the leave (YYYY-MM-DD), can be null for single-day leave.
 * @param string $reason The reason for the leave.
 * @param string $status The status of the leave request ('Approved', 'Denied', 'Pending').
 * @return array An associative array with 'success' (bool) and 'message' (string).
 */
function addLeaveRequest(PDO $conn, $employeeId, $startDate, $endDate, $reason, $status) {
    try {
        $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, start_date, end_date, reason, status) VALUES (:employee_id, :start_date, :end_date, :reason, :status)");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':reason' => $reason,
            ':status' => $status
        ]);
        return ['success' => true, 'message' => 'Leave request added successfully.'];
    } catch (PDOException $e) {
        error_log("Error adding leave request: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add leave request: ' . $e->getMessage()];
    }
}

/**
 * Updates an existing leave request.
 * @param PDO $conn The PDO database connection object.
 * @param int $id The ID of the leave request.
 * @param int $employeeId The ID of the employee (for verification).
 * @param string $startDate The new start date of the leave (YYYY-MM-DD).
 * @param string|null $endDate The new end date of the leave (YYYY-MM-DD), can be null for single-day leave.
 * @param string $reason The new reason for the leave.
 * @param string $status The new status of the leave request ('Approved', 'Denied', 'Pending').
 * @return array An associative array with 'success' (bool) and 'message' (string).
 */
function updateLeaveRequest(PDO $conn, $id, $employeeId, $startDate, $endDate, $reason, $status) {
    try {
        $stmt = $conn->prepare("UPDATE leave_requests SET employee_id = :employee_id, start_date = :start_date, end_date = :end_date, reason = :reason, status = :status WHERE id = :id");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':reason' => $reason,
            ':status' => $status,
            ':id' => $id
        ]);
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Leave request updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'No leave request found with the given ID, or no changes were made.'];
        }
    } catch (PDOException $e) {
        error_log("Error updating leave request: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update leave request: ' . $e->getMessage()];
    }
}

/**
 * Deletes a leave request.
 * @param PDO $conn The PDO database connection object.
 * @param int $id The ID of the leave request to delete.
 * @return array An associative array with 'success' (bool) and 'message' (string).
 */
function deleteLeaveRequest(PDO $conn, $id) {
    try {
        $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Leave request deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'No leave request found with the given ID.'];
        }
    } catch (PDOException $e) {
        error_log("Error deleting leave request: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete leave request: ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';

    // --- PHP DEBUGGING LOGS ---
    error_log("PHP Debug: Received POST action: " . $action);
    error_log("PHP Debug: Raw _POST data: " . print_r($_POST, true));
    // --- END PHP DEBUGGING LOGS ---

    switch ($action) {
case 'add_attendance':
    $employeeId = filter_var($_POST['employee_id'] ?? '', FILTER_VALIDATE_INT);
    $date = $_POST['date'] ?? '';
    
    $status = $_POST['attendance_status'] ?? $_POST['status'] ?? $_POST['attendance'] ?? '';

    // Enhanced debugging
    error_log("PHP Debug: add_attendance - All POST keys: " . implode(', ', array_keys($_POST)));
    error_log("PHP Debug: add_attendance - employee_id (raw): " . ($_POST['employee_id'] ?? 'NOT SET'));
    error_log("PHP Debug: add_attendance - employeeId (filtered): " . var_export($employeeId, true));
    error_log("PHP Debug: add_attendance - date: " . var_export($date, true));
    error_log("PHP Debug: add_attendance - status: " . var_export($status, true));
    error_log("PHP Debug: add_attendance - Tried status fields: attendance_status=" . ($_POST['attendance_status'] ?? 'NOT SET') . 
              ", status=" . ($_POST['status'] ?? 'NOT SET') . 
              ", attendance=" . ($_POST['attendance'] ?? 'NOT SET'));

    // IMPROVED VALIDATION: Check for empty string and ensure positive integer
    if ($employeeId !== false && $employeeId !== null && $employeeId > 0 && !empty($date) && in_array($status, ['Present', 'Absent'])) {
        $response = addAttendance($conn, $employeeId, $date, $status);
    } else {
        // More specific error messages
        $errors = [];
        if ($employeeId === false || $employeeId === null || $employeeId <= 0) {
            $errors[] = "Invalid Employee ID (received: " . ($_POST['employee_id'] ?? 'empty') . ")";
        }
        if (empty($date)) {
            $errors[] = "Date is required";
        }
        if (!in_array($status, ['Present', 'Absent'])) {
            $errors[] = "Status must be 'Present' or 'Absent' (received: '$status')";
        }
        
        $response['message'] = "Invalid input for adding attendance: " . implode(", ", $errors);
        error_log("PHP Debug: add_attendance validation failed: " . $response['message']);
    }
    break;

        case 'update_attendance':
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            $employeeId = filter_var($_POST['employee_id'] ?? '', FILTER_VALIDATE_INT);
            $date = $_POST['date'] ?? '';
            $status = $_POST['attendance_status'] ?? '';
            if ($id !== false && $id !== null && $employeeId !== false && $employeeId !== null && $date && in_array($status, ['Present', 'Absent'])) {
                $response = updateAttendance($conn, $id, $employeeId, $date, $status);
            } else {
                $response['message'] = "Invalid input for updating attendance.";
                error_log("PHP Debug: update_attendance validation failed. ID valid: " . var_export(($id !== false && $id !== null), true) . ", Employee ID valid: " . var_export(($employeeId !== false && $employeeId !== null), true) . ", Date set: " . var_export((bool)$date, true) . ", Status valid: " . var_export(in_array($status, ['Present', 'Absent']), true));
            }
            break;

        case 'delete_attendance':
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            if ($id !== false && $id !== null) {
                $response = deleteAttendance($conn, $id);
            } else {
                $response['message'] = "Invalid attendance ID for deletion.";
                error_log("PHP Debug: delete_attendance validation failed. ID valid: " . var_export(($id !== false && $id !== null), true));
            }
            break;

        case 'add_leave_request':
            $employeeId = filter_var($_POST['employee_id'] ?? '', FILTER_VALIDATE_INT);
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? null;
            $reason = $_POST['reason'] ?? '';
            $status = $_POST['leave_status'] ?? ''; 

            // Basic validation for dates
            $validDates = (bool)strtotime($startDate);
            if ($endDate !== null && $endDate !== '') {
                $validDates = $validDates && (bool)strtotime($endDate);
            } else {
                $endDate = null;
            }

            if ($employeeId !== false && $employeeId !== null && $validDates && $reason && in_array($status, ['Approved', 'Denied', 'Pending'])) {
                $response = addLeaveRequest($conn, $employeeId, $startDate, $endDate, $reason, $status);
            } else {
                $response['message'] = "Invalid input for adding leave request. Ensure all required fields are filled and dates are valid.";
                error_log("PHP Debug: add_leave_request validation failed. Employee ID valid: " . var_export(($employeeId !== false && $employeeId !== null), true) . ", Dates valid: " . var_export($validDates, true) . ", Reason set: " . var_export((bool)$reason, true) . ", Status valid: " . var_export(in_array($status, ['Approved', 'Denied', 'Pending']), true));
            }
            break;

        case 'update_leave_request':
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            $employeeId = filter_var($_POST['employee_id'] ?? '', FILTER_VALIDATE_INT);
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? null;
            $reason = $_POST['reason'] ?? '';
            $status = $_POST['leave_status'] ?? ''; 

            $validDates = (bool)strtotime($startDate);
            if ($endDate !== null && $endDate !== '') {
                $validDates = $validDates && (bool)strtotime($endDate);
            } else {
                $endDate = null;
            }

            if ($id !== false && $id !== null && $employeeId !== false && $employeeId !== null && $validDates && $reason && in_array($status, ['Approved', 'Denied', 'Pending'])) {
                $response = updateLeaveRequest($conn, $id, $employeeId, $startDate, $endDate, $reason, $status);
            } else {
                $response['message'] = "Invalid input for updating leave request. Ensure all required fields are filled and dates are valid.";
                error_log("PHP Debug: update_leave_request validation failed. ID valid: " . var_export(($id !== false && $id !== null), true) . ", Employee ID valid: " . var_export(($employeeId !== false && $employeeId !== null), true) . ", Dates valid: " . var_export($validDates, true) . ", Reason set: " . var_export((bool)$reason, true) . ", Status valid: " . var_export(in_array($status, ['Approved', 'Denied', 'Pending']), true));
            }
            break;

        case 'delete_leave_request':
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            if ($id !== false && $id !== null) {
                $response = deleteLeaveRequest($conn, $id);
            } else {
                $response['message'] = "Invalid leave request ID for deletion.";
                error_log("PHP Debug: delete_leave_request validation failed. ID valid: " . var_export(($id !== false && $id !== null), true));
            }
            break;

        default:
            $response['message'] = 'No valid action specified.';
            error_log("PHP Debug: No valid action specified: " . var_export($action, true));
            break;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $conn) {
    try {
        // 1. Fetch Employees
        $stmtEmployees = $conn->prepare("SELECT employee_id, name, photo FROM employees ORDER BY employee_id ASC");
        $stmtEmployees->execute();
        $employees = $stmtEmployees->fetchAll(PDO::FETCH_ASSOC);

        if (empty($employees)) {
            $phpError = "No employees found in the 'employees' table. Please add employees first.";
        }

        // 2. Fetch Attendance Records
        $stmtAttendance = $conn->prepare("SELECT id, employee_id, date, status FROM attendance ORDER BY employee_id, date DESC");
        $stmtAttendance->execute();
        $allAttendance = $stmtAttendance->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch Leave Requests
        $stmtLeaveRequests = $conn->prepare("SELECT id, employee_id, start_date, end_date, reason, status FROM leave_requests ORDER BY employee_id, start_date DESC");
        $stmtLeaveRequests->execute();
        $allLeaveRequests = $stmtLeaveRequests->fetchAll(PDO::FETCH_ASSOC);

        // --- Structure the Data for display ---
        foreach ($employees as $employee) {
            $employeeId = $employee['employee_id'];
            $employeeName = $employee['name'];
            $employeePhoto = $employee['photo'] ?? null;

            $currentEmployeeAttendance = [];
            foreach ($allAttendance as $att) {
                if ($att['employee_id'] == $employeeId) {
                    $currentEmployeeAttendance[] = ['id' => $att['id'], 'date' => $att['date'], 'status' => $att['status']];
                }
            }

            $currentEmployeeLeaveRequests = [];
            foreach ($allLeaveRequests as $leave) {
                if ($leave['employee_id'] == $employeeId) {
                    $currentEmployeeLeaveRequests[] = [
                        'id' => $leave['id'],
                        'start_date' => $leave['start_date'],
                        'end_date' => $leave['end_date'],
                        'reason' => $leave['reason'],
                        'status' => $leave['status']
                    ];
                }
            }

            $attendanceData[] = [
                "employee_id" => $employeeId,
                "name" => $employeeName,
                "photo" => $employeePhoto,
                "attendance" => $currentEmployeeAttendance,
                "leaveRequests" => $currentEmployeeLeaveRequests
            ];
        }

    } catch (PDOException $e) {
        error_log("Database Query Error in AttendanceView.php: " . $e->getMessage());
        $phpError = "Failed to fetch attendance data from tables. Error: " . $e->getMessage();
    }
}

// Helper functions for easy access to latest statuses
function getLatestAttendanceStatus($employee) {
    // Returns the status of the latest attendance record, or 'N/A' if none
    return !empty($employee['attendance']) ? $employee['attendance'][0]['status'] : 'N/A';
}

function getLatestLeaveRequestStatus($employee) {
    // Returns the status of the latest leave request, or 'None' if none
    return !empty($employee['leaveRequests']) ? $employee['leaveRequests'][0]['status'] : 'None';
}

// Encode data for JavaScript to be used by the modal and search functions
$jsonAttendanceData = json_encode($attendanceData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance Overview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=edit" />
<style>
    #btn {
        background-color: #f08331;
    }
    #btn:hover {
        background-color: #e76e37;
        cursor: pointer;
    }

    /* Modal transition styles for zoom and fade effect */
    .modal-zoom-fade-enter-active,
    .modal-zoom-fade-leave-active {
        transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                            transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-zoom-fade-enter-from,
    .modal-zoom-fade-leave-to {
        opacity: 0;
        transform: scale(0.7);
    }
    .modal-zoom-fade-enter-to,
    .modal-zoom-fade-leave-from {
        opacity: 1;
        transform: scale(1);
    }

    /* Utility class to hide elements */
    .hidden {
        display: none !important;
    }

    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        margin: 0;
        transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
    }
    
    .page-exit {
        transform: scale(1.2);
        opacity: 0;
    }

    .content-wrapper {
        display: flex;
        flex-grow: 1;
    }

    .main-content {
        flex-grow: 1;
        padding: 1rem;
        margin-left: 16rem;
    }

    /* PAGE ENTRANCE ANIMATION STYLES*/
    #main-content.page-entrance {
        transform: scale(1.2);
        opacity: 0;
        transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    #main-content.page-entrance-complete {
        transform: scale(1);
        opacity: 1;
    }
    .directory-element {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .directory-element.animate-in {
        opacity: 1;
        transform: translateY(0);
    }
    .delay-100 { transition-delay: 0.1s; }
    .delay-200 { transition-delay: 0.2s; }
    .delay-300 { transition-delay: 0.3s; }
    .delay-400 { transition-delay: 0.4s; }

    /* Adjust main content margin for smaller screens */
    @media (max-width: 1024px) {
        .main-content {
            margin-left: 0;
        }
    }

    /* Footer specific styles */
    #main-footer {
        width: 100%;
        margin-left: 0 !important;
    }

    /* Search bar styles */
    .search-container {
        position: relative;
    }
    
    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }
    
    .search-input {
        padding-left: 2.5rem;
    }

    .employee-card {
        transition: all 0.3s ease;
    }
    
    .employee-card.filtered-out {
        display: none;
    }

    .no-results {
        grid-column: 1 / -1;
        text-align: center;
        padding: 2rem;
        color: #6b7280;
    }

    /* Custom scrollbar for modal lists */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* New styles for forms and action buttons */
    .form-group {
        margin-bottom: 1rem;
        text-align: left;
    }
    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 0.5rem;
        color: #374151;
    }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #374151;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none;
        border-color: #f08331;
        box-shadow: 0 0 0 3px rgba(240, 131, 49, 0.3);
    }
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    .btn-primary {
        background-color: #f08331;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 0.375rem;
        transition: background-color 0.2s ease-in-out;
    }
    .btn-primary:hover {
        background-color: #e76e37;
    }
    .btn-secondary {
        background-color: #6b7280;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 0.375rem;
        transition: background-color 0.2s ease-in-out;
    }
    .btn-secondary:hover {
        background-color: #4b5563;
    }
    .btn-danger {
        background-color: #ef4444;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        transition: background-color 0.2s ease-in-out;
    }
    .btn-danger:hover {
        background-color: #dc2626;
    }
    .action-icon {
        cursor: pointer;
        margin-left: 0.5rem;
        color: #6b7280;
        transition: color 0.2s;
    }
    .action-icon:hover {
        color: #f08331;
    }

    /* MODAL POSITION ADJUSTMENT */
    #attendanceModal .bg-white,
    #addEditRecordModal .bg-white {
        transform: translateY(-10%); /* Adjust this value to move the modal up or down */
    }
</style>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="content-wrapper">
        <?php
        // Include the sidebar component.
        // Ensure 'Sidebar.php' exists in the same directory or provide the correct path.
        include 'Sidebar.php';
        ?>

        <div id="main-content" class="main-content page-entrance">
            <?php if ($phpError): ?>
            <div style='background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 20px; text-align: center; max-width: 700px; margin: 20px auto; border-radius: 5px;'>
                <strong>Error:</strong> <?php echo htmlspecialchars($phpError); ?>
                <br>Please ensure your database is running, credentials are correct, and tables (employees, attendance, leave_requests) exist and are populated.
            </div>
            <?php endif; ?>

            <div class="max-w-7xl mx-auto p-4">
                <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center directory-element delay-100">Employee Attendance Overview</h1>

                <div class="mb-8 max-w-md mx-auto directory-element delay-200">
                    <div class="search-container">
                        <svg class="search-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input 
                            type="text" 
                            id="searchInput" 
                            placeholder="Search employees by name or ID..." 
                            class="search-input w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-colors duration-200"
                            autocomplete="off"
                        >
                    </div>
                    
                    <div class="mt-2 text-center">
                        <span id="searchResults" class="text-sm text-gray-600"></span>
                    </div>
                </div>

                <div class="mb-4 text-center directory-element delay-300">
                    <button 
                        id="clearSearchBtn" 
                        class="hidden bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm transition-colors duration-200"
                        onclick="clearSearch()"
                    >
                        Clear Search
                    </button>
                    <button
                        id="addRecordBtn"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition-colors duration-200 ml-2"
                        onclick="openAddRecordModal()"
                    >
                        Add New Record
                    </button>
                </div>

                <div id="employeeGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-center directory-element delay-400">
                    <?php if (!empty($attendanceData)): ?>
                        <?php foreach ($attendanceData as $employee): ?>
                            <?php
                            // Determine latest attendance status and apply appropriate CSS class
                            $latestAttendanceStatus = getLatestAttendanceStatus($employee);
                            $attendanceClass = '';
                            if ($latestAttendanceStatus === 'Present') {
                                $attendanceClass = 'text-green-500';
                            } elseif ($latestAttendanceStatus === 'Absent') {
                                $attendanceClass = 'text-red-500';
                            } else {
                                $attendanceClass = 'text-gray-500'; // For 'N/A' or other statuses
                            }

                            // Determine latest leave request status and apply appropriate CSS class
                            $latestLeaveRequestStatus = getLatestLeaveRequestStatus($employee);
                            $leaveClass = '';
                            switch ($latestLeaveRequestStatus) {
                                case 'Approved':
                                    $leaveClass = 'text-green-500';
                                    break;
                                case 'Pending':
                                    $leaveClass = 'text-yellow-500';
                                    break;
                                case 'Denied':
                                    $leaveClass = 'text-red-500';
                                    break;
                                default:
                                    $leaveClass = 'text-gray-500';
                                    break;
                            }
                            ?>
                            <div
                                class="employee-card bg-white shadow-md rounded-lg p-4 cursor-pointer hover:shadow-lg transition-all"
                                data-employee='<?php echo htmlspecialchars(json_encode($employee), ENT_QUOTES, 'UTF-8'); ?>'
                                data-search-name="<?php echo strtolower(htmlspecialchars($employee['name'])); ?>"
                                data-search-id="<?php echo htmlspecialchars($employee['employee_id']); ?>"
                                onclick="openModal(this.dataset.employee)"
                            >
                                <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($employee['name']); ?></h3>
                                <p class="text-gray-600">Employee ID: <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                                <p class="mt-2">
                                    Latest Attendance:
                                    <span class="<?php echo $attendanceClass; ?> font-bold">
                                        <?php if ($latestAttendanceStatus === 'Present'): ?>
                                            &#x2713; Present <?php elseif ($latestAttendanceStatus === 'Absent'): ?>
                                            &#x2717; Absent <?php else: ?>
                                            <?php echo htmlspecialchars($latestAttendanceStatus); ?>
                                        <?php endif; ?>
                                    </span>
                                </p>
                                <p class="mt-2">Latest Leave Request:
                                    <span class="<?php echo $leaveClass; ?> font-bold">
                                        <?php echo htmlspecialchars($latestLeaveRequestStatus); ?>
                                    </span>
                                </p>
                            </div>
                        <?php endforeach; ?>
                        
                        <div id="noResultsMessage" class="no-results hidden">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 20a7.962 7.962 0 01-6-2.709M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-1">No employees found</h3>
                                <p class="text-gray-500">Try adjusting your search terms or clear the search to see all employees.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="col-span-full text-center text-gray-600">
                            <?php if (!$phpError): ?>
                                No employee attendance data available.
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div
                    id="attendanceModal"
                    class="fixed inset-0 bg-black/30 backdrop-blur-sm flex justify-center items-center z-50 transition-all duration-300 hidden"
                >
                    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg mx-4 relative text-center">
                        <button
                            onclick="closeModal()"
                            class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl font-bold"
                        >&times;</button>

                        <div class="flex flex-col items-center mb-4">
                            <img id="modal-employee-photo" src="" alt="Employee Photo" class="w-20 h-20 rounded-full object-cover mb-2 hidden" />
                            <span id="modal-fallback-avatar" class="inline-block w-20 h-20 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center mb-2 text-3xl font-bold hidden"></span>
                            <h2 id="modal-employee-name" class="text-xl font-semibold text-center"></h2>
                        </div>

                        <div class="mb-4 text-center max-h-60 overflow-y-auto custom-scrollbar">
                            <h3 class="font-bold mb-2 text-lg text-gray-700 border-b pb-2">Attendance Records:</h3>
                            <ul id="modal-attendance-list" class="space-y-2"></ul>
                        </div>

                        <div class="text-center max-h-60 overflow-y-auto custom-scrollbar">
                            <h3 class="font-bold mb-2 text-lg text-gray-700 border-b pb-2">Leave Requests:</h3>
                            <ul id="modal-leave-requests-list" class="space-y-2"></ul>
                        </div>

                        <button onclick="closeModal()" class="text-white px-4 py-2 rounded-lg mt-4 w-full" id="btn">
                            Close
                        </button>
                    </div>
                </div>

                <div
                    id="addEditRecordModal"
                    class="fixed inset-0 bg-black/30 backdrop-blur-sm flex justify-center items-center z-50 transition-all duration-300 hidden"
                >
                    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg mx-4 relative text-center">
                        <button
                            onclick="closeAddEditRecordModal()"
                            class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl font-bold"
                        >&times;</button>
                        <h2 id="addEditModalTitle" class="text-2xl font-bold text-gray-800 mb-6">Add New Record</h2>

                        <form id="recordForm" method="POST" action="AttendanceView.php" class="text-left">
                            <input type="hidden" name="action" id="formAction">
                            <input type="hidden" name="id" id="recordId">

                            <div class="form-group">
                                <label for="employeeSelect">Employee:</label>
                                <select id="employeeSelect" name="employee_id" required class="block">
                                    <option value="">-- Select Employee --</option>
                                    <?php
                                    // Populate employee dropdown from the fetched $employees data
                                    if (!empty($employees)) {
                                        foreach ($employees as $emp) {
                                            echo '<option value="' . htmlspecialchars($emp['employee_id']) . '">' . htmlspecialchars($emp['name']) . ' (ID: ' . htmlspecialchars($emp['employee_id']) . ')</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="recordType">Record Type:</label>
                                <select id="recordType" name="record_type" required onchange="toggleRecordTypeFields()" class="block">
                                    <option value="attendance">Attendance</option>
                                    <option value="leave">Leave Request</option>
                                </select>
                            </div>

                            <div id="attendanceFields">
                                <div class="form-group">
                                    <label for="attendanceDate">Date:</label>
                                    <input type="date" id="attendanceDate" name="date" required>
                                </div>
                                <div class="form-group">
                                    <label for="attendanceStatus">Status:</label>
                                    <select id="attendanceStatus" name="attendance_status" required> <option value="Present">Present</option>
                                        <option value="Absent">Absent</option>
                                    </select>
                                </div>
                            </div>

                            <div id="leaveFields" class="hidden">
                                <div class="form-group">
                                    <label for="leaveStartDate">Start Date:</label>
                                    <input type="date" id="leaveStartDate" name="start_date">
                                </div>
                                <div class="form-group">
                                    <label for="leaveEndDate">End Date (optional):</label>
                                    <input type="date" id="leaveEndDate" name="end_date">
                                </div>
                                <div class="form-group">
                                    <label for="leaveReason">Reason:</label>
                                    <textarea id="leaveReason" name="reason" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="leaveRequestStatus">Status:</label>
                                    <select id="leaveRequestStatus" name="leave_status"> <option value="Pending">Pending</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Denied">Denied</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="submitRecordBtn">Add Record</button>
                                <button type="button" onclick="closeAddEditRecordModal()" class="btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php
    include 'footer.php';
    ?>

    <script>
        // PHP generated JSON data for JavaScript use
        const attendanceData = <?php echo $jsonAttendanceData; ?>;

        // Get DOM elements for modal and search
        const attendanceModal = document.getElementById('attendanceModal');
        const modalEmployeePhoto = document.getElementById('modal-employee-photo');
        const modalFallbackAvatar = document.getElementById('modal-fallback-avatar');
        const modalEmployeeName = document.getElementById('modal-employee-name');
        const modalAttendanceList = document.getElementById('modal-attendance-list');
        const modalLeaveRequestsList = document.getElementById('modal-leave-requests-list');

        // New elements for Add/Edit Modal
        const addEditRecordModal = document.getElementById('addEditRecordModal');
        const addEditModalTitle = document.getElementById('addEditModalTitle');
        const recordForm = document.getElementById('recordForm');
        const formAction = document.getElementById('formAction'); 
        const recordId = document.getElementById('recordId');
        const employeeSelect = document.getElementById('employeeSelect');
        const recordType = document.getElementById('recordType');
        const attendanceFields = document.getElementById('attendanceFields');
        const leaveFields = document.getElementById('leaveFields');
        const attendanceDate = document.getElementById('attendanceDate');
        const attendanceStatus = document.getElementById('attendanceStatus');
        const leaveStartDate = document.getElementById('leaveStartDate');
        const leaveEndDate = document.getElementById('leaveEndDate');
        const leaveReason = document.getElementById('leaveReason');
        const leaveRequestStatus = document.getElementById('leaveRequestStatus');
        const submitRecordBtn = document.getElementById('submitRecordBtn');

        const actionUrl = 'AttendanceView.php';


        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const searchResults = document.getElementById('searchResults');
        const noResultsMessage = document.getElementById('noResultsMessage');
        const employeeCards = document.querySelectorAll('.employee-card');

        // PAGE ENTRANCE ANIMATION FUNCTION
        function initPageEntrance() {
            const mainContent = document.getElementById('main-content');
            const sidebar = document.getElementById('sidebar'); 
            setTimeout(() => {
                if (sidebar) {
                    sidebar.classList.add('sidebar-entrance-complete');
                }
                mainContent.classList.add('page-entrance-complete');
            }, 100);
            
            setTimeout(() => {
                const directoryElements = document.querySelectorAll('.directory-element');
                directoryElements.forEach(element => {
                    element.classList.add('animate-in');
                });
            }, 300);
        }


        /**
         * Performs the search filtering on employee cards based on input.
         */
        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            employeeCards.forEach(card => {
                const name = card.getAttribute('data-search-name');
                const id = card.getAttribute('data-search-id');
                
                const matchesSearch = name.includes(searchTerm) || id.includes(searchTerm);
                
                if (matchesSearch) {
                    card.classList.remove('filtered-out');
                    card.classList.add('fade-in');
                    visibleCount++;
                } else {
                    card.classList.add('filtered-out');
                    card.classList.remove('fade-in');
                }
            });

            // Update search results counter and visibility of "no results" message and "clear search" button
            if (searchTerm === '') {
                searchResults.textContent = '';
                clearSearchBtn.classList.add('hidden');
                noResultsMessage.classList.add('hidden');
                // Show all cards when search is cleared
                employeeCards.forEach(card => {
                    card.classList.remove('filtered-out', 'fade-in');
                });
            } else {
                if (visibleCount === 0) {
                    searchResults.textContent = 'No employees found';
                    noResultsMessage.classList.remove('hidden');
                } else {
                    searchResults.textContent = `${visibleCount} employee${visibleCount !== 1 ? 's' : ''} found`;
                    noResultsMessage.classList.add('hidden');
                }
                clearSearchBtn.classList.remove('hidden');
            }
        }

        /**
         * Clears the search input and resets the employee cards display.
         */
        function clearSearch() {
            searchInput.value = '';
            performSearch();
            searchInput.focus();
        }

        // Event listener for search input
        searchInput.addEventListener('input', performSearch);
        // Event listener for Escape key to clear search
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearSearch();
            }
        });

        /**
         * Opens the attendance detail modal and populates it with employee data.
         * @param {string} employeeJson - JSON string of the employee data.
         */
        function openModal(employeeJson) {
            const employee = JSON.parse(employeeJson);
            modalEmployeeName.textContent = employee.name;

            // Handle employee photo display or fallback avatar
            const photoPath = employee.photo; 
            if (photoPath) {
                modalEmployeePhoto.src = photoPath;
                modalEmployeePhoto.classList.remove('hidden');
                modalFallbackAvatar.classList.add('hidden');
            } else {
                modalEmployeePhoto.classList.add('hidden');
                modalFallbackAvatar.classList.remove('hidden');
                modalFallbackAvatar.textContent = employee.name.charAt(0);
            }

            // Populate Attendance Records list in the modal (with Edit/Delete buttons)
            modalAttendanceList.innerHTML = ''; // Clear previous entries
            if (employee.attendance && employee.attendance.length > 0) {
                employee.attendance.forEach(attendance => {
                    const listItem = document.createElement('li');
                    listItem.classList.add('p-2', 'border-b', 'border-gray-200', 'last:border-b-0', 'flex', 'flex-col', 'items-center', 'sm:flex-row', 'sm:justify-between', 'sm:items-center'); 
                    const statusClass = attendance.status === 'Present' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                    const statusIcon = attendance.status === 'Present' ? '&#x2713;' : '&#x2717;'; // Checkmark or X
                    listItem.innerHTML = `
                        <div class="flex items-center mb-1 sm:mb-0">
                            <span>${attendance.date}:</span>
                            <span class="ml-2 ${statusClass}">${statusIcon} ${attendance.status}</span>
                        </div>
                        <div class="flex items-center gap-2 mt-2 sm:mt-0">
                            <button
                                class="inline-flex items-center px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded transition-colors duration-150 text-xs font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                title="Edit Attendance"
                                onclick="event.stopPropagation(); openEditAttendance(${attendance.id}, ${employee.employee_id}, '${attendance.date}', '${attendance.status}')"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 3.5a2.121 2.121 0 113 3L7 19.5 3 21l1.5-4L16.5 3.5z" />
                                </svg>
                                Edit
                            </button>
                            <button
                                class="inline-flex items-center px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded transition-colors duration-150 text-xs font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                                title="Delete Attendance"
                                onclick="event.stopPropagation(); deleteRecord('attendance', ${attendance.id})"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Delete
                            </button>
                        </div>
                    `;
                    modalAttendanceList.appendChild(listItem);
                });
            } else {
                modalAttendanceList.innerHTML = '<li class="text-gray-500 py-2 text-center">No attendance records found.</li>';
            }

            // Populate Leave Requests list in the modal (with Edit/Delete buttons)
            modalLeaveRequestsList.innerHTML = ''; // Clear previous entries
            if (employee.leaveRequests && employee.leaveRequests.length > 0) {
                employee.leaveRequests.forEach(leave => {
                    const listItem = document.createElement('li');
                    listItem.classList.add('p-2', 'border-b', 'border-gray-200', 'last:border-b-0', 'flex', 'flex-col', 'items-center', 'sm:flex-row', 'sm:justify-between', 'sm:items-center');
                    let statusClass = '';
                    switch (leave.status) {
                        case 'Approved': statusClass = 'text-green-600'; break;
                        case 'Pending':  statusClass = 'text-yellow-600'; break;
                        case 'Denied':   statusClass = 'text-red-600'; break;
                        default:         statusClass = 'text-gray-500'; break;
                    }

                    // Format the date range for display
                    let dateRange = '';
                    if (leave.start_date === leave.end_date || !leave.end_date) { // Handle single day leave
                        dateRange = `Date: ${leave.start_date}`;
                    } else {
                        dateRange = `From: ${leave.start_date} To: ${leave.end_date}`;
                    }

                    const escapedReason = leave.reason.replace(/'/g, "\\'");

                    listItem.innerHTML = `
                        <div class="flex flex-col items-center mb-1 sm:mb-0 sm:items-start">
                            <span class="font-semibold">${dateRange}</span>
                            <span class="${statusClass} font-bold">${leave.status}</span>
                            <p class="text-sm text-gray-700 text-center sm:text-left">${leave.reason}</p>
                        </div>
                        <div class="flex items-center gap-2 mt-2 sm:mt-0">
                            <button
                                class="inline-flex items-center px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded transition-colors duration-150 text-xs font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                                title="Edit Leave Request"
                                onclick="event.stopPropagation(); openEditLeaveRequest(${leave.id}, ${employee.employee_id}, '${leave.start_date}', '${leave.end_date || ''}', '${escapedReason}', '${leave.status}')"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 3.5a2.121 2.121 0 113 3L7 19.5 3 21l1.5-4L16.5 3.5z" />
                                </svg>
                                Edit
                            </button>
                            <button
                                class="inline-flex items-center px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded transition-colors duration-150 text-xs font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                                title="Delete Leave Request"
                                onclick="event.stopPropagation(); deleteRecord('leave', ${leave.id})"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Delete
                            </button>
                        </div>
                    `;
                    modalLeaveRequestsList.appendChild(listItem);
                });
            } else {
                modalLeaveRequestsList.innerHTML = '<li class="text-gray-500 py-2 text-center">No leave requests found.</li>';
            }

            // Show modal with transition
            attendanceModal.classList.remove('hidden');
            setTimeout(() => {
                attendanceModal.classList.add('modal-zoom-fade-enter-active');
                attendanceModal.querySelector('.bg-white').classList.add('modal-zoom-fade-enter-to');
            }, 10);
        }

        /**
         * Closes the attendance detail modal with a transition.
         */
        function closeModal() {
            // Start modal exit transition
            attendanceModal.classList.remove('modal-zoom-fade-enter-active');
            attendanceModal.querySelector('.bg-white').classList.remove('modal-zoom-fade-enter-to');
            attendanceModal.classList.add('modal-zoom-fade-leave-active');
            attendanceModal.querySelector('.bg-white').classList.add('modal-zoom-fade-leave-to');

            setTimeout(() => {
                attendanceModal.classList.add('hidden');
                attendanceModal.classList.remove('modal-zoom-fade-leave-active');
                attendanceModal.querySelector('.bg-white').classList.remove('modal-zoom-fade-leave-to');
            }, 300);
        }

        // Close modal if clicked outside of the modal content
        attendanceModal.addEventListener('click', function(event) {
            if (event.target === attendanceModal) {
                closeModal();
            }
        });

        // Close modal on Escape key press
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !attendanceModal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // NEW: Add/Edit Record Modal Functions
        /**
         * Opens the Add/Edit Record modal for adding a new record.
         * Resets the form and sets default values.
         */
        function openAddRecordModal() {
            addEditModalTitle.textContent = "Add New Record";
            formAction.value = 'add_attendance';
            recordId.value = ''; // Clear record ID for new record
            
            // Reset the form first to clear any previous values
            recordForm.reset(); 
            
            employeeSelect.disabled = false;
            recordType.value = 'attendance';
            
            toggleRecordTypeFields(); 

            // Set default date to today for attendance, AFTER toggling fields
            const today = new Date().toISOString().split('T')[0];
            attendanceDate.value = today;
            leaveStartDate.value = today; // Also set for leave in case user switches
            
            // Ensure leave-specific fields are cleared when defaulting to attendance
            leaveEndDate.value = ''; 
            leaveReason.value = ''; 
            leaveRequestStatus.value = 'Pending'; 

            addEditRecordModal.classList.remove('hidden');
            setTimeout(() => {
                addEditRecordModal.classList.add('modal-zoom-fade-enter-active');
                addEditRecordModal.querySelector('.bg-white').classList.add('modal-zoom-fade-enter-to');
            }, 10);
        }

        /**
         * Closes the Add/Edit Record modal with a transition.
         */
        function closeAddEditRecordModal() {
            addEditRecordModal.classList.remove('modal-zoom-fade-enter-active');
            addEditRecordModal.querySelector('.bg-white').classList.remove('modal-zoom-fade-enter-to');
            addEditRecordModal.classList.add('modal-zoom-fade-leave-active');
            addEditRecordModal.querySelector('.bg-white').classList.add('modal-zoom-fade-leave-to');

            setTimeout(() => {
                addEditRecordModal.classList.add('hidden');
                addEditRecordModal.classList.remove('modal-zoom-fade-leave-active');
                addEditRecordModal.querySelector('.bg-white').classList.remove('modal-zoom-fade-leave-to');
            }, 300);
        }

        // Close Add/Edit modal if clicked outside of the modal content
        addEditRecordModal.addEventListener('click', function(event) {
            if (event.target === addEditRecordModal) {
                closeAddEditRecordModal();
            }
        });

        // Close Add/Edit modal on Escape key press
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !addEditRecordModal.classList.contains('hidden')) {
                closeAddEditRecordModal();
            }
        });

        /**
         * Toggles the visibility of attendance-specific or leave-specific fields
         * in the Add/Edit Record modal based on the selected record type.
         */
        function toggleRecordTypeFields() {
            if (recordType.value === 'attendance') {
                attendanceFields.classList.remove('hidden');
                leaveFields.classList.add('hidden');
                // Set required attributes for attendance fields
                attendanceDate.required = true;
                attendanceStatus.required = true;
                leaveStartDate.required = false;
                leaveReason.required = false;
                leaveRequestStatus.required = false;
                // Clear leave-specific fields when switching to attendance view
                leaveReason.value = ''; 
                leaveRequestStatus.value = 'Pending'; 
                leaveEndDate.value = '';
            } else { // 'leave'
                attendanceFields.classList.add('hidden');
                leaveFields.classList.remove('hidden');
                // Set required attributes for leave fields
                attendanceDate.required = false;
                attendanceStatus.required = false;
                leaveStartDate.required = true;
                leaveReason.required = true;
                leaveRequestStatus.required = true;
                // Clear attendance-specific fields when switching to leave view
                attendanceDate.value = '';
                attendanceStatus.value = 'Present';
            }
        }

        /**
         * Opens the Add/Edit Record modal for editing an existing attendance record.
         * @param {number} id - The ID of the attendance record.
         * @param {number} employee_id - The ID of the employee.
         * @param {string} date - The date of the attendance.
         * @param {string} status - The status of the attendance ('Present' or 'Absent').
         */
        function openEditAttendance(id, employee_id, date, status) {
            closeModal(); // Close the attendance view modal first

            addEditModalTitle.textContent = "Edit Attendance Record";
            formAction.value = 'update_attendance'; // Set action to update
            recordId.value = id; // Set the record ID to be updated

            // Pre-fill fields
            employeeSelect.value = employee_id;
            employeeSelect.disabled = true;
            recordType.value = 'attendance';
            toggleRecordTypeFields(); // Show attendance fields

            attendanceDate.value = date;
            attendanceStatus.value = status; // Set value using ID

            addEditRecordModal.classList.remove('hidden');
            setTimeout(() => {
                addEditRecordModal.classList.add('modal-zoom-fade-enter-active');
                addEditRecordModal.querySelector('.bg-white').classList.add('modal-zoom-fade-enter-to');
            }, 10);
        }

        /**
         * Opens the Add/Edit Record modal for editing an existing leave request.
         * @param {number} id - The ID of the leave request.
         * @param {number} employee_id - The ID of the employee.
         * @param {string} start_date - The start date of the leave.
         * @param {string} end_date - The end date of the leave.
         * @param {string} reason - The reason for the leave.
         * @param {string} status - The status of the leave request.
         */
        function openEditLeaveRequest(id, employee_id, start_date, end_date, reason, status) {
            closeModal(); // Close the attendance view modal first

            addEditModalTitle.textContent = "Edit Leave Request";
            formAction.value = 'update_leave_request'; // Set action to update
            recordId.value = id; // Set the record ID to be updated

            // Pre-fill fields
            employeeSelect.value = employee_id;
            employeeSelect.disabled = true; // Don't allow changing employee for existing record
            recordType.value = 'leave';
            toggleRecordTypeFields(); // Show leave fields

            leaveStartDate.value = start_date;
            leaveEndDate.value = end_date;
            leaveReason.value = reason;
            leaveRequestStatus.value = status; // Set value using ID

            addEditRecordModal.classList.remove('hidden');
            setTimeout(() => {
                addEditRecordModal.classList.add('modal-zoom-fade-enter-active');
                addEditRecordModal.querySelector('.bg-white').classList.add('modal-zoom-fade-enter-to');
            }, 10);
        }


        // Handle form submission via AJAX for Add/Update operations
        recordForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            const currentRecordType = recordType.value; 
            const cleanFormData = new FormData();

            // Client-side validation before sending
            if (!employeeSelect.value) {
                alert('Please select an Employee.');
                return;
            }

            cleanFormData.append('action', formAction.value);
            if (recordId.value) { // Only include ID if it's an update
                cleanFormData.append('id', recordId.value);
            }
            cleanFormData.append('employee_id', employeeSelect.value);

            if (currentRecordType === 'attendance') {
                if (!attendanceDate.value) {
                    alert('Please select a Date for attendance.');
                    return;
                }
                if (!attendanceStatus.value || !['Present', 'Absent'].includes(attendanceStatus.value)) {
                    alert('Please select a valid Status (Present/Absent) for attendance.');
                    return;
                }
                cleanFormData.append('date', attendanceDate.value);
                cleanFormData.append('attendance_status', attendanceStatus.value);
            } else if (currentRecordType === 'leave') {
                if (!leaveStartDate.value) {
                    alert('Please select a Start Date for leave.');
                    return;
                }
                if (!leaveReason.value.trim()) {
                    alert('Please provide a Reason for leave.');
                    return;
                }
                if (!leaveRequestStatus.value || !['Pending', 'Approved', 'Denied'].includes(leaveRequestStatus.value)) {
                    alert('Please select a valid Status (Pending/Approved/Denied) for leave.');
                    return;
                }
                cleanFormData.append('start_date', leaveStartDate.value);
                // Only append end_date if it has a value
                if (leaveEndDate.value) {
                    cleanFormData.append('end_date', leaveEndDate.value);
                } else {
                    cleanFormData.append('end_date', '');
                }
                cleanFormData.append('reason', leaveReason.value);
                cleanFormData.append('leave_status', leaveRequestStatus.value);
            }

            //DEBUGGING LOGS (Client-side)
            console.log('--- Submitting Form Data (Client-side) ---');
            for (let pair of cleanFormData.entries()) { // Use cleanFormData here
                console.log(pair[0] + ': ' + pair[1]); 
            }
            console.log('--------------------------');

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: cleanFormData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    closeAddEditRecordModal();
                    location.reload(); 
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Submission error:', error);
                alert('An error occurred during submission. Please check the console for details.');
            }
        });


        // NEW: Delete Function via AJAX
        /**
         * Deletes an attendance or leave record via AJAX.
         * @param {string} type - The type of record to delete ('attendance' or 'leave').
         * @param {number} id - The ID of the record to delete.
         */
        async function deleteRecord(type, id) {
            if (!confirm(`Are you sure you want to delete this ${type} record? This action cannot be undone.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('id', id);

            let action;
            if (type === 'attendance') {
                action = 'delete_attendance';
            } else if (type === 'leave') {
                action = 'delete_leave_request';
            } else {
                alert('Invalid record type for deletion.');
                return;
            }
            formData.append('action', action);

            const actionUrl = 'AttendanceView.php';

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    closeModal(); // Close the modal after deletion
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert('Error deleting record: ' + result.message);
                }
            } catch (error) {
                console.error('Deletion error:', error);
                alert('An error occurred during deletion. Please check the console.');
            }
        }

        function applyMainContentMargin() {
            const sidebarCollapsed = document.cookie.includes('sidebarCollapsed=true');
            const mainContent = document.getElementById('main-content');
            const footer = document.getElementById('main-footer');
            const footerTextRow = document.getElementById('f-text');

            if (window.innerWidth <= 1024) {
                mainContent.style.marginLeft = '0';
                if (footer) footer.style.paddingLeft = '0';
                if (footerTextRow) footerTextRow.style.marginLeft = '0';
            } else if (sidebarCollapsed) {
                mainContent.style.marginLeft = '5rem';
                if (footer) footer.style.paddingLeft = '5rem';
                if (footerTextRow) footerTextRow.style.marginLeft = '6rem';
            } else {
                mainContent.style.marginLeft = '16rem';
                if (footer) footer.style.paddingLeft = '16rem';
                if (footerTextRow) footerTextRow.style.marginLeft = '0';
            }
        }

        window.addEventListener('resize', applyMainContentMargin);
        document.addEventListener('DOMContentLoaded', function() {
            applyMainContentMargin();
            initPageEntrance(); // Trigger page entrance animation

            //Page Exit Animation
            document.body.addEventListener('click', function(event) {
                const link = event.target.closest('a');
                
                if (link && link.href && link.target !== '_blank' && !link.href.startsWith(window.location.href + '#')) {
                    event.preventDefault();
                    const destination = link.href;

                    // Apply the exit animation class
                    document.body.classList.add('page-exit');

                    // Wait for animation to complete, then navigate
                    setTimeout(() => {
                        window.location.href = destination;
                    }, 500); // Duration must match the CSS transition time
                }
            });
        });

    </script>
</body>
</html>
<?php
// Clean up output buffer at the very end of the script
if (ob_get_level()) {
    ob_end_flush();
}
?>