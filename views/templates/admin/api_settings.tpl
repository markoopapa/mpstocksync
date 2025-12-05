<form action="{$post_url|escape:'html':'UTF-8'}" method="post" class="form-horizontal">
    <input type="hidden" name="token" value="{$token|escape:'html':'UTF-8'}">
    
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='API Settings - Stock Sync Only' mod='mpstocksync'}
        </div>
        
        <!-- eMAG Settings -->
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-cog"></i> {l s='eMAG Stock Sync' mod='mpstocksync'}
                <button type="submit" name="test_emag_connection" class="btn btn-default pull-right">
                    <i class="icon-check"></i> {l s='Test Connection' mod='mpstocksync'}
                </button>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='API URL' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="text" name="emag_api_url" value="{$config.emag.api_url|escape:'html':'UTF-8'}" class="form-control" 
                               placeholder="https://marketplace.emag.ro/api-3">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Client ID' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="text" name="emag_client_id" value="{$config.emag.client_id|escape:'html':'UTF-8'}" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Client Secret' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="password" name="emag_client_secret" value="{$config.emag.client_secret|escape:'html':'UTF-8'}" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Username' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="text" name="emag_username" value="{$config.emag.username|escape:'html':'UTF-8'}" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Password' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="password" name="emag_password" value="{$config.emag.password|escape:'html':'UTF-8'}" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Auto Stock Sync' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="emag_auto_sync" id="emag_auto_sync_on" value="1" {if $config.emag.auto_sync}checked="checked"{/if}>
                            <label for="emag_auto_sync_on">{l s='Yes' mod='mpstocksync'}</label>
                            <input type="radio" name="emag_auto_sync" id="emag_auto_sync_off" value="0" {if !$config.emag.auto_sync}checked="checked"{/if}>
                            <label for="emag_auto_sync_off">{l s='No' mod='mpstocksync'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <p class="help-block">{l s='Automatically sync stock changes to eMAG' mod='mpstocksync'}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Trendyol Settings -->
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-cog"></i> {l s='Trendyol Stock Sync' mod='mpstocksync'}
                <button type="submit" name="test_trendyol_connection" class="btn btn-default pull-right">
                    <i class="icon-check"></i> {l s='Test Connection' mod='mpstocksync'}
                </button>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='API URL' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="text" name="trendyol_api_url" value="{$config.trendyol.api_url|escape:'html':'UTF-8'}" class="form-control" 
                               placeholder="https://api.trendyol.com/sapigw/">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Seller ID' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="text" name="trendyol_seller_id" value="{$config.trendyol.seller_id|escape:'html':'UTF-8'}" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='API Key' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="password" name="trendyol_api_key" value="{$config.trendyol.api_key|escape:'html':'UTF-8'}" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='API Secret' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="password" name="trendyol_api_secret" value="{$config.trendyol.api_secret|escape:'html':'UTF-8'}" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Supplier ID' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="text" name="trendyol_supplier_id" value="{$config.trendyol.supplier_id|escape:'html':'UTF-8'}" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Auto Stock Sync' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="trendyol_auto_sync" id="trendyol_auto_sync_on" value="1" {if $config.trendyol.auto_sync}checked="checked"{/if}>
                            <label for="trendyol_auto_sync_on">{l s='Yes' mod='mpstocksync'}</label>
                            <input type="radio" name="trendyol_auto_sync" id="trendyol_auto_sync_off" value="0" {if !$config.trendyol.auto_sync}checked="checked"{/if}>
                            <label for="trendyol_auto_sync_off">{l s='No' mod='mpstocksync'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <p class="help-block">{l s='Automatically sync stock changes to Trendyol' mod='mpstocksync'}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- General Stock Sync Settings -->
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-wrench"></i> {l s='General Stock Sync Settings' mod='mpstocksync'}
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Enable Logging' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="log_enabled" id="log_enabled_on" value="1" {if $config.general.log_enabled}checked="checked"{/if}>
                            <label for="log_enabled_on">{l s='Yes' mod='mpstocksync'}</label>
                            <input type="radio" name="log_enabled" id="log_enabled_off" value="0" {if !$config.general.log_enabled}checked="checked"{/if}>
                            <label for="log_enabled_off">{l s='No' mod='mpstocksync'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <p class="help-block">{l s='Log all stock sync activities' mod='mpstocksync'}</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Sync Interval (seconds)' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="number" name="sync_interval" value="{$config.general.sync_interval|intval}" class="form-control" min="30" max="3600">
                        <p class="help-block">{l s='How often to check for stock changes (min 30 seconds)' mod='mpstocksync'}</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Auto Retry Failed Syncs' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="auto_retry" id="auto_retry_on" value="1" {if $config.general.auto_retry}checked="checked"{/if}>
                            <label for="auto_retry_on">{l s='Yes' mod='mpstocksync'}</label>
                            <input type="radio" name="auto_retry" id="auto_retry_off" value="0" {if !$config.general.auto_retry}checked="checked"{/if}>
                            <label for="auto_retry_off">{l s='No' mod='mpstocksync'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <p class="help-block">{l s='Automatically retry failed stock syncs' mod='mpstocksync'}</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Retry Attempts' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="number" name="retry_attempts" value="{$config.general.retry_attempts|intval}" class="form-control" min="0" max="10">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Retry Delay (seconds)' mod='mpstocksync'}</label>
                    <div class="col-lg-9">
                        <input type="number" name="retry_delay" value="{$config.general.retry_delay|intval}" class="form-control" min="0" max="3600">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Save Button -->
        <div class="panel-footer">
            <button type="submit" name="submit_api_settings" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save Stock Sync Settings' mod='mpstocksync'}
            </button>
        </div>
    </div>
</form>

<style>
.panel {
    margin-bottom: 20px;
}
.panel-heading {
    background: #f5f5f5;
    border-bottom: 1px solid #ddd;
}
.help-block {
    margin-top: 5px;
    color: #777;
    font-size: 12px;
}
</style>
