<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FXOCMConfigurator\Controller\Adminhtml\Rate;

use Magento\Backend\App\Action\Context;
use Fedex\CatalogMvp\Helper\CatalogPriceSyncHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Model\ProductPriceWebhook;

class Index extends \Magento\Backend\App\Action
{
    private const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';
    private const EXPLORERS_D196499_FIX = 'explorers_d196499_fix';
    private const STRING_TYPE = 'string';

    /**
     * Index Constructor
     * @param Context $context
     * @param CatalogPriceSyncHelper $pricesync,
     * @param JsonFactory $jsonFactory
     * @param ToggleConfig $toggleConfig
     * @param ProductPriceWebhook $productPriceWebhook
     */
    public function __construct(
        private Context $context,
        private CatalogPriceSyncHelper $pricesync,
        private JsonFactory $jsonFactory,
        private ToggleConfig $toggleConfig,
        private ProductPriceWebhook $productPriceWebhook
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $productPrice = false;
        $customerGroupId = null;
        $price = null;
        $productJson = $this->getRequest()->getParam('data');
        $popupRequest = $this->getRequest()->getParam('isPopup');

        if ($this->pricesync->getCorrectPriceToggle()) {
            $customerGroupId = $this->getCustomerGroupIdBySharedCatalogId();
        }

        $productaray[] = json_decode($productJson, true);
        if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_NON_STANDARD_CATALOG)) {
            $responseData = $this->pricesync->rateApiCall($productaray, '', $customerGroupId);
            if ($responseData) {
                $productPrice = $responseData['output']['rate']['rateDetails'][0]['netAmount'];
            }
        } else {
            $productPrice = $this->pricesync->rateApiCall($productaray, '', $customerGroupId);
        }

        // -------------------------------
        // MAGEGEEKS_D_236791 toggle is enabled
        // -------------------------------
        $responseArray = [];
        if ($popupRequest && $this->pricesync->isMagegeeksD236791ToggleEnabled()) {
            $responseData = $this->pricesync->rateApiCall($productaray, '', $customerGroupId);

            if (!empty($responseData['output']['rate']['rateDetails'][0]['productLines'][0]['productLineDetails'])) {
                $details = $responseData['output']['rate']['rateDetails'][0]['productLines'][0]['productLineDetails'];

                foreach ($details as $detail) {
                    $responseArray[] = [
                        'detailCode' => $detail['detailCode'] ?? '',
                        'detailDescription' => $detail['description'] ?? '',
                        'detailPrice' => $detail['detailPrice'] ?? '',
                        'detailUnitPrice' => $detail['detailUnitPrice'] ?? ''
                    ];
                }
            }

            if (empty($price) && !empty($responseData['output']['rate']['rateDetails'][0]['netAmount'])) {
                $price = $responseData['output']['rate']['rateDetails'][0]['netAmount'];
            }

            if (gettype($price) === self::STRING_TYPE) {
                $price = str_replace(["$", ",", "(", ")"], "", $price);
            }

            $responseArray = [
                'totalPrice' => $price,
                'details' => $responseArray
            ];
        }

        if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_D196499_FIX)) {
            if (gettype($productPrice) === self::STRING_TYPE) {
                $price = str_replace(["$", ",", "(", ")"], "", $productPrice);
            }
        } else {
            $price = str_replace("$", "", $productPrice);
        }

        $json = $this->jsonFactory->create();

        if ($this->pricesync->isMagegeeksD236791ToggleEnabled() 
                && !empty($responseArray)
                && !empty($responseArray['totalPrice'])) {
            return $json->setData($responseArray);
        } else {
            return $json->setData($price);
        }
    }

    /**
     * Get customer group ID
     */
    private function getCustomerGroupIdBySharedCatalogId()
    {
        $sharedCatalogId = $this->getRequest()->getParam('sharedCatalogId');
        $customerGrpId = null;
        if ( $sharedCatalogId ) {
            $sharedCatalogCollection    = $this->productPriceWebhook->getCustomerGroupIdByShareCatalogId($sharedCatalogId);
            $customerGroupId            = current($sharedCatalogCollection->getData());
            $customerGrpId              = $customerGroupId['customer_group_id'] ?? '';
        }

        return $customerGrpId;
    }
}
