<div class="panel">
    <div class="panel-heading">
        <i class="icon-truck"></i> Suppliers
        <span class="badge">{count($suppliers)}</span>
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
        
        {if $suppliers}
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Target Shops</th>
                        <th>Last Sync</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$suppliers item=supplier}
                        <tr>
                            <td>{$supplier.id_supplier}</td>
                            <td><strong>{$supplier.name}</strong></td>
                            <td>
                                {if $supplier.connection_type == 'database'}
                                    <span class="label label-info">Database</span>
                                {else}
                                    <span class="label label-primary">API</span>
                                {/if}
                                {if $supplier.auto_sync}
                                    <span class="label label-success">Auto</span>
                                {/if}
                            </td>
                            <td>{$supplier.target_shops_display}</td>
                            <td>
                                {if $supplier.last_sync}
                                    {$supplier.last_sync}
                                {else}
                                    <span class="text-muted">Never</span>
                                {/if}
                            </td>
                            <td>
                                {if $supplier.active}
                                    <span class="label label-success">Active</span>
                                {else}
                                    <span class="label label-danger">Inactive</span>
                                {/if}
                            </td>
                            <td>
                                <a href="{$form_action}&id_supplier={$supplier.id_supplier}&update" 
                                   class="btn btn-default btn-sm">
                                    <i class="icon-edit"></i> Edit
                                </a>
                                <a href="{$form_action}&sync_supplier={$supplier.id_supplier}" 
                                   class="btn btn-primary btn-sm" 
                                   onclick="return confirm('Sync this supplier now?')">
                                    <i class="icon-refresh"></i> Sync
                                </a>
                                <a href="{$form_action}&delete_supplier={$supplier.id_supplier}" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this supplier?')">
                                    <i class="icon-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <div class="alert alert-info">
                <i class="icon-info"></i> No suppliers configured yet.
                <a href="{$form_action}&addsupplier">Add your first supplier</a>
            </div>
        {/if}
        
        <div class="row">
            <div class="col-md-12 text-center">
                <a href="{$form_action}&addsupplier" class="btn btn-primary btn-lg">
                    <i class="icon-plus"></i> Add New Supplier
                </a>
                <a href="{$form_action}&syncall" class="btn btn-success btn-lg"
                   onclick="return confirm('Sync all active suppliers?')">
                    <i class="icon-cogs"></i> Sync All Suppliers
                </a>
            </div>
        </div>
    </div>
</div>

{if isset($form) && $form}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-edit"></i> 
            {if isset($supplier)}Edit Supplier{else}Add New Supplier{/if}
        </div>
        <div class="panel-body">
            {$form}
        </div>
    </div>
{/if}

<script>
$(document).ready(function() {
    // Show/hide fields based on connection type
    function toggleConnectionFields() {
        var type = $('#connection_type').val();
        
        if (type == 'database') {
            $('.database-field').show();
            $('.api-field').hide();
        } else if (type == 'api') {
            $('.database-field').hide();
            $('.api-field').show();
        } else {
            $('.database-field').hide();
            $('.api-field').hide();
        }
    }
    
    $('#connection_type').on('change', toggleConnectionFields);
    toggleConnectionFields(); // Initial call
    
    // Test connection button
    $('[name="test_connection"]').on('click', function(e) {
        e.preventDefault();
        
        var formData = $('#supplier_form').serialize();
        
        $.ajax({
            url: '{$module_url}',
            type: 'POST',
            data: formData + '&test_connection=1&ajax=true',
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    alert('Connection test successful: ' + result.message);
                } else {
                    alert('Connection test failed: ' + result.message);
                }
            },
            error: function() {
                alert('Connection test request failed');
            }
        });
    });
});
</script>

<style>
.hidden {
    display: none;
}
.database-field, .api-field {
    transition: all 0.3s ease;
}
</style>
