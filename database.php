<?php
require_once __DIR__ . '/client.php';
// Start session only if not already active
if (function_exists('session_status')) {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
} else {
  if (session_id() === '') {
    session_start();
  }
}
require_once __DIR__ . '/csrf.php';

// Only admin allowed
if(empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])){
    header('Location: index.php'); exit;
}

$flash = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Verify CSRF token before any actions
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        $flash = 'Security check failed. Please try again.';
    } else {
        if(!empty($_POST['delete_booking'])){
            delete_booking(intval($_POST['delete_booking']));
            $flash = 'Booking deleted';
        }
        if(!empty($_POST['delete_user'])){
            delete_user(intval($_POST['delete_user']));
            $flash = 'User deleted';
        }
    }
}

$users = get_all_users();
$bookings = get_all_bookings();
?>
<!doctype html>
<html><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
  <title>Admin - Database</title>
</head><body class="p-4 bg-dark text-white">
  <div class="container">
    <h1>Admin Panel</h1>
    <?php if($flash): ?><div class="alert alert-info"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    <p><a href="index.php" class="btn btn-outline-brand">Back to site</a></p>

    <h2>Bookings</h2>
    <div class="table-responsive">
      <table class="table table-dark table-striped">
        <thead><tr><th>#</th><th>User</th><th>Name</th><th>Date</th><th>Time</th><th>Message</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($bookings as $b): ?>
          <tr>
            <td><?php echo (int)$b['id']; ?></td>
            <td><?php echo htmlspecialchars($b['user_email'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($b['full_name']); ?></td>
            <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
            <td><?php echo htmlspecialchars(substr($b['booking_time'],0,5)); ?></td>
            <td><?php echo htmlspecialchars(substr($b['message'],0,80)); ?></td>
            <td>
              <form method="post" style="display:inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="delete_booking" value="<?php echo (int)$b['id']; ?>">
                <button class="btn btn-sm btn-outline-brand">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <h2 class="mt-4">Users</h2>
    <div class="table-responsive">
      <table class="table table-dark table-striped">
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Admin</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
          <tr>
            <td><?php echo (int)$u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['phone']); ?></td>
            <td><?php echo $u['is_admin'] ? 'Yes' : 'No'; ?></td>
            <td>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete user and disassociate bookings?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="delete_user" value="<?php echo (int)$u['id']; ?>">
                <button class="btn btn-sm btn-outline-brand">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body></html>
