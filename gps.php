<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Delivery Tracking | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map { height: 500px; width: 100%; border-radius: 8px; }
        .delivery-list { margin-top: 15px; }
        .delivery-list li { padding: 8px; margin: 5px 0; background: var(--card-bg); border-radius: 6px; list-style: none; }
        .vehicle-marker { background: none !important; border: none !important; }

        /* Improve visibility when Leaflet Routing Machine adds the itinerary panel */
        .leaflet-routing-container,
        .leaflet-routing-alt {
            position: absolute !important;
            bottom: 12px !important;
            right: 12px !important;
            left: auto !important;
            top: auto !important;
            width: 320px !important;
            max-width: 320px !important;
            max-height: 240px !important;
            overflow: auto !important;
            background: rgba(255,255,255,0.92) !important;
            border-radius: 12px !important;
            box-shadow: 0 0 18px rgba(0,0,0,0.22) !important;
            opacity: 0.92 !important;
            z-index: 2000 !important;
        }
        .leaflet-routing-container *,
        .leaflet-routing-alt * {
            font-size: 12px !important;
        }
        /* Keep it from taking the full width of the map */
        .leaflet-routing-container.leaflet-routing-container-hide,
        .leaflet-routing-alt.leaflet-routing-container-hide {
            display: none !important;
        }
        .leaflet-routing-container.leaflet-routing-container-hide + .leaflet-routing-container-toggle,
        .leaflet-routing-alt.leaflet-routing-container-hide + .leaflet-routing-container-toggle {
            display: none !important;
        }
        /* Force the directions pane to stay small */
        .leaflet-routing-container .leaflet-routing-geocoders,
        .leaflet-routing-container .leaflet-routing-instructions {
            max-height: 220px !important;
            overflow: auto !important;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Live Delivery Tracking</h2>
            <p>View multiple active deliveries and driver locations in real time.</p>
            <?php showMessage(); ?>
            <div class="card">
                <h3>Active deliveries map</h3>
                <p style="color: var(--text-muted); font-size: 12px;">Updates every 10 seconds. Drivers appear when they have sent at least one GPS update.</p>
                <div id="map"></div>
                <div class="delivery-list">
                    <h4 style="margin: 15px 0 10px 0;">Active deliveries</h4>
                    <ul id="deliveryList"></ul>
                </div>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
<script>
(function() {
    var map = L.map('map').setView([14.5995, 120.9842], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
    var driverMarkers = {};
    var deliveryMarkers = {};
    var listEl = document.getElementById('deliveryList');

        function getVehicleIcon(vehicle, status) {
            var v = (vehicle || '').toLowerCase();
            var emoji = '\u{1F69A}'; // 📍 default pin
            if (/motor|bike|scooter|habal/i.test(v)) emoji = '\u{1F3CD}'; // 🏍 motorcycle
            else if (/car|sedan|van|suv|auto/i.test(v)) emoji = '\u{1F697}'; // 🚗 car
            else if (/truck|lorry|pickup|trailer|container/i.test(v)) emoji = '\u{1F69A}'; // 🚚 truck

            var statusColor = '#999';
            if (/pending/i.test(status)) statusColor = '#f59e0b'; // yellow
            else if (/dispatched|on the way/i.test(status)) statusColor = '#3b82f6'; // blue
            else if (/delivered/i.test(status)) statusColor = '#10b981'; // green
            else if (/failed/i.test(status)) statusColor = '#ef4444'; // red

            return L.divIcon({
                className: 'vehicle-marker',
                html: '<div style="font-size:24px;line-height:1;text-align:center;">' + emoji + '</div>' +
                      '<div style="font-size:12px;color:' + statusColor + ';">●</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
        }

        function decodePolyline(str, precision) {
            var index = 0, lat = 0, lng = 0, coordinates = [];
            precision = Math.pow(10, -(precision || 5));

            while (index < str.length) {
                var result = 1, shift = 0, b;
                do {
                    b = str.charCodeAt(index++) - 63 - 1;
                    result += b << shift;
                    shift += 5;
                } while (b >= 0x1f);
                lat += (result & 1) ? ~(result >> 1) : (result >> 1);

                result = 1;
                shift = 0;
                do {
                    b = str.charCodeAt(index++) - 63 - 1;
                    result += b << shift;
                    shift += 5;
                } while (b >= 0x1f);
                lng += (result & 1) ? ~(result >> 1) : (result >> 1);

                coordinates.push([lat * precision, lng * precision]);
            }
            return coordinates;
        }
    function fetchDeliveries() {
        fetch('api/get_active_deliveries.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) return;

                var deliveries = data.deliveries || [];
                console.log('gps: deliveries', deliveries);
                listEl.innerHTML = '';

                // Group deliveries by driver for routing
                var drivers = {};
                var stopsByDriver = {};
                var boundsPoints = [];
                var now = Date.now();

                deliveries.forEach(function(d) {
                    // Build list item
                    var li = document.createElement('li');
                    var etaText = '';
                    if (d.last_update) {
                        var last = new Date(d.last_update.replace(' ', 'T'));
                        var minutes = Math.round((now - last.getTime()) / 60000);
                        etaText = 'Last update: ' + minutes + ' min ago';
                    }
                    var vehicleInfo = (d.vehicle ? ' [' + d.vehicle + ']' : '');
                    li.innerHTML =
                        '<strong>' + (d.order_number || '') + '</strong> — ' +
                        (d.driver_name || '') + vehicleInfo +
                        ' <span style="color:var(--text-muted);">(' + (d.status || '') + ')</span>' +
                        '<br><span style="font-size:12px; color:var(--text-muted);">Address: ' + (d.delivery_address || '-') + '</span>' +
                        '<br><span style="font-size:12px; color:var(--text-muted);">Items: ' + (d.products || '-') + '</span>' +
                        '<br><small style="font-size:11px; color:var(--text-muted);">' + etaText + '</small>';

                    var hasDriver = d.driver_id != null && d.driver_id !== '';
                    listEl.appendChild(li);

                    // Track driver positions (latest reported lat/lng)
                    if (hasDriver) {
                        if (!drivers[d.driver_id]) {
                            drivers[d.driver_id] = {
                                driver_id: d.driver_id,
                                driver_name: d.driver_name,
                                vehicle: d.vehicle,
                                lat: d.lat,
                                lng: d.lng,
                                status: d.status
                            };
                        } else {
                            // update latest position/status
                            drivers[d.driver_id].lat = d.lat;
                            drivers[d.driver_id].lng = d.lng;
                            drivers[d.driver_id].status = d.status;
                        }
                    }

                    // Group stops per driver
                    if (hasDriver) {
                        stopsByDriver[d.driver_id] = stopsByDriver[d.driver_id] || [];
                        stopsByDriver[d.driver_id].push(d);
                    }

                    // Add delivery location markers
                    if (d.lat != null && d.lng != null) {
                        boundsPoints.push([d.lat, d.lng]);
                        var popupHtml =
                            (d.order_number || '') + '<br>' +
                            (d.driver_name || '') + (d.vehicle ? ' (' + d.vehicle + ')' : '') + '<br>' +
                            (d.delivery_address || '');

                        var icon = getVehicleIcon(d.vehicle, d.status);
                        if (deliveryMarkers[d.assignment_id]) {
                            deliveryMarkers[d.assignment_id].setLatLng([d.lat, d.lng]).setIcon(icon).bindPopup(popupHtml);
                        } else {
                            var m = L.marker([d.lat, d.lng], { icon: icon }).addTo(map).bindPopup(popupHtml);
                            deliveryMarkers[d.assignment_id] = m;
                        }
                    }
                });

                // Remove markers for deliveries that are no longer active
                var ids = deliveries.map(function(d) { return d.assignment_id; });
                Object.keys(deliveryMarkers).forEach(function(id) {
                    if (ids.indexOf(parseInt(id, 10)) === -1) {
                        map.removeLayer(deliveryMarkers[id]);
                        delete deliveryMarkers[id];
                    }
                });

                // Place or update driver markers
                Object.keys(drivers).forEach(function(driverId) {
                    var drv = drivers[driverId];
                    if (drv.lat == null || drv.lng == null) return;

                    var popup = '<strong>' + (drv.driver_name || 'Driver') + '</strong>' +
                                '<br><span style="font-size:12px;color:var(--text-muted);">' + (drv.vehicle || '') + '</span>' +
                                '<br><span style="font-size:12px;color:var(--text-muted);">Status: ' + (drv.status || '') + '</span>';

                    var icon = getVehicleIcon(drv.vehicle, drv.status);

                    if (driverMarkers[driverId]) {
                        driverMarkers[driverId].setLatLng([drv.lat, drv.lng]).setIcon(icon).setPopupContent(popup);
                    } else {
                        var m = L.marker([drv.lat, drv.lng], { icon: icon }).addTo(map).bindPopup(popup);
                        driverMarkers[driverId] = m;
                    }

                    boundsPoints.push([drv.lat, drv.lng]);
                });

                // Fit map to all active points
                if (boundsPoints.length > 0) {
                    var bounds = L.latLngBounds(boundsPoints);
                    map.fitBounds(bounds.pad(0.2));
                }

                // Save for route calculations later
                window.__deliveryStopsByDriver = stopsByDriver;
                window.__drivers = drivers;
            });
    }

    function formatDuration(seconds) {
        seconds = Math.round(seconds);
        var mins = Math.floor(seconds / 60);
        var hrs = Math.floor(mins / 60);
        mins = mins % 60;
        if (hrs > 0) {
            return hrs + 'h ' + mins + 'm';
        }
        return mins + 'm';
    }


    fetchDeliveries();
    setInterval(fetchDeliveries, 10000);
})();
</script>
</body>
</html>
