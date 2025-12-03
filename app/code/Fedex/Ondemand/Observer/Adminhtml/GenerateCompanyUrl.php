<?php

namespace Fedex\Ondemand\Observer\Adminhtml;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\Shipto\Model\ProductionLocationFactory;

class GenerateCompanyUrl implements \Magento\Framework\Event\ObserverInterface
{

    public function __construct(
        protected \Magento\Store\Model\GroupFactory $groupFactory,
        protected \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        private ToggleConfig $toggleConfig,
        private LoggerInterface $logger,
        protected ProductionLocationFactory $productionLocationFactory
    )
    {
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->toggleConfig->getToggleConfigValue('explorers_restricted_and_recommended_production')) {
            $this->handleLocationRemoval($observer->getRequest());
        }
        try {
            $ondemandStoreCode = "ondemand";
            $urlExtention = $observer->getCompany()->getData('company_url_extention');

            if ($urlExtention) {

                $ondemandGroupObj = $this->groupFactory->create()->load($ondemandStoreCode, 'code');
                $ondemandStoreIds = $ondemandGroupObj->getStoreIds();
                $ondemandStoreId = reset($ondemandStoreIds);

                $urlRewriteCollection = $this->urlRewriteFactory->create()->getCollection();
                $urlRewriteCollection = $urlRewriteCollection->addFieldToFilter('store_id', $ondemandStoreId)
                                        ->addFieldToFilter('entity_type', 'custom')
                                            ->addFieldToFilter('request_path', $urlExtention);

                if (!$urlRewriteCollection->getSize()) {
                    $urlData = [
                                    'entity_type' => 'custom',
                                    'store_id' => $ondemandStoreId,
                                    'is_system' => 0,
                                    'request_path' => $urlExtention,
                                    'target_path' => 'restructure/company/redirect/url/'.$urlExtention,
                                    'redirect_type' => 0,
                                ];

                    $this->urlRewriteFactory->create()->setData($urlData)->save();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Handle locations removal
     * @param string $requestData
     */
    public function handleLocationRemoval($requestData)
    {
        $requestParams = $requestData->getParams();

        $companyId = $requestParams['general']['company_id'];
        $productionLocationData = $requestParams['production_location'];
        if (
            isset($productionLocationData['production_location_option']) &&
            $productionLocationData['production_location_option'] == 'geographical'
        ) {
            try {
                $locationModel = $this->productionLocationFactory->create();
                $collection =  $locationModel->getCollection()
                    ->addFieldToFilter('company_id', $companyId);

                if ($collection->getSize() > 0) {
                    foreach ($collection->getData() as $locationObj) {
                        $locationModel->load($locationObj['id']);
                        $locationModel->delete();
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error occured while removing production locations
                     for the company id :' . $companyId . ' is' . $e->getMessage());
            }
        }
    }
}
