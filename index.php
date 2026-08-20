<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit;
}
require_once 'utils/security.php';
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Modern Supermarket POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--bg-color);
            margin: 0;
            overflow: hidden;
        }
        
        /* Dynamic Background Pattern */
        .bg-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(var(--border-color) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.5;
            z-index: -1;
        }

        .login-container {
            background: var(--card-bg);
            padding: 3rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.5s ease-out forwards;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        /* Glassmorphism top highlight */
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header .logo-icon {
            font-size: 3.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.25rem;
            transition: color var(--transition-fast);
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background-color: var(--input-bg);
            color: var(--text-color);
            font-family: inherit;
            font-size: 1rem;
            transition: all var(--transition-fast);
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-control:focus + i {
            color: var(--primary-color);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .loader {
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Demo credentials helper */
        .demo-creds {
            margin-top: 2rem;
            padding: 1rem;
            background: var(--bg-color);
            border-radius: var(--radius-md);
            border: 1px dashed var(--border-color);
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .demo-creds code {
            background: var(--card-bg);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            color: var(--primary-color);
            font-family: monospace;
        }
    </style>
</head>
<body class="light-mode">
    <div class="bg-pattern"></div>
    
    <div class="login-container">
        <div class="login-header">
            <i class="ph-fill ph-storefront logo-icon"></i>
            <h1>Welcome Back</h1>
            <p>Sign in to manage your store</p>
        </div>

        <div id="loginAlert" class="alert alert-error"></div>

        <form id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required autofocus>
                    <i class="ph ph-user"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    <i class="ph ph-lock-key"></i>
                </div>
            </div>

            <button type="submit" class="btn-primary" id="loginBtn">
                <span class="btn-text">Sign In</span>
                <div class="loader"></div>
            </button>
        </form>

        <div class="demo-creds">
            Admin: <code>admin</code> / <code>admin123</code>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const form = e.target;
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            const loader = btn.querySelector('.loader');
            const alertBox = document.getElementById('loginAlert');
            
            // Loading state
            btn.disabled = true;
            btnText.style.display = 'none';
            loader.style.display = 'block';
            alertBox.style.display = 'none';

            try {
                const formData = new FormData(form);
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    btnText.style.display = 'block';
                    loader.style.display = 'none';
                    btnText.textContent = 'Redirecting...';
                    window.location.href = data.redirect;
                } else {
                    throw new Error(data.message || 'Login failed');
                }
            } catch (error) {
                // Reset state
                btn.disabled = false;
                btnText.style.display = 'block';
                loader.style.display = 'none';
                
                // Show error
                alertBox.textContent = error.message;
                alertBox.style.display = 'block';
            }
        });
    </script>
</body>
</html>
