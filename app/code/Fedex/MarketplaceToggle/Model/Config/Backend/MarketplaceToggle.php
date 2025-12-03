<?php
declare(strict_types=1);

namespace Fedex\MarketplaceToggle\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config as StoreConfig;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use \Magento\Framework\Module\Manager as ModuleManager;

class MarketplaceToggle extends Value
{
    /**
     * @var ValueFactory
     */
    protected ValueFactory $_configValueFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param Config $resourceConfig
     * @param StoreConfig $storeConfig
     * @param ModuleManager $moduleManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Registry             $registry,
        ScopeConfigInterface $config,
        TypeListInterface    $cacheTypeList,
        ValueFactory         $configValueFactory,
        protected Config               $resourceConfig,
        protected StoreConfig          $storeConfig,
        protected ModuleManager       $moduleManager,
        AbstractResource     $resource = null,
        AbstractDb           $resourceCollection = null,
        array                $data = []
    ) {
        $this->_configValueFactory = $configValueFactory;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return MarketplaceToggle
     */
    public function afterSave(): MarketplaceToggle
    {
        if ($this->moduleManager->isEnabled("Mirakl_Api")) {
            $this->resourceConfig->saveConfig(
                \Mirakl\Api\Helper\Config::XML_PATH_ENABLE,
                $this->getValue(),
                ScopeInterface::SCOPE_DEFAULT,
                0
            );

            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        }


        return parent::afterSave();
    }
}
