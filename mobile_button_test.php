<?php
// Simple test page to verify mobile button clickability
session_start();

// If not logged in, redirect to login (optional for testing)
// If logged in or for testing purposes
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$_SESSION['username'] = $_SESSION['username'] ?? 'test';
$_SESSION['role'] = $_SESSION['role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Button Test - LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: Arial, sans-serif; }
        .test-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .test-info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .viewport-info { background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        button.test-button { padding: 10px 20px; margin: 5px; cursor: pointer; font-size: 16px; }
        .click-counter { font-size: 24px; font-weight: bold; color: var(--primary); margin: 20px 0; }
    </style>
</head>
<body>
    <?php include "layouts/header.php"; ?>
    <?php include "layouts/sidebar.php"; ?>
    
    <div class="sidebar-overlay"></div>
    
    <div class="content">
        <div class="test-container">
            <h1>Mobile Button Clickability Test</h1>
            
            <div class="test-info">
                <h3>Test Instructions:</h3>
                <ul>
                    <li>View this page on a mobile device or mobile emulator</li>
                    <li>The hamburger menu (☰) button in the header should be visible and clickable</li>
                    <li>Clicking the button should open/toggle the sidebar</li>
                    <li>Viewport width should be reported below</li>
                </ul>
            </div>
            
            <div class="viewport-info">
                <strong>Current Viewport Width:</strong> <span id="viewport-width">Loading...</span>px
                <br><strong>Mobile View Active:</strong> <span id="mobile-active">Loading...</span>
            </div>
            
            <div class="test-info">
                <h3>Test Results:</h3>
                <p><strong>Mobile Menu Toggle Clicks:</strong></p>
                <div class="click-counter">
                    Button Clicks: <span id="button-click-count">0</span>
                </div>
                <p><strong>Last Action:</strong> <span id="last-action">None</span></p>
            </div>
            
            <div class="test-info">
                <h3>Button CSS Properties (Inspector):</h3>
                <p>Right-click the ☰ button in the header and select "Inspect" to check CSS properties</p>
                <p>Key properties to verify:</p>
                <ul>
                    <li><code>display: flex</code></li>
                    <li><code>pointer-events: auto</code></li>
                    <li><code>z-index: 1055</code></li>
                    <li><code>width: 44px or 48px</code></li>
                    <li><code>height: 44px or 48px</code></li>
                </ul>
            </div>
            
            <button class="test-button" id="test-toggle">Test Toggle Button (Click Here)</button>
            <button class="test-button" id="test-log">Check Browser Console</button>
        </div>
    </div>
    
    <script>
        console.log("Test page loaded");
        
        // Update viewport info
        function updateViewportInfo() {
            const width = window.innerWidth;
            document.getElementById('viewport-width').textContent = width;
            document.getElementById('mobile-active').textContent = width <= 770 ? 'YES' : 'NO';
        }
        
        updateViewportInfo();
        window.addEventListener('resize', updateViewportInfo);
        
        // Track mobile menu toggle clicks
        let clickCount = 0;
        const mobileToggle = document.getElementById('mobileMenuToggle');
        
        if (mobileToggle) {
            console.log("Mobile toggle button found", mobileToggle);
            mobileToggle.addEventListener('click', function(e) {
                clickCount++;
                document.getElementById('button-click-count').textContent = clickCount;
                document.getElementById('last-action').textContent = 'Mobile toggle clicked at ' + new Date().toLocaleTimeString();
                console.log("Mobile toggle clicked", e);
            });
        } else {
            console.warn("Mobile toggle button NOT found");
        }
        
        // Test button
        document.getElementById('test-toggle').addEventListener('click', function() {
            if (mobileToggle) {
                mobileToggle.click();
                console.log("Test button clicked mobile toggle");
            }
        });
        
        document.getElementById('test-log').addEventListener('click', function() {
            const button = document.getElementById('mobileMenuToggle');
            if (button) {
                const styles = window.getComputedStyle(button);
                console.log("Mobile Menu Toggle Computed Styles:", {
                    display: styles.display,
                    pointerEvents: styles.pointerEvents,
                    zIndex: styles.zIndex,
                    width: styles.width,
                    height: styles.height,
                    visibility: styles.visibility,
                    opacity: styles.opacity,
                    cursor: styles.cursor
                });
                console.log("Button element:", button);
                console.log("Button offset:", {
                    offsetTop: button.offsetTop,
                    offsetLeft: button.offsetLeft,
                    offsetWidth: button.offsetWidth,
                    offsetHeight: button.offsetHeight
                });
            }
        });
    </script>
</body>
</html>
