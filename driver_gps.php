<?php
session_start();

// Allow delivery/driver role (also permit admin for management access)
if (!isset($_SESSION['user_id']) || (
        $_SESSION['role'] != 'delivery' && 
        $_SESSION['role'] != 'driver' && 
        $_SESSION['role'] != 'admin'
    )) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Delivery Tracking | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .driver-header {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C5A 100%);
            color: white;
            padding: var(--spacing-lg);
            text-align: center;
            box-shadow: var(--shadow-md);
        }

        .driver-header h2 {
            margin: 0 0 var(--spacing-sm) 0;
            color: white;
        }

        .driver-header p {
            margin: 0;
            opacity: 0.9;
        }

        .map-placeholder {
            width: 100%;
            height: 300px;
            background: var(--bg-tertiary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            border-radius: var(--border-radius);
            border: 2px dashed var(--border-color);
            margin: var(--spacing-md) 0;
        }

        .status {
            font-weight: 600;
            color: #10b981;
            padding: 4px 12px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: var(--border-radius-sm);
            display: inline-block;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
        }

        select {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            font-size: 14px;
            margin-bottom: var(--spacing-md);
            transition: all var(--transition-fast);
        }

        select:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        input[type="file"] {
            width: 100%;
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            border: 2px dashed var(--border-color);
            border-radius: var(--border-radius-sm);
            background: var(--bg-tertiary);
            cursor: pointer;
        }
    </style>
</head>

<body>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include "layouts/sidebar.php"; ?>

    <!-- Main Area -->
    <div class="main">
        <!-- Header -->
        <?php include "layouts/header.php"; ?>

        <!-- Content -->
        <div class="content">
            <div style="text-align: center; margin-bottom: 20px;">
                <?php include "layouts/logo.php"; ?>
            </div>

            <?php include "db_connect.php"; ?>
            <?php if (!file_exists(__DIR__ . '/includes/functions.php')) { /* no-op */ } else { include __DIR__ . '/includes/functions.php'; } ?>
            <?php
            // Get current delivery assignments for this driver
            $driver_id = $_SESSION['user_id'];
            $vehicle_type = "truck"; // default style
            $current_assignment = null;
            $other_assignments = [];
            $all_assignments = [];
            $selected_assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
            $has_order_items = ($conn->query("SHOW TABLES LIKE 'order_items'")->num_rows > 0);

            // Load driver vehicle type for map icon (only if the column exists)
            $colCheck = $conn->query("SHOW COLUMNS FROM employees LIKE 'vehicle_type'");
            if ($colCheck && $colCheck->num_rows > 0) {
                $colCheck->free();
                $veh_stmt = $conn->prepare("SELECT vehicle_type FROM employees WHERE user_id = ? LIMIT 1");
                if ($veh_stmt) {
                    $veh_stmt->bind_param("i", $driver_id);
                    $veh_stmt->execute();
                    $veh_res = $veh_stmt->get_result();
                    if ($veh_row = $veh_res->fetch_assoc()) {
                        $vehicle_type = strtolower($veh_row['vehicle_type'] ?? $vehicle_type);
                    }
                    $veh_stmt->close();
                }
            }

            // Track whether the failure_reason column exists in delivery_assignments (migration may not have run)
            $has_failure_reason = $conn->query("SHOW COLUMNS FROM delivery_assignments LIKE 'failure_reason'")->num_rows > 0;
            $failure_col = $has_failure_reason ? 'da.failure_reason,' : 'NULL AS failure_reason,';

            if ($has_order_items) {
                $assignment_query = "SELECT da.*, so.order_number, so.delivery_address, so.delivery_date, so.delivery_lat, so.delivery_lng,
                    c.customer_name, c.contact_number,
                    {$failure_col}
                    COALESCE((SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = so.order_id), so.quantity) AS quantity,
                    (SELECT GROUP_CONCAT(CONCAT(p2.product_name, ' x ', oi.quantity) SEPARATOR ', ') 
                     FROM order_items oi JOIN products p2 ON oi.product_id = p2.product_id 
                     WHERE oi.order_id = so.order_id) AS products_display,
                    (SELECT p2.product_name FROM order_items oi JOIN products p2 ON oi.product_id = p2.product_id 
                     WHERE oi.order_id = so.order_id LIMIT 1) AS product_name,
                    (SELECT p2.image_path FROM order_items oi JOIN products p2 ON oi.product_id = p2.product_id 
                     WHERE oi.order_id = so.order_id LIMIT 1) AS image_path
                    FROM delivery_assignments da
                    LEFT JOIN sales_orders so ON da.order_id = so.order_id
                    LEFT JOIN customers c ON so.customer_id = c.customer_id
                    WHERE da.driver_id = ? AND da.status IN ('Pending', 'Dispatched', 'On the Way', 'Arrived')
                    ORDER BY da.created_at DESC";
            } else {
                $assignment_query = "SELECT da.*, so.order_number, so.delivery_address, so.delivery_date, 
                    c.customer_name, c.contact_number, so.quantity, p.product_name, p.image_path, {$failure_col} NULL AS products_display
                    FROM delivery_assignments da
                    LEFT JOIN sales_orders so ON da.order_id = so.order_id
                    LEFT JOIN customers c ON so.customer_id = c.customer_id
                    LEFT JOIN products p ON so.product_id = p.product_id
                    WHERE da.driver_id = ? AND da.status IN ('Pending', 'Dispatched', 'On the Way', 'Arrived')
                    ORDER BY da.created_at DESC";
            }

            $stmt = $conn->prepare($assignment_query);
            if ($stmt) {
                $stmt->bind_param("i", $driver_id);
                $stmt->execute();
                $assignment_result = $stmt->get_result();
                if ($assignment_result) {
                    while ($row = $assignment_result->fetch_assoc()) {
                        $all_assignments[] = $row;
                    }
                }
                $stmt->close();
            }

            // Build an array of all delivery stops (for route optimization)
            $deliveryStops = [];
            foreach ($all_assignments as $a) {
                if (!empty($a['delivery_lat']) && !empty($a['delivery_lng'])) {
                    $deliveryStops[] = [(float)$a['delivery_lat'], (float)$a['delivery_lng']];
                }
            }

            // Decide which assignment is "current" (shown on map)
            if (!empty($all_assignments)) {
                // Default to the newest assignment
                $current_assignment = $all_assignments[0];

                // If user selected an assignment from the "Other Active Deliveries" table, honor that
                if ($selected_assignment_id > 0) {
                    foreach ($all_assignments as $row) {
                        if ((int)$row['assignment_id'] === $selected_assignment_id) {
                            $current_assignment = $row;
                            break;
                        }
                    }
                }

                // Everything except the current one goes to "Other Active Deliveries" (exclude completed deliveries)
                foreach ($all_assignments as $row) {
                    if ((int)$row['assignment_id'] !== (int)$current_assignment['assignment_id'] && !in_array(strtolower($row['status']), ['delivered', 'failed'])) {
                        $other_assignments[] = $row;
                    }
                }
            }

            // Fetch all order items for multi-product display
            $order_items_list = [];
            if ($current_assignment && !empty($current_assignment['order_id']) && $has_order_items) {
                $oi_stmt = $conn->prepare("SELECT oi.product_id, oi.quantity, p.product_name, p.image_path 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.product_id 
                    WHERE oi.order_id = ? ORDER BY oi.item_id");
                if ($oi_stmt) {
                    $oi_stmt->bind_param("i", $current_assignment['order_id']);
                    $oi_stmt->execute();
                    $oi_res = $oi_stmt->get_result();
                    while ($oi_row = $oi_res->fetch_assoc()) {
                        $order_items_list[] = $oi_row;
                    }
                    $oi_stmt->close();
                }
            }
            ?>

            <!-- Delivery Assignment -->
            <div class="card">
                <h3>Current Delivery Assignment</h3>
                <?php if ($current_assignment): ?>
                    <?php
                    require_once "includes/functions.php";
                    $product_display = !empty($current_assignment['products_display']) ? $current_assignment['products_display'] : ($current_assignment['product_name'] ?? 'N/A');
                    ?>
                    <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Items you are delivering</strong>
                    <?php if (count($order_items_list) > 0): ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 10px;">
                        <?php foreach ($order_items_list as $item): ?>
                            <?php 
                            $delivery_product = array('image_path' => $item['image_path'] ?? null, 'product_name' => $item['product_name'] ?? 'Product');
                            $delivery_img_url = getProductImagePath($delivery_product);
                            ?>
                            <div class="delivery-item-card" style="flex: 1 1 200px; min-width: 0;">
                                <?php if ($delivery_img_url): ?>
                                    <img src="<?php echo htmlspecialchars($delivery_img_url); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                <?php else: ?>
                                    <div class="product-card-icon" style="margin: 0;"><?php echo getProductIcon($item['product_name'] ?? 'Product'); ?></div>
                                <?php endif; ?>
                                <div class="delivery-item-details">
                                    <p class="delivery-item-name"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                    <p class="delivery-item-qty">Qty: <?php echo number_format($item['quantity'], 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p style="margin-top: 8px; font-size: 14px; font-weight: 600;">Total: <?php echo number_format($current_assignment['quantity'] ?? 0, 2); ?> units</p>
                    <?php else: ?>
                    <div class="delivery-item-card">
                        <div class="delivery-item-details">
                            <p class="delivery-item-name"><?php echo htmlspecialchars($product_display); ?></p>
                            <p class="delivery-item-qty">Total: <?php echo number_format($current_assignment['quantity'] ?? 0, 2); ?> units</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
                        <div>
                            <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Order Number</strong>
                            <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($current_assignment['order_number'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Customer</strong>
                            <p style="margin: 5px 0; font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($current_assignment['customer_name'] ?? 'N/A'); ?></p>
                            <?php if (!empty($current_assignment['contact_number'])): ?>
                                <p style="margin: 5px 0; font-size: 14px; color: var(--text-secondary);">📞 <?php echo htmlspecialchars($current_assignment['contact_number']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Delivery Address</strong>
                            <p style="margin: 5px 0; font-size: 14px; line-height: 1.5;">
                                <?php echo nl2br(htmlspecialchars($current_assignment['delivery_address'] ?? 'N/A')); ?>
                                <?php if (!empty($current_assignment['delivery_address'])): ?>
                                    <br>
                                    <a href="<?php echo 'https://www.google.com/maps/search/?api=1&query=' . urlencode($current_assignment['delivery_address']); ?>"
                                       target="_blank"
                                       style="font-size:12px; color:#2563eb; text-decoration:underline;">
                                        Open in Google Maps
                                    </a>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Delivery Date</strong>
                            <p style="margin: 5px 0; font-size: 14px;"><?php echo $current_assignment['delivery_date'] ? date('F d, Y', strtotime($current_assignment['delivery_date'])) : 'Not set'; ?></p>
                        </div>
                        <div>
                            <strong style="color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Status</strong>
                            <p style="margin: 5px 0;"><span class="status" id="currentStatus"><?php echo htmlspecialchars($current_assignment['status']); ?></span></p>
                            <?php if (!empty($current_assignment['failure_reason'])): ?>
                                <p style="margin: 5px 0; font-size: 13px; color: var(--text-muted);"><strong>Failure Reason:</strong> <?php echo htmlspecialchars($current_assignment['failure_reason']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="hidden" id="assignmentId" value="<?php echo $current_assignment['assignment_id']; ?>">
                    <input type="hidden" id="orderId" value="<?php echo $current_assignment['order_id']; ?>">
                    <input type="hidden" id="customerLat" value="<?php echo $current_assignment['delivery_lat'] ?? ''; ?>">
                    <input type="hidden" id="customerLng" value="<?php echo $current_assignment['delivery_lng'] ?? ''; ?>">
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <p style="font-size: 18px; margin-bottom: 10px;">📦</p>
                        <p style="font-size: 16px; font-weight: 600;">No active delivery assignment</p>
                        <p style="font-size: 14px; margin-top: 5px;">You will be notified when a delivery is assigned to you.</p>
                    </div>
                    <input type="hidden" id="assignmentId" value="0">
                <?php endif; ?>
            </div>

            <!-- GPS Map Area -->
            <div class="card">
                <h3>Live Location</h3>
                <div id="map" class="map-placeholder" style="height: 400px;">
                    <p>Loading map...</p>
                </div>
                <p style="font-size: 14px; color: var(--text-secondary); margin-top: var(--spacing-md);">
                    <span id="locationStatus">Getting your location...</span>
                    <br>
                    <span id="distanceInfo" style="font-weight:600; margin-top:8px; display:inline-block;"></span>
                    <button id="toggleRouteBtn" type="button" style="margin-left: 12px; padding: 4px 10px; font-size: 12px;">Hide Route</button>
                </p>
            </div>

            <?php if (!empty($other_assignments)): ?>
            <div class="card">
                <h3>Other Active Deliveries</h3>
                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom:8px;">
                    These orders are also assigned to you and can be handled in the same trip.
                </p>
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:6px 8px;">Order #</th>
                            <th style="text-align:left; padding:6px 8px;">Customer</th>
                            <th style="text-align:left; padding:6px 8px;">Status</th>
                            <th style="text-align:left; padding:6px 8px;">On Map</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($other_assignments as $oa): ?>
                        <tr>
                            <td style="padding:6px 8px;"><?php echo htmlspecialchars($oa['order_number'] ?? ''); ?></td>
                            <td style="padding:6px 8px;"><?php echo htmlspecialchars($oa['customer_name'] ?? ''); ?></td>
                            <td style="padding:6px 8px;"><?php echo htmlspecialchars($oa['status'] ?? ''); ?></td>
                            <td style="padding:6px 8px;">
                                <a 
                                    href="driver_gps.php?assignment_id=<?php echo (int)$oa['assignment_id']; ?>" 
                                    class="btn" 
                                    style="padding:4px 10px; font-size:12px;">
                                    Set as current
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Update Delivery Status -->
            <?php if ($current_assignment): ?>
            <div class="card">
                <h3>Update Delivery Status</h3>
                <select id="statusSelect" style="margin-bottom: 15px;" onchange="toggleFailureReason()">
                    <option value="Pending" <?php echo ($current_assignment['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="Dispatched" <?php echo ($current_assignment['status'] == 'Dispatched') ? 'selected' : ''; ?>>Dispatched</option>
                    <option value="On the Way" <?php echo ($current_assignment['status'] == 'On the Way') ? 'selected' : ''; ?>>On the Way</option>
                    <option value="Arrived" <?php echo ($current_assignment['status'] == 'Arrived') ? 'selected' : ''; ?>>Arrived</option>
                    <option value="Delivered" <?php echo ($current_assignment['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                    <option value="Failed" <?php echo ($current_assignment['status'] == 'Failed') ? 'selected' : ''; ?>>Failed Delivery</option>
                </select>

                <div id="failureReasonBlock" style="display: <?php echo ($current_assignment['status'] == 'Failed' || !empty($current_assignment['failure_reason'])) ? 'block' : 'none'; ?>; margin-bottom: 12px;">
                    <label for="failureReasonSelect" style="display:block; margin-bottom: 6px; font-weight: 600;">Failure Reason</label>
                    <select id="failureReasonSelect" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px;">
                        <?php
                        $failure_reasons = [
                            'Customer unavailable',
                            'Wrong address',
                            'Access denied / locked gate',
                            'Vehicle breakdown',
                            'Weather conditions',
                            'Damaged goods found',
                            'Customer refused delivery',
                            'Incorrect order details',
                            'Delivery delay',
                            'Other'
                        ];
                        $current_failure_reason = trim((string)($current_assignment['failure_reason'] ?? ''));
                        foreach ($failure_reasons as $reason):
                        ?>
                        <option value="<?php echo htmlspecialchars($reason); ?>" <?php echo ($current_failure_reason === $reason) ? 'selected' : ''; ?>><?php echo htmlspecialchars($reason); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="btn" style="width: 100%; margin-bottom: 10px;" onclick="updateStatus()">Update Status</button>
                <small style="color: var(--text-muted); display: block; text-align: center; font-size: 12px;">
                    Your location is automatically tracked and updated every 5 seconds.
                </small>
            </div>
            <?php endif; ?>

            <?php
                $podPath = (is_array($current_assignment) && !empty($current_assignment['proof_of_delivery'])) ? $current_assignment['proof_of_delivery'] : '';
                $showPodSection = $current_assignment && in_array(($current_assignment['status'] ?? ''), ['Arrived']);
            ?>
            <div id="podSection" class="card" style="display: <?php echo $showPodSection ? 'block' : 'none'; ?>;">
                <h3>Proof of Delivery</h3>
                <?php if (!empty($podPath)): ?>
                    <div id="podPreviewContainer" style="margin-bottom: 12px;">
                        <a id="podPreviewLink" href="<?php echo htmlspecialchars($podPath); ?>" target="_blank">
                            <img id="podPreview" src="<?php echo htmlspecialchars($podPath); ?>" alt="Proof of Delivery" style="max-width:100%; max-height:200px; border-radius:8px; border:1px solid var(--border-color);">
                        </a>
                        <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">Click to view full proof of delivery.</p>
                    </div>
                <?php else: ?>
                    <div id="podPreviewContainer" style="display:none; margin-bottom: 12px;">
                        <a id="podPreviewLink" href="#" target="_blank">
                            <img id="podPreview" src="" alt="Proof of Delivery" style="max-width:100%; max-height:200px; border-radius:8px; border:1px solid var(--border-color);">
                        </a>
                    </div>
                <?php endif; ?>
                <form id="podForm" enctype="multipart/form-data">
                    <input type="file" id="podFile" name="pod_file" accept="image/*" style="margin-bottom: 15px;">
                    <button type="button" class="btn" style="width: 100%;" onclick="uploadPOD()">📷 Upload Photo</button>
                    <small style="color: var(--text-muted); display: block; text-align: center; margin-top: 10px; font-size: 12px;">
                        Upload a photo as proof of delivery (optional but recommended)
                    </small>
                </form>
            </div>

            <!-- Action Buttons -->
            <?php if ($current_assignment): ?>
            <div class="card">
                <?php if ($current_assignment['status'] != 'Delivered' && $current_assignment['status'] != 'Failed'): ?>
                    <button class="btn-danger btn" style="width: 100%;" onclick="endDelivery()">⚠️ Cancel Delivery</button>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-muted); font-size: 14px;">
                        This delivery has been completed.
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- Footer -->
        <?php include "layouts/footer.php"; ?>

    </div>
</div>

<script>
let driverVehicle = "<?php echo htmlspecialchars($vehicle_type, ENT_QUOTES); ?>";
let deliveryStops = <?php echo json_encode($deliveryStops); ?>;
let currentPODPath = "<?php echo htmlspecialchars($podPath ?? '', ENT_QUOTES); ?>";
</script>
<script src="assets/js/sidebar.js"></script>
<script>
let map, marker, watchId, customerMarker, routeLayer;
let customerLat = 0, customerLng = 0;
let currentLat = 0, currentLng = 0;
let lastLocationUpdate = 0;
let locationIntervalId = null;
let routeVisible = true;

function getVehicleEmoji(vehicle) {
    vehicle = (vehicle || '').toLowerCase();
    if (vehicle.includes('truck')) return '🚚';
    if (vehicle.includes('van')) return '🚐';
    if (vehicle.includes('car')) return '🚗';
    if (vehicle.includes('motor')) return '🛵';
    if (vehicle.includes('bike')) return '🚲';
    return '📦';
}

function getDriverIcon(status) {
    var emoji = getVehicleEmoji(driverVehicle);
    var statusColor = '#999';
    if (/pending/i.test(status)) statusColor = '#f59e0b';
    else if (/dispatched|on the way/i.test(status)) statusColor = '#3b82f6';
    else if (/delivered/i.test(status)) statusColor = '#10b981';
    else if (/failed/i.test(status)) statusColor = '#ef4444';

    return L.divIcon({
        html: '<div style="font-size:30px; line-height: 30px; text-align:center;">' + emoji + '</div>' +
              '<div style="font-size:12px; color:' + statusColor + ';">●</div>',
        className: '',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
}

function updateDriverMarkerIcon() {
    var status = document.getElementById('currentStatus')?.textContent || '';
    if (marker) {
        marker.setIcon(getDriverIcon(status));
    }
}

function updatePODSection() {
    var status = document.getElementById('currentStatus')?.textContent || '';
    var podSection = document.getElementById('podSection');
    if (!podSection) return;

    // Show proof-of-delivery area for Arrived/Delivered statuses
    var shouldShow = /^(Arrived|Delivered)$/i.test(status);
    podSection.style.display = shouldShow ? 'block' : 'none';

    // If we already have a path (from PHP or after upload), ensure preview is visible
    if (currentPODPath && shouldShow) {
        var previewContainer = document.getElementById('podPreviewContainer');
        var previewLink = document.getElementById('podPreviewLink');
        var previewImg = document.getElementById('podPreview');
        if (previewContainer && previewLink && previewImg) {
            previewContainer.style.display = 'block';
            previewImg.src = currentPODPath;
            previewLink.href = currentPODPath;
        }
    }
}

function formatDuration(seconds) {
    var mins = Math.round(seconds / 60);
    var hrs = Math.floor(mins / 60);
    mins = mins % 60;
    if (hrs > 0) {
        return hrs + 'h ' + mins + 'm';
    }
    return mins + 'm';
}

function updateRouteInfo() {
    if (isNaN(currentLat) || isNaN(currentLng) || currentLat === 0 || currentLng === 0) return;
    if (isNaN(customerLat) || isNaN(customerLng)) return;

    // Build full route (origin + intermediate stops + destination)
    var coords = [[currentLng, currentLat]];
    if (Array.isArray(deliveryStops)) {
        deliveryStops.forEach(function(stop) {
            var lat = parseFloat(stop[0]);
            var lng = parseFloat(stop[1]);
            if (!isNaN(lat) && !isNaN(lng)) {
                coords.push([lng, lat]);
            }
        });
    }
    coords.push([customerLng, customerLat]);

    var coordString = coords.map(function(c) { return c[0] + ',' + c[1]; }).join(';');
    var url = 'https://router.project-osrm.org/route/v1/driving/' + coordString + '?overview=full&alternatives=false&geometries=geojson';

    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data && data.code === 'Ok' && data.routes && data.routes[0]) {
                var route = data.routes[0];
                var km = (route.distance / 1000).toFixed(1);
                document.getElementById('distanceInfo').textContent =
                    'Distance: ' + km + ' km • ETA: ' + formatDuration(route.duration);

                // Draw the route line on the map (only if visible)
                if (routeVisible) {
                    if (routeLayer) {
                        map.removeLayer(routeLayer);
                    }
                    routeLayer = L.geoJSON(route.geometry, {
                        style: { color: '#FF6B35', weight: 6, opacity: 0.9 }
                    }).addTo(map);
                }
                return;
            }

            // Fallback to straight-line distance
            var distance = calculateDistance(currentLat, currentLng, customerLat, customerLng);
            document.getElementById('distanceInfo').textContent =
                'Distance to customer: ' + distance.toFixed(2) + ' km';
        })
        .catch(function() {
            var distance = calculateDistance(currentLat, currentLng, customerLat, customerLng);
            document.getElementById('distanceInfo').textContent =
                'Distance to customer: ' + distance.toFixed(2) + ' km';
        });
}

// Initialize map
function initMap() {
    if (!navigator.geolocation) {
        document.getElementById('locationStatus').textContent =
            'Geolocation is not supported by your browser.';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            currentLat = position.coords.latitude;
            currentLng = position.coords.longitude;

            // Initialize map (using Leaflet as example - you can use Google Maps API)
            map = L.map('map').setView([currentLat, currentLng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Create marker using the driver's vehicle icon + status
            marker = L.marker([currentLat, currentLng], { icon: getDriverIcon(document.getElementById('currentStatus')?.textContent || '') })
                .addTo(map)
                .bindPopup('Your Delivery Vehicle').openPopup();

            // Get customer coordinates
            customerLat = parseFloat(document.getElementById('customerLat')?.value);
            customerLng = parseFloat(document.getElementById('customerLng')?.value);

            if (!isNaN(customerLat) && !isNaN(customerLng)) {
                // Ensure customer marker is shown
                if (!customerMarker) {
                    customerMarker = L.marker([customerLat, customerLng], {
                        icon: L.divIcon({
                            html: '<div style="font-size:26px; line-height: 26px; text-align:center;">📍</div>',
                            className: '',
                            iconSize: [26, 26],
                            iconAnchor: [13, 13]
                        })
                    }).addTo(map).bindPopup('Delivery destination');
                } else {
                    customerMarker.setLatLng([customerLat, customerLng]);
                }

                updateRouteInfo();
            }

            document.getElementById('locationStatus').textContent =
                `Location: ${currentLat.toFixed(6)}, ${currentLng.toFixed(6)}`;

            // Start tracking
            startTracking();

            // Ensure proof of delivery section is shown/hidden based on status
            updatePODSection();

            // Wire up the toggle button
            var toggleBtn = document.getElementById('toggleRouteBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleRouteVisibility);
            }
        },
        function(error) {
            document.getElementById('locationStatus').textContent =
                'Error getting location: ' + error.message;
            document.getElementById('map').innerHTML =
                '<p style="color: #dc2626;">Unable to get your location. Please enable location services.</p>';
        },
        {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
        }
    );
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth radius in KM
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;

    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) *
        Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function toggleRouteVisibility() {
    routeVisible = !routeVisible;
    var btn = document.getElementById('toggleRouteBtn');
    if (routeVisible) {
        btn.textContent = 'Hide Route';
        updateRouteInfo();
    } else {
        btn.textContent = 'Show Route';
        if (routeLayer && map.hasLayer(routeLayer)) {
            map.removeLayer(routeLayer);
        }
    }
}

function startTracking() {
    if (watchId) return; // Already tracking

    watchId = navigator.geolocation.watchPosition(
        function(position) {
            currentLat = position.coords.latitude;
            currentLng = position.coords.longitude;

            if (map && marker) {
                marker.setLatLng([currentLat, currentLng]);
                map.panTo([currentLat, currentLng]);
            }

            updateDriverMarkerIcon();
            updateRouteInfo();
        },
        function(error) {
            console.error('Location error:', error);
        },
        {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
        }
    );

    // Ensure we push location updates every 5 seconds
    if (!locationIntervalId) {
        locationIntervalId = setInterval(sendLocation, 5000);
    }
}

function sendLocation() {
    const assignmentId = document.getElementById('assignmentId')?.value || 0;
    const now = Date.now();

    // Only send location every 5 seconds
    if (assignmentId > 0 && currentLat != 0 && currentLng != 0 && (now - lastLocationUpdate) >= 5000) {
        const formData = new FormData();
        formData.append('latitude', currentLat);
        formData.append('longitude', currentLng);
        formData.append('assignment_id', assignmentId);

        fetch('api/update_gps.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                lastLocationUpdate = now;
                const statusEl = document.getElementById('locationStatus');
                if (statusEl) {
                    statusEl.textContent = `Location updated: ${currentLat.toFixed(6)}, ${currentLng.toFixed(6)}`;
                }
            }
        }).catch(error => {
            console.error('Error updating location:', error);
        });
    }
}

function updateStatus() {
    const status = document.getElementById('statusSelect').value;
    const assignmentId = document.getElementById('assignmentId')?.value || 0;
    
    if (assignmentId == 0) {
        alert('No active delivery assignment.');
        return;
    }
    
    const formData = new FormData();
    formData.append('status', status);
    formData.append('assignment_id', assignmentId);

    if (status === 'Failed') {
        const failureReason = document.getElementById('failureReasonSelect')?.value || '';
        formData.append('failure_reason', failureReason);
    }

    fetch('api/update_gps.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            const statusElement = document.getElementById('currentStatus');
            if (statusElement) {
                statusElement.textContent = status;
                statusElement.style.background = status === 'Delivered' ? 'rgba(16, 185, 129, 0.1)' : 
                                                  (status === 'On the Way' ? 'rgba(59, 130, 246, 0.1)' : 
                                                  (status === 'Failed' ? 'rgba(254, 202, 202, 0.4)' : 'rgba(255, 107, 53, 0.1)'));
                statusElement.style.color = status === 'Delivered' ? '#10b981' : 
                                           (status === 'On the Way' ? '#3b82f6' : 
                                           (status === 'Failed' ? '#b91c1c' : '#FF6B35'));
            }
            updateDriverMarkerIcon();
            updateRouteInfo();
            updatePODSection();
            alert('Status updated successfully!');

            // If delivery is completed or failed, refresh so the assignment is cleared from the list
            if (status === 'Delivered' || status === 'Failed') {
                setTimeout(function() {
                    location.reload();
                }, 500);
            }
        } else {
            alert('Error updating status: ' + (data.error || 'Unknown error'));
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Error updating status. Please try again.');
    });
}

function toggleFailureReason() {
    var status = document.getElementById('statusSelect')?.value || '';
    var block = document.getElementById('failureReasonBlock');
    if (!block) return;
    block.style.display = (status === 'Failed') ? 'block' : 'none';
}

toggleFailureReason();

function uploadPOD() {
    const fileInput = document.getElementById('podFile');
    const assignmentId = document.getElementById('assignmentId')?.value || 0;
    
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert('Please select a file to upload.');
        return;
    }
    
    if (assignmentId == 0) {
        alert('No active delivery assignment.');
        return;
    }
    
    const formData = new FormData();
    formData.append('pod_file', fileInput.files[0]);
    formData.append('assignment_id', assignmentId);
    
    fetch('api/upload_pod.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            currentPODPath = data.file_path || currentPODPath;
            updatePODSection();
            alert('Proof of delivery uploaded successfully!');
            fileInput.value = '';
        } else {
            alert('Error uploading file: ' + (data.error || 'Unknown error'));
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Error uploading file. Please try again.');
    });
}

function endDelivery() {
    if (!confirm('Are you sure you want to cancel this delivery? This action cannot be undone.')) {
        return;
    }
    
    const assignmentId = document.getElementById('assignmentId')?.value || 0;
    const formData = new FormData();
    formData.append('status', 'Failed');
    formData.append('assignment_id', assignmentId);
    
    fetch('api/update_gps.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Delivery marked as failed. Please inform your warehouse/operations team to process the return.');
            location.reload();
        } else {
            alert('Error cancelling delivery: ' + (data.error || 'Unknown error'));
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Error cancelling delivery. Please try again.');
    });
}

// Load Leaflet CSS and JS
const link = document.createElement('link');
link.rel = 'stylesheet';
link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
document.head.appendChild(link);

const script = document.createElement('script');
script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
script.onload = function() {
    initMap();
};
document.head.appendChild(script);
</script>

</body>
</html>
