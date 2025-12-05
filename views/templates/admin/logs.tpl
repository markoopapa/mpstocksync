<div class="panel">
    <div class="panel-heading">
        <i class="icon-list"></i> Sync Logs
        <div class="pull-right">
            <a href="{$form_action}&clear_logs" class="btn btn-danger btn-sm"
               onclick="return confirm('Clear all logs? This cannot be undone.')">
                <i class="icon-trash"></i> Clear All Logs
            </a>
            <a href="{$form_action}&export_logs" class="btn btn-default btn-sm">
                <i class="icon-download"></i> Export
            </a>
        </div>
    </div>
    
    <div class="panel-body">
        {if $errors}
            <div class="alert alert-danger">
                {foreach from=$errors item=error}
                    <p>{$error}</p>
                {/foreach}
            </div>
        {/if}
        
        {if $confirmations}
            <div class="alert alert-success">
                {foreach from=$confirmations item=confirmation}
                    <p>{$confirmation}</p>
                {/foreach}
            </div>
        {/if}
        
        <div class="row">
            <div class="col-md-12">
                <div class="well">
                    <form method="get" class="form-inline">
                        <input type="hidden" name="controller" value="AdminMpStockSyncLogs">
                        <input type="hidden" name="token" value="{$token}">
                        
                        <div class="form-group">
                            <label for="filter_api">Platform:</label>
                            <select name="filter_api" id="filter_api" class="form-control input-sm">
                                <option value="">All</option>
                                <option value="emag" {if $filter_api == 'emag'}selected{/if}>eMAG</option>
                                <option value="trendyol" {if $filter_api == 'trendyol'}selected{/if}>Trendyol</option>
                                <option value="supplier" {if $filter_api == 'supplier'}selected{/if}>Supplier</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="filter_status">Status:</label>
                            <select name="filter_status" id="filter_status" class="form-control input-sm">
                                <option value="">All</option>
                                <option value="1" {if $filter_status == '1'}selected{/if}>Success</option>
                                <option value="0" {if $filter_status == '0'}selected{/if}>Failed</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="filter_date_from">Date From:</label>
                            <input type="date" name="filter_date_from" id="filter_date_from" 
                                   class="form-control input-sm" value="{$filter_date_from}">
                        </div>
                        
                        <div class="form-group">
                            <label for="filter_date_to">Date To:</label>
                            <input type="date" name="filter_date_to" id="filter_date_to" 
                                   class="form-control input-sm" value="{$filter_date_to}">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="icon-search"></i> Filter
                        </button>
                        <a href="{$form_action}" class="btn btn-default btn-sm">Reset</a>
                    </form>
                </div>
            </div>
        </div>
        
        {if $logs}
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th width="100">Platform</th>
                        <th width="80">Product ID</th>
                        <th>Action</th>
                        <th width="100">Status</th>
                        <th>Message</th>
                        <th width="140">Date</th>
                        <th width="80">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$logs item=log}
                        <tr class="{if $log.status == 0}danger{else}success{/if}">
                            <td>{$log.id_log}</td>
                            <td>
                                {if $log.api_name == 'emag'}
                                    <span class="label label-primary">eMAG</span>
                                {elseif $log.api_name == 'trendyol'}
                                    <span class="label" style="background:#ff6b00">Trendyol</span>
                                {elseif $log.api_name == 'supplier'}
                                    <span class="label label-info">Supplier</span>
                                {else}
                                    <span class="label label-default">{$log.api_name}</span>
                                {/if}
                            </td>
                            <td>
                                {if $log.id_product > 0}
                                    <a href="{$link->getAdminLink('AdminProducts')}&id_product={$log.id_product}&updateproduct"
                                       target="_blank">
                                        {$log.id_product}
                                    </a>
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>
                                <span class="label {if $log.action == 'error'}label-danger{else}label-default{/if}">
                                    {$log.action}
                                </span>
                            </td>
                            <td>
                                {if $log.status == 1}
                                    <span class="label label-success">Success</span>
                                {else}
                                    <span class="label label-danger">Failed</span>
                                {/if}
                            </td>
                            <td>
                                {if $log.error_message}
                                    <span class="text-danger">{$log.error_message|truncate:50}</span>
                                {elseif $log.response_data}
                                    <span class="text-muted">Response received</span>
                                {else}
                                    <span class="text-success">OK</span>
                                {/if}
                            </td>
                            <td>{$log.date_add}</td>
                            <td>
                                <button type="button" class="btn btn-default btn-xs view-log-details" 
                                        data-log-id="{$log.id_log}">
                                    <i class="icon-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            
            {if $total_pages > 1}
                <div class="text-center">
                    <ul class="pagination">
                        {if $current_page > 1}
                            <li>
                                <a href="{$form_action}&page={$current_page-1}{$filter_query}">&laquo;</a>
                            </li>
                        {/if}
                        
                        {for $i=1 to $total_pages}
                            <li class="{if $i == $current_page}active{/if}">
                                <a href="{$form_action}&page={$i}{$filter_query}">{$i}</a>
                            </li>
                        {/for}
                        
                        {if $current_page < $total_pages}
                            <li>
                                <a href="{$form_action}&page={$current_page+1}{$filter_query}">&raquo;</a>
                            </li>
                        {/if}
                    </ul>
                </div>
            {/if}
            
            <div class="text-muted text-center">
                Showing {$start_item}-{$end_item} of {$total_items} logs
            </div>
        {else}
            <div class="alert alert-info text-center">
                <i class="icon-info"></i> No sync logs found.
            </div>
        {/if}
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Log Details #<span id="log-id"></span></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Basic Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Platform:</th>
                                <td id="log-platform"></td>
                            </tr>
                            <tr>
                                <th>Product ID:</th>
                                <td id="log-product"></td>
                            </tr>
                            <tr>
                                <th>Action:</th>
                                <td id="log-action"></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td id="log-status"></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td id="log-date"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Data</h5>
                        <div class="well" style="max-height: 200px; overflow-y: auto;">
                            <pre id="log-data" style="background: transparent; border: none; margin: 0;"></pre>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h5>Error Message</h5>
                        <div class="well" style="max-height: 150px; overflow-y: auto;">
                            <pre id="log-error" style="background: transparent; border: none; margin: 0;"></pre>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h5>Response Data</h5>
                        <div class="well" style="max-height: 300px; overflow-y: auto;">
                            <pre id="log-response" style="background: transparent; border: none; margin: 0;"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // View log details
    $('.view-log-details').on('click', function() {
        var logId = $(this).data('log-id');
        
        $.ajax({
            url: '{$form_action}',
            type: 'GET',
            data: {
                action: 'get_log_details',
                id_log: logId,
                ajax: true
            },
            success: function(response) {
                var log = JSON.parse(response);
                
                $('#log-id').text(log.id_log);
                $('#log-platform').text(log.api_name);
                $('#log-product').html(log.id_product > 0 ? 
                    '<a href="{$link->getAdminLink("AdminProducts")}&id_product=' + log.id_product + '&updateproduct" target="_blank">' + 
                    log.id_product + '</a>' : '-');
                $('#log-action').html('<span class="label ' + (log.action == 'error' ? 'label-danger' : 'label-default') + '">' + 
                    log.action + '</span>');
                $('#log-status').html(log.status == 1 ? 
                    '<span class="label label-success">Success</span>' : 
                    '<span class="label label-danger">Failed</span>');
                $('#log-date').text(log.date_add);
                
                // Format and display data
                try {
                    var oldData = JSON.parse(log.old_value || '{}');
                    var newData = JSON.parse(log.new_value || '{}');
                    
                    var dataStr = 'Old Value:\n' + JSON.stringify(oldData, null, 2) + 
                                 '\n\nNew Value:\n' + JSON.stringify(newData, null, 2);
                    $('#log-data').text(dataStr);
                } catch(e) {
                    $('#log-data').text(log.old_value || 'No data');
                }
                
                $('#log-error').text(log.error_message || 'No error message');
                
                try {
                    var responseData = JSON.parse(log.response_data || '{}');
                    $('#log-response').text(JSON.stringify(responseData, null, 2));
                } catch(e) {
                    $('#log-response').text(log.response_data || 'No response data');
                }
                
                $('#logDetailsModal').modal('show');
            }
        });
    });
    
    // Auto-refresh logs every 30 seconds if on page 1
    {if $current_page == 1}
    setInterval(function() {
        window.location.reload();
    }, 30000); // 30 seconds
    {/if}
});
</script>

<style>
.pagination {
    margin: 0;
}
.well pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
