<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;

/**
 * Get the source item configuration
 * Firstly try to load Source Item configuration if configuration isn't exist then load global configuration value
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface GetSourceItemConfigurationInterface
{
    /**
     * Get the source item configuration
     *
     * @param string $sourceCode
     * @param string $sku
     * @return \Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(string $sourceCode, string $sku): SourceItemConfigurationInterface;
}
