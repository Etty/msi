<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\InventoryApi\Api\IsProductInStockInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Return product availability by Product SKU and Stock Id (stock data + reservations)
 */
class IsProductInStock implements IsProductInStockInterface
{
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver
    ) {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $indexTableName = $this->stockIndexTableNameResolver->execute($stockId);

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($indexTableName, [IndexStructure::IS_AVAILABLE])
            ->where(IndexStructure::SKU . ' = ?', $sku);

        $isAvailable = $connection->fetchOne($select);
        return $isAvailable;
    }
}
