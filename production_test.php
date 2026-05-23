<link rel="stylesheet" href="assets/css/notifications.css">
<form method="POST" action="save_production.php" data-loading-message="Saving production batch..." data-loading-subtext="Recording production.">
    Product: 
    <select name="product_id">
        <option value="1">Fish Sauce</option>
        <option value="2">Shrimp Paste</option>
    </select><br><br>

    Quantity Produced: <input type="number" name="quantity" step="0.01"><br><br>

    Materials Used:<br>
    Raw Fish: <input type="number" name="material_qty[1]" step="0.01"><br>
    Salt: <input type="number" name="material_qty[2]" step="0.01"><br>
    Shrimp: <input type="number" name="material_qty[3]" step="0.01"><br><br>

    <button type="submit">Save Batch</button>
</form>
<script src="assets/js/notifications.js" defer></script>
