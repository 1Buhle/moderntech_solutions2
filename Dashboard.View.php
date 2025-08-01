<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header('Location: LoginView.php'); exit; }
require_once 'config.php';
$conn = getDBConnection();
$employeeInformation = [];
$resultEmployees = $conn->query("SELECT employee_id, department, salary, employment_history FROM employees");
if ($resultEmployees) {
    while ($row = $resultEmployees->fetch_assoc()) {
        $employeeInformation[] = [
            'id' => $row['employee_id'],
            'department' => $row['department'],
            'salary' => (float)$row['salary'],
            'employmentHistory' => $row['employment_history']
        ];
    }
    $resultEmployees->free();
}
$totalPresentEmployees = $conn->query("SELECT COUNT(*) AS present_count FROM attendance WHERE TRIM(LOWER(status)) = 'present'")->fetch_assoc()['present_count'] ?? 0;
$totalAbsentEmployees = $conn->query("SELECT COUNT(*) AS absent_count FROM attendance WHERE TRIM(LOWER(status)) = 'absent'")->fetch_assoc()['absent_count'] ?? 0;
$totalDeniedRequests = $conn->query("SELECT COUNT(*) AS denied_count FROM leave_requests WHERE TRIM(LOWER(status)) = 'denied'")->fetch_assoc()['denied_count'] ?? 0;
$totalPendingRequests = $conn->query("SELECT COUNT(*) AS pending_count FROM leave_requests WHERE TRIM(LOWER(status)) = 'pending'")->fetch_assoc()['pending_count'] ?? 0;
$weeklyHoursData = [];
$resultWeeklyHours = $conn->query("SELECT employee_id, week_start, hours_worked FROM weekly_hours ORDER BY week_start ASC");
if ($resultWeeklyHours) {
    while ($row = $resultWeeklyHours->fetch_assoc()) {
        $weeklyHoursData[] = [
            'employeeId' => $row['employee_id'],
            'weekStart' => $row['week_start'],
            'hoursWorked' => (float)$row['hours_worked']
        ];
    }
    $resultWeeklyHours->free();
}
$conn->close();
$departments = array_values(array_unique(array_filter(array_column($employeeInformation, 'department'))));
$jsonEmployeeInformation = json_encode($employeeInformation, JSON_UNESCAPED_SLASHES);
$jsonDepartments = json_encode($departments, JSON_UNESCAPED_SLASHES);
$jsonWeeklyHoursData = json_encode($weeklyHoursData, JSON_UNESCAPED_SLASHES);
$hour = (int)date('G');
$phpGreeting = $hour < 12 ? "Good morning" : ($hour < 18 ? "Good afternoon" : "Good evening");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.js"></script>
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
        .active-link { color: #39bbc8 !important; background-color: #ddfafd !important; font-weight: bold !important; }
        .card-shadow-hover:hover { box-shadow: 0 8px 32px 0 rgba(60,60,120,0.18),0 1.5px 6px 0 rgba(60,60,120,0.10); transform: translateY(-4px) scale(1.03); transition: box-shadow 0.2s, transform 0.2s; }
        .main-content-collapsed { margin-left: 6rem !important; }
        .main-content-expanded { margin-left: 16rem !important; }
        @media (max-width:1024px){.main-content-collapsed,.main-content-expanded{margin-left:0!important;}}
        
        /* ENTRANCE ANIMATION STYLES */
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
        .dashboard-element {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .dashboard-element.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        .delay-100 { transition-delay: 0.1s; }
        .delay-200 { transition-delay: 0.2s; }
        .delay-300 { transition-delay: 0.3s; }
        .delay-400 { transition-delay: 0.4s; }
        .delay-500 { transition-delay: 0.5s; }
        .delay-600 { transition-delay: 0.6s; }
        .delay-700 { transition-delay: 0.7s; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    <div class="flex flex-1">
        <main id="main-content" class="flex-1 p-8 page-entrance">
            <div class="py-8 px-2 sm:px-6 md:px-10 lg:px-16 max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-900 mb-2 text-left sm:text-4xl sm:mb-4 dashboard-element delay-100">Dashboard</h1>
                <h2 class="text-xl font-semibold text-gray-700 mb-6 text-left sm:text-2xl dashboard-element delay-200"><?= htmlspecialchars($phpGreeting); ?></h2>
                <div class="flex flex-wrap gap-4 justify-center mb-8 dashboard-element delay-300">
                    <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center w-60 min-w-[140px] flex-1 max-w-xs card-shadow-hover">
                        <div class="text-base font-medium mb-1" style="color:#2b80ff;">Total Employees</div>
                        <div id="total-employees" class="text-2xl font-bold" style="color:#2b80ff;"><?= count($employeeInformation); ?></div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center w-60 min-w-[140px] flex-1 max-w-xs card-shadow-hover">
                        <div class="text-base font-medium mb-1" style="color:#22c55e;">Present</div>
                        <div id="total-present" class="text-2xl font-bold" style="color:#22c55e;"><?= $totalPresentEmployees; ?></div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center w-60 min-w-[140px] flex-1 max-w-xs card-shadow-hover">
                        <div class="text-base font-medium mb-1" style="color:#ef4444;">Absent</div>
                        <div id="total-absent" class="text-2xl font-bold" style="color:#ef4444;"><?= $totalAbsentEmployees; ?></div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center w-60 min-w-[140px] flex-1 max-w-xs card-shadow-hover">
                        <div class="text-base font-medium mb-1" style="color:#f59e42;">Pending</div>
                        <div id="total-pending" class="text-2xl font-bold" style="color:#f59e42;"><?= $totalPendingRequests; ?></div>
                    </div>
                </div>
                <div class="flex w-full justify-center mb-2 dashboard-element delay-400">
                    <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center w-60 min-w-[140px] max-w-xs card-shadow-hover">
                        <div class="text-base font-medium mb-1" style="color:#ef4444;">Denied</div>
                        <div id="total-denied" class="text-2xl font-bold" style="color:#ef4444;"><?= $totalDeniedRequests; ?></div>
                    </div>
                </div>
                <div class="flex w-full justify-center mb-6 dashboard-element delay-500">
                    <div class="font-bold text-gray-700 text-lg">This month</div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-2 dashboard-element delay-600">
                    <select id="department-select" class="border px-3 py-2 rounded shadow-sm w-full sm:w-auto">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div id="charts-section" class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-7 dashboard-element delay-700">
                    <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center card-shadow-hover">
                        <h2 class="text-lg font-medium mb-2">Employees per Department</h2>
                        <canvas id="deptBar" width="250" height="200"></canvas>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center card-shadow-hover">
                        <h2 class="text-lg font-medium mb-2">Salary Range Distribution</h2>
                        <canvas id="salaryBar" width="250" height="200"></canvas>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 flex flex-col items-center card-shadow-hover">
                        <h2 class="text-lg font-medium mb-2">Employees Joined Per Year</h2>
                        <canvas id="historyBar" width="250" height="200"></canvas>
                    </div>
                </div>
                <div class="relative mt-8 bg-white rounded-lg shadow p-4 flex flex-col items-center w-full max-w-2xl mx-auto card-shadow-hover dashboard-element delay-700">
                    <div class="bbi-dbrd-control-background absolute inset-0 bg-gray-100 opacity-50 rounded-lg z-0"></div>
                    <h2 class="text-lg font-medium mb-2 z-10 relative">Total Hours Worked</h2>
                    <canvas id="hoursLine" width="600" height="250" class="z-10 relative w-full max-w-full"></canvas>
                </div>
                <div id="loading-message" class="text-gray-500 mt-6 text-center hidden">Loading...</div>
                <div id="error-message" class="text-red-500 mt-4 text-center hidden"></div>
                <div id="no-data-message" class="text-gray-500 mt-6 text-center hidden">No data to display.</div>
            </div>
        </main>
    </div>
    <?php include 'footer.php'; ?>
    <script>
        let employees = <?= $jsonEmployeeInformation; ?> || [];
        let departments = <?= $jsonDepartments; ?> || [];
        let weeklyHoursData = <?= $jsonWeeklyHoursData; ?> || [];
        let deptBarChart = null, salaryBarChart = null, historyBarChart = null, hoursLineChart = null;
        const totalEmployeesEl = document.getElementById('total-employees');
        const totalPresentEl = document.getElementById('total-present');
        const totalAbsentEl = document.getElementById('total-absent');
        const totalPendingEl = document.getElementById('total-pending');
        const totalDeniedEl = document.getElementById('total-denied');
        const departmentSelectEl = document.getElementById('department-select');
        const deptBarCanvas = document.getElementById('deptBar');
        const salaryBarCanvas = document.getElementById('salaryBar');
        const historyBarCanvas = document.getElementById('historyBar');
        const hoursLineCanvas = document.getElementById('hoursLine');
        const loadingMessageEl = document.getElementById('loading-message');
        const errorMessageEl = document.getElementById('error-message');
        const noDataMessageEl = document.getElementById('no-data-message');
        const chartsSectionEl = document.getElementById('charts-section');
        let selectedDepartment = "";
        function getFilteredEmployees() {
            return employees.filter(emp => selectedDepartment ? emp.department === selectedDepartment : true);
        }
        function destroyCharts() {
            if (deptBarChart) { deptBarChart.destroy(); deptBarChart = null; }
            if (salaryBarChart) { salaryBarChart.destroy(); salaryBarChart = null; }
            if (historyBarChart) { historyBarChart.destroy(); historyBarChart = null; }
            if (hoursLineChart) { hoursLineChart.destroy(); hoursLineChart = null; }
        }
        function renderCharts() {
            const filtered = getFilteredEmployees();
            chartsSectionEl.classList.toggle('hidden', filtered.length === 0);
            noDataMessageEl.classList.toggle('hidden', filtered.length > 0);
            const deptCounts = {};
            filtered.forEach(emp => { if (emp.department) deptCounts[emp.department] = (deptCounts[emp.department] || 0) + 1; });
            if (deptBarCanvas && Object.keys(deptCounts).length) {
                if (deptBarChart) deptBarChart.destroy();
                deptBarChart = new Chart(deptBarCanvas, {
                    type: 'bar',
                    data: { labels: Object.keys(deptCounts), datasets: [{ label: 'Employees', data: Object.values(deptCounts), backgroundColor: '#60a5fa', borderColor: '#2563eb', borderWidth: 2, borderRadius: 6, barPercentage: 0.7, categoryPercentage: 0.7 }] },
                    options: { responsive: false, plugins: { legend: { display: false }, title: { display: false } }, scales: { y: { beginAtZero: true, precision: 0 } } }
                });
            } else if (deptBarChart) deptBarChart.destroy();
            const salaryRanges = { '50k-59k': 0, '60k-69k': 0, '70k-79k': 0, '80k+': 0 };
            filtered.forEach(emp => {
                const salary = parseFloat(emp.salary);
                if (!isNaN(salary)) {
                    if (salary >= 50000 && salary < 60000) salaryRanges['50k-59k']++;
                    else if (salary >= 60000 && salary < 70000) salaryRanges['60k-69k']++;
                    else if (salary >= 70000 && salary < 80000) salaryRanges['70k-79k']++;
                    else if (salary >= 80000) salaryRanges['80k+']++;
                }
            });
            if (salaryBarCanvas && Object.keys(salaryRanges).length) {
                if (salaryBarChart) salaryBarChart.destroy();
                salaryBarChart = new Chart(salaryBarCanvas, {
                    type: 'bar',
                    data: { labels: Object.keys(salaryRanges), datasets: [{ label: 'Employees', data: Object.values(salaryRanges), backgroundColor: '#34d399', borderColor: '#059669', borderWidth: 2, borderRadius: 6, barPercentage: 0.7, categoryPercentage: 0.7 }] },
                    options: { responsive: false, plugins: { legend: { display: false }, title: { display: false } }, scales: { y: { beginAtZero: true, precision: 0 } } }
                });
            } else if (salaryBarChart) salaryBarChart.destroy();
            const hireYears = {};
            filtered.forEach(emp => {
                const match = emp.employmentHistory && emp.employmentHistory.match(/\b(19|20)\d{2}\b/);
                const hireYear = match ? match[0] : null;
                if (hireYear) hireYears[hireYear] = (hireYears[hireYear] || 0) + 1;
            });
            if (historyBarCanvas && Object.keys(hireYears).length) {
                if (historyBarChart) historyBarChart.destroy();
                historyBarChart = new Chart(historyBarCanvas, {
                    type: 'bar',
                    data: { labels: Object.keys(hireYears), datasets: [{ label: 'Employees Hired', data: Object.values(hireYears), backgroundColor: '#818cf8', borderColor: '#6366f1', borderWidth: 2, borderRadius: 6, barPercentage: 0.7, categoryPercentage: 0.7 }] },
                    options: { responsive: false, plugins: { legend: { display: false }, title: { display: false } }, scales: { y: { beginAtZero: true, precision: 0 } } }
                });
            } else if (historyBarChart) historyBarChart.destroy();
            if (hoursLineCanvas) {
                const weekMap = {};
                weeklyHoursData.forEach(row => { weekMap[row.weekStart] = (weekMap[row.weekStart] || 0) + row.hoursWorked; });
                const weekLabels = Object.keys(weekMap).sort();
                const weekTotals = weekLabels.map(week => weekMap[week]);
                if (hoursLineChart) hoursLineChart.destroy();
                hoursLineChart = new Chart(hoursLineCanvas, {
                    type: 'line',
                    data: { labels: weekLabels, datasets: [{ label: 'Total Hours Worked', data: weekTotals, backgroundColor: 'rgba(79,70,229,0.1)', borderColor: '#4f46e5', borderWidth: 2, fill: true, tension: 0.4 }] },
                    options: { responsive: false, plugins: { legend: { display: false }, title: { display: false } }, scales: { x: { type: 'category', title: { display: true, text: 'Week Starting' } }, y: { beginAtZero: true, precision: 0, title: { display: true, text: 'Hours Worked' } } } }
                });
            }
        }
        function updateDepartmentSelect() {
            departmentSelectEl.innerHTML = `<option value="">All Departments</option>` + departments.map(dept => `<option value="${dept}">${dept}</option>`).join('');
        }
        
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
                const dashboardElements = document.querySelectorAll('.dashboard-element');
                dashboardElements.forEach(element => {
                    element.classList.add('animate-in');
                });
            }, 300);
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            initPageEntrance();
            updateDepartmentSelect();
            setTimeout(() => { renderCharts(); }, 800);
            departmentSelectEl.addEventListener('change', (event) => {
                selectedDepartment = event.target.value;
                destroyCharts();
                renderCharts();
            });
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.getElementById('sidebar-toggle');
            const sidebarLinks = document.querySelectorAll('#sidebar .sidebar-link-text');
            const sidebarUserText = document.getElementById('sidebar-user-text');
            const mainContent = document.getElementById('main-content');
            let isSidebarCollapsed = window.innerWidth <= 1024;
            function applySidebarState() {
                if (sidebar) {
                    sidebar.classList.toggle('w-64', !isSidebarCollapsed);
                    sidebar.classList.toggle('w-24', isSidebarCollapsed);
                }
                sidebarLinks.forEach(span => span.classList.toggle('hidden', isSidebarCollapsed));
                if (sidebarUserText) sidebarUserText.classList.toggle('hidden', isSidebarCollapsed);
                if (mainContent) {
                    mainContent.classList.toggle('main-content-expanded', !isSidebarCollapsed);
                    mainContent.classList.toggle('main-content-collapsed', isSidebarCollapsed);
                }
            }
            applySidebarState();
            if (toggleButton) {
                toggleButton.addEventListener('click', () => {
                    isSidebarCollapsed = !isSidebarCollapsed;
                    applySidebarState();
                });
            }
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    const shouldBeCollapsed = window.innerWidth <= 1024;
                    if (isSidebarCollapsed !== shouldBeCollapsed) {
                        isSidebarCollapsed = shouldBeCollapsed;
                        applySidebarState();
                    }
                }, 250);
            });

            // Page Exit Animation
            document.body.addEventListener('click', function(event) {
                const link = event.target.closest('a');
                
                if (link && link.href && link.target !== '_blank' && !link.href.startsWith(window.location.href + '#')) {
                    event.preventDefault(); // Stop navigation
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