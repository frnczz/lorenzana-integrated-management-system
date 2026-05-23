<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";
// prefer explicit query parameter; do not auto-select based on last created orders
$has_from_production = @$conn->query("SHOW COLUMNS FROM sales_orders LIKE 'from_production_request'")->num_rows > 0;
$preselect_order = intval($_GET['order_id'] ?? 0);
$preselected_driver = 0;
// if an order is preselected, only fetch an existing driver assignment when that order is a production-based order
if ($preselect_order > 0 && $has_from_production) {
    $is_production_order = false;
    $check = $conn->prepare("SELECT from_production_request, delivery_person_id FROM sales_orders WHERE order_id = ? LIMIT 1");
    if ($check) {
        $check->bind_param("i", $preselect_order);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ($row) {
            if (!empty($row['from_production_request'])) {
                $is_production_order = true;
            }

            // Prevent opening the delivery page if delivery is already assigned.
            if (!empty($row['delivery_person_id'])) {
                die('Delivery already assigned for this order.');
            }
        }
        $check->close();
    }

    if ($is_production_order) {
        $stmt = $conn->prepare("SELECT driver_id FROM delivery_assignments WHERE order_id = ? ORDER BY assignment_id DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $preselect_order);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $preselected_driver = intval($row['driver_id']);
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Scheduling | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .delivery-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }
        .delivery-card {
            border-radius: 12px;
            padding: 20px 24px;
            background: linear-gradient(135deg, #f9fafb 0%, #eef2ff 100%);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
        }
        .delivery-card h3 {
            margin-top: 0;
            margin-bottom: 12px;
            color: #111827;
        }
        .delivery-card p.subtitle {
            margin-top: 0;
            margin-bottom: 16px;
            color: #6b7280;
            font-size: 13px;
        }
        .delivery-form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .delivery-field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 4px;
        }
        .delivery-field select,
        .delivery-field input,
        .delivery-field textarea {
            width: 100%;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 13px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .delivery-field textarea {
            min-height: 70px;
            resize: vertical;
        }
        .delivery-field select:focus,
        .delivery-field input:focus,
        .delivery-field textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 1px #6366f1;
            outline: none;
        }
        .order-items-summary {
            background: #f9fafb;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12px;
        }
        .delivery-actions {
            text-align: right;
            margin-top: 12px;
        }
        .customer-marker{
            font-size: 24px;
            width: 42px;
            height: 42px;
            background: white;
            border-radius: 50%;
            display:flex;
            align-items:center;
            justify-content:center;
            border: 2px solid #3b82f6;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Delivery Scheduling & Assignment</h2>
            <p>Assign delivery personnel to pending orders. When an order contains multiple products, all items are listed together so they can be dispatched in a single run.</p>
            <?php showMessage(); ?>

            <div class="delivery-layout">
            <div class="card delivery-card" id="delivery-scheduling">
                <h3>Assign Delivery (From Customer Orders)</h3>
                <p class="subtitle">For standard customer orders created from the main Sales form.</p>
                <form method="POST" action="api/save_delivery.php" data-loading-message="Assigning delivery..." data-loading-subtext="Scheduling delivery assignment.">
                    <div class="delivery-form-grid">
                        <div class="delivery-field">
                            <label for="delivery-order-select">Orders (you can select multiple)</label>
                                <?php
                                // Use the same filter as the Pending Deliveries table in sales.php
                                $pending_orders = $conn->query("
                                    SELECT 
                                        so.order_id,
                                        so.order_number,
                                        so.customer_id,
                                        c.customer_name,
                                        c.address AS customer_address,
                                        so.status,
                                        CASE WHEN da.assignment_id IS NULL THEN 'Not Assigned' ELSE da.status END as assignment_status
                                    FROM sales_orders so
                                    LEFT JOIN customers c ON so.customer_id = c.customer_id
                                    LEFT JOIN delivery_assignments da ON so.order_id = da.order_id
                                    WHERE so.fulfillment_type = 'Delivery'
                                      AND so.status IN ('Pending','Confirmed','Dispatched')
                                      AND so.delivery_person_id IS NULL
                                      AND (so.from_production_request IS NULL OR so.from_production_request = 0)
                                    ORDER BY so.order_date DESC
                                ");
                                ?>
                                <select name="order_ids[]" id="delivery-order-select" style="width:100%; padding:8px;" multiple required>
                                    <option value="">-- Select one or more orders --</option>
                                    <?php if ($pending_orders && $pending_orders->num_rows > 0): ?>
                                        <?php while ($ord = $pending_orders->fetch_assoc()): ?>
                                            <option 
                                                value="<?php echo $ord['order_id']; ?>" 
                                                data-order-number="<?php echo htmlspecialchars($ord['order_number']); ?>"
                                                data-customer-name="<?php echo htmlspecialchars($ord['customer_name'] ?? 'N/A'); ?>"
                                                data-address="<?php echo htmlspecialchars($ord['customer_address'] ?? '', ENT_QUOTES); ?>"
                                                data-status="<?php echo htmlspecialchars($ord['status']); ?>"
                                                data-assignment="<?php echo htmlspecialchars($ord['assignment_status']); ?>"
                                                <?php echo ($preselect_order && $preselect_order == $ord['order_id']) ? 'selected' : ''; ?>
                                            >
                                                <?php echo htmlspecialchars($ord['order_number']); ?> - <?php echo htmlspecialchars($ord['customer_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($ord['assignment_status']); ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <small id="delivery-order-summary" style="display:block; margin-top:6px; color:var(--text-muted); font-size:13px;"></small>
                            </div>
                        </div>

                        <div class="delivery-field">
                            <label>Order Items</label>
                            <textarea id="delivery_order_items" class="order-items-summary" readonly placeholder="Select an order to view items..."></textarea>
                        </div>

                        <div class="delivery-field">
                            <label for="delivery_address_override">Delivery Address (Override)</label>
                            <textarea name="delivery_address_override" id="delivery_address_override" placeholder="Leave blank to use the order's existing delivery address."></textarea>
                        </div>

                        <div class="delivery-field">
                            <label for="delivery_date_override">Delivery Date (Override)</label>
                            <input type="date" name="delivery_date_override" id="delivery_date_override">
                        </div>

                        <div class="delivery-field">
                            <label>Set Delivery Location (Optional)</label>
                            <p style="color:var(--text-muted); font-size:13px; margin-bottom:8px;">
                                You can click on the map to set the exact delivery point. This will be used by the driver GPS page.
                            </p>
                            <div id="deliveryMap" style="width:100%; height:220px; border-radius:8px; border:1px solid #e5e7eb; overflow:hidden;"></div>
                            <input type="hidden" name="delivery_lat" id="delivery_lat">
                            <input type="hidden" name="delivery_lng" id="delivery_lng">
                            <small id="deliveryCoordsDisplay" style="display:block; margin-top:6px; color:var(--text-muted); font-size:12px;"></small>
                        </div>

                        <div class="delivery-field">
                            <label>Delivery Person / Driver</label>
                                <?php
                                $delivery_personnel_query = "SELECT DISTINCT u.id, u.username, u.full_name, e.employee_number, e.position, e.department,
                                                            COUNT(da2.assignment_id) as active_deliveries
                                                            FROM users u
                                                            LEFT JOIN employees e ON u.id = e.user_id
                                                            LEFT JOIN delivery_assignments da2 ON u.id = da2.driver_id 
                                                            AND da2.status IN ('Dispatched', 'On the Way', 'Arrived')
                                                            WHERE u.role IN ('delivery', 'driver') 
                                                            GROUP BY u.id, u.username, u.full_name, e.employee_number, e.position, e.department
                                                            ORDER BY COALESCE(e.position, u.full_name, u.username)";
                                $delivery_personnel_result = $conn->query($delivery_personnel_query);
                                ?>
                                <select name="driver_id" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Delivery Person --</option>
                                    <?php if ($delivery_personnel_result && $delivery_personnel_result->num_rows > 0): ?>
                                        <?php while ($person = $delivery_personnel_result->fetch_assoc()): ?>
                                            <?php 
                                            $display_name = $person['full_name'] ?? $person['username'];
                                            if (!empty($person['employee_number'])) $display_name .= ' (' . $person['employee_number'] . ')';
                                            if (!empty($person['position'])) $display_name .= ' - ' . $person['position'];
                                            $active_deliveries = intval($person['active_deliveries']);
                                            if ($active_deliveries > 0) $display_name .= ' [' . $active_deliveries . ' active]';
                                            $disabled = $active_deliveries >= 3 ? 'disabled style="color: #999;"' : '';
                                            $suffix = $active_deliveries >= 3 ? ' (Fully Booked)' : '';
                                            $sel = ($preselected_driver && $preselected_driver == $person['id']) ? ' selected' : '';
                                            ?>
                                            <option value="<?php echo $person['id']; ?>" <?php echo $disabled . $sel; ?>>
                                                <?php echo htmlspecialchars($display_name); ?><?php echo $suffix; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <small style="color: var(--text-muted); display: block; margin-top: 5px;">Personnel with 3+ active deliveries are marked as fully booked.</small>
                            </div>

                        <div class="delivery-actions">
                            <button type="submit" class="btn">Assign Delivery</button>
                        </div>
                    </div>
                </form>
            </div>



            <?php
            // Separate form that only shows orders created from Request Production / QC flow
            $has_from_production = @$conn->query("SHOW COLUMNS FROM sales_orders LIKE 'from_production_request'")->num_rows > 0;
            $prod_order = null;
            if ($has_from_production) {
                // Only load the specific order passed via query param (clicking "Assign Delivery" from Request Production)
                if ($preselect_order > 0) {
                    $stmt = $conn->prepare(
                        "SELECT 
                            so.order_id,
                            so.order_number,
                            so.order_date,
                            so.total_amount,
                            so.status,
                            c.customer_name,
                            c.address AS customer_address,
                            CASE WHEN da.assignment_id IS NULL THEN 'Not Assigned' ELSE da.status END as assignment_status
                        FROM sales_orders so
                        LEFT JOIN customers c ON so.customer_id = c.customer_id
                        LEFT JOIN delivery_assignments da ON so.order_id = da.order_id
                        WHERE so.fulfillment_type = 'Delivery'
                          AND so.from_production_request = 1
                          AND so.order_id = ?
                          AND (da.assignment_id IS NULL OR da.status IN ('Pending','Dispatched'))
                        LIMIT 1"
                    );
                    if ($stmt) {
                        $stmt->bind_param('i', $preselect_order);
                        $stmt->execute();
                        $prod_order = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    }
                }
            }
            ?>

            <div class="card delivery-card" id="delivery-scheduling-production" style="margin-top:0;">
                <h3>Assign Delivery (From Request Production)</h3>
                <p class="subtitle">
                    This form only accepts delivery orders that were auto-created from Request Production / QC approval.
                </p>
                <form method="POST" action="api/save_delivery.php" data-loading-message="Assigning delivery..." data-loading-subtext="Scheduling delivery assignment for production-based order.">
                    <div class="delivery-form-grid">
                        <div class="delivery-field">
                            <label for="prod_delivery_order_select">Order (from Request Production)</label>
                            <?php if ($prod_order): ?>
                                <select name="order_ids[]" id="prod_delivery_order_select" style="width:100%; padding:8px;" required>
                                    <option value="<?php echo $prod_order['order_id']; ?>" 
                                        data-order-number="<?php echo htmlspecialchars($prod_order['order_number']); ?>"
                                        data-customer-name="<?php echo htmlspecialchars($prod_order['customer_name'] ?? 'N/A'); ?>"
                                        data-address="<?php echo htmlspecialchars($prod_order['customer_address'] ?? '', ENT_QUOTES); ?>"
                                        data-status="<?php echo htmlspecialchars($prod_order['status']); ?>"
                                        data-assignment="<?php echo htmlspecialchars($prod_order['assignment_status']); ?>"
                                        selected
                                    >
                                        <?php echo htmlspecialchars($prod_order['order_number']); ?> - <?php echo htmlspecialchars($prod_order['customer_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($prod_order['assignment_status']); ?>)
                                    </option>
                                </select>
                            <?php else: ?>
                                <p style="margin:8px 0; color:var(--text-muted); font-size:13px;">
                                    Click "Assign Delivery" from a completed Request Production record to load its delivery order here.
                                </p>
                                <select name="order_ids[]" id="prod_delivery_order_select" style="width:100%; padding:8px;" disabled>
                                    <option value="">-- No order selected --</option>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="delivery-field">
                            <label>Order Items</label>
                            <textarea id="prod_delivery_order_items" class="order-items-summary" readonly placeholder="Select an order to view items..."></textarea>
                        </div>

                        <div class="delivery-field">
                            <label for="delivery_address_override_prod">Delivery Address (Override)</label>
                            <textarea name="delivery_address_override" id="delivery_address_override_prod" placeholder="Leave blank to use the order's existing delivery address."></textarea>
                        </div>

                        <div class="delivery-field">
                            <label for="delivery_date_override_prod">Delivery Date (Override)</label>
                            <input type="date" name="delivery_date_override" id="delivery_date_override_prod">
                        </div>

                        <div class="delivery-field">
                            <label>Set Delivery Location (Optional)</label>
                            <p style="color:var(--text-muted); font-size:13px; margin-bottom:8px;">
                                You can click on the map to set the exact delivery point. This will be used by the driver GPS page.
                            </p>
                            <div id="deliveryMapProd" style="width:100%; height:220px; border-radius:8px; border:1px solid #e5e7eb; overflow:hidden;"></div>
                            <input type="hidden" name="delivery_lat" id="delivery_lat_prod">
                            <input type="hidden" name="delivery_lng" id="delivery_lng_prod">
                            <small id="deliveryCoordsDisplayProd" style="display:block; margin-top:6px; color:var(--text-muted); font-size:12px;"></small>
                        </div>

                        <div class="delivery-field">
                            <label>Delivery Person / Driver</label>
                                <?php
                                // Reuse same personnel query as above
                                $delivery_personnel_query2 = "SELECT DISTINCT u.id, u.username, u.full_name, e.employee_number, e.position, e.department,
                                                            COUNT(da2.assignment_id) as active_deliveries
                                                            FROM users u
                                                            LEFT JOIN employees e ON u.id = e.user_id
                                                            LEFT JOIN delivery_assignments da2 ON u.id = da2.driver_id 
                                                            AND da2.status IN ('Dispatched', 'On the Way', 'Arrived')
                                                            WHERE u.role IN ('delivery', 'driver') 
                                                            GROUP BY u.id, u.username, u.full_name, e.employee_number, e.position, e.department
                                                            ORDER BY COALESCE(e.position, u.full_name, u.username)";
                                $delivery_personnel_result2 = $conn->query($delivery_personnel_query2);
                                ?>
                                <select name="driver_id" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Delivery Person --</option>
                                    <?php if ($delivery_personnel_result2 && $delivery_personnel_result2->num_rows > 0): ?>
                                        <?php while ($person = $delivery_personnel_result2->fetch_assoc()): ?>
                                            <?php 
                                            $display_name = $person['full_name'] ?? $person['username'];
                                            if (!empty($person['employee_number'])) $display_name .= ' (' . $person['employee_number'] . ')';
                                            if (!empty($person['position'])) $display_name .= ' - ' . $person['position'];
                                            $active_deliveries = intval($person['active_deliveries']);
                                            if ($active_deliveries > 0) $display_name .= ' [' . $active_deliveries . ' active]';
                                            $disabled = $active_deliveries >= 3 ? 'disabled style="color: #999;"' : '';
                                            $suffix = $active_deliveries >= 3 ? ' (Fully Booked)' : '';
                                            ?>
                                            <option value="<?php echo $person['id']; ?>" <?php echo $disabled; ?>>
                                                <?php echo htmlspecialchars($display_name); ?><?php echo $suffix; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                        </div>

                        <div class="delivery-field">
                            <label>Vehicle</label>
                            <input type="text" name="vehicle_info">
                        </div>
                        <div class="delivery-field">
                            <label>Dispatch Time</label>
                            <input type="datetime-local" name="dispatch_time" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>

                        <div class="delivery-actions">
                            <button type="submit" class="btn" id="prod_assign_button" <?php echo $prod_order ? '' : 'disabled'; ?>>Assign Delivery</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card" style="margin-top:25px;">
            <h3>Completed Delivery Transactions</h3>

            <table class="table">
            <thead>
            <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Courier</th>
            <th>Vehicle</th>
            <th>Dispatch Time</th>
            <th>Status</th>
            <th>Receipt</th>
            </tr>
            </thead>
            <tbody>

            <?php
            $completed = $conn->query("
            SELECT 
            da.assignment_id,
            so.order_number,
            c.customer_name,
            u.full_name AS courier,
            da.vehicle_info,
            da.dispatch_time,
            da.status
            FROM delivery_assignments da
            LEFT JOIN sales_orders so ON da.order_id = so.order_id
            LEFT JOIN customers c ON so.customer_id = c.customer_id
            LEFT JOIN users u ON da.driver_id = u.id
            WHERE da.status = 'Delivered'
            ORDER BY da.dispatch_time DESC
            ");

            if($completed && $completed->num_rows > 0){
                while($row = $completed->fetch_assoc()){
            ?>

            <tr>
            <td><?php echo htmlspecialchars($row['order_number']); ?></td>
            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($row['courier']); ?></td>
            <td><?php echo htmlspecialchars($row['vehicle_info']); ?></td>
            <td><?php echo date("M d, Y H:i", strtotime($row['dispatch_time'])); ?></td>
            <td><span style="color:green;font-weight:600;">Delivered</span></td>
            <td><a class="btn" href="delivery_receipt.php?assignment_id=<?php echo urlencode($row['assignment_id']); ?>" target="_blank">Receipt</a></td>
            </tr>

            <?php
                }
            }else{
            ?>

            <tr>
            <td colspan="6" style="text-align:center;">No completed deliveries yet.</td>
            </tr>

            <?php } ?>

            </tbody>
            </table>
            </div>
            </div>
        </div>
    </div>
    <?php include "layouts/footer.php"; ?>
<script src="assets/js/sidebar.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Autofill order summary and sensible defaults when an order is selected
(function() {
    var select = document.getElementById('delivery-order-select');
    var summary = document.getElementById('delivery-order-summary');
    if (!select || !summary) return;

    function updateSummary() {
        var selected = Array.prototype.slice.call(select.options).filter(function(opt) {
            return opt.selected && opt.value;
        });
        if (!selected.length) {
            summary.textContent = '';
            return;
        }
        if (selected.length === 1) {
            var opt = selected[0];
            var num = opt.getAttribute('data-order-number') || '';
            var cust = opt.getAttribute('data-customer-name') || '';
            var status = opt.getAttribute('data-status') || '';
            var assign = opt.getAttribute('data-assignment') || '';
            summary.textContent = 'Order ' + num + ' — ' + cust + ' | Status: ' + status + ' | Assignment: ' + assign;
        } else {
            summary.textContent = selected.length + ' orders selected for this delivery run.';
        }
    }

    select.addEventListener('change', updateSummary);
    // Run once on load in case an order_id was preselected
    updateSummary();
})();

// Load and display order items for a given order select + textarea pair
function attachOrderItemsSummary(selectId, textareaId, overrideSuffix) {
    overrideSuffix = overrideSuffix || '';
    var sel = document.getElementById(selectId);
    var box = document.getElementById(textareaId);
    if (!sel || !box) return;

    function refreshItems() {
        var selectedOptions = Array.prototype.slice.call(sel.options).filter(function (opt) {
            return opt.selected && opt.value;
        });
        box.value = '';
        if (!selectedOptions.length) return;

        var lines = [];
        var remaining = selectedOptions.length;

        selectedOptions.forEach(function (opt) {
            var orderId = opt.value;
            var orderNum = opt.getAttribute('data-order-number') || ('#' + orderId);
            var cust = opt.getAttribute('data-customer-name') || '';

            $.get('api/get_order_items.php', { order_id: orderId }, function (data) {
                if (!data || !data.success || !data.items) {
                    lines.push('Order ' + orderNum + ' — ' + cust + '\nUnable to load order items.');
                } else {
                    var itemLines = data.items.map(function (it) {
                        return '  • ' + (it.product_name || ('Product #' + it.product_id)) + '  ×  ' + it.quantity;
                    });
                    lines.push('Order ' + orderNum + ' — ' + cust + '\n' + itemLines.join('\n'));

                    // If only one order is selected, prefill delivery overrides
                    if (selectedOptions.length === 1) {
                        var address = data.delivery_address || data.customer_address || '';
                        if (address) {
                            $('#delivery_address_override' + overrideSuffix).val(address);

                            var mapId = overrideSuffix === '_prod' ? 'deliveryMapProd' : 'deliveryMap';
                            var coordsDisplayId = overrideSuffix === '_prod' ? 'deliveryCoordsDisplayProd' : 'deliveryCoordsDisplay';
                            geocodeAddress(address, mapId, '#delivery_lat' + overrideSuffix, '#delivery_lng' + overrideSuffix, '#' + coordsDisplayId);
                        }
                        if (data.delivery_date) {
                            $('#delivery_date_override' + overrideSuffix).val(data.delivery_date);
                        }
                    }
                }
                remaining--;
                if (remaining === 0) {
                    box.value = lines.join('\n\n');
                }
            }, 'json');
        });
    }

    sel.addEventListener('change', refreshItems);
    // initial load if already selected
    refreshItems();
}

// Geocode an address using OpenStreetMap Nominatim and update the map + form fields.
function geocodeAddress(address, mapId, latInputSelector, lngInputSelector, coordsDisplaySelector) {
    if (!address) return;
    var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(address);

    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data || !data.length) {
                console.warn('Geocoding failed, no results for:', address);
                if (latInputSelector) $(latInputSelector).val('');
                if (lngInputSelector) $(lngInputSelector).val('');
                if (coordsDisplaySelector) $(coordsDisplaySelector).text('Delivery Coordinates: not found (click map to set)');
                return;
            }

            var lat = parseFloat(data[0].lat);
            var lng = parseFloat(data[0].lon);
            if (isNaN(lat) || isNaN(lng)) {
                if (latInputSelector) $(latInputSelector).val('');
                if (lngInputSelector) $(lngInputSelector).val('');
                if (coordsDisplaySelector) $(coordsDisplaySelector).text('Delivery Coordinates: invalid (click map to set)');
                return;
            }

            var mapInfo = deliveryMaps[mapId];
            if (mapInfo && mapInfo.setCoords) {
                mapInfo.setCoords(lat, lng);
                if (mapInfo.map) mapInfo.map.setView([lat, lng], 14);
            }

            if (latInputSelector) $(latInputSelector).val(lat.toFixed(6));
            if (lngInputSelector) $(lngInputSelector).val(lng.toFixed(6));
            if (coordsDisplaySelector) $(coordsDisplaySelector).text('Delivery Coordinates: ' + lat.toFixed(6) + ', ' + lng.toFixed(6));
        })
        .catch(function(err) {
            console.warn('Geocode error:', err);
            if (latInputSelector) $(latInputSelector).val('');
            if (lngInputSelector) $(lngInputSelector).val('');
            if (coordsDisplaySelector) $(coordsDisplaySelector).text('Delivery Coordinates: geocode error (click map to set)');
        });
}

attachOrderItemsSummary('delivery-order-select', 'delivery_order_items');
attachOrderItemsSummary('prod_delivery_order_select', 'prod_delivery_order_items', '_prod');

// Auto-fill vehicle from driver profile when delivery person is selected
$('select[name="driver_id"]').on('change', function() {
    var driverId = $(this).val();
    var $form = $(this).closest('form');
    if (!driverId) return;
    $.get('api/get_driver_vehicle.php', { driver_id: driverId }, function(data) {
        if (data && data.success && data.vehicle) {
            $form.find('input[name="vehicle_info"]').val(data.vehicle);
        }
    }, 'json');
});

// Set Delivery Location map - init both maps
var deliveryMaps = {};
function initDeliveryMap(mapElId, latInputId, lngInputId, coordsDisplayId) {
    var mapEl = document.getElementById(mapElId);
    if (!mapEl) return;
    var latInput = document.getElementById(latInputId);
    var lngInput = document.getElementById(lngInputId);
    var coordsDisplay = document.getElementById(coordsDisplayId);
    // Default to Tanauan, Batangas if not set
    var defaultLat = 14.0833, defaultLng = 121.1833;
    var startLat = defaultLat, startLng = defaultLng;
    if (latInput && latInput.value && lngInput && lngInput.value) {
        var parsedLat = parseFloat(latInput.value), parsedLng = parseFloat(lngInput.value);
        if (!isNaN(parsedLat) && !isNaN(parsedLng)) {
            startLat = parsedLat;
            startLng = parsedLng;
        }
    }
    var map = L.map(mapElId).setView([startLat, startLng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
    var marker = null;
    function setCoords(lat, lng) {
        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
        if (coordsDisplay) coordsDisplay.textContent = 'Delivery Coordinates: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);

        var customerIcon = L.divIcon({
            html: '<div class="customer-marker">👤</div>',
            className: "",
            iconSize: [42, 42],
            iconAnchor: [21, 21]
        });

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], {icon: customerIcon}).addTo(map);
        }
    }
    // If there is a value, show it, else set default
    // Only set marker if coordinates already exist
    if (latInput && lngInput && latInput.value && lngInput.value) {
        setCoords(parseFloat(latInput.value), parseFloat(lngInput.value));
    }
    map.on('click', function(e) { setCoords(e.latlng.lat, e.latlng.lng); });
    deliveryMaps[mapElId] = { map: map, marker: marker, setCoords: setCoords };
}
$(function() {
    // Helper to get URL param
    function getUrlParam(name) {
        var results = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.search);
        return results ? decodeURIComponent(results[1].replace(/\+/g, ' ')) : null;
    }
    var urlLat = getUrlParam('lat');
    var urlLng = getUrlParam('lng');
    var urlOrderId = getUrlParam('order_id');
    var urlCoordsProvided = urlLat && urlLng && !isNaN(parseFloat(urlLat)) && !isNaN(parseFloat(urlLng));
    if (typeof L !== 'undefined') {
        // If lat/lng in URL, set as value before map init
        if (urlLat && urlLng && !isNaN(parseFloat(urlLat)) && !isNaN(parseFloat(urlLng))) {
            // Only apply coords to the main form when no specific order_id is targeted.
            if (!urlOrderId) {
                $('#delivery_lat').val(parseFloat(urlLat).toFixed(6));
                $('#delivery_lng').val(parseFloat(urlLng).toFixed(6));
            }
            // Always set production delivery coordinates if present in URL
            $('#delivery_lat_prod').val(parseFloat(urlLat).toFixed(6));
            $('#delivery_lng_prod').val(parseFloat(urlLng).toFixed(6));
        }
        initDeliveryMap('deliveryMap', 'delivery_lat', 'delivery_lng', 'deliveryCoordsDisplay');
        initDeliveryMap('deliveryMapProd', 'delivery_lat_prod', 'delivery_lng_prod', 'deliveryCoordsDisplayProd');
    }

    // If an order_id is provided in the URL, pre-select it in the production delivery selector.
    if (urlOrderId) {
        var prodSelect = $('#prod_delivery_order_select');
        if (prodSelect.length && prodSelect.find('option[value="' + urlOrderId + '"]').length) {
            prodSelect.val([urlOrderId]).trigger('change');
            var target = document.getElementById('delivery-scheduling-production');
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    $('#delivery-order-select').on('change', function() {
        var orderId = $(this).val();
        if (!orderId) {
            return;
        }

        // Prefer geocoding the customer's address for a more accurate marker placement.
        var selectedOption = $(this).find('option:selected');
        var address = selectedOption.data('address') || '';
        if (address) {
            geocodeAddress(address, 'deliveryMap', '#delivery_lat', '#delivery_lng', '#deliveryCoordsDisplay');
            return;
        }

        // Fallback: use any saved coordinates for the order
        $.get('api/get_order_delivery_coords.php', { order_id: orderId }, function(data) {
            var dm = deliveryMaps['deliveryMap'];
            if (data && data.success && data.lat && data.lng) {
                var lat = parseFloat(data.lat), lng = parseFloat(data.lng);
                $('#delivery_lat').val(lat.toFixed(6)); $('#delivery_lng').val(lng.toFixed(6));
                $('#deliveryCoordsDisplay').text('Delivery Coordinates: ' + lat.toFixed(6) + ', ' + lng.toFixed(6));
                if (dm && dm.setCoords) dm.setCoords(lat, lng);
                if (dm && dm.map) dm.map.setView([lat, lng], 14);
            } else {
                $('#delivery_lat').val('');
                $('#delivery_lng').val('');
                $('#deliveryCoordsDisplay').text('');
            }
        }, 'json');
    });
    $('#prod_delivery_order_select').on('change', function() {
        var orderId = $(this).val();
        if (!orderId) {
            return;
        }

        // Prefer geocoding the customer's address for a more accurate marker placement.
        var selectedOption = $(this).find('option:selected');
        var address = selectedOption.data('address') || '';
        if (address) {
            geocodeAddress(address, 'deliveryMapProd', '#delivery_lat_prod', '#delivery_lng_prod', '#deliveryCoordsDisplayProd');
            return;
        }

        // Fallback: use any saved coordinates for the order
        $.get('api/get_order_delivery_coords.php', { order_id: orderId }, function(data) {
            var dm = deliveryMaps['deliveryMapProd'];
            if (data && data.success && data.lat && data.lng) {
                var lat = parseFloat(data.lat), lng = parseFloat(data.lng);
                $('#delivery_lat_prod').val(lat.toFixed(6)); $('#delivery_lng_prod').val(lng.toFixed(6));
                $('#deliveryCoordsDisplayProd').text('Delivery Coordinates: ' + lat.toFixed(6) + ', ' + lng.toFixed(6));
                if (dm && dm.setCoords) dm.setCoords(lat, lng);
                if (dm && dm.map) dm.map.setView([lat, lng], 14);
            } else {
                // Only overwrite coords with defaults if the URL didn't already provide coordinates.
                if (!urlCoordsProvided) {
                    $('#delivery_lat_prod').val('14.106402'); $('#delivery_lng_prod').val('121.144180');
                    $('#deliveryCoordsDisplayProd').text('Delivery Coordinates: 14.106402, 121.144180');
                    if (dm && dm.setCoords) dm.setCoords(14.106402, 121.144180);
                    if (dm && dm.map) dm.map.setView([14.106402, 121.144180], 14);
                }
            }
        }, 'json');
    });
    if ($('#delivery-order-select').val()) $('#delivery-order-select').trigger('change');
    if ($('#prod_delivery_order_select').val()) $('#prod_delivery_order_select').trigger('change');
});
</script>
</body>
</html>
