<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LORINIMS | Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 50%, #FFB84D 100%);
        }

        /* Background Image Layer */
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/images/lorins-products-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.65;
            z-index: 0;
            animation: subtleZoom 25s ease-in-out infinite alternate;
            filter: blur(2px) brightness(1.05);
            will-change: transform;
        }

        /* Dark Overlay for better contrast */
        .login-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg, 
                rgba(255, 107, 53, 0.65) 0%, 
                rgba(255, 140, 90, 0.60) 30%,
                rgba(255, 184, 77, 0.58) 60%,
                rgba(255, 107, 53, 0.62) 100%
            );
            z-index: 1;
            backdrop-filter: blur(0.5px);
            -webkit-backdrop-filter: blur(0.5px);
        }

        @keyframes subtleZoom {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }

        /* Animated particles overlay */
        .login-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .login-particles::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: float 25s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-50px, -50px) rotate(360deg); }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 
                0 25px 70px rgba(255, 107, 53, 0.5),
                0 10px 30px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.6s ease-out;
            position: relative;
            z-index: 10;
            border: 2px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }

        /* Subtle gradient border effect */
        .login-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #FF6B35, #FF8C5A, #FFB84D, #FF6B35);
            background-size: 300% 300%;
            border-radius: 24px;
            z-index: -1;
            opacity: 0.3;
            animation: gradientShift 8s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo {
            margin-bottom: 30px;
        }

        .login-logo p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all var(--transition-fast);
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.15);
            transform: translateY(-1px);
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
            background: linear-gradient(135deg, #E55A2B 0%, #FF6B35 100%);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #dc2626;
        }

        @media (max-width: 768px) {
            .login-container::before {
                filter: blur(4px) brightness(1.15);
                opacity: 0.35;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
                border-radius: 20px;
            }

            .login-logo h1 {
                font-size: 28px;
            }

            .login-container::before {
                filter: blur(5px) brightness(1.2);
                opacity: 0.3;
            }

            .login-container::after {
                background: linear-gradient(
                    135deg, 
                    rgba(255, 107, 53, 0.88) 0%, 
                    rgba(255, 140, 90, 0.85) 50%,
                    rgba(255, 184, 77, 0.88) 100%
                );
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-particles"></div>
    <div class="login-card">
        <div class="login-logo">
            <?php include "layouts/logo.php"; ?>
            <p style="margin-top: 20px; color: var(--text-secondary);">Lorenzana Food Corporation<br>Integrated Management System</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div data-notify="error" data-message="<?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>" style="display:none;"></div>
        <?php endif; ?>

        <form method="POST" action="login_process.php" data-loading-message="Signing in..." data-loading-subtext="Authenticating your credentials.">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus placeholder="Enter your username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>
        <div style="margin-top: 22px; text-align: center;">
            <a href="customer_register.php" class="login-btn" style="display: block; text-decoration: none; box-sizing: border-box; text-align: center; background: linear-gradient(135deg, #334155 0%, #475569 100%); box-shadow: 0 4px 15px rgba(51, 65, 85, 0.35);">
                Customer registration
            </a>
            <p style="margin-top: 14px; font-size: 14px; color: var(--text-secondary);">
                Already have a customer username? Sign in with the form above.
            </p>
        </div>
    </div>
</div>

<script src="assets/js/notifications.js" defer></script>
</body>
</html>
