// assets/js/production_requests.js
$(document).ready(function() {

    // --- Check for in-progress batches on page load ---
    var processingBatches = JSON.parse(localStorage.getItem('processingBatches') || '[]');
    var now = Date.now();
    
    // Clean up old entries (older than 30 minutes)
    processingBatches = processingBatches.filter(function(item) {
        return (now - item.timestamp) < 30 * 60 * 1000;
    });
    localStorage.setItem('processingBatches', JSON.stringify(processingBatches));

    // Disable Create Batch buttons for in-progress batches
    $('.btn-create-batch').each(function() {
        var $btn = $(this);
        var href = $btn.attr('href');
        var ids = (href && href.split('request_ids=')[1]) ? decodeURIComponent(href.split('request_ids=')[1]) : '';
        
        var isProcessing = processingBatches.some(function(item) { 
            return item.ids === ids; 
        });
        
        if (isProcessing) {
            $btn.prop('disabled', true)
                .css({ 'background': '#f59e0b', 'color': '#fff', 'opacity': '0.7' })
                .text('Creating Batch...')
                .attr('title', 'Batch is being created. Please wait...');
        }
    });

    // --- Inline status update (single or grouped requests) ---
    $('.status-dropdown').on('change', function() {
        var ids = $(this).data('ids');
        var val = $(this).val();
        if (!ids) return;

        var payload = { ids: ids.toString().split(',').map(function(x){ return x.trim(); }), status: val };
        $.post('api/update_production_request.php', payload, function(){
            location.reload();
        });
    });

    // --- Inline priority update ---
    $('.priority-dropdown').on('change', function() {
        var ids = $(this).data('ids');
        var val = $(this).val();
        if (!ids) return;

        var payload = { ids: ids.toString().split(',').map(function(x){ return x.trim(); }), priority: val };
        $.post('api/update_production_request.php', payload, function(){
            location.reload();
        });
    });

    // --- Inline due date update ---
    $('.due-date').on('change', function() {
        var ids = $(this).data('ids');
        var val = $(this).val();
        if (!ids) return;

        var idList = ids.toString().split(',').map(function(x){ return x.trim(); });
        idList.forEach(function(rid) {
            $.post('api/update_production_request.php', { id: rid, due_date: val });
        });
    });

    // --- Filter/search functionality ---
    $('#searchCustomer, #filterStatus, #filterPriority').on('input change', function() {
        var search = $('#searchCustomer').val().toLowerCase();
        var status = $('#filterStatus').val();
        var priority = $('#filterPriority').val();

        $('#requestsTable tbody tr').each(function() {
            var rowText = $(this).find('td:eq(2)').text().toLowerCase();
            var rowStatus = $(this).find('.status-dropdown').val();
            var rowPriority = $(this).find('.priority-dropdown').val();
            var show = true;
            if (search && !rowText.includes(search)) show = false;
            if (status && rowStatus !== status) show = false;
            if (priority && rowPriority !== priority) show = false;
            $(this).toggle(show);
        });
    });

    // --- Reset filters ---
    $('#resetFilters').on('click', function() {
        $('#searchCustomer').val('');
        $('#filterStatus').val('');
        $('#filterPriority').val('');
        $('#requestsTable tbody tr').show();
    });

    // --- Select all checkboxes ---
    $('#selectAll').on('change', function() {
        $('.rowCheckbox').prop('checked', $(this).is(':checked'));
    });

    // --- Batch actions ---
    function batchUpdate(status) {
        var idSets = $('.rowCheckbox:checked').map(function(){ return $(this).data('ids'); }).get();
        var ids = [];
        idSets.forEach(function(s) {
            if (s) s.toString().split(',').forEach(function(x){ 
                var n = parseInt(x.trim(),10); 
                if (n) ids.push(n); 
            });
        });
        if(ids.length === 0) { 
            alert('Select at least one request'); 
            return; 
        }
        $.post('api/update_production_request.php', {ids: ids, status: status}, function(){ 
            location.reload(); 
        });
    }

    $('#batchInProgress').on('click', function(){ batchUpdate('In Progress'); });
    $('#batchCompleted').on('click', function(){ batchUpdate('Completed'); });

    // --- NEW: API fetch for Record Production Batch page ---
    // This returns JSON for the dropdown in production_record.php
    window.fetchProductionRequestsForBatch = function(callback) {
        $.ajax({
            url: 'api/get_production_requests.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                // Each item should include request_id, customer_name, and products array
                if(callback) callback(data);
            },
            error: function(err) {
                console.error('Error fetching production requests', err);
                if(callback) callback([]);
            }
        });
    };

    // Create Batch button: navigate to start_production_batch API, which sets status to In Progress
    // and redirects to production_record.php with the request pre-selected in the dropdown

});