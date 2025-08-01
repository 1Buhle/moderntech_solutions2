<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "CubanaKing@2016";
$dbname = "moderntech_hr";

$employeeInformation = [];
$message = ''; // To display success or error messages

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Handle Employee Actions ---

    // Handle Add Employee
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_employee') {
        $name = trim($_POST['name']);
        $initialScore = isset($_POST['initialScore']) ? (int)$_POST['initialScore'] : 0;

        if (!empty($name)) {
            // Insert without specifying id, as it's AUTO_INCREMENT
            $insertStmt = $pdo->prepare("INSERT INTO performance (name, performanceScore) VALUES (:name, :score)");
            $insertStmt->execute([
                ':name' => $name,
                ':score' => $initialScore
            ]);
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>Employee '" . htmlspecialchars($name) . "' added successfully!</div>";
        } else {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Employee name cannot be empty.</div>";
        }
    }

    // Handle Edit Employee (Update Score and Name)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_employee') {
        $employeeIdFromForm = $_POST['employeeId'];
        $newName = trim($_POST['newName']);
        $newScore = $_POST['newScore'];

        if (!empty($newName)) {
            $updateStmt = $pdo->prepare("UPDATE performance SET name = :name, performanceScore = :score WHERE id = :id_param");
            $updateStmt->execute([
                ':name' => $newName,
                ':score' => $newScore,
                ':id_param' => $employeeIdFromForm
            ]);
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>Employee ID " . htmlspecialchars($employeeIdFromForm) . " updated successfully!</div>";
        } else {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Employee name cannot be empty.</div>";
        }
    }

    // Handle Delete Employee
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_employee') {
        $employeeIdFromForm = $_POST['employeeId'];
        $deleteStmt = $pdo->prepare("DELETE FROM performance WHERE id = :id_param");
        $deleteStmt->execute([
            ':id_param' => $employeeIdFromForm
        ]);
        $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative' role='alert'>Employee ID " . htmlspecialchars($employeeIdFromForm) . " deleted successfully!</div>";
    }

    // Refresh employee data after any action
    $stmt = $pdo->prepare("SELECT id, name, performanceScore FROM performance ORDER BY id");
    $stmt->execute();
    $employeeInformation = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    $employeeInformation = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Performance Reviews</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .transition-width {
        transition-property: width;
        transition-duration: 1000ms;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    }
    .performance-bar {
        background: linear-gradient(90deg, #f08331, #e76e37);
    }
    .table-header {
        background-color: #14b8a6 !important;
        color: white !important;
    }
    body {
        margin-left: 250px;
        transition: margin 0.3s ease;
        transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
    }
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
<body class="bg-gray-50">
    <?php include 'Sidebar.php'; ?>

    <div class="main-content page-entrance" id="main-content">
        <div class="p-4 sm:p-8">
            <div class="text-center mb-4 sm:mb-8">
                <h1 class="text-2xl sm:text-3xl font-semibold directory-element delay-100">Performance Reviews</h1>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 directory-element delay-200">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow-md mb-8 mx-auto max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl directory-element delay-300">
                <h2 class="text-xl font-semibold mb-4">Add New Employee</h2>
                <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <input type="hidden" name="action" value="add_employee">
                    <div>
                        <label for="employeeName" class="block text-sm font-medium text-gray-700">Employee Name</label>
                        <input type="text" id="employeeName" name="name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="initialScore" class="block text-sm font-medium text-gray-700">Initial Score (%)</label>
                        <input type="number" id="initialScore" name="initialScore" value="0" min="0" max="100" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                    </div>
                    <div>
                        <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-1.5 px-3 rounded-md shadow-sm">
                            Add Employee
                        </button>
                    </div>
                </form>
            </div>

            <div id="tableContainer" class="overflow-x-auto mx-auto max-w-full lg:max-w-6xl rounded-lg shadow-md directory-element delay-400">
                <table class="w-full table-auto border-collapse bg-white">
                    <thead>
                        <tr class="table-header">
                            <th class="px-2 py-2 sm:px-4 sm:py-3 text-left text-sm sm:text-base w-1/12">#</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 text-left text-sm sm:text-base w-3/12">Employee Name</th>
                            <th class="px-2 py-2 sm:px-2 sm:py-3 text-center text-sm sm:text-base w-2/12">Score (%)</th>
                            <th class="px-2 py-2 sm:px-6 sm:py-3 text-center text-sm sm:text-base w-4/12 hidden sm:table-cell">Performance Bar</th>
                            <th class="px-2 py-2 sm:px-4 sm:py-3 text-center text-sm sm:text-base w-2/12">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employeeInformation)): ?>
                            <tr>
                                <td colspan="5" class="px-2 py-3 sm:px-4 sm:py-4 text-center text-sm sm:text-base text-gray-500">No employee data available.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employeeInformation as $index => $employee): ?>
                            <tr class="border-b hover:bg-gray-100">
                                <td class="px-2 py-3 sm:px-4 sm:py-4 text-left text-sm sm:text-base"><?= $index + 1 ?></td>
                                <td class="px-2 py-3 sm:px-4 sm:py-4 text-left text-sm sm:text-base">
                                    <form method="POST" class="flex items-center gap-2">
                                        <input type="hidden" name="action" value="edit_employee">
                                        <input type="hidden" name="employeeId" value="<?= $employee['id'] ?>">
                                        <input type="text" name="newName" value="<?= htmlspecialchars($employee['name']) ?>" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                </td>
                                <td class="px-2 py-3 sm:px-2 sm:py-4 text-center text-sm sm:text-base">
                                        <input type="number" name="newScore" value="<?= $employee['performanceScore'] ?>" class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-sm" min="0" max="100">
                                </td>
                                <td class="px-2 py-3 sm:px-6 sm:py-4 hidden sm:table-cell">
                                    <div class="mx-auto bg-gray-300 rounded-full h-5 overflow-hidden flex items-center" style="width: 200px;">
                                        <div
                                            class="h-5 rounded-full performance-bar transition-width ease-out flex items-center justify-end pr-2 animated-bar"
                                            data-width="<?= $employee['performanceScore'] ?>"
                                            data-score="<?= $employee['performanceScore'] ?>"
                                            style="width: 0%;"
                                        >
                                            <span class="text-xs font-semibold score-text" style="display: none;">
                                                <?= $employee['performanceScore'] ?>%
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 py-3 sm:px-4 sm:py-4 text-center text-sm sm:text-base">
                                        <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white text-sm px-3 py-1 rounded mr-2">Update</button>
                                    </form>
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                        <input type="hidden" name="action" value="delete_employee">
                                        <input type="hidden" name="employeeId" value="<?= $employee['id'] ?>">
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-sm px-3 py-1 rounded">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include 'footer.php'; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let animationTriggered = false;
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !animationTriggered) {
                        animationTriggered = true;
                        fillBars();
                        observer.disconnect();
                    }
                });
            }, { threshold: 0.1 });

            const tableContainer = document.getElementById('tableContainer');
            if (tableContainer) observer.observe(tableContainer);

            function fillBars() {
                const bars = document.querySelectorAll('.animated-bar');
                bars.forEach(bar => {
                    const targetWidth = bar.getAttribute('data-width');
                    const score = parseInt(bar.getAttribute('data-score'));
                    const scoreText = bar.querySelector('.score-text');

                    setTimeout(() => {
                        bar.style.width = targetWidth + '%';
                        setTimeout(() => {
                            scoreText.className = score > 10 ? 'text-xs font-semibold text-white' : 'text-xs font-semibold text-gray-700 pl-2';
                            scoreText.style.display = 'inline';
                        }, 500);
                    }, 100);
                });
            }

            const toggleButton = document.getElementById('sidebarToggle');
            if (toggleButton) {
                toggleButton.addEventListener('click', () => {
                    document.body.classList.toggle('sidebar-collapsed');
                });
            }

            // Page Entrance Animation
            function initPageEntrance() {
                const mainContent = document.getElementById('main-content');
                if (mainContent) {
                    mainContent.offsetWidth;

                    mainContent.classList.remove('page-entrance');

                    document.querySelectorAll('.directory-element').forEach((element, index) => {
                        // Apply the 'animate-in' class with staggered delays
                        element.classList.remove('delay-100', 'delay-200', 'delay-300', 'delay-400');
                        element.classList.remove('animate-in');
                        void element.offsetWidth;
                        element.classList.add(`delay-${(index % 4 + 1) * 100}`);
                        element.classList.add('animate-in');
                    });
                }
            }

            // Page Exit Animation
            document.body.addEventListener('click', function(event) {
                const link = event.target.closest('a');

                if (link && link.href && link.target !== '_blank' && !link.href.startsWith(window.location.href.split('#')[0] + '#')) {
                    event.preventDefault();
                    const destination = link.href;

                    // Apply the exit animation class
                    document.body.classList.add('page-exit');

                    // Wait for animation to complete, then navigate
                    setTimeout(() => {
                        window.location.href = destination;
                    }, 500); // Duration must match the CSS transition time (0.5s)
                }
            });

            // Initialize page entrance on load
            initPageEntrance();
        });
    </script>
</body>
</html>