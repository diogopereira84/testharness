<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Controller\Update;

use Psr\Log\LoggerInterface;
use \Magento\Cms\Model\Page;
use \Magento\Store\Model\GroupFactory;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Config\Model\ResourceModel\Config;
use \Magento\Cms\Model\BlockFactory;
use \Magento\Framework\App\ResourceConnection;

class SdeSetting extends \Magento\Framework\App\Action\Action
{
    protected $moduleDataSetup;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Store\Model\GroupFactory $groupFactory
     * @param \Magento\Store\Model\StoreFactory $storeFactory
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Indexer\Model\IndexerFactory $indexerFactory
     * @param LoggerInterface $logger
     * @param Data $jsonHelper
     */

    public const PRODUCT_MASK_IMAGE_PATH = 'sde/sde_mask/sde_mask_img';
    public const LINE_BREAK = '<br/>';
    public function __construct(
        Context $context,
        protected Page $page,
        protected GroupFactory $groupFactory,
        protected ScopeConfigInterface $scopeConfigInterface,
        protected TypeListInterface $cacheTypeList,
        protected Config $config,
        protected BlockFactory $blockFactory,
        protected LoggerInterface $logger,
        protected ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
    }

    /**
     * Execute view action of Pickup Address
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $sdeStoreCode = "sde_store";
        $cmsPagesKey = 'sde-home';
        $ondemandStoreCode = "ondemand";

        $sdeSettings = [
            'sde/general/is_enable', 'sde/general/sde_msg_title', 'sde/general/sde_msg_content',
            'sde/general/sde_checkout_signature_message', 'sde/general/secure_img', 'sde/sde_mask/sde_mask_img',
            'sde/sde_mask/is_enable', 'web/default/cms_home_page'
        ];
        try {
            if (!$this->isPhpUnit()) {
                print_r('Script execution start');
                print_r(self::LINE_BREAK);
            }

            // get group Obj from store code i.e. "ondemand"
            $ondemandGroupObj = $this->groupFactory->create()->load($ondemandStoreCode, 'code');
            $ondemandStoreIds = $ondemandGroupObj->getStoreIds();
            $ondemandStoreId = reset($ondemandStoreIds);

            // get group Obj from store code i.e. "sde_store"
            $sdeGroupObj = $this->groupFactory->create()->load($sdeStoreCode, 'code');
            $sdeStoreIds = current($sdeGroupObj->getStoreIds());
            $this->UpdateCmsBlockForOndemand($ondemandStoreId);
            if (!$this->isPhpUnit()) {
                print_r('Commercial footer updated to ondemand');
                print_r(self::LINE_BREAK);
            }

            foreach ($sdeSettings as $sdeSetting) {
                $configValue = $this->getConfigValue($sdeSetting, $sdeStoreIds);
                $this->setConfigData($sdeSetting, $configValue, $ondemandStoreId);
            }
            if (!$this->isPhpUnit()) {
                print_r('Core config setting updated to ondemand');
                print_r(self::LINE_BREAK);
            }

            $this->UpdateCmsPages($cmsPagesKey, $ondemandStoreId);
            if (!$this->isPhpUnit()) {
                print_r('Commercial cms page updated to ondemand');
                print_r(self::LINE_BREAK);
            }

            $this->logger->info('Script execution done');
            if (!$this->isPhpUnit()) {
                print_r('Script execution done');
            }

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
    /**
     * @method createCmsPages
     *
     * @param  string $pageKey
     * @param  int    $storeId
     * @return boolean
     */
    public function UpdateCmsPages($pageKey, $ondemandStoreId)
    {
        try {
            $sdeCmsPageData = $this->page->load($pageKey, 'identifier');
            $connection  = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName('cms_page_store');

            $pageData = [
                'store_id' => $ondemandStoreId,
                'row_id' => $sdeCmsPageData->getRowId()
            ];
            $connection->insert($tableName, $pageData);
            $this->logger->info('cms page setup done');
            return true;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    private function isPhpUnit(): bool
    {
        return (bool) strpos($_SERVER['argv'][0], 'phpunit') !== false;
    }

    public function getConfigValue($sdeSetting, $sdeStoreIds)
    {
        return $this->scopeConfigInterface->getValue($sdeSetting, ScopeInterface::SCOPE_STORE, $sdeStoreIds);
    }
    /**
     * set config data
     *
     * @param  string $path
     * @param  string $value
     * @param  int    $scopeId
     * @return boolean
     */
    public function setConfigData($path, $value, $scopeId)
    {
        try {
            $this->config->saveConfig(
                $path,
                $value,
                ScopeInterface::SCOPE_STORES,
                $scopeId
            );

            $types = [
                'config', 'layout', 'block_html', 'collections', 'reflection', 'db_ddl', 'eav',
                'config_integration', 'config_integration_api', 'full_page', 'translate', 'config_webservice'
            ];

            foreach ($types as $type) {
                $this->cacheTypeList->cleanType($type);
            }

            $this->logger->info('core config setting done');
            return true;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
    /**
     * To import and update CMS Block Data in database
     *
     * @param  int $ondemandStoreId
     * @return boolean
     */
    public function UpdateCmsBlockForOndemand($ondemandStoreId)
    {
        try {
            $connection  = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName('cms_block_store');
            $updateBlock = $this->blockFactory->create()->load(
                'commercial_footer',
                'identifier'
            );
            $storeIds = $updateBlock->getRowId();
            $blockData = [
                'store_id' => $ondemandStoreId,
                'row_id' => $storeIds
            ];

            $connection->insert($tableName, $blockData);
            $this->logger->info('cms Block updated');
            return true;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
