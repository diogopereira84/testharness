<?php
declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ProductPluginCache
{
    /**
     * Constructor to inject dependencies
     *
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected RequestInterface $request,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig
    ) {}

    /**
     * After plugin for getIdentities() method
     *
     * @param Product $subject
     * @param array $result
     * @return array
     */
    public function afterGetIdentities(Product $subject, $result): array
    {
        // Get current controller full action
        $actionName = $this->request->getFullActionName();
        $toggleVCatalogMvpSaveValue = $this->toggleConfig->getToggleConfigValue('mazegeeks_save_catalogmvp_product_operation_redis');

        // Condition based on controller and action
        if ($toggleVCatalogMvpSaveValue && ($actionName == "catalogmvp_index_saveproduct" || $actionName == "catalogmvp_index_renameItem" || $actionName == "catalogmvp_index_renameitem" || $actionName == "catalogmvp_index_duplicateproduct" || $actionName == "catalogmvp_index_updateproduct")) {
            $this->logger->info(__METHOD__.':'.__LINE__.':'.__FILE__.' Catalog Mvp Product Save suppress redis cache reset ' . $actionName);
            return [];
        }

        // Return the modified result
        return $result;
    }

    /**
     * After plugin for GetCacheTags() method
     *
     * @param Product $subject
     * @param array $result
     * @return array
     */
    public function afterGetCacheTags(Product $subject, $result)
    {
        // Get current controller full action
        $actionName = $this->request->getFullActionName();
        $toggleVCatalogMvpSaveValue = $this->toggleConfig->getToggleConfigValue('mazegeeks_save_catalogmvp_product_operation_redis');

        // Condition based on controller and action
        if ($toggleVCatalogMvpSaveValue && ($actionName == "catalogmvp_index_saveproduct" || $actionName == "catalogmvp_index_renameItem" || $actionName == "catalogmvp_index_renameitem" || $actionName == "catalogmvp_index_duplicateproduct" || $actionName == "catalogmvp_index_updateproduct")) {
            $this->logger->info(__METHOD__.':'.__LINE__.':'.__FILE__.' Catalog Mvp Product Save suppress redis cache reset ' . $actionName);
            return [];
        }

        // Return the modified result
        return $result;
    }
}
