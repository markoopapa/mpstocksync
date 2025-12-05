<?php
class ProductMapping
{
    public static function getByProduct($id_product, $id_product_attribute, $api_name)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'mpstocksync_mapping`
                WHERE id_product = '.(int)$id_product.'
                AND id_product_attribute = '.(int)$id_product_attribute.'
                AND api_name = "'.pSQL($api_name).'"';
        
        return Db::getInstance()->getRow($sql);
    }
}
