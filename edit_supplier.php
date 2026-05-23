<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}

include "db_connect.php";
include "includes/functions.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: procurement_suppliers.php");
    exit;
}

$supplier_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT supplier_name, contact_person, contact_number, email, address
    FROM suppliers
    WHERE supplier_id = ?
");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: procurement_suppliers.php");
    exit;
}

$supplier = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Supplier | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>

    <div class="main">
        <?php include "layouts/header.php"; ?>

        <div class="content">
            <h2>Edit Supplier</h2>

            <div class="card">
                <form method="POST" action="api/update_supplier.php">
                    <input type="hidden" name="supplier_id" value="<?= $supplier_id; ?>">

                    <table width="100%" cellpadding="8">
                        <tr>
                            <td width="25%">Supplier Name</td>
                            <td>
                                <input type="text" name="supplier_name"
                                       value="<?= htmlspecialchars($supplier['supplier_name']); ?>"
                                       required style="width:100%; padding:8px;">
                            </td>
                        </tr>
                        <tr>
                            <td>Contact Person</td>
                            <td>
                                <input type="text" name="contact_person"
                                       value="<?= htmlspecialchars($supplier['contact_person']); ?>"
                                       style="width:100%; padding:8px;">
                            </td>
                        </tr>
                        <tr>
                            <td>Contact Number</td>
                            <td>
                                <input type="text" name="contact_number"
                                       value="<?= htmlspecialchars($supplier['contact_number']); ?>"
                                       style="width:100%; padding:8px;">
                            </td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td>
                                <input type="email" name="email"
                                       value="<?= htmlspecialchars($supplier['email']); ?>"
                                       style="width:100%; padding:8px;">
                            </td>
                        </tr>
                        <tr>
                            <td>Address</td>
                            <td>
                                <textarea name="address" rows="3"
                                          style="width:100%; padding:8px;"><?= htmlspecialchars($supplier['address']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Update Supplier</button>
                                <a href="procurement_suppliers.php" class="btn btn-secondary">Cancel</a>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>

        <?php include "layouts/footer.php"; ?>
    </div>
</div>

</body>
</html>
