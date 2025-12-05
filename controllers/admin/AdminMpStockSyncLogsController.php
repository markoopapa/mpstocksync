<?php
class AdminMpStockSyncLogsController extends ModuleAdminController
{
    private $items_per_page = 50;
    
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mpstocksync_log';
        $this->identifier = 'id_log';
        $this->className = 'MpStockSyncLog';
        $this->lang = false;
        
        parent::__construct();
        
        $this->fields_list = [
            'id_log' => [
                'title' => 'ID',
                'width' => 50,
                'align' => 'center'
            ],
            'api_name' => [
                'title' => 'Platform',
                'width' => 100,
                'callback' => 'renderPlatform'
            ],
            'id_product' => [
                'title' => 'Product ID',
                'width' => 80,
                'align' => 'center'
            ],
            'action' => [
                'title' => 'Action',
                'width' => 100
            ],
            'status' => [
                'title' => 'Status',
                'width' => 80,
                'callback' => 'renderStatus'
            ],
            'error_message' => [
                'title' => 'Message',
                'width' => 200,
                'callback' => 'renderErrorMessage'
            ],
            'date_add' => [
                'title' => 'Date',
                'width' => 140,
                'type' => 'datetime'
            ]
        ];
        
        $this->bulk_actions = [
            'delete' => [
                'text' => 'Delete',
                'icon' => 'icon-trash',
                'confirm' => 'Delete selected logs?'
            ]
        ];
        
        // Disable new button
        $this->list_no_link = true;
    }
    
    public function initContent()
    {
        parent::initContent();
        
        // Handle actions
        if (Tools::isSubmit('clear_logs')) {
            $this->processClearLogs();
        }
        
        if (Tools::isSubmit('export_logs')) {
            $this->processExportLogs();
        }
        
        if (Tools::isSubmit('ajax') && Tools::getValue('action') == 'get_log_details') {
            $this->ajaxProcessGetLogDetails();
        }
        
        // Get logs with filters
        $logs_data = $this->getLogsWithPagination();
        
        $this->context->smarty->assign([
            'logs' => $logs_data['logs'],
            'total_items' => $logs_data['total_items'],
            'total_pages' => $logs_data['total_pages'],
            'current_page' => $logs_data['current_page'],
            'start_item' => $logs_data['start_item'],
            'end_item' => $logs_data['end_item'],
            'filter_api' => Tools::getValue('filter_api', ''),
            'filter_status' => Tools::getValue('filter_status', ''),
            'filter_date_from' => Tools::getValue('filter_date_from', ''),
            'filter_date_to' => Tools::getValue('filter_date_to', ''),
            'form_action' => self::$currentIndex . '&token=' . $this->token,
            'filter_query' => $this->getFilterQueryString(),
            'token' => $this->token
        ]);
        
        $this->setTemplate('logs.tpl');
    }
    
    private function getLogsWithPagination()
    {
        $current_page = (int)Tools::getValue('page', 1);
        if ($current_page < 1) {
            $current_page = 1;
        }
        
        $offset = ($current_page - 1) * $this->items_per_page;
        
        // Build WHERE clause
        $where = '1';
        
        $filter_api = Tools::getValue('filter_api');
        if ($filter_api) {
            $where .= " AND api_name = '" . pSQL($filter_api) . "'";
        }
        
        $filter_status = Tools::getValue('filter_status');
        if ($filter_status !== '') {
            $where .= " AND status = " . (int)$filter_status;
        }
        
        $filter_date_from = Tools::getValue('filter_date_from');
        if ($filter_date_from) {
            $where .= " AND date_add >= '" . pSQL($filter_date_from) . " 00:00:00'";
        }
        
        $filter_date_to = Tools::getValue('filter_date_to');
        if ($filter_date_to) {
            $where .= " AND date_add <= '" . pSQL($filter_date_to) . " 23:59:59'";
        }
        
        // Get total count
        $sql = 'SELECT COUNT(*) as total FROM `'._DB_PREFIX_.'mpstocksync_log` WHERE ' . $where;
        $total_items = (int)Db::getInstance()->getValue($sql);
        
        // Calculate pagination
        $total_pages = ceil($total_items / $this->items_per_page);
        
        if ($current_page > $total_pages && $total_pages > 0) {
            $current_page = $total_pages;
            $offset = ($current_page - 1) * $this->items_per_page;
        }
        
        // Get logs for current page
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mpstocksync_log` 
                WHERE ' . $where . '
                ORDER BY date_add DESC
                LIMIT ' . (int)$offset . ', ' . (int)$this->items_per_page;
        
        $logs = Db::getInstance()->executeS($sql);
        
        return [
            'logs' => $logs,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'start_item' => $offset + 1,
            'end_item' => min($offset + $this->items_per_page, $total_items)
        ];
    }
    
    private function getFilterQueryString()
    {
        $params = [];
        
        $filters = ['filter_api', 'filter_status', 'filter_date_from', 'filter_date_to'];
        foreach ($filters as $filter) {
            $value = Tools::getValue($filter);
            if ($value !== '') {
                $params[] = $filter . '=' . urlencode($value);
            }
        }
        
        return $params ? '&' . implode('&', $params) : '';
    }
    
    public function renderPlatform($value, $row)
    {
        if ($value == 'emag') {
            return '<span class="label label-primary">eMAG</span>';
        } elseif ($value == 'trendyol') {
            return '<span class="label" style="background:#ff6b00">Trendyol</span>';
        } elseif ($value == 'supplier') {
            return '<span class="label label-info">Supplier</span>';
        }
        return $value;
    }
    
    public function renderStatus($value, $row)
    {
        return $value 
            ? '<span class="label label-success">Success</span>'
            : '<span class="label label-danger">Failed</span>';
    }
    
    public function renderErrorMessage($value, $row)
    {
        if ($value) {
            return '<span class="text-danger">' . Tools::substr($value, 0, 50) . 
                   (Tools::strlen($value) > 50 ? '...' : '') . '</span>';
        } elseif ($row['response_data']) {
            return '<span class="text-muted">Response received</span>';
        } else {
            return '<span class="text-success">OK</span>';
        }
    }
    
    private function processClearLogs()
    {
        $result = Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.'mpstocksync_log`');
        
        if ($result) {
            $this->confirmations[] = 'All logs cleared successfully';
        } else {
            $this->errors[] = 'Error clearing logs';
        }
        
        Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
    }
    
    private function processExportLogs()
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mpstocksync_log` ORDER BY date_add DESC';
        $logs = Db::getInstance()->executeS($sql);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sync_logs_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write header
        fputcsv($output, [
            'ID', 'Platform', 'Product ID', 'Action', 'Status', 
            'Error Message', 'Date', 'Old Value', 'New Value', 'Response Data'
        ]);
        
        // Write data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id_log'],
                $log['api_name'],
                $log['id_product'],
                $log['action'],
                $log['status'] ? 'Success' : 'Failed',
                $log['error_message'],
                $log['date_add'],
                $log['old_value'],
                $log['new_value'],
                $log['response_data']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function ajaxProcessGetLogDetails()
    {
        $id_log = (int)Tools::getValue('id_log');
        
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mpstocksync_log` WHERE id_log = ' . $id_log;
        $log = Db::getInstance()->getRow($sql);
        
        if ($log) {
            die(json_encode($log));
        } else {
            die(json_encode(['error' => 'Log not found']));
        }
    }
    
    public function initToolbar()
    {
        parent::initToolbar();
        
        // Remove default new button
        unset($this->toolbar_btn['new']);
        
        // Add custom buttons
        $this->page_header_toolbar_btn['clear_logs'] = [
            'href' => self::$currentIndex . '&clear_logs&token=' . $this->token,
            'desc' => 'Clear All Logs',
            'icon' => 'process-icon-delete',
            'class' => 'btn-danger',
            'confirm' => 'Clear all logs? This cannot be undone.'
        ];
        
        $this->page_header_toolbar_btn['export_logs'] = [
            'href' => self::$currentIndex . '&export_logs&token=' . $this->token,
            'desc' => 'Export Logs',
            'icon' => 'process-icon-download',
            'class' => 'btn-success'
        ];
    }
    
    public function renderList()
    {
        // Don't use default list rendering, we use custom template
        return '';
    }
}
