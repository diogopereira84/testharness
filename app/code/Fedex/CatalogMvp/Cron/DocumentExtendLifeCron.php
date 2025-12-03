<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;

class DocumentExtendLifeCron
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var configInterface
     */
    protected $catalogDocumentRefranceApiHelper;

    /**
     * Constructor
     * @param catalogPriceSyncHelper $catalogPriceSyncHelper
     */
    public function __construct(
        LoggerInterface $loggerInterface,
        protected ToggleConfig $toggleConfig,
        CatalogDocumentRefranceApi $catalogDocumentRefranceApiHelper
    ) {
        $this->logger                               = $loggerInterface;
        $this->catalogDocumentRefranceApiHelper     = $catalogDocumentRefranceApiHelper;
    }

    /**
     * execute
     * @return mixed
     */
    public function execute()
    {
        if ($this->toggleConfig->getToggleConfigValue('explorers_catalog_migration')) {
            $this->catalogDocumentRefranceApiHelper->extendDocumentLifeForProducts();
        } else {
            $this->catalogDocumentRefranceApiHelper->getExtendDocumentLifeForPodEitableProduct();
        }
        
        return true;
    }
}
