<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Controller\Update;

use Fedex\Company\Api\Data\ConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\AdditionalData;
use Magento\Company\Model\CompanyFactory;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\Store\Model\StoreFactory;

class LogoSetting extends \Magento\Framework\App\Action\Action
{
    public const LINE_BREAK = '<br/>';
    protected $storeManager;
    /**
     * @param \Magento\Framework\App\Action\Context             $context
     * @param \Magento\Store\Model\GroupFactory                 $groupFactory
     * @param \Magento\Store\Model\StoreFactory                 $storeFactory
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Indexer\Model\IndexerFactory             $indexerFactory
     * @param LoggerInterface                                   $logger
     * @param Data                                              $jsonHelper
     */
    public function __construct(
        Context $context,
        protected GroupFactory $groupFactory,
        protected ScopeConfigInterface $scopeConfigInterface,
        protected LoggerInterface $logger,
        protected AdditionalDataFactory $additionalDataFactory,
        protected CompanyFactory $companyFactory,
        protected UrlRewriteFactory $urlRewriteFactory,
        protected StoreFactory $storeFactory,
        protected ConfigInterface $configInterface
    ) {
        parent::__construct($context);
    }
    /**
     * Execute view action of Image and URL update
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $b2bStoreCode = "b2b_store";
        $sdeStoreCode = "sde_store";
        $ondemandStoreCode = "ondemand";
        $storeIds = $imageSources = [];
        try {
            if (!$this->isPhpUnit()) {
                print_r('Script execution started ...!');
            }

            $this->logger->info('Logo cloning script started');
            $b2bGroupObj = $this->groupFactory->create()->load($b2bStoreCode, 'code');
            $storeIds = $b2bGroupObj->getStoreIds();

            $sdeGroupObj = $this->groupFactory->create()->load($sdeStoreCode, 'code');

            $sdeStoreIds = $sdeGroupObj->getStoreIds();

            $ondemandGroupObj = $this->groupFactory->create()->load($ondemandStoreCode, 'code');
            $ondemandStoreIds = $ondemandGroupObj->getStoreIds();
            $ondemandStoreId = reset($ondemandStoreIds);

            $storeIds = array_merge($storeIds, $sdeStoreIds);
            $this->logger->info('Logo and extensions updated for store ids ' . implode(',', $storeIds));

            foreach ($storeIds as $storeId) {
                $imagePath = $this->getImgSrcValue($storeId);
                $imgAlt = $this->getImgAltValue($storeId);
                $arrImgPath = explode("/", $imagePath);
                $imageName = end($arrImgPath);
                $ext = 'image/' . pathinfo($imagePath, PATHINFO_EXTENSION);

                $imageSources = [
                    'name' => $imageName,
                    'type' => $ext,
                    'url' => '/media/logo/' . $imagePath,
                    'full_path' => $imageName,
                    'previewType' => 'image',
                    'file' => $imageName,
                    'size' => '4669',
                    'alt' => $imgAlt ?? 'Logo Image'
                ];
                $arrCompanyIds = (array) $this->getCustomerCompanyIdByStore($storeId);
                $encodedImage = json_encode($imageSources);
                foreach ($arrCompanyIds as $companyId) {
                    $this->saveCompanyData($companyId, $encodedImage, $storeId);
                }
            }
            if (!$this->isPhpUnit()) {
                print_r(self::LINE_BREAK);
                print_r('Logo and url ext updated ...!');
            }

            foreach ($storeIds as $storeId) {
                $arrCompanyIds = (array) $this->getCustomerCompanyIdByStore($storeId);
                foreach ($arrCompanyIds as $companyId) {
                    $this->updateUrls($ondemandStoreId, $storeId);
                }
            }
            if (!$this->isPhpUnit()) {
                print_r(self::LINE_BREAK);
                print_r('URL rewrite done...!');
                print_r(self::LINE_BREAK);
                print_r('Script execution Stop ...!');
            }

            $this->logger->info('Logo cloning script Stop');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    private function isPhpUnit(): bool
    {
        return (bool) strpos($_SERVER['argv'][0], 'phpunit') !== false;
    }

    /**
     * getImgSrcValue
     *
     * @return string
     */
    public function getImgSrcValue($storeId)
    {
        return $this->scopeConfigInterface->getValue('design/header/logo_src', ScopeInterface::SCOPE_STORES, $storeId);
    }
    /**
     * getImgAltValue
     *
     * @return string
     */
    public function getImgAltValue($storeId)
    {
        return $this->scopeConfigInterface->getValue('design/header/logo_alt', ScopeInterface::SCOPE_STORES, $storeId);
    }
    /**
     * getCustomerCompanyIdByStore
     *
     * @return array
     */
    public function getCustomerCompanyIdByStore($storeId)
    {
        $companyAdditionalDataCollection = $this->additionalDataFactory->create()->getCollection();
        $companyAdditionalDataCollection->addFieldToSelect(AdditionalData::COMPANY_ID);

        return $companyAdditionalDataCollection->getData();
    }
    /**
     * saveCompanyData
     *
     * @return boolean
     */
    public function saveCompanyData($companyId, $imageMetaData, $storeId)
    {
        $collection = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('entity_id', ['eq' => $companyId])->getFirstItem();

        $companyUrlExt = $this->storeFactory->create()->load($storeId)->getCode();
        if (!empty($companyUrlExt)) {
            $collection->setData('company_url_extention', $companyUrlExt);
        }
        $collection->setData('company_logo', $imageMetaData);
        $collection->save();
        $this->logger->info('Logo and extensions updated ');
        return true;
    }
    /**
     * updateUrls
     *
     * @return boolean
     */
    public function updateUrls($ondemandStoreId, $storeId)
    {

        try {
            $companyUrlExt = $this->storeFactory->create()->load($storeId)->getCode();
            if (!empty($companyUrlExt)) {
                $urlRewriteModel = $this->urlRewriteFactory->create();
                $urlRewriteModel->setStoreId($ondemandStoreId);
                $urlRewriteModel->setEntityType('custom');
                $urlRewriteModel->setIsSystem(0);
                $urlRewriteModel->setIdPath(rand(1, 100000));
                $urlRewriteModel->setRedirectType(0);
                $urlRewriteModel->setTargetPath('restructure/company/redirect/url/' . $companyUrlExt);
                $urlRewriteModel->setRequestPath($companyUrlExt);
                $urlRewriteModel->save();
                $this->logger->info('Url rewrite Done');
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
        return true;
    }
}
