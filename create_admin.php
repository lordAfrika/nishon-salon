<?php
// create_admin.php - Protected admin creation page with strict authentication
require_once __DIR__ . '/client.php';
require_once __DIR__ . '/csrf.php';
// Start session only if not already active (avoid duplicate session_start notices)
if (function_exists('session_status')) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
} else {
    // Fallback for very old PHP versions
    if (session_id() === '') {
        session_start();
    }
}

// Functions
function has_any_admin() {
    $pdo = db_connect();
    $stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 1');
    return (int)$stmt->fetchColumn() > 0;
}

// Initialize variables
$auth_timeout = 300; // 5 minutes timeout
$message = '';
$message_type = 'info';
$mode = 'login'; // Always start with login mode

// Initialize session variables if not set
if (!isset($_SESSION['create_admin_auth_time'])) {
    $_SESSION['create_admin_auth_time'] = 0;
}

// Check for session timeout
if ($_SESSION['create_admin_auth_time'] < time() - $auth_timeout) {
    unset($_SESSION['create_admin_auth']);
    unset($_SESSION['create_admin_user_id']);
}

// First check if there are any admins
$has_admins = has_any_admin();

// CSRF protection for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $message = 'Security check failed. Please try again.';
    $message_type = 'error';
    $mode = 'login';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'login':
            $email = trim($_POST['email'] ?? '');
            $pass = $_POST['password'] ?? '';
            $res = verify_login($email, $pass);
            
            if ($res['success'] && $res['is_admin']) {
                $_SESSION['create_admin_auth'] = true;
                $_SESSION['create_admin_auth_time'] = time();
                $_SESSION['create_admin_user_id'] = $res['user_id'];
                session_regenerate_id(true);
                
                $mode = 'create';
                $message = 'Authenticated for admin creation page';
                $message_type = 'success';
            } else {
                $message = $res['success'] ? 'Access denied: Admin privileges required' : 'Login failed: ' . $res['error'];
                $message_type = 'error';
                $mode = 'login';
            }
            break;

        case 'create':
            // Only process if properly authenticated or no admins exist
            if (!$has_admins || (isset($_SESSION['create_admin_auth']) && 
                $_SESSION['create_admin_auth_time'] >= time() - $auth_timeout)) {
                
                $name = trim($_POST['full_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass = $_POST['password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';

                if ($name === '' || $email === '' || $pass === '' || $confirm === '') {
                    $message = 'Please fill all fields';
                    $message_type = 'error';
                } elseif ($pass !== $confirm) {
                    $message = 'Passwords do not match';
                    $message_type = 'error';
                } elseif (strlen($pass) < 8) {
                    $message = 'Password must be at least 8 characters';
                    $message_type = 'error';
                } else {
                    $res = register_user($name, $email, '', $pass, 1);
                    if ($res['success']) {
                        $message = 'Admin created successfully.';
                        $message_type = 'success';
                        if (!$has_admins) {
                            $_SESSION['create_admin_auth'] = true;
                            $_SESSION['create_admin_auth_time'] = time();
                            $_SESSION['create_admin_user_id'] = $res['user_id'];
                            session_regenerate_id(true);
                        }
                    } else {
                        $message = 'Error: ' . $res['error'];
                        $message_type = 'error';
                    }
                }
            } else {
                $message = 'Authentication required';
                $message_type = 'error';
                $mode = 'login';
            }
            break;

        case 'logout':
            session_destroy();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
    }
}

// Determine page mode based on authentication status if not already set
if ($has_admins && $mode !== 'create') {
    if (!isset($_SESSION['create_admin_auth']) || 
        $_SESSION['create_admin_auth_time'] < time() - $auth_timeout) {
        $mode = 'login';
        if (isset($_SESSION['create_admin_auth'])) {
            $message = 'Session expired. Please login again.';
            $message_type = 'info';
            unset($_SESSION['create_admin_auth']);
        }
    } else {
        $user = get_user_by_id($_SESSION['create_admin_user_id']);
        if (!$user || !$user['is_admin']) {
            $mode = 'login';
            $message = 'Invalid session. Please login again.';
            $message_type = 'error';
            unset($_SESSION['create_admin_auth']);
        }
    }
} elseif (!$has_admins && $mode === 'login') {
    // No admins exist - allow creation of first admin
    $mode = 'create';
    $message = 'Create the first admin account';
    $message_type = 'info';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <title><?php echo $mode === 'create' ? 'Create Admin' : 'Admin Authentication Required'; ?></title>
    <style>
        .alert-error { background: rgba(220,53,69,0.1); border-color: rgba(220,53,69,0.25); color: #dc3545; }
        .alert-success { background: rgba(25,135,84,0.1); border-color: rgba(25,135,84,0.25); color: #198754; }
        .alert-info { background: rgba(13,202,240,0.1); border-color: rgba(13,202,240,0.25); color: #0dcaf0; }
    </style>
</head>
<body class="p-4 bg-dark text-white">
    <div class="container">
        <h1><?php echo $mode === 'create' ? 'Create Admin Account' : 'Admin Authentication Required'; ?></h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($mode === 'login'): ?>
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body">
                    <p class="text-info">
                        This page requires specific authentication. Even if you're already logged in elsewhere,
                        you must authenticate again to access the admin creation functionality.
                    </p>
                </div>
            </div>
            
            <form method="post" style="max-width:480px">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label">Admin Email</label>
                    <input name="email" type="email" class="form-control" required 
                           autofocus autocomplete="email">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input name="password" type="password" class="form-control" required
                           autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-brand">Authenticate</button>
                    <a href="index.php" class="btn btn-outline-brand ms-2">Back to Site</a>
                </div>
            </form>

        <?php else: /* mode === 'create' */ ?>
            <?php if (!$has_admins): ?>
                <div class="card bg-dark border-info mb-4">
                    <div class="card-body">
                        <p class="text-info">
                            No admin accounts exist yet. Create the first admin account below.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" style="max-width:480px" onsubmit="return validateForm(this);">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input name="full_name" class="form-control" required 
                           minlength="2" maxlength="150" pattern="[A-Za-z0-9\s\-'.]+"
                           title="Name can contain letters, numbers, spaces, hyphens and periods">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input name="password" type="password" class="form-control" required 
                           minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                           title="Password must be at least 8 characters and include uppercase, lowercase and numbers">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input name="confirm_password" type="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-brand">Create Admin Account</button>
                    
                    <?php if (isset($_SESSION['create_admin_auth'])): ?>
                        <form method="post" style="display:inline-block">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="btn btn-outline-brand ms-2">Logout</button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($has_admins): ?>
                        <a href="database.php" class="btn btn-outline-brand ms-2">Back to Admin Panel</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    function validateForm(form) {
        const pass = form.password.value;
        const confirm = form.confirm_password.value;
        
        if (pass !== confirm) {
            alert('Passwords do not match');
            return false;
        }
        
        if (pass.length < 8) {
            alert('Password must be at least 8 characters');
            return false;
        }
        
        if (!/[A-Z]/.test(pass) || !/[a-z]/.test(pass) || !/[0-9]/.test(pass)) {
            alert('Password must include uppercase, lowercase and numbers');
            return false;
        }
        
        return true;
    }
    </script>
</body>
</html>
