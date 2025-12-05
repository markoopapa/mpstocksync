// Real-time sync status updates
var SyncManager = {
    interval: null,
    isSyncing: false,
    
    init: function() {
        this.checkSyncStatus();
        this.interval = setInterval(this.checkSyncStatus.bind(this), 5000);
    },
    
    checkSyncStatus: function() {
        if (this.isSyncing) return;
        
        $.ajax({
            url: mpstocksync_ajax_url,
            type: 'POST',
            data: {
                action: 'check_sync_status',
                token: mpstocksync_token,
                ajax: true
            },
            success: function(response) {
                var data = JSON.parse(response);
                SyncManager.updateStatus(data);
            }
        });
    },
    
    updateStatus: function(data) {
        // Update queue counts
        if (data.emag_queue) {
            $('#emag-queue-count').text(data.emag_queue.pending);
            $('#emag-processing-count').text(data.emag_queue.processing);
        }
        
        if (data.trendyol_queue) {
            $('#trendyol-queue-count').text(data.trendyol_queue.pending);
            $('#trendyol-processing-count').text(data.trendyol_queue.processing);
        }
        
        // Update progress bars
        if (data.emag_progress) {
            this.updateProgressBar('emag-progress', data.emag_progress);
        }
        
        if (data.trendyol_progress) {
            this.updateProgressBar('trendyol-progress', data.trendyol_progress);
        }
        
        // Update last sync times
        if (data.last_sync) {
            $('#last-sync-time').text(data.last_sync);
        }
    },
    
    updateProgressBar: function(id, progress) {
        var bar = $('#' + id + ' .sync-progress-bar');
        var text = $('#' + id + ' .progress-text');
        
        bar.css('width', progress.percent + '%');
        text.text(progress.current + '/' + progress.total + ' (' + progress.percent + '%)');
    },
    
    startBatchSync: function(api, productIds) {
        this.isSyncing = true;
        $('#sync-progress-modal').modal('show');
        
        this.processBatch(api, productIds, 0);
    },
    
    processBatch: function(api, productIds, index) {
        if (index >= productIds.length) {
            this.isSyncing = false;
            $('#sync-progress-modal').modal('hide');
            showMessage('success', 'Batch sync completed');
            return;
        }
        
        var batchSize = 10;
        var batch = productIds.slice(index, index + batchSize);
        
        // Update progress
        var percent = Math.round((index / productIds.length) * 100);
        $('#batch-progress-bar').css('width', percent + '%');
        $('#batch-progress-text').text(index + '/' + productIds.length + ' (' + percent + '%)');
        
        // Send batch
        $.ajax({
            url: mpstocksync_ajax_url,
            type: 'POST',
            data: {
                action: 'sync_batch',
                api: api,
                products: batch,
                token: mpstocksync_token,
                ajax: true
            },
            success: function(response) {
                var data = JSON.parse(response);
                
                if (data.failed && data.failed.length > 0) {
                    // Log failed items
                    console.log('Failed items:', data.failed);
                }
                
                // Process next batch
                SyncManager.processBatch(api, productIds, index + batchSize);
            },
            error: function() {
                // Retry current batch
                setTimeout(function() {
                    SyncManager.processBatch(api, productIds, index);
                }, 5000);
            }
        });
    }
};

// Initialize on page load
$(document).ready(function() {
    if (typeof mpstocksync_ajax_url !== 'undefined') {
        SyncManager.init();
    }
});
