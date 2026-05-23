<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer sign up | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="customer-register-page">

<div class="customer-reg-container">
    <div class="customer-reg-particles"></div>
    <div class="customer-reg-card">
        <div class="reg-logo-wrap">
            <?php include __DIR__ . '/layouts/logo.php'; ?>
            <p style="margin-top: 16px; color: var(--text-secondary); font-size: 13px; line-height: 1.45;">
                Lorenzana Food Corporation<br>Integrated Management System
            </p>
        </div>
        <h1>Create a customer account</h1>
        <p class="sub">Your details are saved in our customer list. Sales can select you when creating orders. Use the username and password below to sign in and order from the shop.</p>
        <div id="msg" class="msg"></div>
        <form id="regForm" autocomplete="on">
            <label for="customer_name">Full name / business name</label>
            <input type="text" id="customer_name" name="customer_name" required maxlength="100">

            <label for="contact_number">Mobile number</label>
            <input type="text" id="contact_number" name="contact_number" maxlength="20">

            <label for="email">Email (optional)</label>
            <input type="email" id="email" name="email" maxlength="100">

            <label for="address">Address</label>
            <textarea id="address" name="address" rows="2" maxlength="500"></textarea>

            <label for="portal_username">Username for login</label>
            <input type="text" id="portal_username" name="portal_username" required minlength="3" maxlength="100" autocomplete="username">
            <p class="hint">At least 3 characters. This is not your email unless you choose it to be.</p>

            <label for="portal_password">Password</label>
            <input type="password" id="portal_password" name="portal_password" required minlength="6" maxlength="128" autocomplete="new-password">

            <button type="submit" class="reg-btn">Create account</button>
        </form>
        <a class="back" href="login.php">← Back to login</a>
    </div>
</div>
<script>
document.getElementById('regForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var msg = document.getElementById('msg');
    msg.style.display = 'none';
    var fd = new FormData(this);
    fetch('api/customer_register.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            msg.style.display = 'block';
            msg.className = 'msg ' + (data.success ? 'ok' : 'err');
            msg.textContent = data.message || (data.success ? 'Done.' : 'Something went wrong.');
            if (data.success) {
                document.getElementById('regForm').reset();
            }
        })
        .catch(function() {
            msg.style.display = 'block';
            msg.className = 'msg err';
            msg.textContent = 'Network error. Try again.';
        });
});
</script>
</body>
</html>
