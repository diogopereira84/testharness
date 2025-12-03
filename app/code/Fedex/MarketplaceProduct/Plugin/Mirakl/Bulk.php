<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Plugin\Mirakl;

use Fedex\Catalog\Model\Config;
use Magento\Framework\App\ResourceConnection;
use \Magento\SharedCatalog\Model\ProductManagement;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Fedex\MarketplaceToggle\Helper\Config as StoreConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\SharedCatalog\Model\State as SharedCatalogState;
use Magento\SharedCatalog\Model\Management as SharedCatalogManagement;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

/**
 * Plugin Class
 */
class Bulk
{
    private const XPATH_DEFAULT_3P_PRODUCT_ID = 'fedex/marketplace_configuration/external_product_id';
    private const IN_STORE_PICKUP_ATTRIBUTE_CODE = 'in_store_pickup';
    private const IN_STORE_PICKUP_NOT_AVAILABLE = 'Not Available';
    private const CUSTOMIZABLE_PRODUCT_ATTRIBUTE_CODE = 'customizable_product';
    private const CUSTOMIZABLE_PRODUCT_NO_VALUE = 'No';
    private const NON_CUSTOMIZABLE_ATTRIBUTE_SET = 'FXONonCustomizableProducts';
    private const ATTRIBUTE_SET_CODE = 'attribute_set_code';

    /**
     * @param ToggleConfig $toggleConfig
     * @param ProductRepository $productRepository
     * @param ProductManagement $sharedCatalog
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param ResourceConnection $resourceConnection
     * @param Config $catalogConfig
     * @param SharedCatalogState $sharedCatalogState
     * @param SharedCatalogManagement $sharedCatalogManagement
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     */
    public function __construct(
        private ToggleConfig                     $toggleConfig,
        private ProductRepository                $productRepository,
        private ProductManagement                $sharedCatalog,
        private SearchCriteriaBuilder            $searchCriteriaBuilder,
        private SharedCatalogRepositoryInterface $sharedCatalogRepository,
        private ResourceConnection               $resourceConnection,
        private Config                           $catalogConfig,
        private SharedCatalogState               $sharedCatalogState,
        private SharedCatalogManagement          $sharedCatalogManagement,
        private MarketplaceCheckoutHelper        $marketplaceCheckoutHelper
    ) {}

    /**
     * Sets product attributes for all products that comes from Mirakl
     *
     * @param \Mirakl\Mcm\Model\Product\Import\Adapter\Bulk $subject
     * @param $data
     * @return array
     */
    public function beforeImport(
        \Mirakl\Mcm\Model\Product\Import\Adapter\Bulk $subject,
                                                      $data
    )
    {
        $data['page_layout'] = StoreConfig::MIRAKL_LAYOUT_IDENTIFIER;
        $data['is_catalog_product'] = 1;
        $data['product_id'] = (string)$this->toggleConfig->getToggleConfig(SELF::XPATH_DEFAULT_3P_PRODUCT_ID) ?? '';
        $data['in_store_pickup'] = $this->getDefaultOptionIdForInStorePickup();
        if ($this->catalogConfig->getTigerDisplayUnitCost3P1PProducts()) {
            $data['is_delivery_only'] = 1;
        }
        $data['page_layout'] = StoreConfig::MIRAKL_LAYOUT_IDENTIFIER;

        // if attribute set is "FXONonCustomizableProduct", set customizable_product to 0
        if ($this->marketplaceCheckoutHelper->isEssendantToggleEnabled() &&
            isset($data['data'][self::ATTRIBUTE_SET_CODE])
            && $data['data'][self::ATTRIBUTE_SET_CODE] === self::NON_CUSTOMIZABLE_ATTRIBUTE_SET) {
            $data[self::CUSTOMIZABLE_PRODUCT_ATTRIBUTE_CODE] = $this->getCustomizableNoOptionId();
        }
        $data['shared_catalogs'] = (string)$this->sharedCatalogManagement->getPublicCatalog()->getId();

        return [$data];
    }

    public function getDefaultOptionIdForInStorePickup()
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['eaov' => 'eav_attribute_option_value'],
            'eaov.option_id'
        )
            ->joinInner(
                ['eao' => 'eav_attribute_option'],
                'eao.option_id = eaov.option_id',
                ''
            )
            ->joinInner(
                ['ea' => 'eav_attribute'],
                'ea.attribute_id = eao.attribute_id',
                ''
            )
            ->where('eaov.value = ?', self::IN_STORE_PICKUP_NOT_AVAILABLE)
            ->where('ea.attribute_code = ?', self::IN_STORE_PICKUP_ATTRIBUTE_CODE);

        $value = $connection->fetchOne($select);

        return $value !== false ? $value : 0;
    }

    /**
     * Retrieve the option_id for the "No" value of the customizable_product attribute.
     *
     * @return int
     */
    public function getCustomizableNoOptionId(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['eaov' => 'eav_attribute_option_value'],
                'eaov.option_id'
            )
            ->joinInner(
                ['eao' => 'eav_attribute_option'],
                'eao.option_id = eaov.option_id',
                ''
            )
            ->joinInner(
                ['ea' => 'eav_attribute'],
                'ea.attribute_id = eao.attribute_id',
                ''
            )
            ->where('eaov.value = ?', self::CUSTOMIZABLE_PRODUCT_NO_VALUE)
            ->where('ea.attribute_code = ?', self::CUSTOMIZABLE_PRODUCT_ATTRIBUTE_CODE);

        $value = $connection->fetchOne($select);

        return $value !== false ? (int)$value : 0;
    }
}
