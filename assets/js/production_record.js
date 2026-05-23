$(document).ready(function() {
    var requestGroupSelect = document.getElementById('request_group_select');
    var requestGroupSummary = document.getElementById('request_group_summary');
    var batchFormCard = document.getElementById('autofillBatchCard');
    var batchLinesBody = document.getElementById('autofillBatchBody');
    var manualBatchBody = document.getElementById('manualBatchBody');
    var hiddenRequestIds = document.getElementById('hidden_request_ids');
    var addManualLineBtn = document.getElementById('addManualLine');
    var productionForm = document.getElementById('productionForm');

    var materialSelect = document.getElementById('material_select');
    var materialQtyInput = document.getElementById('material_qty_input');
    var addMaterialBtn = document.getElementById('add_material_btn');
    var materialsSummary = document.getElementById('materials_summary');
    var materialsContainer = document.getElementById('selected_materials');
    var materialFilterHint = document.getElementById('material_filter_hint');

    var autofillMaterialSelect = $('#autofill_material_select');
    var autofillMaterialQtyInput = $('#autofill_material_qty_input');
    var autofillMaterialsContainer = $('#autofill_selected_materials');
    var autofillMaterialsSummary = $('#autofill_materials_summary');
    var autofillTotalCount = $('#autofill_total_materials_count');

    var requestGroups = [];
    var selectedMaterials = [];

    var fermDurationMap = (typeof fermentationDurations !== 'undefined' && fermentationDurations) ? fermentationDurations : {};

    function lineStatusSelectHtml(lineStat) {
        var ls = lineStat || 'Processing';
        return '<select name="line_status[]" class="line-status-select" style="width:100%;padding:8px;">' +
            '<option value="Processing"' + (ls === 'Processing' ? ' selected' : '') + '>Processing</option>' +
            '<option value="Ready"' + (ls === 'Ready' ? ' selected' : '') + '>Ready</option>' +
            '<option value="Completed"' + (ls === 'Completed' ? ' selected' : '') + '>Completed</option>' +
            '</select>';
    }

    function applyReadyIfFermentationCompleted(tr) {
        var fs = tr.querySelector('.line-fermentation-hidden');
        var st = tr.querySelector('.line-status-select');
        if (!fs || !st) {
            return;
        }
        if (fs.value === 'Completed' && st.value === 'Processing') {
            st.value = 'Ready';
        }
    }

    function syncAutofillRowToManual(autofillTr) {
        var rowsA = batchLinesBody.querySelectorAll('.batch-line-row');
        var rowsM = manualBatchBody.querySelectorAll('.batch-line-row');
        var idx = Array.prototype.indexOf.call(rowsA, autofillTr);
        if (idx < 0 || idx >= rowsM.length) {
            return;
        }
        var m = rowsM[idx];
        var fA = autofillTr.querySelector('.line-fermentation-hidden');
        var fM = m.querySelector('.line-fermentation-hidden');
        if (fA && fM) {
            fM.value = fA.value;
            applyReadyIfFermentationCompleted(m);
        }
        var sA = autofillTr.querySelector('.line-status-select');
        var sM = m.querySelector('.line-status-select');
        if (sA && sM) {
            sM.value = sA.value;
        }
    }

    function syncAutofillToManualFull() {
        syncAutofillQtyToManual();
        batchLinesBody.querySelectorAll('.batch-line-row').forEach(function(ar) {
            syncAutofillRowToManual(ar);
        });
    }

    function bindFermentationAndStatusSelects(tr) {
        var st = tr.querySelector('.line-status-select');
        if (st && !st._stBound) {
            st._stBound = true;
            st.addEventListener('change', function() {
                if (tr.closest('#autofillBatchBody')) {
                    syncAutofillRowToManual(tr);
                }
            });
        }
    }

    function getFermentationDurationDays(productId) {
        var key = String(productId);
        var v = fermDurationMap[key];
        if (v === undefined && fermDurationMap[productId] !== undefined) {
            v = fermDurationMap[productId];
        }
        var days = parseInt(v, 10);
        return isNaN(days) || days < 0 ? 0 : days;
    }

    function getProductionDateForRow(tr) {
        if (tr.closest('#autofillBatchBody')) {
            return (document.getElementById('autofill_production_date_input') || {}).value || '';
        }
        return (document.getElementById('production_date_input') || {}).value || '';
    }

    function calculateAutoFermentationStatus(productId, eligible, productionDate) {
        if (!eligible || !productId) {
            return 'Not Applicable';
        }
        if (!productionDate) {
            return 'Not Started';
        }
        var durationDays = getFermentationDurationDays(productId);
        if (durationDays <= 0) {
            return 'Not Started';
        }
        var start = new Date(productionDate + 'T00:00:00');
        if (isNaN(start.getTime())) {
            return 'Not Started';
        }
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        if (today.getTime() < start.getTime()) {
            return 'Not Started';
        }
        var elapsedDays = Math.floor((today.getTime() - start.getTime()) / 86400000);
        if (elapsedDays >= durationDays) {
            return 'Completed';
        }
        if (elapsedDays <= 0) {
            return 'Not Started';
        }
        return 'Ongoing';
    }

    function fermentationBadgeHtml(status, durationDays, productionDate) {
        var badgeClass = 'fermentation-badge no';
        if (status === 'Completed') badgeClass = 'fermentation-badge yes';
        if (status === 'Ongoing') badgeClass = 'fermentation-badge yes';
        var hint = '';
        var eta = '';
        if (status !== 'Not Applicable') {
            hint = '<small style="display:block;color:var(--text-muted);font-size:12px;margin-top:4px;">Duration: ' + durationDays + ' day(s)</small>';
            if (durationDays > 0 && productionDate) {
                var start = new Date(productionDate + 'T00:00:00');
                if (!isNaN(start.getTime())) {
                    start.setDate(start.getDate() + durationDays);
                    var yyyy = start.getFullYear();
                    var mm = String(start.getMonth() + 1).padStart(2, '0');
                    var dd = String(start.getDate()).padStart(2, '0');
                    eta = '<small style="display:block;color:#475569;font-size:12px;">ETA: ' + yyyy + '-' + mm + '-' + dd + '</small>';
                }
            }
        }
        return '<input type="hidden" name="fermentation_status[]" class="line-fermentation-hidden" value="' + status + '">' +
            '<span class="fermentation-display ' + badgeClass + '">' + status + '</span>' + hint + eta;
    }

    function refreshLineFermentationStatus(tr) {
        var fermTd = tr.querySelector('.line-fermentation-cell');
        if (!fermTd) return;
        var productId = 0;
        var eligible = false;
        var sel = tr.querySelector('.line-product-select');
        if (sel && sel.value) {
            productId = parseInt(sel.value, 10) || 0;
            var opt = sel.options[sel.selectedIndex];
            eligible = !!(opt && parseInt(opt.getAttribute('data-fermentation'), 10) === 1);
        } else {
            var hid = tr.querySelector('input[name="product_id[]"]');
            if (hid && hid.value) {
                productId = parseInt(hid.value, 10) || 0;
                var p = baseProductOptions.fermentation.concat(baseProductOptions.noFermentation).find(function(x) { return parseInt(x.id, 10) === productId; });
                eligible = !!(p && parseInt(p.fermentation_eligible, 10) === 1);
            }
        }
        var prodDate = getProductionDateForRow(tr);
        var status = calculateAutoFermentationStatus(productId, eligible, prodDate);
        var durationDays = eligible ? getFermentationDurationDays(productId) : 0;
        fermTd.innerHTML = fermentationBadgeHtml(status, durationDays, prodDate);
        applyReadyIfFermentationCompleted(tr);
    }

    function refreshAllFermentationStatuses() {
        document.querySelectorAll('.batch-line-row').forEach(function(tr) {
            refreshLineFermentationStatus(tr);
        });
    }

    /** --- Auto raw materials from recipes (settings_warehouse) --- */
    function computeAggregatedMaterials(batchBody, isAutofill) {
        var aggregated = {};
        if (!batchBody) {
            return aggregated;
        }
        var rows = batchBody.querySelectorAll('.batch-line-row');
        rows.forEach(function(tr) {
            var productId = 0;
            var qty = 0;
            if (isAutofill) {
                var hid = tr.querySelector('input[name="product_id[]"]');
                var qtyInp = tr.querySelector('input[name="quantity[]"]');
                productId = hid ? parseInt(hid.value, 10) : 0;
                qty = qtyInp ? parseFloat(qtyInp.value) || 0 : 0;
            } else {
                var sel = tr.querySelector('select[name="product_id[]"]');
                var qtyInp2 = tr.querySelector('input[name="quantity[]"]');
                productId = sel ? parseInt(sel.value, 10) : 0;
                qty = qtyInp2 ? parseFloat(qtyInp2.value) || 0 : 0;
            }
            if (!productId || qty <= 0) {
                return;
            }
            var recipe = (typeof productRecipes !== 'undefined' && productRecipes[productId]) ? productRecipes[productId] : [];
            recipe.forEach(function(line) {
                var matId = line.material_id;
                var total = (line.quantity_required || 0) * qty;
                if (matId && total > 0) {
                    aggregated[matId] = (aggregated[matId] || 0) + total;
                }
            });
        });
        return aggregated;
    }

    function refreshMaterialsFromRecipes(section) {
        var isAutofill = (section === 'autofill');
        var batchBody = isAutofill ? document.getElementById('autofillBatchBody') : document.getElementById('manualBatchBody');
        var aggregated = computeAggregatedMaterials(batchBody, isAutofill);
        var mats = (typeof materialsMap !== 'undefined') ? materialsMap : {};

        if (isAutofill) {
            var container = document.getElementById('autofill_selected_materials');
            if (!container) {
                return;
            }
            var manualFrags = [];
            container.querySelectorAll('.material-item[data-manual="1"]').forEach(function(node) {
                manualFrags.push(node.cloneNode(true));
            });
            container.innerHTML = '';
            var keys = Object.keys(aggregated);
            if (keys.length === 0 && manualFrags.length === 0) {
                container.innerHTML = '<p style="color: var(--text-muted); font-size:14px;">Add products above — raw materials will auto-fill from recipes (Warehouse Settings).</p>';
            } else {
                if (keys.length === 0 && manualFrags.length > 0) {
                    container.innerHTML = '';
                }
                keys.forEach(function(matId) {
                    matId = parseInt(matId, 10);
                    var qty = aggregated[matId];
                    var info = mats[matId] || { material_name: 'Material', unit: '' };
                    var div = document.createElement('div');
                    div.className = 'material-item';
                    div.setAttribute('data-manual', '0');
                    div.id = 'material_' + matId;
                    div.innerHTML = '<div><div>' + (info.material_name || 'Material') + '</div><small style="color: var(--text-muted); font-size:12px;">Avail: ' + (info.quantity != null ? info.quantity : 0) + ' ' + (info.unit || '') + '</small></div>' +
                        '<input type="number" class="autofill-qty" value="' + qty + '" step="0.01" min="0.01" style="width:80px; padding:8px;">' +
                        '<span style="font-size:12px; color: var(--text-muted);">' + (info.unit || '') + '</span>' +
                        '<input type="hidden" class="autofill-id" value="' + matId + '">' +
                        '<button type="button" class="remove-material-btn">Remove</button>';
                    container.appendChild(div);
                });
                manualFrags.forEach(function(n) {
                    container.appendChild(n);
                });
            }
            var summaryEl = document.getElementById('autofill_total_materials_count');
            var summaryBox = document.getElementById('autofill_materials_summary');
            var totalItems = container.querySelectorAll('.material-item').length;
            if (summaryEl) {
                summaryEl.textContent = totalItems + ' material(s)';
            }
            if (summaryBox) {
                summaryBox.style.display = totalItems ? 'block' : 'none';
            }
        } else {
            var manualOnly = [];
            selectedMaterials.forEach(function(m) {
                if (!Object.prototype.hasOwnProperty.call(aggregated, m.material_id)) {
                    manualOnly.push(m);
                }
            });
            selectedMaterials = [];
            Object.keys(aggregated).forEach(function(matId) {
                matId = parseInt(matId, 10);
                var info = mats[matId] || { material_name: 'Material', unit: '', quantity: 0 };
                selectedMaterials.push({
                    material_id: matId,
                    material_name: info.material_name || 'Material',
                    category: '',
                    quantity_used: aggregated[matId],
                    available: info.quantity != null ? info.quantity : 0,
                    unit: info.unit || '',
                    is_expired: !!info.is_expired
                });
            });
            manualOnly.forEach(function(m) {
                selectedMaterials.push(m);
            });
            renderSelectedMaterials();
        }
    }

    function syncAutofillQtyToManual() {
        var autofillRows = batchLinesBody.querySelectorAll('.batch-line-row');
        var manualRows = manualBatchBody.querySelectorAll('.batch-line-row');
        autofillRows.forEach(function(ar, i) {
            var m = manualRows[i];
            if (!m) {
                return;
            }
            var aq = ar.querySelector('input[name="quantity[]"]');
            var mq = m.querySelector('input[name="quantity[]"]');
            if (aq && mq && aq.value !== mq.value) {
                mq.value = aq.value;
            }
        });
    }

    /** --- Load production request groups --- */
    function loadRequestGroups() {
        $.get('api/get_production_request_groups.php', function(data) {
            if (data.success && data.groups) {
                requestGroups = data.groups;
                var $sel = $(requestGroupSelect);
                $sel.empty().append('<option value="">-- Select after clicking "Create Batch" in Production Requests --</option>');
                requestGroups.forEach(function(g) {
                    var ids = g.request_ids.join(',');
                    var label = g.customer_name + ' — ' + g.lines.length + ' product(s) — ' + (g.status || 'Pending');
                    $sel.append($('<option></option>').attr('value', ids).attr('data-ids', ids).text(label));
                });
                if (requestIdsPreload && requestIdsPreload.length > 0) {
                    var idsStr = requestIdsPreload.join(',');
                    var found = requestGroups.find(function(g) { return g.request_ids.join(',') === idsStr; });
                    if (found) {
                        $sel.val(idsStr).trigger('change');
                    }
                }
            }
        }, 'json');
    }

    /** --- On request group change --- */
    function onRequestGroupChange(idsStr) {
        if (!idsStr) {
            batchFormCard.style.display = 'none';
            requestGroupSummary.style.display = 'none';
            batchLinesBody.innerHTML = '';
            $('#autofill_material_select').closest('.materials-section').hide();
            return;
        }
        hiddenRequestIds.value = idsStr;
        var group = requestGroups.find(function(g) { return g.request_ids.join(',') === idsStr; });
        if (group) {
            requestGroupSummary.textContent = group.customer_name + ' — ' + group.lines.length + ' line(s). Status: ' + (group.status || 'Pending');
            renderBatchLines(group.lines);
        } else {
            requestGroupSummary.textContent = 'Request IDs: ' + idsStr + '. Add product lines below.';
            renderBatchLines([]);
        }
        requestGroupSummary.style.display = 'block';
        batchFormCard.style.display = 'block';
        $('#autofill_material_select').closest('.materials-section').show();
        filterMaterialsByProduct();
    }

    function fermentationCellHtml(productIdVal, eligible, lineStatusVal, fermStatusVal) {
        var pid = parseInt(productIdVal, 10) || 0;
        var el = eligible !== 0 && eligible !== false;
        var fermVal = fermStatusVal && ['Not Started', 'Ongoing', 'Completed', 'Not Applicable'].indexOf(fermStatusVal) >= 0
            ? fermStatusVal
            : calculateAutoFermentationStatus(pid, el, (document.getElementById('production_date_input') || {}).value || '');
        var lineStat = lineStatusVal || 'Processing';
        var pd = (document.getElementById('production_date_input') || {}).value || '';
        var fermPart = fermentationBadgeHtml(fermVal, el ? getFermentationDurationDays(pid) : 0, pd);
        return (
            '<td class="line-fermentation-cell">' + fermPart + '</td>' +
            '<td>' + lineStatusSelectHtml(lineStat) + '</td>'
        );
    }

    /** --- Render batch lines --- */
    function renderBatchLines(lines) {
        batchLinesBody.innerHTML = '';
        manualBatchBody.innerHTML = '';

        if (lines.length === 0) {
            addLine(batchLinesBody, {});
            addLine(manualBatchBody, {});
            refreshMaterialsFromRecipes('autofill');
            refreshMaterialsFromRecipes('manual');
            return;
        }

        lines.forEach(function(line) {
            addLine(batchLinesBody, line);
            addLine(manualBatchBody, line);
        });
        refreshMaterialsFromRecipes('autofill');
        refreshMaterialsFromRecipes('manual');
    }

    /** --- Add a batch line --- */
    function addLine(targetBody, line) {
        var tr = document.createElement('tr');
        tr.className = 'batch-line-row';
        var qty = line.requested_qty || '';
        var img = (line && line.image_path) ? 'assets/images/products/' + line.image_path : '';
        var fermentationDisabled = line.fermentation_eligible === 0;
        var pid = line.product_id || '';

        if (targetBody === batchLinesBody) {
            var productName = line.product_name || '';
            var lineStatusVal = line.line_status || 'Processing';
            var fermFromLine = line.fermentation_status || null;
            tr.innerHTML =
                '<td>' +
                '<div class="product-name-display">' + productName + '</div>' +
                '<div class="product-hover-preview"><img src="' + img + '" style="display:' + (img ? 'inline-block' : 'none') + ';"></div>' +
                '<input type="hidden" name="product_id[]" value="' + pid + '">' +
                '</td>' +
                '<td><input type="number" name="quantity[]" class="line-qty autofill-qty-input" step="0.01" min="0.01" value="' + qty + '" style="width:100%; padding:8px;"></td>' +
                fermentationCellHtml(pid, fermentationDisabled ? 0 : 1, lineStatusVal, fermFromLine) +
                '<td><button type="button" class="btn btn-remove-line" style="padding:6px 10px;">✕</button></td>';
            targetBody.appendChild(tr);
            bindLineEvents(tr);
            refreshLineFermentationStatus(tr);
        } else {
            tr.innerHTML =
                '<td style="width:100px; text-align:center; vertical-align:middle;">' +
                '<img id="img-' + (line.product_id || 'new') + '" src="' + img + '" style="display:' + (img ? 'inline-block' : 'none') + '; width:80px; height:80px; object-fit:contain; border-radius:4px;">' +
                '</td>' +
                '<td>' +
                '<select name="product_id[]" class="line-product-select" style="width:100%; padding:8px;">' +
                productOptionsHtml() +
                '</select>' +
                '</td>' +
                '<td><input type="number" name="quantity[]" class="line-qty" step="0.01" min="0.01" value="' + qty + '" style="width:100%; padding:8px;" required></td>' +
                fermentationCellHtml(line.product_id || '', line.product_id ? (fermentationDisabled ? 0 : 1) : 0, line.line_status || 'Processing', line.fermentation_status || null) +
                '<td><button type="button" class="btn btn-remove-line" style="padding:6px 10px;">✕</button></td>';
            targetBody.appendChild(tr);
            if (line && line.product_id) {
                tr.querySelector('.line-product-select').value = line.product_id;
            }
            updateFermentation(tr);
            bindLineEvents(tr);
        }
    }

    function productOptionsHtml() {
        var html = '<option value="">-- Select Product --</option>';
        baseProductOptions.noFermentation.forEach(function(p) {
            html += '<option value="' + p.id + '" data-category-id="' + p.category_id + '" data-image="' + (p.image_path || '') + '" data-fermentation="0">' + p.name + '</option>';
        });
        baseProductOptions.fermentation.forEach(function(p) {
            html += '<option value="' + p.id + '" data-category-id="' + p.category_id + '" data-image="' + (p.image_path || '') + '" data-fermentation="1">' + p.name + '</option>';
        });
        return html;
    }

    function bindLineEvents(tr) {
        var productSelect = tr.querySelector('.line-product-select');
        if (productSelect && !productSelect._bound) {
            productSelect._bound = true;
            productSelect.addEventListener('change', function() {
                updateFermentation(tr);
                updateProductImage(tr);
                filterMaterialsByProduct();
                if (typeof calculateBatchExpiry === 'function') {
                    calculateBatchExpiry('manual');
                }
                refreshMaterialsFromRecipes('manual');
            });
        }
        var qtyInput = tr.querySelector('input[name="quantity[]"]');
        if (qtyInput && !qtyInput._boundQty) {
            qtyInput._boundQty = true;
            var isAutofill = !!tr.closest('#autofillBatchBody');
            var section = isAutofill ? 'autofill' : 'manual';
            qtyInput.addEventListener('input', function() {
                refreshMaterialsFromRecipes(section);
                if (isAutofill) {
                    syncAutofillQtyToManual();
                }
            });
            qtyInput.addEventListener('change', function() {
                refreshMaterialsFromRecipes(section);
                if (isAutofill) {
                    syncAutofillQtyToManual();
                }
            });
        }
        var removeBtn = tr.querySelector('.btn-remove-line');
        if (removeBtn && !removeBtn._boundRm) {
            removeBtn._boundRm = true;
            removeBtn.addEventListener('click', function() {
                var section = tr.closest('#autofillBatchBody') ? 'autofill' : 'manual';
                var tbody = tr.parentElement;
                if (tbody && tbody.querySelectorAll('.batch-line-row').length > 1) {
                    tr.remove();
                    refreshMaterialsFromRecipes(section);
                }
            });
        }
        bindFermentationAndStatusSelects(tr);
    }

    function updateProductImage(tr) {
        var sel = tr.querySelector('.line-product-select');
        if (!sel) {
            return;
        }
        var opt = sel.options[sel.selectedIndex];
        if (!opt) {
            return;
        }
        var imageUrl = opt.getAttribute('data-image');
        var imgTag = tr.querySelector('img');
        if (imgTag) {
            if (imageUrl) {
                imgTag.src = 'assets/images/products/' + imageUrl;
                imgTag.style.display = 'inline-block';
            } else {
                imgTag.style.display = 'none';
            }
        }
    }

    function updateFermentation(tr) {
        var sel = tr.querySelector('.line-product-select');
        var fermTd = tr.querySelector('.line-fermentation-cell');
        if (!sel || !fermTd) {
            return;
        }
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) {
            fermTd.innerHTML = fermentationBadgeHtml('Not Applicable', 0, '');
            return;
        }
        refreshLineFermentationStatus(tr);
    }

    addManualLineBtn.addEventListener('click', function() {
        addLine(manualBatchBody, {});
        if (typeof calculateBatchExpiry === 'function') {
            calculateBatchExpiry('manual');
        }
    });

    /** --- Raw Materials (manual) --- */
    function renderSelectedMaterials() {
        if (selectedMaterials.length === 0) {
            materialsContainer.innerHTML = '<p style="color: var(--text-muted); text-align:center; padding:20px;">No materials selected. Add products or pick materials above.</p>';
            materialsSummary.style.display = 'none';
            return;
        }
        materialsSummary.style.display = 'block';
        document.getElementById('total_materials_count').textContent = selectedMaterials.length + ' material(s)';
        var html = '';
        selectedMaterials.forEach(function(mat) {
            var qtyClass = mat.quantity_used > mat.available ? 'style="color:#dc2626; font-weight:600;"' : '';
            var exceeds = mat.quantity_used > mat.available ? ' ⚠️ (Exceeds stock!)' : '';
            html += '<div class="material-item" data-material-id="' + mat.material_id + '">' +
                '<div>' +
                '<div class="material-name">' + mat.material_name + '</div>' +
                '<small style="color: var(--text-muted); font-size:12px;">Avail: ' + mat.available + ' ' + mat.unit + '</small>' +
                '</div>' +
                '<input type="number" name="material_qty[' + mat.material_id + ']" value="' + mat.quantity_used + '" step="0.01" min="0.01" required onchange="window.updateMaterialQty(' + mat.material_id + ', this.value)" ' + qtyClass + '>' +
                '<span style="font-size:12px; color: var(--text-muted);">' + mat.unit + exceeds + '</span>' +
                '<button type="button" class="remove-material-btn" onclick="window.removeMaterial(' + mat.material_id + ')">Remove</button>' +
                '</div>';
        });
        materialsContainer.innerHTML = html;
    }

    window.addMaterial = function() {
        var option = materialSelect.options[materialSelect.selectedIndex];
        if (!option || !option.value) {
            alert('Please select a material');
            return;
        }
        var qty = parseFloat(materialQtyInput.value) || 0;
        if (qty <= 0 || isNaN(qty)) {
            alert('Please enter a valid quantity');
            return;
        }
        var materialId = parseInt(option.value, 10);
        var materialName = option.getAttribute('data-name');
        var category = option.getAttribute('data-category');
        var available = parseFloat(option.getAttribute('data-quantity'));
        var unit = option.getAttribute('data-unit');
        var isExpired = option.getAttribute('data-expired') === '1';

        if (isExpired) {
            alert('⚠️ This material has expired and cannot be used for production.');
            return;
        }

        if (available <= 0) {
            if (!confirm('⚠️ WARNING: This material has zero or negative stock (' + available + ' ' + unit + ').\n\nAre you sure you want to add it to production? This should only be done for planning/forecasting purposes.')) {
                return;
            }
        }

        var minLevel = parseFloat(option.getAttribute('data-min')) || 0;
        if (minLevel > 0 && available <= minLevel) {
            alert('⚠️ Warning: This material is at or below minimum stock level (' + minLevel + ' ' + unit + ')');
        }

        if (qty > available) {
            alert('⚠️ Quantity exceeds available stock (' + available + ' ' + unit + '). Proceeding anyway.');
        }

        if (selectedMaterials.find(function(m) { return m.material_id === materialId; })) {
            alert('This material is already added.');
            return;
        }
        selectedMaterials.push({
            material_id: materialId,
            material_name: materialName,
            category: category,
            quantity_used: qty,
            available: available,
            unit: unit,
            is_expired: isExpired
        });
        renderSelectedMaterials();
        materialSelect.value = '';
        materialQtyInput.value = '';
        materialSelect.focus();
    };
    window.removeMaterial = function(id) {
        selectedMaterials = selectedMaterials.filter(function(m) { return m.material_id !== id; });
        renderSelectedMaterials();
    };
    window.updateMaterialQty = function(id, newQty) {
        var mat = selectedMaterials.find(function(m) { return m.material_id === id; });
        if (mat) {
            mat.quantity_used = parseFloat(newQty) || 0;
            renderSelectedMaterials();
        }
    };

    addMaterialBtn.addEventListener('click', window.addMaterial);
    materialQtyInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            window.addMaterial();
        }
    });

    /** --- Autofill materials --- */
    $('#autofill_add_material_btn').on('click', function() {
        var materialId = autofillMaterialSelect.val();
        var option = autofillMaterialSelect.find('option:selected');
        var materialName = option.data('name');
        var unit = option.data('unit');
        var available = parseFloat(option.attr('data-quantity')) || 0;
        var minLevel = parseFloat(option.attr('data-min')) || 0;
        var isExpired = option.attr('data-expired') === '1';
        var qty = parseFloat(autofillMaterialQtyInput.val());

        if (!materialId) {
            alert('Please select a material.');
            return;
        }
        if (!qty || qty <= 0) {
            alert('Please enter a valid quantity.');
            return;
        }

        if (isExpired) {
            alert('⚠️ This material has expired and cannot be used for production.');
            return;
        }

        if (available <= 0) {
            if (!confirm('⚠️ WARNING: This material has zero or negative stock (' + available + ' ' + unit + ').\n\nAre you sure you want to add it to production? This should only be done for planning/forecasting purposes.')) {
                return;
            }
        }

        if (minLevel > 0 && available <= minLevel) {
            alert('⚠️ Warning: This material is at or below minimum stock level (' + minLevel + ' ' + unit + ')');
        }

        if (qty > available) {
            alert('⚠️ Quantity exceeds available stock (' + available + ' ' + unit + '). Proceeding anyway.');
        }

        if (autofillMaterialsContainer.find('#material_' + materialId).length > 0) {
            alert('This material is already added.');
            return;
        }

        var html =
            '<div class="material-item" id="material_' + materialId + '" data-manual="1">' +
            '<div>' +
            '<div>' + materialName + '</div>' +
            '<small style="color: var(--text-muted); font-size:12px;">Avail: ' + available + ' ' + unit + '</small>' +
            '</div>' +
            '<input type="number" class="autofill-qty" value="' + qty + '" step="0.01" min="0.01" style="width:80px; padding:8px;">' +
            '<span style="font-size:12px; color: var(--text-muted);">' + unit + '</span>' +
            '<input type="hidden" class="autofill-id" value="' + materialId + '">' +
            '<button type="button" class="remove-material-btn">Remove</button>' +
            '</div>';
        autofillMaterialsContainer.append(html);
        autofillMaterialQtyInput.val('');
        var total = autofillMaterialsContainer.find('.material-item').length;
        autofillTotalCount.text(total + ' material(s)');
        autofillMaterialsSummary.show();
    });

    autofillMaterialsContainer.on('click', '.remove-material-btn', function() {
        $(this).closest('.material-item').remove();
        var total = autofillMaterialsContainer.find('.material-item').length;
        autofillTotalCount.text(total + ' material(s)');
        if (total === 0) {
            autofillMaterialsSummary.hide();
        }
    });

    /** --- Filter raw materials by first product category --- */
    function filterMaterialsByProduct() {
        var firstRow = batchLinesBody.querySelector('.batch-line-row') || manualBatchBody.querySelector('.batch-line-row');
        if (!firstRow) {
            materialFilterHint.textContent = '';
            return;
        }
        var sel = firstRow.querySelector('.line-product-select');
        var hid = firstRow.querySelector('input[name="product_id[]"]');
        var categoryId = 0;
        if (sel && sel.value) {
            var opt = sel.options[sel.selectedIndex];
            categoryId = opt ? parseInt(opt.getAttribute('data-category-id'), 10) : 0;
        } else if (hid && hid.value) {
            var match = null;
            var pid = parseInt(hid.value, 10);
            baseProductOptions.fermentation.concat(baseProductOptions.noFermentation).some(function(p) {
                if (parseInt(p.id, 10) === pid) {
                    match = p;
                    return true;
                }
                return false;
            });
            categoryId = match ? match.category_id : 0;
        }
        var allowedIds = (categoryId && categoryMaterialIds[categoryId]) ? categoryMaterialIds[categoryId] : null;
        materialFilterHint.textContent = (allowedIds && allowedIds.length) ? 'Filtered by first product category.' : 'All raw materials.';
        materialSelect.querySelectorAll('optgroup option').forEach(function(o) {
            var id = parseInt(o.value, 10);
            if (!id) {
                return;
            }
            o.disabled = !!(allowedIds && allowedIds.length && allowedIds.indexOf(id) === -1);
        });
    }

    /** --- Save autofill batch --- */
    $('#saveAutofillBatch').on('click', function() {
        var rows = batchLinesBody.querySelectorAll('.batch-line-row');
        if (!rows || rows.length === 0) {
            alert('No lines to save');
            return;
        }

        syncAutofillToManualFull();

        var autofillProdDate = document.getElementById('autofill_production_date_input').value;
        if (autofillProdDate) {
            document.getElementById('production_date_input').value = autofillProdDate;
        }

        var autofillWarehouse = document.getElementById('autofill_warehouse_location');
        if (autofillWarehouse) {
            var manualWarehouse = document.querySelector('input[name="warehouse_location"]');
            if (manualWarehouse) {
                manualWarehouse.value = autofillWarehouse.value;
            }
        }

        $('#productionForm input[name^="material_qty"]').remove();
        $('#productionForm .autofill-hidden-material').remove();
        autofillMaterialsContainer.find('.material-item').each(function() {
            var id = $(this).find('.autofill-id').val();
            var qtyInp = $(this).find('.autofill-qty');
            var qty = qtyInp.length ? qtyInp.val() : '';
            if (!id || !qty) {
                return;
            }
            var input = document.createElement('input');
            input.type = 'hidden';
            input.className = 'autofill-hidden-material';
            input.name = 'material_qty[' + id + ']';
            input.value = qty;
            document.getElementById('productionForm').appendChild(input);
        });

        if (typeof calculateBatchExpiry === 'function') {
            calculateBatchExpiry('manual');
        }

        if (hiddenRequestIds && hiddenRequestIds.value) {
            document.getElementById('hidden_request_ids').value = hiddenRequestIds.value;
        }

        if (productionForm) {
            productionForm.submit();
        } else {
            document.getElementById('manualBatchCard').scrollIntoView({ behavior: 'smooth' });
        }
    });

    renderSelectedMaterials();
    loadRequestGroups();
    requestGroupSelect.addEventListener('change', function() { onRequestGroupChange(this.value); });
    $('#autofill_material_select').closest('.materials-section').hide();

    var manualDate = document.getElementById('production_date_input');
    if (manualDate) {
        manualDate.addEventListener('change', refreshAllFermentationStatuses);
    }
    var autoDate = document.getElementById('autofill_production_date_input');
    if (autoDate) {
        autoDate.addEventListener('change', refreshAllFermentationStatuses);
    }
    refreshAllFermentationStatuses();
});
