<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="wrapper">

    <?php include "layouts/sidebar.php"; ?>

    <div class="main">

        <?php include "layouts/header.php"; ?>

        <div class="content">
            <h2>User Management</h2>
            <p>Manage system users, roles, and permissions.</p>

            <?php include "includes/functions.php"; showMessage(); ?>
            <?php include "db_connect.php"; ?>

            <!-- Add/Edit User Form -->
            <div class="card">
                <h3 id="form-title">Add New User</h3>
                <form method="POST" action="api/save_user.php" id="userForm" data-loading-message="Saving user..." data-loading-subtext="Creating or updating user account.">
                    <input type="hidden" name="action" id="form-action" value="create">
                    <input type="hidden" name="user_id" id="form-user-id" value="">
                    <table>
                        <tr>
                            <td>Username</td>
                            <td><input type="text" name="username" id="form-username" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Password</td>
                            <td>
                                <input type="password" name="password" id="form-password" style="width:100%; padding:8px;" placeholder="Leave blank to keep current password">
                                <small style="color: var(--text-muted);">Required for new users</small>
                            </td>
                        </tr>
                        <tr>
                            <td>Full Name</td>
                            <td><input type="text" name="full_name" id="form-full-name" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td><input type="email" name="email" id="form-email" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Role</td>
                            <td>
                                <select name="role" id="form-role" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Role --</option>
                                    <option value="admin">Admin</option>
                                    <option value="production">Production</option>
                                    <option value="warehouse">Warehouse</option>
                                    <option value="qc">Quality Control</option>
                                    <option value="accounting">Accounting</option>
                                    <option value="sales">Sales</option>
                                    <option value="procurement">Procurement</option>
                                    <option value="delivery">Delivery</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="button" class="btn" onclick="resetForm()" style="margin-right: 10px; background: var(--text-muted);">Cancel</button>
                                <button type="submit" class="btn">Save User</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Users List -->
            <div class="card">
                <h3>System Users</h3>
                <?php
                $sort_usr = getSortParams('created_at', ['id', 'username', 'full_name', 'email', 'role', 'created_at']);
                $col_usr = ['id' => 'id', 'username' => 'username', 'full_name' => 'full_name', 'email' => 'email', 'role' => 'role', 'created_at' => 'created_at'];
                $order_by_usr = isset($col_usr[$sort_usr['column']]) ? $col_usr[$sort_usr['column']] : 'created_at';
                $pagination = function_exists('getPagination') ? getPagination($conn, "SELECT COUNT(*) as c FROM users") : ['offset' => 0, 'per_page' => 25];
                $users_query = "SELECT * FROM users ORDER BY " . $order_by_usr . " " . $sort_usr['order'] . " LIMIT " . $pagination['offset'] . ", " . $pagination['per_page'];
                $users_result = $conn->query($users_query);
                ?>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar">' . renderPerPageSelector($conn, $pagination['per_page']) . '</div>'; ?>
                <table>
                    <tr>
                        <th><?php echo sortHeader('id', 'ID', $sort_usr); ?></th>
                        <th><?php echo sortHeader('username', 'Username', $sort_usr); ?></th>
                        <th><?php echo sortHeader('full_name', 'Full Name', $sort_usr); ?></th>
                        <th><?php echo sortHeader('email', 'Email', $sort_usr); ?></th>
                        <th><?php echo sortHeader('role', 'Role', $sort_usr); ?></th>
                        <th><?php echo sortHeader('created_at', 'Created', $sort_usr); ?></th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                <td><span style="padding: 4px 8px; background: rgba(255, 107, 53, 0.1); color: #FF6B35; border-radius: 4px; font-weight: 600;"><?php echo ucfirst($user['role']); ?></span></td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <button class="btn" style="padding: 6px 12px; font-size: 12px; margin-right: 5px;" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn" style="padding: 6px 12px; font-size: 12px; background: #dc2626;" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($pagination); ?>
            </div>
        </div>

        <script>
        function editUser(user) {
            document.getElementById('form-title').textContent = 'Edit User';
            document.getElementById('form-action').value = 'update';
            document.getElementById('form-user-id').value = user.id;
            document.getElementById('form-username').value = user.username;
            document.getElementById('form-password').value = '';
            document.getElementById('form-password').placeholder = 'Leave blank to keep current password';
            document.getElementById('form-full-name').value = user.full_name || '';
            document.getElementById('form-email').value = user.email || '';
            document.getElementById('form-role').value = user.role;
            
            // Scroll to form
            document.getElementById('form-title').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function resetForm() {
            document.getElementById('form-title').textContent = 'Add New User';
            document.getElementById('form-action').value = 'create';
            document.getElementById('form-user-id').value = '';
            document.getElementById('userForm').reset();
            document.getElementById('form-password').placeholder = 'Required for new users';
        }

        function deleteUser(userId, username) {
            if (confirm('Are you sure you want to delete user "' + username + '"? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'api/save_user.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'user_id';
                idInput.value = userId;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        </script>

        <?php include "layouts/footer.php"; ?>

    </div>
</div>

<script src="assets/js/sidebar.js"></script>
</body>
</html>
