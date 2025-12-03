<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Controller\Index;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\UploadToQuote\Helper\LocationApiHelper;
use Fedex\UploadToQuote\Model\Config;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

/**
 * SaveLocationCode Controller
 */
class SaveLocationCode extends \Magento\Framework\App\Action\Action
{
    /**
     * @param Context $context
     * @param CartFactory $cartFactory
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param LoggerInterface $logger
     * @param LocationApiHelper $locationApiHelper
     * @param JsonFactory $jsonFactory
     * @param ProductionLocationFactory $productionLocationFactory
     * @param CompanyHelper $companyHelper
     * @param Config $config
     */
    public function __construct(
        Context                             $context,
        protected CartFactory               $cartFactory,
        protected UploadToQuoteViewModel    $uploadToQuoteViewModel,
        protected LoggerInterface           $logger,
        protected LocationApiHelper         $locationApiHelper,
        private JsonFactory                 $jsonFactory,
        protected ProductionLocationFactory $productionLocationFactory,
        protected CompanyHelper             $companyHelper,
        private Config                      $config
    ) {
        parent::__construct($context);
    }

    /**
     * Save location code
     *
     * @return mixed
     */
    public function execute()
    {
        $postData = $this->getRequest()->getPostValue();
        $apiUrl = $this->uploadToQuoteViewModel->getUploadToQuoteConfigValue('location_search_api_url');
        $locationResp = $this->locationApiHelper->getHubCenterCodeByState($postData, $apiUrl);
        $companyId = $this->companyHelper->getCompanyId();
        $recommendedLocation = $this->getRecommendedLocations($companyId);
        $restrictedLocation = $this->getRestrictedLocations($companyId);
        try {
            if ($this->companyHelper->isSiteLevelQuoteToggle() && (!empty($recommendedLocation) || !empty($restrictedLocation))) {
                $result = $this->jsonFactory->create();
                $locationId = $this->getRequest()->getParam('id');
                if (empty($locationId)) {
                    $locationId = !empty($locationResp['output']['search']) ?
                        $locationResp['output']['search'][0]['officeLocationId'] : '';
                }
                $quote = $this->cartFactory->create()->getQuote();
                $quote->setData("quote_mgnt_location_code", $locationId);
                $quote->save();
                $result = [
                    "success" => true,
                    "message" => "Location code save successfully"
                ];
            } elseif (isset($locationResp['output']['search'])) {
                if ($this->config->isTk4673962ToggleEnabled() &&
                    !empty($this->getRequest()->getParam('id'))) {
                    $locationId = $this->_getLocationId($this->getRequest()->getParam('id'), $locationResp);
                } else {
                    $locationId = $locationResp['output']['search'][0]['officeLocationId'] ?? '';
                }
                $quote = $this->cartFactory->create()->getQuote();
                $quote->setData("quote_mgnt_location_code", $locationId);
                $quote->save();
                $result = [
                    "success" => true,
                    "message" => "Location code save successfully"
                ];
            } elseif (isset($locationResp['errors'])) {
                $result = [
                    "success" => false,
                    "message" => $locationResp['errors'][0]['message'] ?? 'System error, Please try again.'
                ];
            } else {
                $result = [
                    "success" => false,
                    "message" => "Location code not validated"
                ];
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ': Quote save error ',
                ['exception' => $e->getMessage()]
            );

            $result = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);

        return $resultJson;
    }

    /**
     * @param int $companyId
     * @return array|mixed $result
     */
    public function getRecommendedLocations($companyId)
    {
        $prodLocationModel = $this->productionLocationFactory->create();
        $storesLocations = $prodLocationModel->getCollection()->addFieldToFilter('company_id', $companyId)
            ->addFieldToFilter('is_recommended_store', ['eq' => 1]);
        $recommendedLocation = [];
        foreach ($storesLocations as $storesLocation) {
            $recommendedLocation[] = $storesLocation->getData();
        }
        return $recommendedLocation;
    }

    /**
     * @param int $companyId
     * @return array|mixed $result
     */
    public function getRestrictedLocations($companyId)
    {
        $prodLocationModel = $this->productionLocationFactory->create();
        $storesLocations = $prodLocationModel->getCollection()->addFieldToFilter('company_id', $companyId)
            ->addFieldToFilter('is_recommended_store', ['eq' => 0]);
        $restrictedLocation = [];
        foreach ($storesLocations as $storesLocation) {
            $restrictedLocation[] = $storesLocation->getData();
        }
        return $restrictedLocation;
    }

    /**
     * Returns store location id based on what is selected during checkout or
     * defaults to Ship Hub store location id
     *
     * @param string $id
     * @param array $locationResp
     * @return string
     */
    private function _getLocationId(string $id, array $locationResp): string
    {
        return $id ?:
            ($locationResp['output']['search'][0]['officeLocationId'] ?? '');
    }
}
