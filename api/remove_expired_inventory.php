try {
    // Remove expired raw material batches using processInventoryEvent
    $expired_raw = $conn->query("
        SELECT rmb.batch_id, rmb.material_id, rmb.quantity_remaining, rmb.batch_number, rm.material_name
        FROM raw_material_batches rmb
        LEFT JOIN raw_materials rm ON rmb.material_id = rm.material_id
        WHERE rmb.expiry_date IS NOT NULL AND rmb.expiry_date < CURDATE() AND rmb.quantity_remaining > 0
    ");
    if ($expired_raw) {
        $raw_items = [];
        while ($batch = $expired_raw->fetch_assoc()) {
            $raw_items[] = [
                'material_id' => $batch['material_id'],
                'quantity' => $batch['quantity_remaining'],
                'batch_id' => $batch['batch_id'],
                'batch_number' => $batch['batch_number'],
                'item_name' => $batch['material_name'],
                'created_by' => $created_by
            ];
        }
        if (count($raw_items) > 0) {
            processInventoryEvent($conn, 'EXPIRED_RAW_REMOVAL', [ 'items' => $raw_items, 'created_by' => $created_by ]);
            $removed_count += count($raw_items);
        }
    }

    // Remove expired finished goods batches using processInventoryEvent
    $expired_finished = $conn->query("
        SELECT fgb.batch_id, fgb.product_id, fgb.quantity_remaining, fgb.batch_number, p.product_name
        FROM finished_goods_batches fgb
        LEFT JOIN products p ON fgb.product_id = p.product_id
        WHERE fgb.expiry_date IS NOT NULL AND fgb.expiry_date < CURDATE() AND fgb.quantity_remaining > 0
    ");
    if ($expired_finished) {
        $finished_items = [];
        while ($batch = $expired_finished->fetch_assoc()) {
            $finished_items[] = [
                'product_id' => $batch['product_id'],
                'quantity' => $batch['quantity_remaining'],
                'batch_id' => $batch['batch_id'],
                'batch_number' => $batch['batch_number'],
                'item_name' => $batch['product_name'],
                'created_by' => $created_by
            ];
        }
        if (count($finished_items) > 0) {
            processInventoryEvent($conn, 'EXPIRED_FINISHED_REMOVAL', [ 'items' => $finished_items, 'created_by' => $created_by ]);
            $removed_count += count($finished_items);
        }
    }

    echo json_encode(['success' => true, 'removed_count' => $removed_count]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
                        ];
                    }
                    if (count($finished_items) > 0) {
                        processInventoryEvent($conn, 'EXPIRED_FINISHED_REMOVAL', [ 'items' => $finished_items, 'created_by' => $created_by ]);
                        $removed_count += count($finished_items);
                    }
                }

                echo json_encode(['success' => true, 'removed_count' => $removed_count]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }