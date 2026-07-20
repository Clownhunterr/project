<?php
require 'admin_auth_check.php';
require '../database/db.php';

$actionError = '';
$actionMessage = '';

if (isset($_GET['promote']) || isset($_GET['demote'])) {
    $targetId = (int) ($_GET['promote'] ?? $_GET['demote']);
    $newRole = isset($_GET['promote']) ? 'admin' : 'customer';

    if ($targetId === (int) $_SESSION['admin_id']) {
        $actionError = "You can't change your own role while logged in as that account.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$newRole, $targetId]);
        $actionMessage = "Role updated.";
    }
}

if (isset($_GET['delete'])) {
    $targetId = (int) $_GET['delete'];

    if ($targetId === (int) $_SESSION['admin_id']) {
        $actionError = "You can't delete the account you're currently logged in as.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$targetId]);
            $actionMessage = "User deleted.";
        } catch (PDOException $e) {
            $actionError = "Can't delete this user — they have existing bookings or wishlist items linked to their account.";
        }
    }
}

$users = $pdo->query("
    SELECT u.user_id, u.name, u.email, u.role, u.created_at,
           COUNT(b.booking_id) AS booking_count,
           COALESCE(SUM(CASE WHEN b.status = 'confirmed' THEN b.total_amount ELSE 0 END), 0) AS total_spent
    FROM users u
    LEFT JOIN bookings b ON u.user_id = b.user_id
    GROUP BY u.user_id, u.name, u.email, u.role, u.created_at
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="admin.css" />
    <title>CineBooking | Manage Users</title>
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <div class="admin-layout">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-main">
            <h1 class="admin-page-title">Manage Users</h1>
            <p class="admin-welcome">All registered accounts and their booking activity.</p>

            <?php if ($actionError): ?>
                <div class="admin-alert admin-alert-error"><?php echo htmlspecialchars($actionError); ?></div>
            <?php endif; ?>
            <?php if ($actionMessage): ?>
                <div class="admin-alert admin-alert-success"><?php echo htmlspecialchars($actionMessage); ?></div>
            <?php endif; ?>

            <?php if (count($users) === 0): ?>
                <div class="admin-empty">No users have registered yet.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Bookings</th>
                            <th>Total Spent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo htmlspecialchars($u['role']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($u['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                <td><?php echo (int) $u['booking_count']; ?></td>
                                <td>Rs. <?php echo number_format($u['total_spent'], 2); ?></td>
                                <td>
                                    <div class="action-group">
                                        <a href="user_details.php?id=<?php echo $u['user_id']; ?>"
                                            class="btn-admin btn-admin-outline btn-sm">
                                            <i class="fa-solid fa-eye"></i> View
                                        </a>
                                        <?php if ((int) $u['user_id'] !== (int) $_SESSION['admin_id']): ?>
                                            <?php if ($u['role'] === 'admin'): ?>
                                                <a href="manage_users.php?demote=<?php echo $u['user_id']; ?>"
                                                    class="btn-admin btn-admin-outline btn-sm"
                                                    onclick="return confirm('Remove admin access from this user?');">
                                                    <i class="fa-solid fa-arrow-down"></i> Demote
                                                </a>
                                            <?php else: ?>
                                                <a href="manage_users.php?promote=<?php echo $u['user_id']; ?>"
                                                    class="btn-admin btn-admin-outline btn-sm"
                                                    onclick="return confirm('Make this user an admin?');">
                                                    <i class="fa-solid fa-arrow-up"></i> Promote
                                                </a>
                                            <?php endif; ?>
                                            <a href="manage_users.php?delete=<?php echo $u['user_id']; ?>"
                                                class="btn-admin btn-admin-danger btn-sm"
                                                onclick="return confirm('Delete this user? This cannot be undone.');">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

</body>

</html>