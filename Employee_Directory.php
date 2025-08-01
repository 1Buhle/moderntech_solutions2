<?php
session_start();
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO employees (name, position, department, salary, employment_history, contact, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'], $_POST['position'], $_POST['department'], $_POST['salary'],
                    $_POST['employment_history'], $_POST['contact'], $_POST['photo']
                ]);
                echo json_encode(['success' => true]);
                break;
            case 'edit':
                $stmt = $pdo->prepare("UPDATE employees SET name=?, position=?, department=?, salary=?, employment_history=?, contact=?, photo=? WHERE employee_id=?");
                $stmt->execute([
                    $_POST['name'], $_POST['position'], $_POST['department'], $_POST['salary'],
                    $_POST['employment_history'], $_POST['contact'], $_POST['photo'], $_POST['employee_id']
                ]);
                echo json_encode(['success' => true]);
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM employees WHERE employee_id = ?");
                $stmt->execute([$_POST['employee_id']]);
                echo json_encode(['success' => true]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
$search = $_GET['search'] ?? '';
$query = "SELECT employee_id, name, position, department, salary, employment_history, contact, photo FROM employees";
$params = [];
if ($search) {
    $query .= " WHERE name LIKE ?";
    $params[] = "%$search%";
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
        
        #page-wrapper { transition: margin-left 0.3s cubic-bezier(.4,2,.6,1);}
        @media (max-width:1024px){#page-wrapper,.page-wrapper-collapsed{margin-left:0!important;}}
        table { border-collapse: collapse; width: 100%; }
        th, td { text-align: left; padding: 8px; }
        th { background-color: #f2f2f2; font-weight: 600; }
        tr:hover { background-color: #f5f5f5; }
        .rounded { border-radius: 0.5rem; }
        .shadow { box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        #heading{margin-right:20%;}
        #table-wrapper { display: flex; justify-content: center; align-items: center; min-height: 60vh; margin-left: -120px;}
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 50; display: none; align-items: center; justify-content: center;}
        .modal-overlay.show { display: flex;}
        .modal-content { background: white; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); max-width: 32rem; width: 100%; padding: 2rem; position: relative; transform: scale(0.7); opacity: 0; transition: opacity 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1); max-height: 90vh; overflow-y: auto;}
        .modal-content.show { transform: scale(1); opacity: 1;}
        .close-btn { position: absolute; top: 1rem; right: 1rem; color: #9CA3AF; font-size: 1.5rem; cursor: pointer; background: none; border: none;}
        .close-btn:hover { color: #6B7280;}
        .employee-photo-modal { width: 5rem; height: 5rem; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem;}
        .modal-table { width: 100%; margin-bottom: 1rem;}
        .modal-table td { padding: 0.25rem 0.5rem 0.25rem 0;}
        .modal-table .font-semibold { font-weight: 600;}
        .content-disabled { pointer-events: none; user-select: none; filter: blur(3px);}
        .sidebar-disabled { filter: blur(3px);}
        #close{background-color:#f08331;} #close:hover{background-color:#e76e37;}
        .form-input { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;}
        .form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1);}
        .form-label { display: block; font-weight: 600; margin-bottom: 0.25rem; color: #374151;}
        .form-group { margin-bottom: 1rem;}
        .btn-primary { background-color: #14b8a6; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; cursor: pointer; font-weight: 500;}
        .btn-primary:hover { background-color: #0f766e;}
        .btn-secondary { background-color: #6b7280; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; cursor: pointer; font-weight: 500;}
        .btn-secondary:hover { background-color: #4b5563;}
        .btn-danger { background-color: #dc2626; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; cursor: pointer; font-weight: 500;}
        .btn-danger:hover { background-color: #b91c1c;}
        .btn-teal { background-color: #14b8a6; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; cursor: pointer; font-weight: 500;}
        .btn-teal:hover { background-color: #0f766e;}
        .btn-small { padding: 0.25rem 0.5rem; font-size: 0.75rem;}
        
        /* --- ENTRANCE ANIMATION STYLES --- */
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
        .delay-600 { transition-delay: 0.6s; }
        .delay-700 { transition-delay: 0.7s; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'Sidebar.php'; ?>
    <div id="page-wrapper" class="flex flex-col min-h-screen">
        <main id="main-content" class="flex-grow p-8 page-entrance">
            <div id="content-wrapper" class="content-wrapper">
                <form method="get" class="flex items-center justify-end mb-4 directory-element delay-100" onsubmit="return false;">
                    <input type="text" id="searchInput" name="search" placeholder="Enter employee name"
                        value="<?= htmlspecialchars($search) ?>"
                        class="border border-gray-300 rounded px-3 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        autocomplete="off">
                </form>
                <div class="flex items-center justify-center mb-4 relative directory-element delay-200">
                    <h2 class="text-2xl font-bold" id="heading">Employee Directory</h2>
                    <button onclick="showAddEmployeeModal()" class="w-8 h-8 bg-gray-500 hover:bg-gray-600 text-white rounded-full flex items-center justify-center text-lg font-bold transition-colors duration-200 ml-8" title="Add New Employee">+</button>
                </div>
                <div id="table-wrapper" class="w-full directory-element delay-300">
                    <table id="employeeTable" class="w-full bg-white rounded shadow">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 border-b">Photo</th>
                                <th class="px-4 py-2 border-b">ID</th>
                                <th class="px-4 py-2 border-b">Name</th>
                                <th class="px-4 py-2 border-b">Position</th>
                                <th class="px-4 py-2 border-b">Department</th>
                                <th class="px-4 py-2 border-b">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr class="hover:bg-gray-100 cursor-pointer" onclick="showEmployee(<?= htmlspecialchars(json_encode($emp)) ?>)">
                                <td class="px-4 py-2 border-b">
                                    <?php
                                    $imgSrc = '';
                                    if (!empty($emp['photo'])) {
                                        $imgSrc = htmlspecialchars($emp['photo']);
                                    } else {
                                        $name = $emp['name'];
                                        $fallbacks = [
                                            'Sibongile Nkosi' => 'assets/Sibongile Nkosi.jpg', 'Lungile Moyo' => 'assets/Lungile Moyo.jpg',
                                            'Thabo Molefe' => 'assets/Thabo Molefe.jpg', 'Keshav Naidoo' => 'assets/Keshav Naidoo.jpg',
                                            'Zanele Khumalo' => 'assets/Zanele Khumalo.jpg', 'Sipho Zulu' => 'assets/Sipho Zulu.jpg',
                                            'Naledi Moeketsi' => 'assets/Naledi Moeketsi.jpg', 'Farai Gumbo' => 'assets/Farai Gumbo.jpg',
                                            'Karabo Dlamini' => 'assets/Karabo Dlamini.jpg', 'Fatima Patel' => 'assets/Fatima Patel.jpg'
                                        ];
                                        if (isset($fallbacks[$name])) { $imgSrc = $fallbacks[$name]; }
                                    }
                                    if ($imgSrc):
                                    ?>
                                        <img src="<?= $imgSrc ?>" alt="Employee Photo" class="w-12 h-12 rounded-full object-cover" />
                                    <?php else: ?>
                                        <span class="inline-block w-12 h-12 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center"></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2 border-b"><?= htmlspecialchars($emp['employee_id']) ?></td>
                                <td class="px-4 py-2 border-b"><?= htmlspecialchars($emp['name']) ?></td>
                                <td class="px-4 py-2 border-b"><?= htmlspecialchars($emp['position']) ?></td>
                                <td class="px-4 py-2 border-b"><?= htmlspecialchars($emp['department']) ?></td>
                                <td class="px-4 py-2 border-b" onclick="event.stopPropagation()">
                                    <button onclick="showEditEmployeeModal(<?= htmlspecialchars(json_encode($emp)) ?>)" class="btn-teal btn-small mr-1">Edit</button>
                                    <button onclick="deleteEmployee(<?= $emp['employee_id'] ?>)" class="btn-danger btn-small">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php include 'footer.php'; ?>
    </div>
    <div id="modal-overlay" class="modal-overlay" onclick="closeModalOnOverlay(event)">
        <div id="modal-content" class="modal-content">
            <button class="close-btn" onclick="closeModal()" aria-label="Close">&times;</button>
            <div class="flex flex-col items-center mb-4">
                <img id="modal-photo" src="" alt="Employee Photo" class="employee-photo-modal" style="display: none;" />
                <span id="modal-photo-placeholder" class="inline-block w-20 h-20 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center mb-2" style="display: none;"></span>
                <h2 id="modal-name" class="text-2xl font-bold"></h2>
            </div>
            <table class="modal-table">
                <tbody>
                    <tr><td class="font-semibold pr-2">ID:</td><td id="modal-id"></td></tr>
                    <tr><td class="font-semibold pr-2">Name:</td><td id="modal-name-field"></td></tr>
                    <tr><td class="font-semibold pr-2">Position:</td><td id="modal-position"></td></tr>
                    <tr><td class="font-semibold pr-2">Department:</td><td id="modal-department"></td></tr>
                    <tr><td class="font-semibold pr-2">Salary:</td><td id="modal-salary"></td></tr>
                    <tr><td class="font-semibold pr-2">Employment History:</td><td id="modal-employment-history"></td></tr>
                    <tr><td class="font-semibold pr-2">Contact:</td><td id="modal-contact"></td></tr>
                </tbody>
            </table>
            <button class="w-full py-2 rounded text-white mt-4" onclick="closeModal()" id="close">Close</button>
        </div>
    </div>
    <div id="form-modal-overlay" class="modal-overlay" onclick="closeFormModalOnOverlay(event)">
        <div id="form-modal-content" class="modal-content">
            <button class="close-btn" onclick="closeFormModal()" aria-label="Close">&times;</button>
            <h2 id="form-modal-title" class="text-2xl font-bold mb-4">Add New Employee</h2>
            <form id="employee-form">
                <input type="hidden" id="form-employee-id" name="employee_id">
                <input type="hidden" id="form-action" name="action" value="add">
                <div class="form-group"><label class="form-label" for="form-name">Name</label><input type="text" id="form-name" name="name" class="form-input" required></div>
                <div class="form-group"><label class="form-label" for="form-position">Position</label><input type="text" id="form-position" name="position" class="form-input" required></div>
                <div class="form-group"><label class="form-label" for="form-department">Department</label><input type="text" id="form-department" name="department" class="form-input" required></div>
                <div class="form-group"><label class="form-label" for="form-salary">Salary</label><input type="text" id="form-salary" name="salary" class="form-input"></div>
                <div class="form-group"><label class="form-label" for="form-employment-history">Employment History</label><textarea id="form-employment-history" name="employment_history" class="form-input" rows="3"></textarea></div>
                <div class="form-group"><label class="form-label" for="form-contact">Contact</label><input type="text" id="form-contact" name="contact" class="form-input"></div>
                <div class="form-group"><label class="form-label" for="form-photo">Photo URL</label><input type="url" id="form-photo" name="photo" class="form-input"></div>
                <div class="flex gap-2 mt-6">
                    <button type="submit" id="form-submit-btn" class="btn-primary flex-1">Add Employee</button>
                    <button type="button" onclick="closeFormModal()" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
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
        
        function applyMainContentMargin() {
            const sidebarCollapsed = document.cookie.includes('sidebarCollapsed=true');
            const pageWrapper = document.getElementById('page-wrapper');
            if (window.innerWidth <= 1024) {
                pageWrapper.classList.remove('page-wrapper-collapsed');
                pageWrapper.style.marginLeft = '0';
            } else if (sidebarCollapsed) {
                pageWrapper.classList.add('page-wrapper-collapsed');
                pageWrapper.style.marginLeft = '6rem';
            } else {
                pageWrapper.classList.remove('page-wrapper-collapsed');
                pageWrapper.style.marginLeft = '16rem';
            }
        }
        
        window.addEventListener('resize', applyMainContentMargin);
        
        document.addEventListener('DOMContentLoaded', function() {
            initPageEntrance();
            applyMainContentMargin();
            
            // Page Exit Animation
            document.body.addEventListener('click', function(event) {
                const link = event.target.closest('a');
                
                if (link && link.href && link.target !== '_blank' && !link.href.startsWith(window.location.href + '#')) {
                    event.preventDefault(); // Stop navigation
                    const destination = link.href;

                    // Apply the exit animation class
                    document.body.classList.add('page-exit');

                    setTimeout(() => {
                        window.location.href = destination;
                    }, 500);
                }
            });
        });
        
        document.getElementById('searchInput').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#employeeTable tbody tr');
            rows.forEach(row => {
                const nameCell = row.cells[2];
                if (nameCell) {
                    const name = nameCell.textContent.toLowerCase();
                    row.style.display = name.includes(filter) ? '' : 'none';
                }
            });
        });
        function showEmployee(employee) {
            document.getElementById('modal-name').textContent = employee.name;
            document.getElementById('modal-id').textContent = employee.employee_id;
            document.getElementById('modal-name-field').textContent = employee.name;
            document.getElementById('modal-position').textContent = employee.position;
            document.getElementById('modal-department').textContent = employee.department;
            document.getElementById('modal-salary').textContent = employee.salary || '-';
            document.getElementById('modal-employment-history').textContent = employee.employment_history || '-';
            document.getElementById('modal-contact').textContent = employee.contact || '-';
            const modalPhoto = document.getElementById('modal-photo');
            const modalPhotoPlaceholder = document.getElementById('modal-photo-placeholder');
            const fallbacks = {
                'Sibongile Nkosi': 'assets/Sibongile Nkosi.jpg',
                'Lungile Moyo': 'assets/Lungile Moyo.jpg',
                'Thabo Molefe': 'assets/Thabo Molefe.jpg',
                'Keshav Naidoo': 'assets/Keshav Naidoo.jpg',
                'Zanele Khumalo': 'assets/Zanele Khumalo.jpg',
                'Sipho Zulu': 'assets/Sipho Zulu.jpg',
                'Naledi Moeketsi': 'assets/Naledi Moeketsi.jpg',
                'Farai Gumbo': 'assets/Farai Gumbo.jpg',
                'Karabo Dlamini': 'assets/Karabo Dlamini.jpg',
                'Fatima Patel': 'assets/Fatima Patel.jpg'
            };
            let imgSrc = employee.photo || fallbacks[employee.name] || '';
            if (imgSrc) {
                modalPhoto.src = imgSrc;
                modalPhoto.alt = employee.name;
                modalPhoto.style.display = 'block';
                modalPhotoPlaceholder.style.display = 'none';
            } else {
                modalPhoto.style.display = 'none';
                modalPhotoPlaceholder.style.display = 'flex';
            }
            showModal('modal-overlay', 'modal-content');
        }
        function closeModal() { hideModal('modal-overlay', 'modal-content'); }
        function closeModalOnOverlay(event) { if (event.target.id === 'modal-overlay') closeModal(); }
        function showAddEmployeeModal() {
            document.getElementById('form-modal-title').textContent = 'Add New Employee';
            document.getElementById('form-submit-btn').textContent = 'Add Employee';
            document.getElementById('form-action').value = 'add';
            document.getElementById('employee-form').reset();
            document.getElementById('form-employee-id').value = '';
            showModal('form-modal-overlay', 'form-modal-content');
        }
        function showEditEmployeeModal(employee) {
            document.getElementById('form-modal-title').textContent = 'Edit Employee';
            document.getElementById('form-submit-btn').textContent = 'Update Employee';
            document.getElementById('form-action').value = 'edit';
            document.getElementById('form-employee-id').value = employee.employee_id;
            document.getElementById('form-name').value = employee.name;
            document.getElementById('form-position').value = employee.position;
            document.getElementById('form-department').value = employee.department;
            document.getElementById('form-salary').value = employee.salary || '';
            document.getElementById('form-employment-history').value = employee.employment_history || '';
            document.getElementById('form-contact').value = employee.contact || '';
            document.getElementById('form-photo').value = employee.photo || '';
            showModal('form-modal-overlay', 'form-modal-content');
        }
        function closeFormModal() { hideModal('form-modal-overlay', 'form-modal-content'); }
        function closeFormModalOnOverlay(event) { if (event.target.id === 'form-modal-overlay') closeFormModal(); }
        function showModal(overlayId, contentId) {
            const overlay = document.getElementById(overlayId);
            const content = document.getElementById(contentId);
            const contentWrapper = document.getElementById('content-wrapper');
            const sidebar = document.getElementById('main-sidebar');
            overlay.classList.add('show');
            contentWrapper.classList.add('content-disabled');
            if (sidebar) sidebar.classList.add('sidebar-disabled');
            setTimeout(() => { content.classList.add('show'); }, 10);
        }
        function hideModal(overlayId, contentId) {
            const overlay = document.getElementById(overlayId);
            const content = document.getElementById(contentId);
            const contentWrapper = document.getElementById('content-wrapper');
            const sidebar = document.getElementById('main-sidebar');
            content.classList.remove('show');
            contentWrapper.classList.remove('content-disabled');
            if (sidebar) sidebar.classList.remove('sidebar-disabled');
            setTimeout(() => { overlay.classList.remove('show'); }, 300);
        }
        document.getElementById('employee-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeFormModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => { alert('Error: ' + error.message); });
        });
        function deleteEmployee(employeeId) {
            if (confirm('Are you sure you want to delete this employee?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('employee_id', employeeId);
                fetch('', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error: ' + data.message);
                })
                .catch(error => { alert('Error: ' + error.message); });
            }
        }
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeFormModal();
            }
        });
    </script>
</body>
</html>