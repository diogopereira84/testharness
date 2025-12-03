<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FXOCMConfigurator\Controller\Rate;

use Magento\Framework\App\Action\Context;
use Fedex\CatalogMvp\Helper\CatalogPriceSyncHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Index extends \Magento\Framework\App\Action\Action
{
    private const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';

    /**
     * @param Context $context
     * @param CatalogPriceSyncHelper $pricesync
     * @param JsonFactory $jsonFactory
     * @param ToggleConfig $toggleConfig
     */

    public function __construct(
        Context $context,
        protected CatalogPriceSyncHelper $pricesync,
        protected JsonFactory $jsonFactory,
        protected ToggleConfig $toggleConfig
    )
    {
        return parent::__construct($context);
    }

    public function execute()
    {
        $productPrice = false;
        $productJson = $this->getRequest()->getParam('data');
        $productaray[] = json_decode($productJson, true);
        if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_NON_STANDARD_CATALOG)) {
            $responseData = $this->pricesync->rateApiCall($productaray, '', '');
            if ($responseData) {
                $productPrice = $responseData['output']['rate']['rateDetails'][0]['netAmount'];
            }
        } else {
            $productPrice = $this->pricesync->rateApiCall($productaray, '', '');
        }
        $price = str_replace("$","",$productPrice);
        $json = $this->jsonFactory->create();
        return $json->setData($price);
    }
}
