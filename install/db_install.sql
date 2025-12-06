-- suppliers table
CREATE TABLE IF NOT EXISTS `PREFIX_mpstocksync_suppliers` (
  `id_supplier` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `connection_type` VARCHAR(50) NOT NULL DEFAULT 'prestashop_api',
  `api_url` VARCHAR(255) DEFAULT NULL,
  `api_key` VARCHAR(255) DEFAULT NULL,
  `target_shops` TEXT DEFAULT NULL,
  `auto_sync` TINYINT(1) DEFAULT 0,
  `sync_interval` INT(11) DEFAULT 900,
  `last_sync` DATETIME DEFAULT NULL,
  `active` TINYINT(1) DEFAULT 1,
  `date_add` DATETIME NOT NULL,
  `date_upd` DATETIME NOT NULL,
  PRIMARY KEY (`id_supplier`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- supplier mapping table
CREATE TABLE IF NOT EXISTS `PREFIX_mpstocksync_supplier_map` (
  `id_map` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_supplier` INT(11) NOT NULL,
  `supplier_reference` VARCHAR(255) NOT NULL,
  `local_id_product` INT(11) DEFAULT NULL,
  `local_id_product_attribute` INT(11) DEFAULT NULL,
  `sync_enabled` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id_map`),
  INDEX (`id_supplier`),
  INDEX (`supplier_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
