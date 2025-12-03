<?php
/**
 * @category  Fedex
 * @package   Fedex_LiveSearch
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\ViewModel;

use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;
use Magento\Catalog\Helper\Image;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Session;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Catalog\Model\Config;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\LiveSearch\Api\Data\ConfigInterface;
use Fedex\Punchout\Helper\Data as PunchOutHelper;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SaaSCommon\Api\ConfigInterface as SaasConfigInterface;
class Parameters implements ArgumentInterface
{
    public const CUSTOMER_GROUP_ID_COLUMN = 'customer_group_id';
    public const LOGGED_IN_CUSTOMER_GROUP_ID = 1;

    /**
     * @param Image $imageHelper
     * @param ConfigInterface $config
     * @param ServicesConfigInterface $servicesConfig
     * @param DeliveryHelper $deliveryHelper
     * @param PunchOutHelper $punchOutHelper
     * @param StoreManagerInterface $storeManager
     * @param Config $catalogConfig
     * @param Session $customerSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param CatalogMvp $catalogMvp
     * @param AuthHelper $authHelper
     * @param ToggleConfig $toggleConfig
     * @param CheckProductAvailabilityDataModel $checkProductAvailabilityDataModel
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        private readonly Image $imageHelper,
        private readonly ConfigInterface $config,
        private readonly ServicesConfigInterface $servicesConfig,
        private readonly DeliveryHelper $deliveryHelper,
        private readonly PunchOutHelper $punchOutHelper,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $catalogConfig,
        private Session $customerSession,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private SharedCatalogRepositoryInterface $sharedCatalogRepository,
        private CatalogMvp $catalogMvp,
        protected AuthHelper $authHelper,
        private ToggleConfig $toggleConfig,
        private readonly CheckProductAvailabilityDataModel $checkProductAvailabilityDataModel,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        private readonly AttributeSetRepositoryInterface $attributeSetRepository,
        private SaasConfigInterface $saasConfig
    ) {
    }

    public const TIGER_ENHANCED_COMMERCIAL_SORT_BY_TOGGLE = 'tiger_enhanced_commercial_sort_by';
    public const IS_VARIANT_QUERY_ENABLED = 'tiger_tk_4392056';
    public const TIGER_TEAM_D_217182_FIX = 'tiger_team_d_217182';
    public const TIGER_D236292_3P_PRODUCTS_CTA_FIX_ON_PLP = 'tiger_d236292';
    public const TIGER_TEAM_D_240007_FIX = 'tiger_d240007_default_sku_logic_for_configurable_products';

    /**
     * Function to get the parameters required for Live Search
     *
     * @param $environmentId
     * @param $websiteCode
     * @param $storeCode
     * @param $storeViewCode
     * @param $customerGroupCode
     * @return array
     */
    public function getLiveSearchParameters(
        $environmentId,
        $websiteCode,
        $storeCode,
        $storeViewCode,
        $customerGroupCode
    ): array
    {
        return [
            'environmentId' => $environmentId,
            'websiteCode' => $websiteCode,
            'storeCode' => $storeCode,
            'storeViewCode' => $storeViewCode,
            'customerGroup' => $customerGroupCode,
            'placeholderImage' => $this->getDefaultPlaceholderUrl('thumbnail'),
            'serviceUrl' => $this->getServiceUrl(),
            'graphqlServiceUrl' => $this->getGraphqlServiceUrl(),
            'XApiKey' => $this->getXApiKey(),
            'customerGroupId' => $this->getCustomerGroupId(),
            'displayUnitCost3p1pProductsToggle' => $this->isDisplayUnitCost3p1pProductsToggleEnabled(),
            'ellipsisEnabled' => $this->isEllipsisControlEnabled(),
            'ellipsisTotalCharacters' => $this->getEllipsisControlTotalCharacters(),
            'ellipsisStartCharacters' => $this->getEllipsisControlStartCharacters(),
            'ellipsisEndCharacters' => $this->getEllipsisControlEndCharacters(),
            'sharedCatalogId' => $this->getSharedCatalogId(),
            'isE441563ToggleEnabled' => $this->isE441563ToggleEnabled(),
            'isEssendantToggleEnabled' => $this->isEssendantToggleEnabled(),
            'isEnhancedCommercialSortByEnabled' => $this->getEnhancedCommercialSortByToggle(),
            'hideUnpublishedInSearch' => !$this->getUnpublishedSearchVisibility(),
            'attributeSets' => $this->isEssendantToggleEnabled() ? $this->getAttributeSetList() : [],
            'enableVariantQuery' => $this->getIsVariantQueryEnabled(),
            'isTigerD200529Enabled' => $this->isTigerD200529Enabled(),
            'isTigerD236292Enabled' => $this->isTigerD236292Enabled(),
            'allowOwnDocument' => $this->isAllowOwnDocument(),
            'tigerTeamD240007Enabled' => $this->isTigerTeamD240007Enabled(),
            'tiger_team_d_217182' => $this->isTigerTeamD217182Enabled(),
            'siteName' => $this->isCommercialCustomer() ? $this->getSiteName() : null,
            'TazToken' => $this->isCommercialCustomer() ? $this->getTazToken() : null,
        ];
    }

    /**
     * @param string $type
     * @return string
     */
    public function getDefaultPlaceholderUrl(string $type): string
    {
        return $this->imageHelper->getDefaultPlaceholderUrl($type);
    }

    /**
     * @return string
     */
    public function getServiceUrl(): string
    {
        return (string)$this->config->getServiceUrl();
    }
      /**
     * @return string
     */
    public function getGraphqlServiceUrl(): string
    {
        return (string)$this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB).'graphql';
    }

    /**
     * @return string
     */
    public function getXApiKey(): string
    {
        $isProductionApiKey = $this->config->getToggleValueForLiveSearchProductionMode();

        if($isProductionApiKey){
           return (string)$this->servicesConfig->getProductionApiKey();
        }
        return (string)$this->servicesConfig->getSandboxApiKey();
    }

    /**
     * @return string
     */
    public function getSiteName(): string
    {
        return $this->deliveryHelper->getCompanySite() ?? '';
    }

    /**
     * @return string
     */
    public function getTazToken(): string
    {
        return $this->punchOutHelper->getTazToken();
    }
    /**
     * @return bool
     */
    public function isCommercialCustomer(): bool
    {
        return (bool)$this->deliveryHelper->isCommercialCustomer();
    }

    /**
     * @return bool
     */
    public function isDisplayUnitCost3p1pProductsToggleEnabled(): bool
    {
        return $this->catalogConfig->getTigerDisplayUnitCost3P1PProducts();
    }

    /**
     * @return bool
     */
    public function isEllipsisControlEnabled(): bool
    {
        return $this->config->isEllipsisControlEnabled();
    }

    /**
     * @return bool
     */
    public function getTigerB2315919Toggle(): bool
    {
        return $this->catalogConfig->getTigerB2315919Toggle();
    }

    /**
     * @return int
     */
    public function getEllipsisControlTotalCharacters(): int
    {
        return $this->config->getEllipsisControlTotalCharacters();
    }

    /**
     * @return int
     */
    public function getEllipsisControlStartCharacters(): int
    {
        return $this->config->getEllipsisControlStartCharacters();
    }

    /**
     * @return int
     */
    public function getEllipsisControlEndCharacters(): int
    {
        return $this->config->getEllipsisControlEndCharacters();
    }

    /**
     * @return array
     */
    public function getSharedCatalogId(): array
    {
        $sharedCatalogId[] = $this->config->getGuestUserSharedCatalogId();
        if ($this->isCommercialCustomer() && $this->authHelper->isLoggedIn()) {
            if($this->customerSession->getSharedCatalogId()){
                return $this->customerSession->getSharedCatalogId();
            }
            $groupId = $this->customerSession->getCustomerGroupId();
            $parentGroupId = $this->catalogMvp->getParentGroupId($groupId);
            $isAdminUser = $this->catalogMvp->isSelfRegCustomerAdmin();
            if ($parentGroupId) {
                $sharedCatalogId = $this->getSharedCatalogIdByGroupId($parentGroupId);
                $groupIds = [$parentGroupId, $groupId];
                $groupIds = array_merge($groupIds, $sharedCatalogId);
                $this->customerSession->setSharedCatalogId($groupIds);
                return $groupIds;
            } else if ($isAdminUser) {
                $groupIds = $this->catalogMvp->getChildGroupIds($groupId);
                $sharedCatalogId = $this->getSharedCatalogIdByGroupId($groupId);
                $groupIds = array_merge($groupIds, $sharedCatalogId);
                $this->customerSession->setSharedCatalogId($groupIds);
                return $groupIds;
            } else {
                $customerGroupId = $this->customerSession->getCustomerGroupId();
            }

            $sharedCatalogId = $this->getSharedCatalogIdByGroupId($customerGroupId);
        }
        return $sharedCatalogId;
    }

    /**
     * Get SharedCatalogId by GroupId
     * @return array
     */
    public function getSharedCatalogIdByGroupId($customerGroupId)
    {
        $this->searchCriteriaBuilder->addFilter(self::CUSTOMER_GROUP_ID_COLUMN, $customerGroupId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $sharedCatalog = $this->sharedCatalogRepository->getList($searchCriteria);
        $sharedCatalogId = [];
        if ($sharedCatalog->getTotalCount() > 0) {
            foreach ($sharedCatalog->getItems() as $item) {
                $sharedCatalogId[] = $item->getId();
                $this->customerSession->setSharedCatalogId($sharedCatalogId);
                return $sharedCatalogId;
            }
        }
        return $sharedCatalogId;
    }

    /**
     * @return bool
     */
    public function getEnhancedCommercialSortByToggle(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_ENHANCED_COMMERCIAL_SORT_BY_TOGGLE);
    }

    /**
     * @return bool
     */
    public function isEssendantToggleEnabled(): bool
    {
        return (bool)$this->marketplaceCheckoutHelper->isEssendantToggleEnabled();
    }

    /**
     * @return bool
     */
    public function isE441563ToggleEnabled():bool
    {
        return (bool) $this->checkProductAvailabilityDataModel->isE441563ToggleEnabled();
    }

    /**
     * Function to get the attribute set list
     *
     * @return array
     */
    public function getAttributeSetList(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributeSets = $this->attributeSetRepository->getList($searchCriteria)->getItems();

        $attributeSetList = [];
        foreach ($attributeSets as $attributeSet) {
            $attributeSetList[$attributeSet->getAttributeSetName()] = $attributeSet->getAttributeSetId();
        }

        return $attributeSetList;
    }

    /**
     * Determines if unpublished products should be visible in search results
     *
     * @return bool True if unpublished products should be visible
     */
    public function getUnpublishedSearchVisibility(): bool
    {
        return $this->getTigerB2315919Toggle() && $this->catalogMvp->isSelfRegCustomerAdmin();
    }

    public function getIsVariantQueryEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::IS_VARIANT_QUERY_ENABLED);
    }

    public function isTigerD236292Enabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D236292_3P_PRODUCTS_CTA_FIX_ON_PLP);
    }

    public function isTigerD200529Enabled(): bool {
         return $this->saasConfig->isTigerD200529Enabled();
    }

    /**
     * @return int
     */
    public function getCustomerGroupId()
    {
        try {
            $customerGroupId = $this->customerSession->getCustomerGroupId();
        } catch (LocalizedException $e) {
            $customerGroupId = self::LOGGED_IN_CUSTOMER_GROUP_ID;
        }

        return $customerGroupId;
    }

    public function isTigerTeamD217182Enabled(): bool {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_TEAM_D_217182_FIX);
    }

    public function isTigerTeamD240007Enabled(): bool {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_TEAM_D_240007_FIX);
    }
    
    /**
     * Check if the current company allows own document.
     *
     * @return bool
     */
    public function isAllowOwnDocument(): bool
    {
        if (!(bool) $this->isTigerTeamD217182Enabled()) {
            return false;
        }

        $customer = $this->deliveryHelper->getCustomer();
        $company = $this->deliveryHelper->getAssignedCompany($customer);

        return (bool) ($company && $company->getAllowOwnDocument());
    }
}

