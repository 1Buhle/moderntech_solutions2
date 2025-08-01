<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page variables
$isLoginPage = false;
$pageTitle = "Leave Request Management";

// Database connection details
$servername = "localhost";
$username = "root";
$password = "CubanaKing@2016";
$dbname = "moderntech_hr";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'submit_leave':
            $employee_id = $_POST['employee_id'];
            $start_date = $_POST['startDate'];
            $end_date = $_POST['endDate'];
            $reason = $_POST['reason'];

            // Updated SQL query to insert start_date and end_date
            $sql = "INSERT INTO leave_requests (employee_id, start_date, end_date, reason, status)
                    VALUES (?, ?, ?, ?, 'Pending')";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $employee_id, $start_date, $end_date, $reason);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error submitting leave request: ' . $conn->error]);
            }
            $stmt->close();
            break;

        case 'get_leaves':
            // Select both start_date and end_date
            $sql = "SELECT lr.*, e.name as employee_name
                    FROM leave_requests lr
                    JOIN employees e ON lr.employee_id = e.employee_id
                    ORDER BY lr.id DESC";
            $result = $conn->query($sql);

            $leaves = [];
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $leaves[] = $row;
                }
            }

            echo json_encode(['success' => true, 'data' => $leaves]);
            break;

        case 'update_status':
            $leave_id = $_POST['leave_id'];
            $status = $_POST['status'];

            $sql = "UPDATE leave_requests SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $status, $leave_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $conn->error]);
            }
            $stmt->close();
            break;

        case 'delete_leave':
            $leave_id = $_POST['leave_id'];

            $sql = "DELETE FROM leave_requests WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $leave_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Leave request deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting leave request: ' . $conn->error]);
            }
            $stmt->close();
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

    $conn->close();
    exit;
}

// Fetch employees from the database
$employees = [];
$sql = "SELECT employee_id, name FROM employees ORDER BY employee_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fade-enter-active,
        .fade-leave-active {
            transition: opacity 0.3s ease;
        }
        .fade-enter-from,
        .fade-leave-to {
            opacity: 0;
        }
        #submit{
            cursor: pointer;
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .transition-transform {
            transition: transform 0.2s ease;
        }
        .rotate-180 {
            transform: rotate(180deg);
        }
        .dropdown-transition {
            transition: opacity 0.3s ease, max-height 0.3s ease;
            overflow: hidden;
        }
        .dropdown-hidden {
            opacity: 0;
            max-height: 0;
        }
        .dropdown-visible {
            opacity: 1;
            max-height: 200px;
        }

        /* PAGE TRANSITION STYLES */
        body {
            margin-left: 250px;
            transition: margin 0.3s ease;
            transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
        }
        /* Class to trigger the zoom-in exit animation */
        .page-exit {
            transform: scale(1.2);
            opacity: 0;
        }
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            transition: margin 0.3s ease, max-width 0.3s ease;
        }
        body.sidebar-collapsed {
            margin-left: 0;
        }
        body.sidebar-collapsed .main-content {
            margin-left: auto;
            margin-right: auto;
            max-width: 1100px;
        }
        @media (max-width: 768px) {
            body, .main-content {
                margin-left: 0 !important;
            }
        }

        /* Page Entrance Animation Styles */
        #main-content.page-entrance {
            transform: scale(1.2);
            opacity: 0;
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
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
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'Sidebar.php'; ?>
<div class="p-4 sm:p-6 max-w-7xl mx-auto main-content page-entrance" id="main-content">
    <div class="flex">
        <div class="w-10/12 lg:w-8/12 mx-auto directory-element delay-100">
            <div class="bg-white shadow-md rounded-lg">
                <div id="header" class="rounded-t-lg bg-[#39BBC8] text-white px-4 py-3 sm:px-6 sm:py-4 flex flex-col sm:flex-row justify-between items-center directory-element delay-200">
                    <h2 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-0">Leave Request Form</h2>
                    <button class="btn-refresh flex items-center space-x-2 text-white bg-teal-400 hover:bg-teal-500 px-3 py-1 rounded text-sm sm:text-base" onclick="refreshLeaves()" id="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582M20 20v-5h-.581M5.586 19A9 9 0 1119 5.586" />
                        </svg>
                        <span>Refresh</span>
                    </button>
                </div>

                <div class="p-4 sm:p-6">
                    <form onsubmit="submitForm(event)" class="space-y-4 sm:space-y-6 directory-element delay-300">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <button type="button" class="w-full border border-gray-300 rounded-md px-3 py-2 flex justify-between items-center text-left text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-400" onclick="toggleEmployeeSelect()" aria-expanded="false" aria-controls="employeeSelectCollapse">
                                <span id="selectedName">Select your name</span>
                                <svg id="dropdownArrow" class="h-5 w-5 text-gray-500 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <select id="employeeSelectCollapse" onchange="selectEmployee(this)" required class="mt-2 w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-400 dropdown-transition dropdown-hidden">
                                <option value="" disabled selected>Select your name</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo htmlspecialchars($employee['name']); ?>" data-employee-id="<?php echo htmlspecialchars($employee['employee_id']); ?>">
                                        <?php echo htmlspecialchars($employee['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                            <input id="reason" type="text" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-400" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <input id="startDate" type="date" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-400" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <input id="endDate" type="date" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-400" />
                            </div>
                        </div>

                        <div class="flex justify-center"> <button type="submit" id="submitBtn" class="inline-flex items-center justify-center bg-teal-500 hover:bg-teal-600 disabled:bg-teal-300 text-white font-semibold rounded-md px-4 py-2">
                                <svg id="submitSpinner" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582M20 20v-5h-.581M5.586 19A9 9 0 1119 5.586" />
                                </svg>
                                Submit Request
                            </button>
                        </div>
                    </form>

                    <hr class="my-4 sm:my-6" />

                    <div id="loadingDiv" class="text-center py-8" style="display: none;">
                        <svg class="animate-spin mx-auto h-8 w-8 text-teal-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582M20 20v-5h-.581M5.586 19A9 9 0 1119 5.586" />
                        </svg>
                        <span class="sr-only">Loading...</span>
                    </div>

                    <div id="noLeavesDiv" class="bg-blue-100 border border-blue-300 text-blue-700 px-4 py-3 rounded relative text-sm sm:text-base" role="alert" style="display: none;">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10c0 4.418-3.582 8-8 8s-8-3.582-8-8 3.582-8 8-8 8 3.582 8 8zm-8 4a1 1 0 100-2 1 1 0 000 2zm.707-6.707a1 1 0 10-1.414-1.414L9 7.586 7.707 6.293a1 1 0 00-1.414 1.414L7.586 9l-1.293 1.293a1 1 0 001.414 1.414L10.414 9l1.293-1.293z" clip-rule="evenodd" /></svg>
                            <span>No leave requests found. Submit a request using the form.</span>
                        </div>
                    </div>

                    <div id="leavesTableDiv" class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg directory-element delay-400" style="display: none;">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-4 sm:py-3 sm:text-sm">Name</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-4 sm:py-3 sm:text-sm">Reason</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-4 sm:py-3 sm:text-sm">Date(s)</th>
                                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-4 sm:py-3 sm:text-sm">Status</th>
                                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider sm:px-4 sm:py-3 sm:text-sm">Action</th>
                                </tr>
                            </thead>
                            <tbody id="leavesTableBody" class="bg-white divide-y divide-gray-200">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script>
let leavesData = [], isLoading = false, isSubmitting = false, showEmployeeSelect = false, selectedEmployeeId = null;
document.addEventListener('DOMContentLoaded', function() {
    loadLeaves();
    initPageEntrance(); // Initialize page entrance on load
});
function toggleEmployeeSelect() {
    showEmployeeSelect = !showEmployeeSelect;
    const dropdown = document.getElementById('employeeSelectCollapse');
    const arrow = document.getElementById('dropdownArrow');
    if (showEmployeeSelect) {
        dropdown.classList.remove('dropdown-hidden'); dropdown.classList.add('dropdown-visible'); arrow.classList.add('rotate-180');
    } else {
        dropdown.classList.remove('dropdown-visible'); dropdown.classList.add('dropdown-hidden'); arrow.classList.remove('rotate-180');
    }
}
function selectEmployee(select) {
    document.getElementById('selectedName').textContent = select.options[select.selectedIndex].text;
    selectedEmployeeId = select.options[select.selectedIndex].dataset.employeeId;
    showEmployeeSelect = false; toggleEmployeeSelect();
}
function loadLeaves() {
    isLoading = true;
    document.getElementById('loadingDiv').style.display = 'block';
    document.getElementById('noLeavesDiv').style.display = 'none';
    document.getElementById('leavesTableDiv').style.display = 'none';
    fetch(window.location.href, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=get_leaves' })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            leavesData = data.data;
            if (leavesData.length === 0) document.getElementById('noLeavesDiv').style.display = 'block';
            else { document.getElementById('leavesTableDiv').style.display = 'block'; renderLeavesTable(); }
        } else { alert('Error loading leave requests'); }
        document.getElementById('loadingDiv').style.display = 'none'; isLoading = false;
    })
    .catch(() => {
        alert('Error loading leave requests');
        document.getElementById('loadingDiv').style.display = 'none'; isLoading = false;
    });
}
function refreshLeaves() { loadLeaves(); }
function submitForm(event) {
    event.preventDefault();
    const name = document.getElementById('selectedName').textContent;
    const reason = document.getElementById('reason').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    if (name === 'Select your name' || !selectedEmployeeId || !reason.trim() || !startDate || !endDate) {
        alert('Please fill all fields'); return;
    }
    if (new Date(startDate) > new Date(endDate)) {
        alert('End date cannot be before start date.'); return;
    }
    isSubmitting = true;
    const submitBtn = document.getElementById('submitBtn');
    const submitSpinner = document.getElementById('submitSpinner');
    submitBtn.disabled = true; submitSpinner.style.display = 'inline';
    const formData = new FormData();
    formData.append('action', 'submit_leave');
    formData.append('employee_id', selectedEmployeeId);
    formData.append('startDate', startDate);
    formData.append('endDate', endDate);
    formData.append('reason', reason);
    fetch(window.location.href, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Leave request submitted successfully!');
            event.target.reset();
            document.getElementById('selectedName').textContent = 'Select your name';
            selectedEmployeeId = null;
            loadLeaves();
        } else alert('Error submitting leave request: ' + data.message);
        isSubmitting = false; submitBtn.disabled = false; submitSpinner.style.display = 'none';
    })
    .catch(() => {
        alert('Error submitting leave request');
        isSubmitting = false; submitBtn.disabled = false; submitSpinner.style.display = 'none';
    });
}
function renderLeavesTable() {
    const tbody = document.getElementById('leavesTableBody');
    tbody.innerHTML = '';
    leavesData.forEach(leave => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        let displayDates = '';
        if (leave.start_date && leave.end_date) {
            if (leave.start_date === leave.end_date) displayDates = formatDate(leave.start_date);
            else {
                const numDays = countDays(leave.start_date, leave.end_date);
                displayDates = `${formatDate(leave.start_date)} - ${formatDate(leave.end_date)} (${numDays} day${numDays > 1 ? 's' : ''})`;
            }
        } else if (leave.start_date) displayDates = formatDate(leave.start_date);
        else displayDates = 'N/A';
        row.innerHTML = `
            <td class="px-2 py-2 text-xs text-gray-700 sm:px-4 sm:py-3 sm:text-sm">${leave.employee_name}</td>
            <td class="px-2 py-2 text-xs text-gray-600 sm:px-4 sm:py-3 sm:text-sm">${leave.reason}</td>
            <td class="px-2 py-2 text-xs text-gray-700 sm:px-4 sm:py-3 sm:text-sm">${displayDates}</td>
            <td class="px-2 py-2 text-center text-xs sm:px-4 sm:py-3 sm:text-sm">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusBadgeClass(leave.status)}">${leave.status}</span>
            </td>
            <td class="px-2 py-2 text-center text-xs text-gray-700 sm:px-4 sm:py-3 sm:text-sm">
                ${getActionButtons(leave)}
            </td>
        `;
        tbody.appendChild(row);
    });
}
function getActionButtons(leave) {
    if (leave.status === 'Pending') {
        return `
            <div class="flex flex-col sm:flex-row items-center justify-center space-y-1 sm:space-y-0 sm:space-x-2">
                <div class="flex flex-col sm:flex-row space-y-1 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
                    <button class="bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded inline-flex items-center justify-center w-full sm:w-auto" onclick="changeStatus(${leave.id}, 'Approved')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-0 sm:mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="hidden sm:inline">Approve</span>
                        <span class="inline sm:hidden">Appr.</span>
                    </button>
                    <button class="bg-red-600 hover:bg-red-700 text-white text-xs px-2 py-1 rounded inline-flex items-center justify-center w-full sm:w-auto" onclick="changeStatus(${leave.id}, 'Denied')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-0 sm:mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span class="hidden sm:inline">Deny</span>
                        <span class="inline sm:hidden">Deny</span>
                    </button>
                </div>
            </div>
        `;
    } else {
        return `
            <div class="flex flex-col sm:flex-row items-center justify-center space-y-1 sm:space-y-0 sm:space-x-2">
                <button class="border border-red-500 hover:bg-red-100 text-red-600 text-xs px-2 py-1 rounded inline-flex items-center justify-center w-full sm:w-auto ml-auto sm:ml-0" onclick="deleteLeave(${leave.id})" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-0 sm:mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7L5 21M5 7l14 14" />
                    </svg>
                    <span class="hidden sm:inline">Delete</span>
                    <span class="inline sm:hidden">Del</span>
                </button>
            </div>
        `;
    }
}
function changeStatus(id, status) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('leave_id', id);
    formData.append('status', status);
    fetch(window.location.href, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) { alert(`Leave request ${status.toLowerCase()} successfully!`); loadLeaves(); }
        else alert('Error updating status: ' + data.message);
    })
    .catch(() => { alert('Error updating status'); });
}
function deleteLeave(id) {
    if (confirm('Are you sure you want to delete this leave request?')) {
        const formData = new FormData();
        formData.append('action', 'delete_leave');
        formData.append('leave_id', id);
        fetch(window.location.href, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) { alert('Leave request deleted successfully!'); loadLeaves(); }
            else alert('Error deleting leave request: ' + data.message);
        })
        .catch(() => { alert('Error deleting leave request'); });
    }
}
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
function countDays(start, end) {
    if (!start || !end) return 0;
    const s = new Date(start + 'T00:00:00');
    const e = new Date(end + 'T00:00:00');
    const diffTime = Math.abs(e - s);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
}
function getStatusBadgeClass(status) {
    const s = status ? status.toLowerCase() : '';
    if (s === 'pending') return 'bg-yellow-100 text-yellow-800';
    if (s === 'approved') return 'bg-green-100 text-green-800';
    if (s === 'denied') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
}

//Page Entrance Animation
function initPageEntrance() {
    const mainContent = document.getElementById('main-content');
    if (mainContent) {
        mainContent.offsetWidth;
        mainContent.classList.remove('page-entrance');

        // Animate in the directory elements
        document.querySelectorAll('.directory-element').forEach((element, index) => {
            // Remove any existing delay classes to allow re-application
            element.classList.remove('delay-100', 'delay-200', 'delay-300', 'delay-400');
            element.classList.remove('animate-in');
            void element.offsetWidth;
            element.classList.add(`delay-${((index % 4) + 1) * 100}`);
            element.classList.add('animate-in');
        });
    }
}

//Page Exit Animation
document.body.addEventListener('click', function(event) {
    const link = event.target.closest('a');

    if (link && link.href && link.target !== '_blank' && !link.href.startsWith(window.location.href.split('#')[0] + '#')) {
        event.preventDefault(); // Stop navigation
        const destination = link.href;

        // Apply the exit animation class
        document.body.classList.add('page-exit');

        // Wait for animation to complete, then navigate
        setTimeout(() => {
            window.location.href = destination;
        }, 500); // Duration must match the CSS transition time (0.5s)
    }
});

</script>
</body>
</html>