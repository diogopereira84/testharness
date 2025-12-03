<?php
declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Plugin;

use Magento\Framework\App\Cache;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CachePlugin
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
     * Around plugin for clean method in Cache
     *
     * @param Cache $subject
     * @param callable $proceed
     * @return void
     */
    public function aroundClean(Cache $subject, \Closure $proceed, $type)
    {
        // Get current controller full action
        $actionName = $this->request->getFullActionName();
        $toggleVCatalogMvpSaveValue = $this->toggleConfig->getToggleConfigValue('mazegeeks_save_catalogmvp_product_operation_redis');

        // Condition based on controller and action
        if ($toggleVCatalogMvpSaveValue && ($actionName == "catalogmvp_index_saveproduct" || $actionName == "catalogmvp_index_renameItem" || $actionName == "catalogmvp_index_renameitem" || $actionName == "catalogmvp_index_duplicateproduct" || $actionName == "catalogmvp_index_updateproduct")) {
            $this->logger->info(__METHOD__.':'.__LINE__.':'.__FILE__.' Catalog Mvp Product Save suppress redis cache reset ' . $actionName);
            return;
        }
       
        return $proceed($type);
    }
}
