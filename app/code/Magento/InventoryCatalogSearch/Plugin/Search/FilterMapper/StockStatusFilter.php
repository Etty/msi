<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Plugin\Search\FilterMapper;

use Magento\Framework\DB\Select;
use Magento\CatalogSearch\Model\Search\FilterMapper\StockStatusFilter as OriginStockStatusFilter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Search\Adapter\Mysql\ConditionManager;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;


/**
 */
class StockStatusFilter
{
    /**
     * Stock table names and aliases
     */
    const STOCK_TABLE_BASE_NAME =  'inventory_stock_stock_';
    const TABLE_ALIAS_PRODUCT =  'product';
    const TABLE_ALIAS_SUB_PRODUCT =  'sub_product';
    const TABLE_ALIAS_STOCK_INDEX =  'stock_index';
    const TABLE_ALIAS_SUB_PRODUCT_STOCK_INDEX =  'sub_product_stock_index';

    /**
     * @var ConditionManager
     */
    private $conditionManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param ConditionManager $conditionManager
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        ConditionManager $conditionManager,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver
    ) {
        $this->conditionManager = $conditionManager;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @param OriginStockStatusFilter $subject
     * @param callable $proceed
     * @param Select $select
     * @param array $stockValues
     * @param string $type
     * @param bool $showOutOfStockFlag
     * @return Select
     * @throws \InvalidArgumentException
     * @throws LocalizedException
     */
    public function aroundApply(
        OriginStockStatusFilter $subject,
        callable $proceed,
        Select $select,
        $stockValues,
        $type,
        $showOutOfStockFlag
    ) {
        if ($type !== OriginStockStatusFilter::FILTER_JUST_ENTITY &&
            $type !== OriginStockStatusFilter::FILTER_ENTITY_AND_SUB_PRODUCTS
        ) {
            throw new \InvalidArgumentException(sprintf('Invalid filter type: %s', $type));
        }

        $select = clone $select;
        $mainTableAlias = $this->extractTableAliasFromSelect($select);

        $this->addProductEntityJoin($select, $mainTableAlias);
        $this->addInventoryStockJoin($select, $showOutOfStockFlag);

        if ($type === OriginStockStatusFilter::FILTER_ENTITY_AND_SUB_PRODUCTS) {
            $this->addSubProductEntityJoin($select, $mainTableAlias);
            $this->addSubProductInventoryStockJoin($select, $showOutOfStockFlag);
        }

        return $select;
    }

    /**
     * @param Select $select
     * @param string $mainTableAlias
     */
    private function addProductEntityJoin(Select $select, $mainTableAlias)
    {
        $select->joinInner(
            [static::TABLE_ALIAS_PRODUCT => 'catalog_product_entity'],
            sprintf('%s.entity_id = %s.entity_id', static::TABLE_ALIAS_PRODUCT, $mainTableAlias),
            []
        );
    }

    /**
     * @param Select $select
     * @param string $mainTableAlias
     */
    private function addSubProductEntityJoin(Select $select, $mainTableAlias)
    {
        $select->joinInner(
            [static::TABLE_ALIAS_SUB_PRODUCT => 'catalog_product_entity'],
            sprintf('%s.entity_id = %s.source_id', static::TABLE_ALIAS_SUB_PRODUCT, $mainTableAlias),
            []
        );
    }

    /**
     * @param Select $select
     * @param bool $showOutOfStockFlag
     * @throws LocalizedException
     */
    private function addInventoryStockJoin(Select $select, $showOutOfStockFlag)
    {
        $select->joinInner(
            [static::TABLE_ALIAS_STOCK_INDEX => $this->getStockTableName()],
            sprintf(
                '%s.sku = %s.sku',
                static::TABLE_ALIAS_STOCK_INDEX,
                static::TABLE_ALIAS_PRODUCT
            ),
            []
        );

        if ($showOutOfStockFlag === false) {
            $select->where($this->conditionManager->generateCondition(
                sprintf('%s.quantity', static::TABLE_ALIAS_STOCK_INDEX),
                '>',
                0
            ));
        }
    }

    /**
     * @param Select $select
     * @param bool $showOutOfStockFlag
     * @throws LocalizedException
     */
    private function addSubProductInventoryStockJoin(Select $select, $showOutOfStockFlag)
    {
        $select->joinInner(
            [static::TABLE_ALIAS_SUB_PRODUCT_STOCK_INDEX => $this->getStockTableName()],
            sprintf(
                '%s.sku = %s.sku',
                static::TABLE_ALIAS_SUB_PRODUCT_STOCK_INDEX,
                static::TABLE_ALIAS_SUB_PRODUCT
            ),
            []
        );

        if ($showOutOfStockFlag === false) {
            $select->where($this->conditionManager->generateCondition(
                sprintf('%s.quantity', static::TABLE_ALIAS_SUB_PRODUCT_STOCK_INDEX),
                '>',
                0
            ));
        }
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getStockTableName(): string
    {
        $website = $this->storeManager->getWebsite();
        $stock = $this->stockResolver->get(
            SalesChannelInterface::TYPE_WEBSITE,
            $website->getCode()
        );

        return self::STOCK_TABLE_BASE_NAME . $stock->getStockId();
    }

    /**
     * Extracts alias for table that is used in FROM clause in Select
     *
     * @param Select $select
     * @return string|null
     */
    private function extractTableAliasFromSelect(Select $select)
    {
        $fromArr = array_filter(
            $select->getPart(Select::FROM),
            function ($fromPart) {
                return $fromPart['joinType'] === Select::FROM;
            }
        );

        return $fromArr ? array_keys($fromArr)[0] : null;
    }
}
