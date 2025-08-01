<?php
// Start a PHP session
if (session_status() == PHP_SESSION_NONE) session_start();

// Database configuration file
require_once 'config.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username'] ?? '');
    $password = htmlspecialchars($_POST['password'] ?? '');

    // Establish DB connection
    $conn = getDBConnection();

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT password FROM pwd WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password']) || $password === $row['password']) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_name'] = $username;
            $_SESSION['login_success'] = true;
            $loginSuccess = true;
        } else {
            $errorMessage = 'Incorrect username or password.';
        }
    } else {
        $errorMessage = 'Incorrect username or password.';
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        #login-container.page-exit {
            transform: scale(1.5);
            opacity: 0;
            transition: transform 0.6s ease-in-out, opacity 0.6s ease-in-out;
            transform-origin: center center;
        }

        button {
            background-color: #f08331;
            border-radius: 0.375rem;
        }

        button:hover {
            background-color: #e76e37;
            cursor: pointer;
        }

        .pwd {
            color: #f08331;
        }

        .pwd:hover {
            color: #e76e37;
        }

        input:focus {
            outline: 2px solid #f08331 !important;
            outline-offset: 2px;
        }

        .success-message {
            background: linear-gradient(90deg, #10b981, #059669);
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-top: 16px;
            font-weight: 600;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #f08331;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn-loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .zoom-out-entrance {
            transform: scale(1.2);
            opacity: 0;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .zoom-out-entrance-complete {
            transform: scale(1);
            opacity: 1;
        }
        
        .delay-100 { transition-delay: 0.1s; }
        .delay-200 { transition-delay: 0.2s; }
        .delay-300 { transition-delay: 0.3s; }
        .delay-400 { transition-delay: 0.4s; }
        .delay-500 { transition-delay: 0.5s; }

        .login-zoom-in {
            transform: scale(1.5);
            opacity: 0;
            transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div id="login-container" class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8 w-full max-w-md mx-auto">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm zoom-out-entrance delay-100">
            <img class="mx-auto h-20 w-auto rounded-md" src="assets/Logo.png" alt="Your Company" />
            <h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900">
                Sign in to your account
            </h2>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
            <form id="login-form" class="space-y-6" method="post">
                <div class="zoom-out-entrance delay-200">
                    <label for="username" class="block text-left text-sm/6 font-medium text-gray-900">Username</label>
                    <div class="mt-2">
                        <input
                            type="text"
                            name="username"
                            id="username"
                            autocomplete="username"
                            required
                            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                        />
                    </div>
                </div>

                <div class="zoom-out-entrance delay-300">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm/6 font-medium text-gray-900">Password</label>
                    </div>
                    <div class="mt-2">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            autocomplete="current-password"
                            required
                            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                        />
                    </div>
                </div>

                <div class="zoom-out-entrance delay-400">
                    <button
                        type="submit"
                        id="login-btn"
                        class="flex w-full justify-center rounded-md px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 bg-[#f08331] hover:bg-[#e76e37]"
                    >
                        <span id="btn-text">Sign in</span>
                    </button>
                </div>
                
                <?php if ($errorMessage): ?>
                <div id="error-message" class="text-red-500 text-center text-sm mt-2 zoom-out-entrance delay-500">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
                <?php endif; ?>

                <div id="success-message" class="success-message hidden zoom-out-entrance delay-500">
                    <div class="spinner"></div>
                    Login successful! Redirecting...
                </div>
            </form>
        </div>
    </div>

    <script>
        // Get the login container element
        const loginContainer = document.getElementById('login-container');

        // Initialize page entrance animations
        function initLoginEntrance() {
            setTimeout(() => {
                const elements = document.querySelectorAll('.zoom-out-entrance');
                elements.forEach(element => {
                    element.classList.add('zoom-out-entrance-complete');
                });
            }, 100);
        }

        // Check if login was successful (set by PHP)
        const loginSuccess = <?= isset($loginSuccess) ? 'true' : 'false' ?>;

        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initLoginEntrance();
            
            // Page Exit Animation
            document.body.addEventListener('click', function(event) {
                const link = event.target.closest('a');
                if (link && link.href && link.target !== '_blank' && !link.href.startsWith(window.location.href + '#')) {
                    event.preventDefault();
                    const destination = link.href;
                    loginContainer.classList.add('page-exit'); 
                    setTimeout(() => {
                        window.location.href = destination;
                    }, 600);
                }
            });

            // If login was successful, show message and redirect
            if (loginSuccess) {
                if (errorMessage) errorMessage.style.display = 'none';
                successMessage.classList.remove('hidden');
                
                // Apply zoom-in animation to all elements
                const elements = document.querySelectorAll('.zoom-out-entrance');
                elements.forEach(element => {
                    element.classList.add('login-zoom-in');
                });
                
                // Redirect after animation
                setTimeout(() => {
                    window.location.href = 'Dashboard.View.php';
                }, 700);
            }
        });

        // Handle form submission
        document.getElementById('login-form').addEventListener('submit', function(event) {
            if (!loginSuccess) {
                event.preventDefault();
                const loginBtn = document.getElementById('login-btn');
                const btnText = document.getElementById('btn-text');
                loginBtn.classList.add('btn-loading');
                btnText.innerHTML = '<div class="spinner"></div>Signing in...';
                setTimeout(() => {
                    this.submit();
                }, 300);
            }
        });

        // Hide error message on input focus
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                if (errorMessage) {
                    errorMessage.style.opacity = '0.5';
                }
            });
        });
    </script>
</body>
</html>