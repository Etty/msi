<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\StockSourceLink\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Inventory\Indexer\Stock\StockIndexer;
use Magento\InventoryApi\Api\AssignSourcesToStockInterface;

/**
 * TODO: remove this plugin (https://github.com/magento-engcom/msi/issues/213)
 * Invalidate StockIndexer
 */
class AssignSourcesToStock
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * @param AssignSourcesToStockInterface $subject
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(AssignSourcesToStockInterface $subject)
    {
        $indexer = $this->indexerRegistry->get(StockIndexer::INDEXER_ID);
        if ($indexer->isValid()) {
            $indexer->invalidate();
        }
    }
}
