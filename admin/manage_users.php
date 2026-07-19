<?php
require 'admin_auth_check.php';
require '../database/db.php';

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
                                    <a href="user_details.php?id=<?php echo $u['user_id']; ?>"
                                        class="btn-admin btn-admin-outline btn-sm">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
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