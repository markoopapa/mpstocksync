<?php
namespace MpStockSync\Service;

use PrestaShopException;

/**
 * LocalStockService
 * - updates StockAvailable in a given shop context
 */
class LocalStockService
{
    /**
     * Update stock for a product in shop (default current shop)
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $quantity
     * @param int|null $idShop optional shop id (multi-shop)
     * @throws PrestaShopException
     */
    public function updateStockInShop(int $idProduct, int $idProductAttribute, int $quantity, int $idShop = null)
    {
        // StockAvailable::setQuantity expects (id_product, id_product_attribute, quantity, id_shop = null)
        if ($idShop === null) {
            \StockAvailable::setQuantity((int)$idProduct, (int)$idProductAttribute, (int)$quantity);
        } else {
            // set quantity for specific shop context
            \StockAvailable::setQuantity((int)$idProduct, (int)$idProductAttribute, (int)$quantity, (int)$idShop);
        }
    }
}
