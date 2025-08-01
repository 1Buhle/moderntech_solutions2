<?php
// Payroll.View.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: LoginView.php');
    exit;
}

$pageTitle = 'Payroll';

require_once 'config.php';

$conn = getDBConnection();

$phpError = null;

// --- Fetch Employee Information ---
$employeeInformation = [];
$sqlEmployees = "SELECT employee_id, name, position, department, salary, employment_history FROM employees";
$resultEmployees = $conn->query($sqlEmployees);
if ($resultEmployees) {
    while ($row = $resultEmployees->fetch_assoc()) {
        $employeeInformation[] = [
            'employeeId' => $row['employee_id'],
            'name' => $row['name'],
            'position' => $row['position'],
            'department' => $row['department'],
            'salary' => (float)$row['salary'],
            'employmentHistory' => $row['employment_history'],
            'userId' => 'EMP' . str_pad($row['employee_id'], 3, '0', STR_PAD_LEFT)
        ];
    }
    $resultEmployees->free();
} else {
    $phpError = "Error fetching employee data: " . $conn->error;
}

$employeesById = [];
foreach ($employeeInformation as $emp) {
    $employeesById[$emp['employeeId']] = $emp;
}

// Define the pay period for this payroll page
$payPeriodStart = '2025-06-01';
$payPeriodEnd = '2025-06-30';
$payPeriodDisplay = 'June 2025';

$aggregatedPayrollData = [];

foreach ($employeeInformation as $emp) {
    $aggregatedPayrollData[$emp['employeeId']] = [
        'employeeId' => $emp['employeeId'],
        'hoursWorked' => 0,
        'leaveDeductions' => 0,
    ];
}

// Fetch attendance data for the period
$sqlAttendance = "SELECT employee_id, status, date FROM attendance WHERE date BETWEEN ? AND ?";
$stmtAttendance = $conn->prepare($sqlAttendance);
if ($stmtAttendance) {
    $stmtAttendance->bind_param("ss", $payPeriodStart, $payPeriodEnd);
    $stmtAttendance->execute();
    $resultAttendance = $stmtAttendance->get_result();
    while ($row = $resultAttendance->fetch_assoc()) {
        if (isset($aggregatedPayrollData[$row['employee_id']]) && trim(strtolower($row['status'])) === 'present') {
            $aggregatedPayrollData[$row['employee_id']]['hoursWorked'] += 8;
        }
    }
    $resultAttendance->free();
    $stmtAttendance->close();
} else {
    if (!$phpError) {
        $phpError = "Error preparing attendance data query: " . $conn->error;
    }
}

// Fetch approved leave requests for the period
$sqlLeaveRequests = "SELECT employee_id, start_date, status FROM leave_requests WHERE start_date BETWEEN ? AND ? AND TRIM(LOWER(status)) = 'approved'";
$stmtLeaveRequests = $conn->prepare($sqlLeaveRequests);
if ($stmtLeaveRequests) {
    $stmtLeaveRequests->bind_param("ss", $payPeriodStart, $payPeriodEnd);
    $stmtLeaveRequests->execute();
    $resultLeaveRequests = $stmtLeaveRequests->get_result();
    while ($row = $resultLeaveRequests->fetch_assoc()) {
        if (isset($aggregatedPayrollData[$row['employee_id']])) {
            $aggregatedPayrollData[$row['employee_id']]['leaveDeductions']++;
        }
    }
    $resultLeaveRequests->free();
    $stmtLeaveRequests->close();
} else {
    if (!$phpError) {
        $phpError = "Error preparing leave requests query: " . $conn->error;
    }
}

$payrollDataForJs = array_values($aggregatedPayrollData);

$conn->close();

// Default values for initial load
$initialEmployeeInfo = [
    'userId' => '',
    'employeeName' => '',
    'payPeriod' => $payPeriodStart . ' to ' . $payPeriodEnd,
    'leaveDeductions' => 0
];

$initialDays = [];
$startDate = new DateTime($payPeriodStart);
$endDate = new DateTime($payPeriodEnd);
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($startDate, $interval, $endDate);
$daysOfWeekOrder = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

foreach ($period as $dt) {
    $dayName = $dt->format('D');
    if (in_array($dayName, $daysOfWeekOrder)) {
        $initialDays[] = [
            'name' => $dayName,
            'date' => $dt->format('Y-m-d'),
            'worked' => false,
            'regular' => 0,
            'rate' => 412.61
        ];
    }
}


// JSON encode data for JavaScript
$jsonEmployeeInformation = json_encode($employeeInformation, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$jsonPayrollData = json_encode($payrollDataForJs, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$jsonInitialDays = json_encode($initialDays, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$jsonPhpError = json_encode($phpError, JSON_UNESCAPED_SLASHES);

// Path to logo
$logoIcon = '../../assets/Logo.png'; 

// User session data
$loggedInUserName = $_SESSION['user_name'] ?? 'Tom Cook';
$loggedInUserProfilePic = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80';

$currentPath = basename(__FILE__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script> 
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
            transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
        }

        .page-exit {
            transform: scale(1.2);
            opacity: 0;
        }

        .active-link {
            color: #39bbc8 !important;
            background-color: #ddfafd !important;
            font-weight: bold !important;
        }

        .material-symbols-outlined {
            font-variation-settings:
                'FILL' 0,
                'wght' 400,
                'GRAD' 0,
                'opsz' 24;
            color: #222 !important;
        }
        .material-symbols-outlined:hover {
            cursor: pointer;
            color: #111;
        }

        .employee-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin:0 auto 20px auto;
            border: 1px solid #dee2e6;
            max-width:fit-content;
        }

        .employee-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }

        .employee-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
        }

        .employee-field label {
            font-size: 12px;
            font-weight: 600;
            color: #6c7883;
            text-transform: uppercase;
        }

        .employee-field select {
            padding: 6px 10px;
            border: 1px solid #419af4;
            border-radius: 4px;
            font-size: 14px;
            min-width: 200px;
            background-color: white;
        }

        .employee-field input {
            padding: 6px 10px;
            border: 1px solid #419af4;
            border-radius: 4px;
            font-size: 14px;
            min-width: 150px;
        }

        .employee-field input[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        input[type="number"] {
            width: 80px;
        }

        /*ENTRANCE ANIMATION STYLES*/
        #sidebar {
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), 
                        width 0.3s ease-in-out;
        }
        .sidebar-entrance {
            transform: translateX(-100%);
        }
        .sidebar-entrance-complete {
            transform: translateX(0);
        }
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
        .delay-500 { transition-delay: 0.5s; }

        /* Media Queries*/
        @media (max-width: 1024px) {
            .employee-info {
                padding: 10px !important;
                max-width: 100% !important;
            }
            .employee-row {
                gap: 12px !important;
            }
            .employee-field input,
            .employee-field select {
                min-width: 120px !important;
                width: 100% !important;
                font-size: 13px !important;
            }
            th, td {
                padding: 8px !important;
                font-size: 13px !important;
            }
        }

        @media (max-width: 768px) {
            .payroll {
                padding: 0.5rem !important;
            }
            .employee-info {
                padding: 8px !important;
                border-radius: 4px !important;
                margin-bottom: 12px !important;
            }
            .employee-row {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
            }
            .employee-field {
                align-items: stretch !important;
                width: 100% !important;
            }
            .employee-field input,
            .employee-field select {
                min-width: 0 !important;
                width: 100% !important;
                font-size: 12px !important;
            }
            h1.text-2xl {
                font-size: 1.2rem !important;
            }
            th, td {
                padding: 6px !important;
                font-size: 12px !important;
            }
            button {
                width: 100% !important;
                padding: 8px 0 !important;
                margin: 12px 0 !important;
            }
        }

        @media (max-width: 480px) {
            .payroll {
                padding: 0.25rem !important;
            }
            .employee-info {
                padding: 4px !important;
                border-radius: 2px !important;
                margin-bottom: 8px !important;
            }
            .employee-row {
                gap: 6px !important;
            }
            .employee-field label {
                font-size: 10px !important;
            }
            .employee-field input,
            .employee-field select {
                font-size: 11px !important;
                padding: 4px 6px !important;
            }
            h1.text-2xl {
                font-size: 1rem !important;
            }
            th, td {
                padding: 4px !important;
                font-size: 11px !important;
            }
            .overflow-x-auto {
                overflow-x: auto !important;
                width: 100% !important;
            }
            table {
                width: 600px !important;
                font-size: 11px !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    <div class="flex flex-1">
        <main id="main-content" class="flex-1 p-8 page-entrance">
            <div class="payroll px-2 py-6 max-w-5xl mx-auto">
                <div class="employee-info flex flex-col gap-3 bg-gray-50 p-4 rounded-md border border-gray-200 mb-8 w-full max-w-3xl mx-auto directory-element delay-100">
                    <div class="employee-row flex flex-wrap gap-6 items-center justify-center">
                        <div class="employee-field flex flex-col gap-1 items-center">
                            <label class="text-xs font-semibold text-gray-500 uppercase">Select Employee</label>
                            <select id="employee-select" class="px-3 py-2 border border-blue-400 rounded w-52 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">-- Select Employee --</option>
                                <?php foreach ($employeeInformation as $emp): ?>
                                    <option value="<?php echo htmlspecialchars($emp['employeeId']); ?>">
                                        <?php echo htmlspecialchars($emp['name']); ?> (ID: <?php echo htmlspecialchars($emp['employeeId']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="employee-field flex flex-col gap-1 items-center">
                            <label class="text-xs font-semibold text-gray-500 uppercase">User ID</label>
                            <input id="user-id" placeholder="Enter User ID" readonly class="px-3 py-2 border border-blue-400 rounded w-40 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300" />
                        </div>
                        <div class="employee-field flex flex-col gap-1 items-center">
                            <label class="text-xs font-semibold text-gray-500 uppercase">Employee Name</label>
                            <input id="employee-name" placeholder="Enter Employee Name" readonly class="px-3 py-2 border border-blue-400 rounded w-40 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300" />
                        </div>
                        <div class="employee-field flex flex-col gap-1 items-center">
                            <label class="text-xs font-semibold text-gray-500 uppercase">Pay Period</label>
                            <input id="pay-period" readonly value="<?php echo htmlspecialchars($initialEmployeeInfo['payPeriod']); ?>" class="px-3 py-2 border border-blue-400 rounded w-40 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300" />
                        </div>
                        <div class="employee-field flex flex-col gap-1 items-center">
                            <label class="text-xs font-semibold text-gray-500 uppercase">Leave Deductions (Days)</label>
                            <input id="leave-deductions" type="number" min="0" step="1" placeholder="0" value="<?php echo htmlspecialchars($initialEmployeeInfo['leaveDeductions']); ?>" class="px-3 py-2 border border-blue-400 rounded w-32 text-sm bg-gray-100 text-gray-500" />
                        </div>
                    </div>
                </div>

            <h1 class="text-2xl font-bold text-center mb-4 directory-element delay-200">MONTHLY PAYROLL</h1>

            <div class="flex justify-center mb-6 directory-element delay-300">
                <button id="download-payslip-btn" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded shadow transition">DOWNLOAD PAYSLIP</button>
            </div>

            <div class="overflow-x-auto directory-element delay-400">
                <table class="w-full border-collapse rounded shadow bg-white text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-3 py-2 border">WORKED</th>
                            <th class="px-3 py-2 border">DAY</th>
                            <th class="px-3 py-2 border">DATE</th>
                            <th class="px-3 py-2 border">REGULAR HOURS</th>
                            <th class="px-3 py-2 border">RATE PER HOUR</th>
                            <th class="px-3 py-2 border">TOTAL SALARY</th>
                        </tr>
                    </thead>
                    <tbody id="payroll-table-body"></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3"></th>
                            <th id="total-regular-hours" class="px-3 py-2 border">0</th>
                            <th class="px-3 py-2 border">Monthly Total</th>
                            <th id="total-salary" class="px-3 py-2 border">0.00</th>
                        </tr>
                        <tr id="leave-deductions-row" class="hidden">
                            <th colspan="5" class="px-3 py-2 border text-right">Leave Deductions (<span id="leave-deduction-days">0</span> days)</th>
                            <th id="leave-deduction-amount" class="px-3 py-2 border text-red-600">-0.00</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="px-3 py-2 border text-right">Net Salary</th>
                            <th id="net-salary" class="px-3 py-2 border text-green-700">0.00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </main>
    </div>

    <?php include 'footer.php'; ?>

<script>
    // Initialize jsPDF
    const { jsPDF } = window.jspdf;

    // PHP-provided data (JSON encoded)
    const employeeInformation = <?php echo $jsonEmployeeInformation; ?>;
    const payrollSummaryData = <?php echo $jsonPayrollData; ?>;
    const initialDaysStructure = <?php echo $jsonInitialDays; ?>;
    const phpError = <?php echo $jsonPhpError; ?>;

    // Path to logo, assuming relative to current file
    const logoPath = '<?php echo $logoIcon; ?>';

    // DOM elements references
    const employeeSelectEl = document.getElementById('employee-select');
    const userIdInput = document.getElementById('user-id');
    const employeeNameInput = document.getElementById('employee-name');
    const payPeriodInput = document.getElementById('pay-period');
    const leaveDeductionsInput = document.getElementById('leave-deductions');
    const payrollTableBody = document.getElementById('payroll-table-body');
    const totalRegularHoursEl = document.getElementById('total-regular-hours');
    const totalSalaryEl = document.getElementById('total-salary');
    const leaveDeductionsRowEl = document.getElementById('leave-deductions-row');
    const leaveDeductionDaysEl = document.getElementById('leave-deduction-days');
    const leaveDeductionAmountEl = document.getElementById('leave-deduction-amount');
    const netSalaryEl = document.getElementById('net-salary');
    const downloadPayslipBtn = document.getElementById('download-payslip-btn');

    // Reactive-like data in JS (will be updated directly by event handlers)
    let selectedEmployeeId = '';
    let employeeInfo = {
        userId: '',
        employeeName: '',
        payPeriod: '<?php echo $payPeriodStart . ' to ' . $payPeriodEnd; ?>', // Initial PHP provided period
        leaveDeductions: 0
    };
    // Deep copy the initial structure to avoid modifying the original
    let days = JSON.parse(JSON.stringify(initialDaysStructure));

    //Entrance Animation Function
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
    }-

    // Helper function to find employee name by ID
    function getEmployeeName(employeeId) {
        const employee = employeeInformation.find(emp => emp.employeeId == employeeId);
        return employee ? employee.name : `Employee #${employeeId}`;
    }

    // Calculates and updates the totals displayed in the table footer
    function calculateTotals() {
        let currentTotalRegularHours = 0;
        let currentTotalSalary = 0;
        days.forEach(day => {
            if (day.worked) {
                currentTotalRegularHours += day.regular;
                currentTotalSalary += (day.regular * day.rate);
            }
        });
        totalRegularHoursEl.textContent = currentTotalRegularHours;
        totalSalaryEl.textContent = currentTotalSalary.toFixed(2);

        // Calculate daily rate for leave deductions based on a standard 8-hour day
        const averageDailyRate = days.length > 0 && days[0].rate > 0 ? (days[0].rate * 8) : 0;
        const leaveDeductionAmount = (employeeInfo.leaveDeductions || 0) * averageDailyRate;

        leaveDeductionDaysEl.textContent = employeeInfo.leaveDeductions;
        leaveDeductionAmountEl.textContent = `- ${leaveDeductionAmount.toFixed(2)}`;
        leaveDeductionsRowEl.classList.toggle('hidden', employeeInfo.leaveDeductions === 0);

        const netSalary = currentTotalSalary - leaveDeductionAmount;
        netSalaryEl.textContent = netSalary.toFixed(2);
    }

    // Renders the daily payroll table rows
    function renderPayrollTable() {
        payrollTableBody.innerHTML = ''; // Clear existing rows
        days.forEach((day, index) => {
            const row = document.createElement('tr');
            row.classList.add('hover:bg-gray-50');

            // Using innerHTML for simplicity, but for complex/large tables, consider DOM manipulation for performance
            row.innerHTML = `
                <td class="px-3 py-2 border">
                    <input type="checkbox" data-index="${index}" class="form-checkbox h-4 w-4 text-blue-600" ${day.worked ? 'checked' : ''} />
                </td>
                <td class="px-3 py-2 border">${day.name}</td>
                <td class="px-3 py-2 border">${day.date}</td>
                <td class="px-3 py-2 border">
                    <input type="number" data-index="${index}" data-field="regular" min="0" value="${day.regular}"
                        class="w-20 px-2 py-1 border border-blue-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-200" ${!day.worked ? 'disabled' : ''} />
                </td>
                <td class="px-3 py-2 border">
                    <input type="number" data-index="${index}" data-field="rate" min="412.61" max="412.61" value="${day.rate}"
                        class="w-20 px-2 py-1 border border-blue-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-200" ${!day.worked ? 'disabled' : ''} />
                </td>
                <td class="px-3 py-2 border">
                    <span>${day.worked ? ((day.regular * day.rate)).toFixed(2) : '0.00'}</span>
                </td>
            `;
            payrollTableBody.appendChild(row);
        });
        calculateTotals(); // Recalculate totals after rendering
    }

    // Resets the 'days' array to its initial empty state
    function resetDays() {
        days = JSON.parse(JSON.stringify(initialDaysStructure));
        days.forEach(day => {
            day.worked = false;
            day.regular = 0;
        });
    }

    // Populates the 'days' array based on total hours worked and leave deductions
    function populateWorkDays(totalHours, leaveDays) {
        resetDays(); // Start with a clean slate

        const fullDaysWorked = Math.floor(totalHours / 8);
        const remainingHours = totalHours % 8;

        let daysPopulated = 0;
        let currentLeaveDaysCount = 0;

        // Prioritize marking actual working days first (full 8-hour days)
        for (let i = 0; i < days.length && daysPopulated < fullDaysWorked; i++) {
            if (days[i].name !== '') { // Ensure it's a weekday placeholder
                days[i].worked = true;
                days[i].regular = 8;
                daysPopulated++;
            }
        }

        // Handle any remaining hours (partial day)
        if (remainingHours > 0) {
            // Find the next available day to assign remaining hours
            for (let i = 0; i < days.length; i++) {
                if (days[i].name !== '' && !days[i].worked) {
                    days[i].worked = true;
                    days[i].regular = remainingHours;
                    break;
                }
            }
        }

        const currentlyWorkedDaysIndices = days.map((day, index) => ({ day, index }))
                                                .filter(item => item.day.worked && item.day.name !== '');

        // Randomly select days to mark as leave
        while (currentLeaveDaysCount < leaveDays && currentlyWorkedDaysIndices.length > 0) {
            const randomIndex = Math.floor(Math.random() * currentlyWorkedDaysIndices.length);
            const { index: originalDayIndex } = currentlyWorkedDaysIndices[randomIndex];

            if (days[originalDayIndex].worked) {
                days[originalDayIndex].worked = false;
                days[originalDayIndex].regular = 0;
                currentLeaveDaysCount++;
            }
            // Remove from the list to avoid selecting the same day again
            currentlyWorkedDaysIndices.splice(randomIndex, 1);
        }
    }


    // Function to load employee data into the form and table
    function loadEmployeeData() {
        selectedEmployeeId = employeeSelectEl.value;

        if (!selectedEmployeeId) {
            // Reset form if no employee selected
            employeeInfo = {
                userId: '',
                employeeName: '',
                payPeriod: '<?php echo $payPeriodStart . ' to ' . $payPeriodEnd; ?>',
                leaveDeductions: 0
            };
            resetDays();
            renderPayrollTable();
            updateEmployeeInfoInputs();
            return;
        }

        const employeeSummary = payrollSummaryData.find(p => p.employeeId == selectedEmployeeId);
        const employeeDetails = employeeInformation.find(e => e.employeeId == selectedEmployeeId);

        if (employeeSummary && employeeDetails) {
            // Auto-populate employee info fields
            employeeInfo.userId = employeeDetails.userId;
            employeeInfo.employeeName = employeeDetails.name;
            employeeInfo.leaveDeductions = employeeSummary.leaveDeductions;

            // Populate the daily work table based on aggregated hours and leave
            populateWorkDays(employeeSummary.hoursWorked, employeeSummary.leaveDeductions);
        } else {
            // If no payroll data for this employee, reset to defaults but keep name if available
            employeeInfo.userId = employeeDetails ? employeeDetails.userId : '';
            employeeInfo.employeeName = employeeDetails ? employeeDetails.name : '';
            employeeInfo.leaveDeductions = 0;
            resetDays();
        }
        renderPayrollTable(); // Re-render table with new data for the selected employee
        updateEmployeeInfoInputs(); // Update the top input fields
    }

    // Updates the employee information input fields (read-only ones)
    function updateEmployeeInfoInputs() {
        userIdInput.value = employeeInfo.userId;
        employeeNameInput.value = employeeInfo.employeeName;
        payPeriodInput.value = employeeInfo.payPeriod;
        leaveDeductionsInput.value = employeeInfo.leaveDeductions;
    }

    // Function to convert image URL to Base64 and return its natural dimensions
    function getBase64Image(imgSrc) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                resolve({
                    dataUrl: canvas.toDataURL('image/png'),
                    originalWidth: img.naturalWidth,
                    originalHeight: img.naturalHeight
                });
            };
            img.onerror = (e) => {
                console.error("Error loading image for PDF:", imgSrc, e);
                reject(new Error("Failed to load image"));
            };
            img.src = imgSrc;
        });
    }

    // Function to download payslip as PDF
    async function downloadPayslip() {
        const doc = new jsPDF();
        const currentDate = new Date().toLocaleDateString();

        // Find the logo URL for the selected employee
        const selectedEmployee = employeeInformation.find(emp => emp.employeeId == selectedEmployeeId);
        const logoUrl = selectedEmployee ? logoPath : null;

        try {
            let logoDataUrl = null;
            let originalLogoWidth = 0;
            let originalLogoHeight = 0;

            if (logoUrl) {
                try {
                    const logoInfo = await getBase64Image(logoUrl);
                    logoDataUrl = logoInfo.dataUrl;
                    originalLogoWidth = logoInfo.originalWidth;
                    originalLogoHeight = logoInfo.originalHeight;
                } catch (error) {
                    console.warn("Could not load logo image for PDF. Proceeding without logo.", error);
                    logoDataUrl = null;
                }
            }

            // Header
            doc.setFontSize(18);
            let payslipTitleY = 20;
            if (logoDataUrl && originalLogoWidth > 0 && originalLogoHeight > 0) {
                const desiredImgWidth = 40;
                const aspectRatio = originalLogoWidth / originalLogoHeight;
                const calculatedImgHeight = desiredImgWidth / aspectRatio;
                const logoBottomY = 10 + calculatedImgHeight; // Y position of logo bottom edge
                payslipTitleY = logoBottomY + 5; // Add 5 units padding below logo

                const xPos = (doc.internal.pageSize.getWidth() - desiredImgWidth) / 2;
                doc.addImage(logoDataUrl, 'PNG', xPos, 10, desiredImgWidth, calculatedImgHeight);
            }

            doc.text("PAYSLIP", 105, payslipTitleY, null, null, "center");
            doc.setFontSize(10);
            doc.text(`Date Generated: ${currentDate}`, 105, payslipTitleY + 8, null, null, "center");

            let employeeInfoStartY = payslipTitleY + 25;
            doc.setFontSize(12);
            doc.text("Employee Information:", 10, employeeInfoStartY);
            doc.setFontSize(10);
            doc.text(`User ID: ${employeeInfo.userId}`, 10, employeeInfoStartY + 10);
            doc.text(`Employee Name: ${employeeInfo.employeeName}`, 10, employeeInfoStartY + 15);
            doc.text(`Pay Period: ${employeeInfo.payPeriod}`, 10, employeeInfoStartY + 20);

            // Payroll Details Table
            const tableData = days.filter(day => day.worked).map(day => [
                day.name,
                day.date,
                day.regular,
                day.rate.toFixed(2),
                (day.regular * day.rate).toFixed(2)
            ]);

            const headers = [["DAY", "DATE", "REGULAR HOURS", "RATE PER HOUR", "TOTAL SALARY"]];

            doc.autoTable({
                startY: employeeInfoStartY + 30,
                head: headers,
                body: tableData,
                theme: 'grid',
                headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                styles: { fontSize: 9, cellPadding: 2, overflow: 'linebreak' },
                columnStyles: {
                    0: { halign: 'center' },
                    1: { halign: 'center' },
                    2: { halign: 'center' },
                    3: { halign: 'center' },
                    4: { halign: 'center' }
                },
                margin: { top: employeeInfoStartY + 25 }
            });

            // Get the final Y position of the table
            const finalY = doc.autoTable.previous.finalY;

            // Summary Totals
            doc.setFontSize(10);
            doc.text(`Total Regular Hours: ${totalRegularHoursEl.textContent}`, 140, finalY + 10, null, null, "right");
            doc.text(`Monthly Total: ${totalSalaryEl.textContent}`, 190, finalY + 10, null, null, "right");

            if (employeeInfo.leaveDeductions > 0) {
                doc.setTextColor(255, 0, 0); // Red color for deductions
                doc.text(`Leave Deductions (${leaveDeductionDaysEl.textContent} days): ${leaveDeductionAmountEl.textContent}`, 190, finalY + 15, null, null, "right");
                doc.setTextColor(0, 0, 0); // Reset color
            }

            doc.setFontSize(12);
            doc.setTextColor(0, 128, 0); // Green color for net salary
            doc.text(`Net Salary: ${netSalaryEl.textContent}`, 190, finalY + 25, null, null, "right");
            doc.setTextColor(0, 0, 0); // Reset color

            // Save the PDF
            doc.save(`${employeeInfo.employeeName}_Payslip_${payPeriodInput.value.replace(/ /g, '_')}.pdf`);

        } catch (error) {
            console.error("Error generating PDF:", error);
            alert("Failed to generate payslip. Please check the console for details.");
        }
    }

    // --- Event Listeners ---
    employeeSelectEl.addEventListener('change', loadEmployeeData);

    // Event listener for changes in worked checkbox and input fields
    payrollTableBody.addEventListener('change', (event) => {
        const target = event.target;
        const index = target.dataset.index;

        if (target.type === 'checkbox') {
            days[index].worked = target.checked;
            const regularHoursInput = target.closest('tr').querySelector('input[data-field="regular"]');
            const rateInput = target.closest('tr').querySelector('input[data-field="rate"]');

            if (target.checked) {
                days[index].regular = 8; // Default to 8 hours when checked
                regularHoursInput.value = 8;
                regularHoursInput.disabled = false;
                rateInput.disabled = false;
            } else {
                days[index].regular = 0;
                regularHoursInput.value = 0;
                regularHoursInput.disabled = true;
                rateInput.disabled = true;
            }
        } else if (target.tagName === 'INPUT' && (target.dataset.field === 'regular' || target.dataset.field === 'rate')) {
            days[index][target.dataset.field] = parseFloat(target.value) || 0;
        }
        renderPayrollTable();
    });

    // Event listener for leave deductions input
    leaveDeductionsInput.addEventListener('change', (event) => {
        employeeInfo.leaveDeductions = parseInt(event.target.value) || 0;

        const selectedEmployeeSummary = payrollSummaryData.find(p => p.employeeId == selectedEmployeeId);
        if (selectedEmployeeSummary) {
            populateWorkDays(selectedEmployeeSummary.hoursWorked, employeeInfo.leaveDeductions);
        } else {
            populateWorkDays(0, employeeInfo.leaveDeductions);
        }
        renderPayrollTable();
        calculateTotals();
    });

    downloadPayslipBtn.addEventListener('click', downloadPayslip);

    document.addEventListener('DOMContentLoaded', () => {
        initPageEntrance();

        if (phpError) {
            alert("PHP Error: " + phpError);
        }
        loadEmployeeData();

        // Page Exit Animation
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