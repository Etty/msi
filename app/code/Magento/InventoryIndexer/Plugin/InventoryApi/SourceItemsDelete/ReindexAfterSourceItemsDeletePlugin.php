<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi\SourceItemsDelete;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemId;
use Magento\InventoryIndexer\Indexer\Source\SourceIndexer;

/**
 * Reindex after source items delete plugin
 */
class ReindexAfterSourceItemsDeletePlugin
{
    /**
     * @var GetSourceItemId
     */
    private $getSourceItemId;

    /**
     * @var IndexerInterfaceFactory
     */
    private $indexerFactory;

    /**
     * @param GetSourceItemId $getSourceItemId
     * @param IndexerInterfaceFactory $indexerFactory
     */
    public function __construct(GetSourceItemId $getSourceItemId, IndexerInterfaceFactory $indexerFactory)
    {
        $this->getSourceItemId = $getSourceItemId;
        $this->indexerFactory = $indexerFactory;
    }

    /**
     * @param SourceItemsDeleteInterface $subject
     * @param callable $proceed
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        SourceItemsDeleteInterface $subject,
        callable $proceed,
        array $sourceItems
    ) {
        $sourceCodes = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceCodes[] = $sourceItem->getSourceCode();
        }

        $proceed($sourceItems);

        if (count($sourceCodes)) {
            /** @var IndexerInterface $indexer */
            $indexer = $this->indexerFactory->create();
            $indexer->load(SourceIndexer::INDEXER_ID);
            $indexer->reindexList($sourceCodes);
        }
    }
}
