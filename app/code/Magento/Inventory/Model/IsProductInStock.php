<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\IsProductInStockInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Return product availability by Product SKU and Stock Id.
 */
class IsProductInStock implements IsProductInStockInterface
{
    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param ResourceConnection $resource
     */
    public function __construct(
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        ResourceConnection $resource
    ) {
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->resource = $resource;
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

        return (bool)$isAvailable;
    }
}
