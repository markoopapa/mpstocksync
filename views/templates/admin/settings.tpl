<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> Module Settings
    </div>
    <div class="panel-body">
        {$settings_form}
        
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <i class="icon-info"></i> Information
                    </div>
                    <div class="panel-body">
                        <p><strong>Module Version:</strong> 2.0.0</p>
                        <p><strong>PrestaShop Compatibility:</strong> 1.7.0.0 - 9.9.9</p>
                        <p><strong>Supported APIs:</strong> eMAG, Trendyol</p>
                        <p><strong>Last Database Update:</strong> 
                            {if Configuration::get('MP_LAST_DB_UPDATE')}
                                {Configuration::get('MP_LAST_DB_UPDATE')}
                            {else}
                                Never
                            {/if}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <i class="icon-warning"></i> Maintenance
                    </div>
                    <div class="panel-body">
                        <div class="text-center">
                            <a href="{$link->getAdminLink('AdminMpStockSyncLogs')}&clear_logs&token={$token}" 
                               class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                <i class="icon-trash"></i> Clear All Logs
                            </a>
                            <br><br>
                            <a href="{$link->getAdminLink('AdminMpStockSyncDashboard')}&sync_all&token={$token}" 
                               class="btn btn-primary">
                                <i class="icon-refresh"></i> Force Full Sync
                            </a>
                            <br><br>
                            <a href="#" class="btn btn-default" id="check-updates">
                                <i class="icon-download"></i> Check for Updates
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#check-updates').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{$link->getAdminLink('AdminMpStockSyncSettings')}',
            data: {
                action: 'check_updates',
                ajax: true,
                token: '{$token}'
            },
            success: function(response) {
                alert(response.message);
            }
        });
    });
});
</script>
