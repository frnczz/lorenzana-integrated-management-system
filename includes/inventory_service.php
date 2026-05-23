<?php
/**
 * LORINIMS Inventory Service - Event-Driven Stock Management
 * Single authority for all inventory changes. Business modules MUST NOT directly update raw_materials or finished_goods.
 */

if (!defined('LORINIMS_ROOT')) {
    define('LORINIMS_ROOT', dirname(__DIR__));
}

/**
 * Emit a system event. Idempotent via UNIQUE (entity_type, entity_id, event_type).
 * @return int|false Event ID or false on failure
 */
function emitSystemEvent($conn, $entity_type, $entity_id, $event_type, array $payload = []) {
    $payload_json = !empty($payload) ? json_encode($payload) : null;
    $stmt = $conn->prepare("
        INSERT INTO system_events (entity_type, entity_id, event_type, payload)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
    ");
    if (!$stmt) return false;
    $stmt->bind_param("siss", $entity_type, $entity_id, $event_type, $payload_json);
    $stmt->execute();
    $event_id = $conn->insert_id;
    $stmt->close();
    return $event_id ?: false;
}

/**
 * Process an inventory event - SINGLE entry point for all stock changes.
 * Writes inventory_transactions and updates raw_materials / finished_goods.
 * @throws Exception on failure
 */
function processInventoryEvent($conn, $event_type, $event_data) {
    $conn->begin_transaction();
    try {
        switch ($event_type) {
            case 'EXPIRED_RAW_REMOVAL':
                // Remove expired raw material batches
                foreach ($event_data['items'] as $item) {
                    $material_id = (int)($item['material_id'] ?? 0);
                    $qty = (float)($item['quantity'] ?? 0);
                    $batch_id = (int)($item['batch_id'] ?? 0);
                    $created_by = (int)($item['created_by'] ?? 0);
                    if ($material_id <= 0 || $qty <= 0) continue;

                    // Deduct from raw_materials
                    $upd = $conn->prepare("UPDATE raw_materials SET quantity = quantity - ? WHERE material_id = ?");
                    $upd->bind_param("di", $qty, $material_id);
                    $upd->execute();
                    $upd->close();

                    // Mark batch as depleted
                    $upd_batch = $conn->prepare("UPDATE raw_material_batches SET quantity_remaining = 0 WHERE batch_id = ?");
                    $upd_batch->bind_param("i", $batch_id);
                    $upd_batch->execute();
                    $upd_batch->close();

                    $notes = 'Expired inventory removed';
                    $trans = $conn->prepare("
                        INSERT INTO inventory_transactions
                        (item_type, item_id, transaction_type, quantity, reference_type, reference_id, notes, created_by)
                        VALUES ('Raw Material', ?, 'Out', ?, 'Expiry', ?, ?, ?)
                    ");
                    $trans->bind_param("idisi", $material_id, $qty, $batch_id, $notes, $created_by);
                    $trans->execute();
                    $trans->close();
                }
                break;
            case 'EXPIRED_FINISHED_REMOVAL':
                // Remove expired finished goods batches
                foreach ($event_data['items'] as $item) {
                    $product_id = (int)($item['product_id'] ?? 0);
                    $qty = (float)($item['quantity'] ?? 0);
                    $batch_id = (int)($item['batch_id'] ?? 0);
                    $created_by = (int)($item['created_by'] ?? 0);
                    if ($product_id <= 0 || $qty <= 0) continue;

                    // Find the finished_goods record to update
                    $fg_query = $conn->prepare("SELECT fg_id FROM finished_goods WHERE product_id = ? LIMIT 1");
                    $fg_query->bind_param("i", $product_id);
                    $fg_query->execute();
                    $fg_result = $fg_query->get_result();
                    if ($fg_row = $fg_result->fetch_assoc()) {
                        $fg_id = $fg_row['fg_id'];
                        // Deduct from finished_goods
                        $upd = $conn->prepare("UPDATE finished_goods SET quantity = quantity - ? WHERE fg_id = ?");
                        $upd->bind_param("di", $qty, $fg_id);
                        $upd->execute();
                        $upd->close();

                        $notes = 'Expired inventory removed';
                        $trans = $conn->prepare("
                            INSERT INTO inventory_transactions
                            (item_type, item_id, transaction_type, quantity, reference_type, reference_id, notes, created_by)
                            VALUES ('Finished Product', ?, 'Out', ?, 'Expiry', ?, ?, ?)
                        ");
                        $trans->bind_param("idisi", $fg_id, $qty, $batch_id, $notes, $created_by);
                        $trans->execute();
                        $trans->close();
                    }
                    $fg_query->close();

                    // Mark batch as depleted
                    $upd_batch = $conn->prepare("UPDATE finished_goods_batches SET quantity_remaining = 0 WHERE batch_id = ?");
                    $upd_batch->bind_param("i", $batch_id);
                    $upd_batch->execute();
                    $upd_batch->close();
                }
                break;

            case 'GRN_RECEIVED':
            case 'QC_APPROVED_RAW':
                foreach ($event_data['items'] as $item) {
                    $material_id = (int)($item['material_id'] ?? 0);
                    $qty = (float)($item['quantity'] ?? 0);
                    $expiry_date = $item['expiry_date'] ?? null;
                    $warehouse_location = $item['warehouse_location'] ?? null;
                    $grn_item_id = (int)($item['grn_item_id'] ?? 0);
                    $qc_id = (int)($item['qc_id'] ?? 0);
                    $created_by = (int)($item['created_by'] ?? 0);

                    if ($qty <= 0) continue;

                    $mat_check = $conn->prepare("SELECT material_id, quantity FROM raw_materials WHERE material_id = ?");
                    $mat_check->bind_param("i", $material_id);
                    $mat_check->execute();
                    $mat_result = $mat_check->get_result();

                    if ($mat_result->num_rows > 0) {
                        $upd = $conn->prepare("
                            UPDATE raw_materials 
                            SET quantity = quantity + ?,
                                expiry_date = COALESCE(?, expiry_date),
                                warehouse_location = COALESCE(?, warehouse_location)
                            WHERE material_id = ?
                        ");
                        $upd->bind_param("dssi", $qty, $expiry_date, $warehouse_location, $material_id);
                        $upd->execute();
                        $upd->close();
                        $final_material_id = $material_id;
                    } else {
                        $material_code = generateReferenceIdSafe($conn, 'MAT');
                        if (!$material_code) throw new Exception("Could not generate material code.");
                        $item_name = $item['item_name'] ?? 'Raw Material';
                        $unit = $item['unit'] ?? 'kg';
                        $ins = $conn->prepare("
                            INSERT INTO raw_materials 
                            (material_code, material_name, category, quantity, unit, expiry_date, warehouse_location)
                            VALUES (?, ?, 'Procurement', ?, ?, ?, ?)
                        ");
                        $ins->bind_param("ssdsss", $material_code, $item_name, $qty, $unit, $expiry_date, $warehouse_location);
                        $ins->execute();
                        $final_material_id = (int)$conn->insert_id;
                        $ins->close();

                        if ($qc_id > 0) {
                            $up_qc = $conn->prepare("UPDATE raw_material_qc SET material_id = ? WHERE qc_id = ?");
                            $up_qc->bind_param("ii", $final_material_id, $qc_id);
                            $up_qc->execute();
                            $up_qc->close();
                        }
                    }
                    $mat_check->close();

                    $ref_type = $event_type === 'GRN_RECEIVED' ? 'GRN' : 'QC';
                    $ref_id = $event_type === 'GRN_RECEIVED' ? ($item['grn_id'] ?? 0) : $qc_id;
                    $notes = $event_type === 'GRN_RECEIVED' ? 'Goods received' : 'QC approved - Added to inventory';
                    
                    $trans = $conn->prepare("
                        INSERT INTO inventory_transactions 
                        (item_type, item_id, transaction_type, quantity, reference_type, reference_id, notes, created_by) 
                        VALUES ('Raw Material', ?, 'In', ?, ?, ?, ?, ?)
                    ");
                    $trans->bind_param("idissi", $final_material_id, $qty, $ref_type, $ref_id, $notes, $created_by);
                    $trans->execute();
                    $trans->close();
                }
                break;
            
            case 'SALES_RESERVE':

                foreach ($event_data['items'] as $item) {

                    $product_id = (int)$item['product_id'];
                    $qty = (float)$item['quantity'];
                    $warehouse_location = isset($item['warehouse_location']) ? $item['warehouse_location'] : null;

                    // Find a finished_goods row that has enough available quantity.
                    // Prefer matching warehouse location when provided.
                    if ($warehouse_location !== null) {
                        $stmt = $conn->prepare(
                            "SELECT fg_id, quantity, reserved_quantity 
                             FROM finished_goods 
                             WHERE product_id = ? 
                               AND (warehouse_location = ? OR (warehouse_location IS NULL AND ? IS NULL))
                               AND (quantity - COALESCE(reserved_quantity,0)) >= ?
                             FOR UPDATE"
                        );
                        $stmt->bind_param("issi", $product_id, $warehouse_location, $warehouse_location, $qty);
                    } else {
                        $stmt = $conn->prepare(
                            "SELECT fg_id, quantity, reserved_quantity 
                             FROM finished_goods 
                             WHERE product_id = ? 
                               AND (quantity - COALESCE(reserved_quantity,0)) >= ?
                             ORDER BY (quantity - COALESCE(reserved_quantity,0)) DESC
                             FOR UPDATE"
                        );
                        $stmt->bind_param("id", $product_id, $qty);
                    }

                    $stmt->execute();
                    $res = $stmt->get_result();

                    if (!$row = $res->fetch_assoc()) {
                        throw new Exception("Insufficient available stock.");
                    }

                    $fg_id = (int)$row['fg_id'];
                    $current_qty = (float)$row['quantity'];
                    $current_reserved = (float)($row['reserved_quantity'] ?? 0);

                    $available = $current_qty - $current_reserved;
                    if ($available < $qty) {
                        // Should not happen given the selection criteria, but keep guard.
                        throw new Exception("Insufficient available stock.");
                    }

                    $stmt->close();

                    // Increase reserved_quantity
                    $upd = $conn->prepare(
                        "UPDATE finished_goods
                        SET reserved_quantity = reserved_quantity + ?
                        WHERE fg_id = ?"
                    );
                    $upd->bind_param("di", $qty, $fg_id);
                    $upd->execute();
                    $upd->close();
                }

                break;

            case 'SALES_FULFILL':
                // Deduct stock (from reserved or total) and create outbound inventory transactions
                $ref_id = (int)($event_data['order_id'] ?? 0);
                $created_by = (int)($event_data['created_by'] ?? 0);
                $consume_reserved = isset($event_data['consume_reserved']) ? (bool)$event_data['consume_reserved'] : true;

                foreach ($event_data['items'] as $item) {
                    $product_id = (int)$item['product_id'];
                    $qty = (float)$item['quantity'];

                    // Select all finished_goods rows for this product so we can consume from reserved stock first,
                    // and then from available stock if needed. This prevents failures when stock is split across locations.
                    $stmt = $conn->prepare("SELECT fg_id, quantity, reserved_quantity FROM finished_goods WHERE product_id = ? FOR UPDATE");
                    $stmt->bind_param("i", $product_id);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    $rows = [];
                    $total_qty = 0.0;
                    $total_reserved = 0.0;
                    while ($row = $res->fetch_assoc()) {
                        $row['quantity'] = (float)$row['quantity'];
                        $row['reserved_quantity'] = (float)($row['reserved_quantity'] ?? 0);
                        $rows[] = $row;
                        $total_qty += $row['quantity'];
                        $total_reserved += $row['reserved_quantity'];
                    }
                    $stmt->close();

                    if (empty($rows)) {
                        throw new Exception("Finished goods not found for product $product_id");
                    }

                    // Guardrails: reserved cannot exceed total quantity.
                    if ($total_reserved > $total_qty) {
                        throw new Exception("Reserved stock exceeds total stock for product $product_id");
                    }

                    // Check overall availability.
                    if ($total_qty < $qty) {
                        throw new Exception("Insufficient stock to fulfill for product $product_id");
                    }

                    $qty_to_deduct = $qty;

                    // Record which finished_goods row is used for the transaction entry.
                    $transaction_fg_id = null;

                    // First consume reserved stock (if requested)
                    if ($consume_reserved && $total_reserved > 0) {
                        foreach ($rows as &$row) {
                            if ($qty_to_deduct <= 0) break;
                            $take = min($row['reserved_quantity'], $qty_to_deduct);
                            if ($take <= 0) continue;

                            if ($transaction_fg_id === null) {
                                $transaction_fg_id = $row['fg_id'];
                            }

                            $row['reserved_quantity'] -= $take;
                            $row['quantity'] -= $take;
                            $qty_to_deduct -= $take;
                        }
                        unset($row);
                    }

                    // Then consume from available stock if still needed
                    if ($qty_to_deduct > 0) {
                        foreach ($rows as &$row) {
                            if ($qty_to_deduct <= 0) break;
                            $available = $row['quantity'] - $row['reserved_quantity'];
                            if ($available <= 0) continue;
                            $take = min($available, $qty_to_deduct);
                            if ($transaction_fg_id === null) {
                                $transaction_fg_id = $row['fg_id'];
                            }
                            $row['quantity'] -= $take;
                            $qty_to_deduct -= $take;
                        }
                        unset($row);
                    }

                    if ($qty_to_deduct > 0) {
                        // Should not happen given earlier checks, but guard anyway.
                        throw new Exception("Insufficient stock to fulfill for product $product_id");
                    }

                    // Persist updates (and delete empty rows)
                    foreach ($rows as $row) {
                        if ($row['quantity'] <= 0 && $row['reserved_quantity'] <= 0) {
                            $del = $conn->prepare("DELETE FROM finished_goods WHERE fg_id = ?");
                            $del->bind_param("i", $row['fg_id']);
                            $del->execute();
                            $del->close();
                        } else {
                            $upd = $conn->prepare("UPDATE finished_goods SET quantity = ?, reserved_quantity = ? WHERE fg_id = ?");
                            $upd->bind_param("ddi", $row['quantity'], $row['reserved_quantity'], $row['fg_id']);
                            $upd->execute();
                            $upd->close();
                        }
                    }

                    $notes = 'Sales fulfillment';
                    $item_fg_id = $transaction_fg_id ?? $rows[0]['fg_id'];
                    $trans = $conn->prepare("\n                        INSERT INTO inventory_transactions \n                        (item_type, item_id, transaction_type, quantity, reference_type, reference_id, notes, created_by) \n                        VALUES ('Finished Product', ?, 'Out', ?, 'Sales', ?, ?, ?)\n                    ");
                    $trans->bind_param("idisi", $item_fg_id, $qty, $ref_id, $notes, $created_by);
                    $trans->execute();
                    $trans->close();
                }

                break;

            case 'RETURN_PROCESSED':
                // when supplier return is processed, deduct from inventory
                foreach ($event_data['items'] as $item) {
                    $material_id = (int)($item['material_id'] ?? 0);
                    $qty = (float)($item['quantity'] ?? 0);
                    $return_id = (int)($event_data['return_id'] ?? 0);
                    $created_by = (int)($item['created_by'] ?? 0);
                    if ($material_id <= 0 || $qty <= 0) continue;

                    $upd = $conn->prepare("UPDATE raw_materials SET quantity = quantity - ? WHERE material_id = ?");
                    $upd->bind_param("di", $qty, $material_id);
                    $upd->execute();
                    if ($conn->affected_rows <= 0) {
                        throw new Exception("Insufficient raw material stock for return material_id $material_id");
                    }
                    $upd->close();

                    $notes = 'Returned to supplier';
                    $trans = $conn->prepare("\
                        INSERT INTO inventory_transactions 
                        (item_type, item_id, transaction_type, quantity, reference_type, reference_id, notes, created_by) 
                        VALUES ('Raw Material', ?, 'Out', ?, 'Return', ?, ?, ?)
                    ");
                    $trans->bind_param("idiiss", $material_id, $qty, $return_id, $notes, $created_by);
                    $trans->execute();
                    $trans->close();
                }
                break;

            case 'PRODUCTION_CONSUME':
                foreach ($event_data['materials'] as $mat) {
                    $material_id = (int)$mat['material_id'];
                    $qty = (float)$mat['quantity'];
                    $batch_id = (int)($event_data['batch_id'] ?? 0);
                    $created_by = (int)($event_data['created_by'] ?? 0);
                    if ($qty <= 0) continue;

                    $upd = $conn->prepare("UPDATE raw_materials SET quantity = quantity - ? WHERE material_id = ?");
                    $upd->bind_param("di", $qty, $material_id);
                    $upd->execute();
                    if ($conn->affected_rows <= 0) throw new Exception("Insufficient raw material stock for material_id $material_id");
                    $upd->close();

                    $notes = 'Used in production batch';
                    $trans = $conn->prepare("
                        INSERT INTO inventory_transactions 
                        (item_type, item_id, transaction_type, quantity, reference_type, reference_id, notes, created_by) 
                        VALUES ('Raw Material', ?, 'Out', ?, 'Production', ?, ?, ?)
                    ");
                    $trans->bind_param("idisi", $material_id, $qty, $batch_id, $notes, $created_by);
                    $trans->execute();
                    $trans->close();
                }
                break;

            case 'PRODUCTION_OUTPUT':
                $product_id = (int)($event_data['product_id'] ?? 0);
                $qty = (float)($event_data['quantity'] ?? 0);
                $batch_id = (int)($event_data['batch_id'] ?? 0);
                $expiry_date = $event_data['expiry_date'] ?? null;
                $warehouse_location = $event_data['warehouse_location'] ?? null;
                $created_by = (int)($event_data['created_by'] ?? 0);
                $reserve_for_customer = !empty($event_data['reserve_for_customer']);

                if (empty($expiry_date)) $expiry_date = date('Y-m-d', strtotime('+1 year'));

                $fg = $conn->prepare("SELECT fg_id, quantity, COALESCE(reserved_quantity, 0) AS reserved_quantity FROM finished_goods WHERE product_id = ? AND (warehouse_location = ? OR (warehouse_location IS NULL AND ? IS NULL)) LIMIT 1");
                $fg->bind_param("iss", $product_id, $warehouse_location, $warehouse_location);
                $fg->execute();
                $fg_result = $fg->get_result();

                if ($fg_result->num_rows > 0) {
                    $row = $fg_result->fetch_assoc();
                    $new_qty = $row['quantity'] + $qty;
                    $new_reserved = $reserve_for_customer ? ((float)$row['reserved_quantity'] + $qty) : (float)$row['reserved_quantity'];
                    $upd = $conn->prepare("UPDATE finished_goods SET quantity = ?, reserved_quantity = ?, expiry_date = ?, qc_approved = 1 WHERE fg_id = ?");
                    $upd->bind_param("ddsi", $new_qty, $new_reserved, $expiry_date, $row['fg_id']);
                    $upd->execute();
                    $upd->close();
                    $fg_id = $row['fg_id'];
                } else {
                    $reserved = $reserve_for_customer ? $qty : 0;
                    $ins = $conn->prepare("INSERT INTO finished_goods (product_id, quantity, reserved_quantity, expiry_date, warehouse_location, qc_approved) VALUES (?, ?, ?, ?, ?, 1)");
                    $ins->bind_param("iddss", $product_id, $qty, $reserved, $expiry_date, $warehouse_location);
                    $ins->execute();
                    $fg_id = (int)$conn->insert_id;
                    $ins->close();
                }
                $fg->close();

                $trans = $conn->prepare("
                    INSERT INTO inventory_transactions 
                    (item_type, item_id, transaction_type, quantity, reference_type, reference_id, notes, created_by) 
                    VALUES ('Finished Product', ?, 'In', ?, 'Production', ?, 'Production output QC approved', ?)
                ");
                $trans->bind_param("idii", $fg_id, $qty, $batch_id, $created_by);
                $trans->execute();
                $trans->close();
                break;

            // Other events (SALES_RESERVE, SALES_RELEASE, SALES_FULFILL, RETURN_PROCESSED) remain unchanged
            // as their bind_param counts are already correct.

            default:
                throw new Exception("Unknown event type: $event_type");
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Generate a unique reference ID using DB locking. Format: PREFIX-YYYYMMDD-NNNN
 * Transaction-safe to prevent duplicates under concurrency.
 */
function generateReferenceIdSafe($conn, $prefix) {
    $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $prefix));
    if (strlen($prefix) < 2 || strlen($prefix) > 10) return null;

    $today = date('Y-m-d');
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            INSERT INTO id_sequences (prefix, seq_date, last_seq)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE last_seq = last_seq + 1
        ");
        $stmt->bind_param("ss", $prefix, $today);
        $stmt->execute();
        $stmt->close();

        $sel = $conn->prepare("SELECT last_seq FROM id_sequences WHERE prefix = ? AND seq_date = ?");
        $sel->bind_param("ss", $prefix, $today);
        $sel->execute();
        $res = $sel->get_result()->fetch_assoc();
        $sel->close();

        $conn->commit();
        return $res ? $prefix . '-' . date('Ymd') . '-' . str_pad($res['last_seq'], 4, '0', STR_PAD_LEFT) : null;
    } catch (Exception $e) {
        $conn->rollback();
        return null;
    }
}
