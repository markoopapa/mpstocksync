<div class="panel">
    <div class="panel-heading">
        <i class="icon-dashboard"></i> {l s='Stock Sync Dashboard' mod='mpstocksync'}
    </div>
    
    <div class="panel-body">
        <!-- Last Sync Info -->
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="icon-info"></i> 
                    <strong>{l s='Last Sync:' mod='mpstocksync'}</strong> 
                    {$stats.last_sync_log}
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="row">
            <!-- eMAG Statistics -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-cogs"></i> {l s='eMAG Sync Statistics' mod='mpstocksync'}
                    </div>
                    <div class="panel-body">
                        <table class="table">
                            <tr>
                                <td><strong>{l s='Total Syncs:' mod='mpstocksync'}</strong></td>
                                <td class="text-right">{$stats.emag.total|intval}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Successful:' mod='mpstocksync'}</strong></td>
                                <td class="text-right text-success">{$stats.emag.success|intval}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Failed:' mod='mpstocksync'}</strong></td>
                                <td class="text-right text-danger">{$stats.emag.failed|intval}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Trendyol Statistics -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-cogs"></i> {l s='Trendyol Sync Statistics' mod='mpstocksync'}
                    </div>
                    <div class="panel-body">
                        <table class="table">
                            <tr>
                                <td><strong>{l s='Total Syncs:' mod='mpstocksync'}</strong></td>
                                <td class="text-right">{$stats.trendyol.total|intval}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Successful:' mod='mpstocksync'}</strong></td>
                                <td class="text-right text-success">{$stats.trendyol.success|intval}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Failed:' mod='mpstocksync'}</strong></td>
                                <td class="text-right text-danger">{$stats.trendyol.failed|intval}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Syncs -->
        {if !empty($stats.recent_syncs)}
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-clock-o"></i> {l s='Recent Sync Activities' mod='mpstocksync'}
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{l s='Date' mod='mpstocksync'}</th>
                                    <th>{l s='Type' mod='mpstocksync'}</th>
                                    <th>{l s='Products' mod='mpstocksync'}</th>
                                    <th>{l s='Success' mod='mpstocksync'}</th>
                                    <th>{l s='Errors' mod='mpstocksync'}</th>
                                    <th>{l s='Duration' mod='mpstocksync'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $stats.recent_syncs as $sync}
                                <tr>
                                    <td>{$sync.date_add|date_format:"%Y-%m-%d %H:%M"}</td>
                                    <td>{$sync.sync_type}</td>
                                    <td>{$sync.products_count|intval}</td>
                                    <td class="text-success">{$sync.success_count|intval}</td>
                                    <td class="text-danger">{$sync.error_count|intval}</td>
                                    <td>{$sync.duration|intval} ms</td>
                                </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {/if}
        
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-rocket"></i> {l s='Quick Actions' mod='mpstocksync'}
                    </div>
                    <div class="panel-body">
                        <a href="{$link->getAdminLink('AdminMpStockSyncProducts')}" class="btn btn-primary">
                            <i class="icon-link"></i> {l s='Manage Product Mapping' mod='mpstocksync'}
                        </a>
                        <a href="{$link->getAdminLink('AdminMpStockSyncApi')}" class="btn btn-default">
                            <i class="icon-cogs"></i> {l s='API Settings' mod='mpstocksync'}
                        </a>
                        <a href="{$link->getAdminLink('AdminMpStockSyncManualSync')}" class="btn btn-success">
                            <i class="icon-sync"></i> {l s='Manual Sync' mod='mpstocksync'}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
