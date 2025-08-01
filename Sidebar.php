<?php
// Ensure session is started for access to $_SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$sidebarCollapsed = isset($_COOKIE['sidebarCollapsed']) && $_COOKIE['sidebarCollapsed'] === 'true';

$userName = $_SESSION['user_name'] ?? 'Tom Cook';
$userProfilePic = $_SESSION['user_pic'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80';

// Get current page path for active link highlighting
$currentPath = basename($_SERVER['PHP_SELF']);

// Icons
$baseAssetPath = '/PHP/moderntech_solutions/moderntech_solutions/src/assets/';

$logoIcon = $baseAssetPath . 'Logo.png';
$dashboardIcon = $baseAssetPath . 'dashboard-icon.png';
$employeeDirectoryIcon = $baseAssetPath . 'employee_directory.png';
$payrollIcon = $baseAssetPath . 'payroll.png';
$attendanceIcon = $baseAssetPath . 'attendance.png';
$performanceReviewIcon = $baseAssetPath . 'performance_review.png';
$leaveRequestIcon = $baseAssetPath . 'leave_request.png';

function navActive($file, $current, $collapsed) {
    $isActive = false;
    switch ($file) {
        case 'Dashboard.View.php':
            $isActive = ($current === 'Dashboard.View.php' || $current === 'index.php');
            break;
        case 'EmployeeDirectory.View.php':
            $isActive = ($current === 'Employee_Directory.php' || $current === 'EmployeeDirectory.View.php');
            break;
        case 'Payroll.View.php':
            $isActive = ($current === 'Payroll.php' || $current === 'Payroll.View.php');
            break;
        case 'Attendance.View.php':
            $isActive = ($current === 'AttendanceView.php' || $current === 'Attendance.View.php');
            break;
        case 'PerformanceReview.View.php':
            $isActive = ($current === 'PerformanceReviewView.php' || $current === 'PerformanceReview.View.php');
            break;
        case 'Leave.View.php':
            $isActive = ($current === 'LeaveView.php' || $current === 'Leave.View.php');
            break;
    }
    return $isActive
        ? ($collapsed
            ? 'bg-blue-50'
            : 'active-link')
        : '';
}
$isLoginPage = $isLoginPage ?? false;
?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

<style>
    .active-link {
        color: #39bbc8 !important;
        background-color: #ddfafd !important;
        font-weight: bold !important;
    }

    #main-sidebar {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        height: 100vh !important;
        z-index: 1000 !important;
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

    #log-out {
        cursor: pointer;
    }

    .filter-grayscale {
        filter: brightness(0) saturate(100%) invert(13%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(90%) contrast(90%);
    }

    #main-content {
        transition: margin-left 0.3s cubic-bezier(.4,2,.6,1);
        margin-left: 16rem;
    }
    .main-content-collapsed {
        margin-left: 6rem !important;
    }
    @media (max-width: 1024px) {
        #main-content, .main-content-collapsed {
            margin-left: 0 !important;
        }
    }

    .nav-item-icon {
        display: none;
        align-items: center;
        justify-content: center;
        height: 100%;
        width: 100%;
        min-height: 48px;
        opacity: 0;
        transition: opacity 0.2s ease-in-out;
    }

    .nav-item-text {
        display: inline-block;
        opacity: 1;
        transition: opacity 0.2s ease-in-out;
    }

    .sidebar-collapsed .nav-item-icon {
        display: flex;
        opacity: 1;
    }

    .sidebar-collapsed .nav-item-text {
        display: none;
        opacity: 0;
    }
</style>

<?php if (!$isLoginPage): ?>
<aside
    id="main-sidebar"
    class="bg-white border-r border-gray-200 flex flex-col transition-all duration-200 overflow-visible <?= $sidebarCollapsed ? 'w-24 sidebar-collapsed' : 'w-64' ?>"
    style="font-family: inherit;"
>
    <div class="h-16 flex items-center justify-center px-2 border-b border-gray-200 relative">
        <?php if (!$sidebarCollapsed): ?>
        <img
            alt="ModernTech Solutions"
            src="<?= htmlspecialchars($logoIcon) ?>"
            class="h-12 mx-auto transition-all duration-200"
            style="max-width: 80%;"
        />
        <?php endif; ?>
        <button
            id="sidebar-toggle"
            class="bg-gray-100 hover:bg-gray-200 rounded p-1 transition flex items-center justify-center absolute"
            aria-label="<?= $sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar' ?>"
            style="<?= $sidebarCollapsed
                ? 'left:50%;top:50%;transform:translate(-50%,-50%);'
                : 'right:1rem;top:50%;transform:translateY(-50%);'
            ?> width:40px; height:40px;"
        >
            <span class="material-symbols-outlined">menu</span>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-6 <?= $sidebarCollapsed ? 'px-1' : 'px-4' ?> text-center">
        <?php if (!$sidebarCollapsed): ?>
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 text-center">Main</h2>
        <?php endif; ?>
        <ul class="space-y-1 text-center">
            <li>
                <a href="Dashboard.View.php"
                   class="block w-full rounded-md text-sm font-bold text-gray-700 hover:bg-gray-100 transition <?= navActive('Dashboard.View.php', $currentPath, $sidebarCollapsed) ?> flex justify-center items-center px-0 py-2">
                    <span title="Dashboard" class="nav-item-icon">
                        <img src="<?= htmlspecialchars($dashboardIcon) ?>" alt="Dashboard" style="width:20px;height:20px;object-fit:contain;" />
                    </span>
                    <span class="nav-item-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="Employee_Directory.php"
                   class="block rounded-md text-sm font-bold text-gray-700 hover:bg-gray-100 transition <?= navActive('EmployeeDirectory.View.php', $currentPath, $sidebarCollapsed) ?> flex justify-center items-center px-0 py-2">
                    <span title="Employee Directory" class="nav-item-icon">
                        <img src="<?= htmlspecialchars($employeeDirectoryIcon) ?>" alt="Employee Directory" style="width:20px;height:20px;object-fit:contain;" />
                    </span>
                    <span class="nav-item-text">Employee Directory</span>
                </a>
            </li>
            <li>
                <a href="Payroll.php"
                   class="block rounded-md text-sm font-bold text-gray-700 hover:bg-gray-100 transition <?= navActive('Payroll.View.php', $currentPath, $sidebarCollapsed) ?> flex justify-center items-center px-0 py-2">
                    <span title="Payroll" class="nav-item-icon">
                        <img src="<?= htmlspecialchars($payrollIcon) ?>" alt="Payroll" style="width:20px;height:20px;object-fit:contain;" />
                    </span>
                    <span class="nav-item-text">Payroll</span>
                </a>
            </li>
            <li>
                <a href="AttendanceView.php"
                   class="block rounded-md text-sm font-bold text-gray-700 hover:bg-gray-100 transition <?= navActive('Attendance.View.php', $currentPath, $sidebarCollapsed) ?> flex justify-center items-center px-0 py-2">
                    <span title="Attendance" class="nav-item-icon">
                        <img src="<?= htmlspecialchars($attendanceIcon) ?>" alt="Attendance" style="width:20px;height:20px;object-fit:contain;" />
                    </span>
                    <span class="nav-item-text">Attendance</span>
                </a>
            </li>
            <li>
                <a href="PerformanceReviewView.php"
                   class="block rounded-md text-sm font-bold text-gray-700 hover:bg-gray-100 transition <?= navActive('PerformanceReview.View.php', $currentPath, $sidebarCollapsed) ?> flex justify-center items-center px-0 py-2">
                    <span title="Performance Review" class="nav-item-icon">
                        <img src="<?= htmlspecialchars($performanceReviewIcon) ?>" alt="Performance Review" style="width:20px;height:20px;object-fit:contain;filter:brightness(0) saturate(100%) invert(13%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(90%) contrast(90%);" />
                    </span>
                    <span class="nav-item-text">Performance Review</span>
                </a>
            </li>
            <li>
                <a href="LeaveView.php"
                   class="block rounded-md text-sm font-bold text-gray-700 hover:bg-gray-100 transition <?= navActive('Leave.View.php', $currentPath, $sidebarCollapsed) ?> flex justify-center items-center px-0 py-2">
                    <span title="Leave Request" class="nav-item-icon">
                        <img src="<?= htmlspecialchars($leaveRequestIcon) ?>" alt="Leave" style="width:20px;height:20px;object-fit:contain;filter:brightness(0) saturate(100%) invert(13%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(90%) contrast(90%);" />
                    </span>
                    <span class="nav-item-text">Leave Request</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="border-t border-gray-200 p-4 flex <?= $sidebarCollapsed ? 'justify-center' : 'items-center justify-between' ?>">
        <?php if (!$sidebarCollapsed): ?>
        <div class="flex items-center">
            <img alt="User Profile" src="<?= htmlspecialchars($userProfilePic) ?>" class="h-10 w-10 rounded-full mr-3" />
            <div>
                <div class="text-sm font-medium text-gray-900">Letty Mcook</div>
                <div class="text-xs text-gray-500">Your Profile</div>
            </div>
        </div>
        <?php endif; ?>
        <form method="post" action="LoginView.php" style="display:inline;">
            <button
                id="log-out"
                class="px-3 py-1 rounded bg-red-100 text-red-600 text-xs font-semibold hover:bg-red-200 transition whitespace-nowrap <?= $sidebarCollapsed ? '' : 'ml-4' ?>"
                style="display: flex; align-items: center; justify-content: center;"
                type="submit"
            >
                Log Out
            </button>
        </form>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('main-sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const mainContent = document.getElementById('main-content');

        function applyMainContentMargin() {
            const sidebarCollapsed = sidebar.classList.contains('w-24');
            const isMobile = window.innerWidth <= 1024;

            if (sidebarCollapsed) {
                sidebar.classList.add('sidebar-collapsed');
            } else {
                sidebar.classList.remove('sidebar-collapsed');
            }

            if (mainContent) {
                if (isMobile) {
                    mainContent.classList.remove('main-content-collapsed');
                    mainContent.style.marginLeft = '0';
                } else if (sidebarCollapsed) {
                    mainContent.classList.add('main-content-collapsed');
                    mainContent.style.marginLeft = '6rem';
                } else {
                    mainContent.classList.remove('main-content-collapsed');
                    mainContent.style.marginLeft = '16rem';
                }
            }
        }

        // Apply initial layout adjustments when the page loads
        applyMainContentMargin();
        // Recalculate and apply adjustments whenever the window is resized
        window.addEventListener('resize', applyMainContentMargin);

        // Event listener for the sidebar toggle button
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const collapsed = sidebar.classList.toggle('w-24');
                sidebar.classList.toggle('w-64', !collapsed);

                document.cookie = 'sidebarCollapsed=' + collapsed + '; path=/';

                applyMainContentMargin();
            });
        }
    });
</script>
<?php endif; ?>