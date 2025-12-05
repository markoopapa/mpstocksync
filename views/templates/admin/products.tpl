<div class="panel">
    <div class="panel-heading">
        <i class="icon-link"></i> Product Mapping
    </div>
    
    <div class="panel-body">
        <h3>Product Mapping</h3>
        <p>Map your PrestaShop products to marketplace products</p>
        
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <i class="icon-info"></i> Instructions
                    </div>
                    <div class="panel-body">
                        <h4>How to map products:</h4>
                        <ol>
                            <li>Select a marketplace (eMAG or Trendyol)</li>
                            <li>Select your PrestaShop product</li>
                            <li>Enter the external ID:
                                <ul>
                                    <li><strong>eMAG:</strong> Your SKU</li>
                                    <li><strong>Trendyol:</strong> EAN13 barcode</li>
                                </ul>
                            </li>
                            <li>Save the mapping</li>
                        </ol>
                        
                        <div class="alert alert-warning">
                            <i class="icon-warning"></i> 
                            <strong>Important:</strong> Make sure the external ID matches exactly with the marketplace.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <i class="icon-rocket"></i> Quick Actions
                    </div>
                    <div class="panel-body text-center">
                        <a href="#" class="btn btn-primary btn-lg btn-block" id="add-mapping-btn">
                            <i class="icon-plus"></i> Add New Mapping
                        </a>
                        <a href="#" class="btn btn-default btn-block" id="import-csv-btn">
                            <i class="icon-upload"></i> Import from CSV
                        </a>
                        <a href="#" class="btn btn-default btn-block" id="export-csv-btn">
                            <i class="icon-download"></i> Export to CSV
                        </a>
                        <a href="#" class="btn btn-info btn-block" id="auto-match-btn">
                            <i class="icon-magic"></i> Auto-match Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-list"></i> Existing Mappings
                    </div>
                    <div class="panel-body">
                        {if isset($mappings) && $mappings}
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Marketplace</th>
                                        <th>Product</th>
                                        <th>External ID</th>
                                        <th>Last Sync</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$mappings item=mapping}
                                    <tr>
                                        <td>{$mapping.id_mapping}</td>
                                        <td>
                                            {if $mapping.api_name == 'emag'}
                                                <span class="label label-primary">eMAG</span>
                                            {elseif $mapping.api_name == 'trendyol'}
                                                <span class="label" style="background:#ff6b00">Trendyol</span>
                                            {else}
                                                {$mapping.api_name}
                                            {/if}
                                        </td>
                                        <td>
                                            {if $mapping.product_name}
                                                {$mapping.product_name}
                                            {else}
                                                ID: {$mapping.id_product}
                                            {/if}
                                        </td>
                                        <td><code>{$mapping.external_id}</code></td>
                                        <td>
                                            {if $mapping.last_sync}
                                                {$mapping.last_sync}
                                            {else}
                                                <span class="text-muted">Never</span>
                                            {/if}
                                        </td>
                                        <td>
                                            {if $mapping.active}
                                                <span class="label label-success">Active</span>
                                            {else}
                                                <span class="label label-danger">Inactive</span>
                                            {/if}
                                        </td>
                                        <td>
                                            <a href="#" class="btn btn-default btn-sm edit-mapping" data-id="{$mapping.id_mapping}">
                                                <i class="icon-edit"></i> Edit
                                            </a>
                                            <a href="#" class="btn btn-danger btn-sm delete-mapping" data-id="{$mapping.id_mapping}">
                                                <i class="icon-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        {else}
                            <p class="text-center text-muted">
                                <i class="icon-info"></i> No product mappings found. 
                                <a href="#" id="add-first-mapping">Add your first mapping</a>
                            </p>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mapping-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Product Mapping</h4>
            </div>
            <div class="modal-body">
                <form id="mapping-form">
                    <div class="form-group">
                        <label>Marketplace</label>
                        <select name="api_name" class="form-control" required>
                            <option value="">-- Select --</option>
                            <option value="emag">eMAG</option>
                            <option value="trendyol">Trendyol</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product Search</label>
                        <input type="text" class="form-control" id="product-search" placeholder="Search by name, SKU or EAN">
                        <div id="product-results" class="list-group" style="display:none; max-height:200px; overflow-y:auto;"></div>
                    </div>
                    <div class="form-group">
                        <label>External ID</label>
                        <input type="text" name="external_id" class="form-control" required 
                               placeholder="SKU for eMAG, EAN for Trendyol">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="active" value="1" checked> Active
                        </label>
                    </div>
                    <input type="hidden" name="id_product" id="selected-product">
                    <input type="hidden" name="id_mapping" value="0">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-mapping">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Open modal for new mapping
    $('#add-mapping-btn, #add-first-mapping').on('click', function(e) {
        e.preventDefault();
        $('#mapping-form')[0].reset();
        $('#mapping-form input[name="id_mapping"]').val(0);
        $('#mapping-modal').modal('show');
    });
    
    // Product search
    $('#product-search').on('keyup', function() {
        var query = $(this).val();
        if (query.length < 2) {
            $('#product-results').hide();
            return;
        }
        
        $.ajax({
            url: '{$link->getAdminLink("AdminMpStockSyncProducts")}',
            data: {
                action: 'search_products',
                query: query,
                ajax: true,
                token: '{$token}'
            },
            success: function(response) {
                var results = JSON.parse(response);
                var html = '';
                
                if (results.length > 0) {
                    results.forEach(function(product) {
                        html += '<a href="#" class="list-group-item select-product" ' +
                               'data-id="' + product.id_product + '" ' +
                               'data-name="' + product.name + '">' +
                               product.name + ' (SKU: ' + product.reference + 
                               (product.ean13 ? ', EAN: ' + product.ean13 : '') + ')' +
                               '</a>';
                    });
                } else {
                    html = '<div class="list-group-item">No products found</div>';
                }
                
                $('#product-results').html(html).show();
            }
        });
    });
    
    // Select product
    $(document).on('click', '.select-product', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#selected-product').val(id);
        $('#product-search').val(name);
        $('#product-results').hide();
    });
    
    // Save mapping
    $('#save-mapping').on('click', function() {
        var formData = $('#mapping-form').serialize();
        
        $.ajax({
            url: '{$link->getAdminLink("AdminMpStockSyncProducts")}',
            type: 'POST',
            data: formData + '&save_mapping=1&ajax=true&token={$token}',
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    $('#mapping-modal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            }
        });
    });
    
    // Edit mapping
    $('.edit-mapping').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        
        // TODO: Load mapping data and populate form
        $('#mapping-modal').modal('show');
    });
    
    // Delete mapping
    $('.delete-mapping').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this mapping?')) {
            $.ajax({
                url: '{$link->getAdminLink("AdminMpStockSyncProducts")}',
                data: {
                    action: 'delete_mapping',
                    id: id,
                    ajax: true,
                    token: '{$token}'
                },
                success: function() {
                    location.reload();
                }
            });
        }
    });
});
</script>

<style>
#product-results {
    position: absolute;
    z-index: 1000;
    width: 100%;
    border: 1px solid #ddd;
    background: white;
}
.select-product:hover {
    background: #f5f5f5;
    cursor: pointer;
}
</style>
