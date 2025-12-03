<?php

namespace Fedex\CatalogMvp\Helper;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\LiveSearch\Model\Config;
use Fedex\LiveSearch\ViewModel\Parameters;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\Model\Config\CheckCatalogPermissionToTemplate;
use Fedex\EnvironmentManager\Model\Config\ByPassLiveSearchApiCacheToggle;

class SharedCatalogLiveSearch extends AbstractHelper
{
    private const RANDOM_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    public const  TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';
    public const D_182472_PAGINATION_BAR_IS_NOT_WORKING_PROPERLY = 'D_182472_pagination_bar_is_not_working_properly';

    public const TECHTITANS_B2193925_PRODUCT_UPDATED_AT = 'techtitans_B_2193925_product_updated_at';
    public const PAGE_SIZE_DEFAULT = 10;
    private CollectionFactory $_productCollectionFactory;

    public function __construct(
        Context                           $context,
        protected Curl                    $curl,
        protected ServicesConfigInterface $servicesConfig,
        protected Http                    $request,
        protected StoreManagerInterface $storeManager,
        ScopeConfigInterface            $scopeConfig,
        protected ToggleConfig          $toggleConfig,
        public LoggerInterface          $logger,
        private CookieManagerInterface  $cookieManager,
        protected DeliveryHelper        $deliveryHelper,
        protected Config                $config,
        protected CatalogMvp            $catalogMvpHelper,
        CollectionFactory               $productCollectionFactory,
        protected ProductFactory        $product,
        protected Parameters            $parameters,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig,
        private readonly CheckCatalogPermissionToTemplate $checkCatalogPermissionToTemplate,
        private ProductRepositoryInterface $productRepository,
        private readonly ByPassLiveSearchApiCacheToggle $byPassLiveSearchApiCacheToggle
    ) {
        parent::__construct($context);
        $this->scopeConfig      = $scopeConfig;
        $this->_productCollectionFactory = $productCollectionFactory;
    }
    /**
     * Function to run the curl
     */
    public function curlCall( $apiData )
    {
        static $return = [];
        if (array_key_exists($apiData['requestData'], $return)
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return[$apiData['requestData']];
        }
        try {
            $environmentId = $this->servicesConfig->getEnvironmentId();
            $magentoWebsiteCode = $this->storeManager->getWebsite()->getCode();
            $mainWebsitestore = $this->storeManager->getStore()->getCode();
            $apiKeyId = $this->servicesConfig->getSandboxApiKey();

            if ($this->config->getToggleValueForLiveSearchProductionMode() ) {
                $apiKeyId = $this->servicesConfig->getProductionApiKey();
            }

            $endpoint = $this->config->getServiceUrl();
            $dataString = $apiData[ 'requestData' ];
            $method = $apiData[ 'method' ];
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'X-Api-Key:'.$apiKeyId;
            $headers[] = 'Magento-Environment-Id:'.$environmentId;
            $headers[] = 'Magento-Website-Code:'. $magentoWebsiteCode;
            $headers[] = 'Magento-Store-Code:'.$mainWebsitestore;
            $headers[] = 'Magento-Store-View-Code:'.$mainWebsitestore;
            $headers[] = 'Content-Length: ' . strlen($dataString);

            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );

            $this->logger->info(__(__LINE__ .' ' .__FILE__.' Live Search API Request Start'));
            $this->curl->post($endpoint, $dataString);
            $output = $this->curl->getBody();
            $this->logger->info(__(__LINE__ .' ' .__FILE__.' Live Search API Request End'));
            $return[$apiData['requestData']] = json_decode($output, true);
            return $return[$apiData['requestData']];
        } catch ( \Exception $e ) {
            $this->logger->error(__('System error while graphql api call ' .__LINE__ .' ' .__FILE__));
        }
        return true;
    }

    /**
     * Function to run the requestData
     */
    public function requestData( $queryParam )
    {
        $customerGroupCode ='';
        $strCustomerGroupCode = $this->cookieManager->getCookie('dataservices_customer_group') ?? '{}';
        $arrCustomerGroupCode = json_decode($strCustomerGroupCode, true);
        if (array_key_exists('customerGroupCode', $arrCustomerGroupCode)) {
            $customerGroupCode = $arrCustomerGroupCode['customerGroupCode'];
        }

        $paginationSize = $queryParam[ 'pageSize' ];
        $currentPage = $queryParam[ 'currentPage' ];
        $sortOrder = $queryParam[ 'sortOrder' ];
        $sortBy = $queryParam[ 'sortBy' ];
        $categoryPath = $queryParam[ 'categoryPath' ];
        $sharedCatalogIds = $this->parameters->getSharedCatalogId();

        $sharedCatalogStringArray = array_map(
            function ($item) {
                return (string) $item;
            }, $sharedCatalogIds
        );

        $filter = [];
        if ($this->byPassLiveSearchApiCacheToggle->isActive()) {
            $filter[] = ['attribute' => 'visibility','in' => ['Search',"Catalog, Search","Not Visible Individually", uniqid()]];
        } else {
            $filter[] = ['attribute' => 'visibility','in' => ['Search',"Catalog, Search","Not Visible Individually"]];
        }
        $filter[] = ['attribute' => 'categoryPath','eq' => $categoryPath];
        $filter[] = ['attribute' => 'shared_catalogs','in' => $sharedCatalogStringArray];

        $enableCatalogMvp = $this->catalogMvpHelper->isMvpSharedCatalogEnable();
        $catalogPermision = $this->catalogMvpHelper->isSharedCatalogPermissionEnabled();
        if(!$catalogPermision && $enableCatalogMvp) {
            $filter[] = ['attribute' => 'published','eq' => '1'];
        }
        $filterJson = json_encode($filter);

        $dataString = '{  "query": "query productSearch($phrase: String!, $pageSize: Int, $currentPage: Int = 1, $filter: [SearchClauseInput!], $sort: [ProductSearchSortInput!], $context: QueryContextInput) { productSearch(phrase: $phrase, page_size: $pageSize, current_page: $currentPage, filter: $filter, sort: $sort, context: $context) { total_count items { productView { attributes { label name value } __typename id sku name url images { label url roles } ... on ComplexProductView { priceRange { maximum { final { amount { value currency } } regular { amount { value currency } } } minimum { final { amount { value currency } } regular { amount { value currency } } } } options { id title values { title ... on ProductViewOptionValueSwatch { id type value } } } } ... on SimpleProductView { price { final { amount { value currency } } regular { amount { value currency } } } } } product { price_range { maximum_price { final_price { value currency  } } } id created_at updated_at attribute_set_id name } highlights { attribute value matched_words } } facets { title attribute buckets { title ... on ScalarBucket { __typename count } ... on RangeBucket { __typename from to count } ... on StatsBucket { __typename min max } } } page_info { current_page page_size total_pages } } attributeMetadata { sortable { label attribute numeric } }}",
        "variables": {
          "phrase": "",
          "pageSize": '.( int )  $paginationSize.',
          "currentPage": '. ( int )  $currentPage.',
          "filter": '.$filterJson.',
          "sort": [
            {
              "attribute": "'.$sortBy.'",
              "direction": "'.$sortOrder.'"
            }
          ]
        }
      }';

        return $dataString;
    }
    /**
     * @param  array
     * @return string
     */


    public function getProductDeatils(array $queryParam = [])
    {
        $sortOrder = $this->catalogMvpHelper->sortingToggle() ? 'ASC' : 'DESC';
        $sortBy = 'name';
        $currentPage = 1;
        $pageSize = null;

        $fullAction = $this->request->getFullActionName();
        $params = $this->request->getParams();

        if (!in_array($fullAction, ['selfreg_ajax_productlistajax', 'catalog_category_view'], true)) {
            return false;
        }

        $category = $this->catalogMvpHelper->getCurrentCategory();
        $productListOrder = $params['product_list_order'] ?? null;

        [$sortBy, $sortOrder] = match ($productListOrder) {
            'name_asc' => ['name', 'ASC'],
            'name_desc' => ['name', 'DESC'],
            'most_recent' => [
                $this->getToggleStatusForNewProductUpdatedAtToggle() ? 'product_updated_date' : 'updated_at',
                'DESC'
            ],
            default => [$sortBy, $sortOrder],
        };

        $currentPage = (int) ($params['p'] ?? $currentPage);
        $productListLimit = $this->request->getParam('product_list_limit');
        $productListMode = $this->request->getParam('product_list_mode', 'list');
        $isListMode = $productListMode === 'list';

            $customerSession = $this->catalogMvpHelper->getOrCreateCustomerSession();

            $listKey = 'ProductListLimitList';
            $gridKey = 'ProductListLimitGrid';

            $listDefault = (int) $this->scopeConfig->getValue('catalog/frontend/list_per_page', ScopeInterface::SCOPE_STORE);
            $gridDefault = (int) $this->scopeConfig->getValue('catalog/frontend/grid_per_page', ScopeInterface::SCOPE_STORE);

            $currentViewMode = $isListMode ? 'list' : 'grid';
            $previousViewMode = $customerSession->getData('previous_view_mode');
            $viewModeChanged = $previousViewMode && $previousViewMode !== $currentViewMode;

            $customerSession->setData('previous_view_mode', $currentViewMode);

            $key = $isListMode ? $listKey : $gridKey;
            $default = $isListMode ? $listDefault : $gridDefault;

            $pageSize = match (true) {
                $viewModeChanged => $default,
                !empty($productListLimit) => (int) $productListLimit,
                $customerSession->getData($key) !== null => (int) $customerSession->getData($key),
                default => $default,
            };

            $customerSession->setData($key, $pageSize);
            $customerSession->unsetData($isListMode ? $gridKey : $listKey);

        $queryParam = [
            'pageSize' => $pageSize,
            'currentPage' => $currentPage,
            'sortOrder' => $sortOrder,
            'sortBy' => $sortBy,
            'categoryPath' => $category?->getUrlPath() ?? '',
        ];

        $apiData = [
            'method' => 'POST',
            'requestData' => $this->requestData($queryParam),
        ];

        return $this->curlCall($apiData);
    }

    /**
     * B-1883021
     *
     * Checks if Catalog performance Toggle Enable
     */
    public function isEnabledCatalogPerformance()
    {
        $flag = false;
        if($this->deliveryHelper->isCommercialCustomer()) {
            $flag = true;
        }
        return $flag;
    }

    public function getCategoryProduction()
    {
        $categoryId =  $this->catalogMvpHelper->getCurrentCategory()->getId();
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addCategoriesFilter(['in' => $categoryId]);
        $skuMapping = [];
        foreach ($collection as $product) {
            $skuMapping[$product->getSku()] = $product->getId();
        }
        return $skuMapping;
    }
    /**
     * @param  ListProduct $subject
     * @param  $result
     * @return object
     */
    public function getProductCollection()
    {
        static $result = null;
        if ($result !== null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $result;
        }
        $data = $this->getProductDeatils();
        $collection = $this->_productCollectionFactory->create();
        $collection->addFieldToFilter('sku', 'liveFedexSearch');
        if ($this->toggleConfig->getToggleConfigValue('explorers_non_standard_catalog')) {
            $collection->addAttributeToSelect('is_pending_review');
        }
        if (isset($data['data']['productSearch']['items'])) {
            $items = $data['data']['productSearch']['items'];
            $skupmapping = $this->getCategoryProduction();
            $attributeSetId = $this->catalogMvpHelper->getAttrSetIdByName("PrintOnDemand");
            foreach ($items as $item) {
                $sku = $item['productView']['sku'] ?? "";
                $name = $item['productView']['name'] ?? "";

                if (!$name || $name == "") {
                    continue;
                }

                $price = $this->toggleConfig->getToggleConfigValue('tiger_d210517')
                    ? ($item['productView']['price']['final']['amount']['value']
                        ?? $item['productView']['price']['regular']['amount']['value']
                        ?? $item['product']['price_range']['maximum_price']['final_price']['value']
                        ?? "0.00")
                    : ($item['product']['price_range']['maximum_price']['final_price']['value']
                        ?? "0.00");

                // 15 minutes after price/name changed, it gets its data from Magento
                if ($this->toggleConfig->getToggleConfigValue('techtitans_D190199_fix')) {
                    $customerSession = $this->catalogMvpHelper->getOrCreateCustomerSession();
                    $cacheDisabledProductsId = $customerSession->getCacheDisableProductsID() ?? [];
                    if (
                        array_key_exists($item['product']['id'], $cacheDisabledProductsId) &&
                        (time() - $cacheDisabledProductsId[$item['product']['id']]) < 900 &&
                        $product = $this->productRepository->getById($item['product']['id'])
                    )  {
                        $price = $product->getPrice();
                        $name = $product->getName();
                    }
                }

                if (isset($skupmapping[$sku])) {
                    $productId = $skupmapping[$sku];
                } else {
                    $productId = $item['product']['id'] ?? "";
                }
                if ($price == "") {
                    $price = "0.00";
                }
                $product = [];
                $product = $this->product->create();
                $product->setData('entity_id', $productId);
                $product->setData('sku', $sku);
                $product->setData('name', $name);
                $product->setData('price', $price);
                $attributes = $item['productView']['attributes'] ?? [];
                foreach ($attributes as $attribute) {
                    $value = $attribute['value'];
                    if ($value == "no") {
                        $value = false;
                    } else if ($value == "yes") {
                        $value = true;
                    }
                    if(isset($attribute['name']) &&  $attribute['name'] == 'product_updated_date' ) {
                        $product->setUpdatedAt($attribute['value']);
                    }
                    if(isset($attribute['name']) && $attribute['name'] == 'product_attribute_sets_id' && $attribute['value'] ) {
                        $attributeSetId = $attribute['value'];
                    }
                    $product->setData($attribute['name'], $value);
                }
                $images = $item['productView']['images'] ?? [];
                foreach($images as $image) {
                    $imageUrl = $image['url'] ?? "";
                    $product->setData("custom_thumbnail_url", $imageUrl);
                    $roles = $image['roles'] ?? [];
                    if(in_array("thumbnail", $roles)) {
                        $product->setData("custom_thumbnail_url", $imageUrl);
                        $imageName = $this->getImageNameFromUrl($imageUrl);
                        $product->setData('thumbnail', $imageName);
                    }
                    if(in_array("image", $roles)) {
                        $imageName = $this->getImageNameFromUrl($imageUrl);
                        $product->setData('image', $imageName);
                    }
                    if(in_array("small_image", $roles)) {
                        $imageName = $this->getImageNameFromUrl($imageUrl);
                        $product->setData('small_image', $imageName);
                    }
                }
                $product->setData('attribute_set_id', $attributeSetId);
                if(!$product->getUpdatedAt()) {
                    $product->setUpdatedAt(date('m/d/Y', strtotime("-1 days")));
                }
                // B-2504475 Print Products CTA causing Scheduled Maintenance error across different sites
                $isB2504475ToggleEnable = $this->toggleConfig->getToggleConfigValue('tech_titans_B_2504475');
                if ($isB2504475ToggleEnable) {
                    if (!$collection->getItemById($productId)) {
                        $collection->addItem($product);
                    }
                } else {
                    $collection->addItem($product);
                }

            }
        }
        $result = $collection;
        return $result;
    }
    /**
     *
     * @param  $imageUrl
     * @return string
     */
    public function getImageNameFromUrl($imageUrl)
    {
        $imageName = explode("/", $imageUrl);
        $imageName = array_slice($imageName, -3, 3, true);
        $imageName = implode("/", $imageName);

        if($imageName != "") {
            $imageName = "/".$imageName;
        }
        return  $imageName;
    }
    /**
     * Get the status of the Grid Item Size Fix Toggle
     * @return bool true|false
     */
    public function getGridPageSizeToggle()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::D_182472_PAGINATION_BAR_IS_NOT_WORKING_PROPERLY);
    }
    /**
     * Get the status toggle status for customer admin
     *
     * @return bool true|false
     */
    public function getToggleStatusCustomerPerformanceImprovmentPhaseOne()
    {
        $toogleStatusFlag = false;
        $toogleStatus = $this->getToggleStatusForPerformanceImprovmentPhasetwo();
        $isSelfRegCustomerAdmin = $this->catalogMvpHelper->isSelfRegCustomerAdmin();
        $isSelfRegCustomer = $this->catalogMvpHelper->isMvpCatalogEnabledForCompany();
        $isSharedCatalogPermission = $this->catalogMvpHelper->isSharedCatalogPermissionEnabled();
        $isSharedCatalogPermissionMgtFixToggleEnabled = $this->checkCatalogPermissionToTemplate->isActive();

        if ($toogleStatus &&
            $isSelfRegCustomer &&
            !$isSelfRegCustomerAdmin &&
            (!$isSharedCatalogPermissionMgtFixToggleEnabled || !$isSharedCatalogPermission)
        ) {
            $toogleStatusFlag = true;
        }
        return $toogleStatusFlag;
    }

    /**
     * Get the status toggle status for customer admin
     *
     * @return bool true|false
     */
    public function getToggleStatusCustomerAdminPerformanceImprovmentPhaseOne()
    {
        $toogleStatusFlag = false;
        $toogleStatus = $this->getToggleStatusForPerformanceImprovmentPhasetwo();
        $isSelfRegCustomerAdmin = $this->catalogMvpHelper->isSelfRegCustomerAdmin();
        $isSharedCatalogPermission = $this->catalogMvpHelper->isSharedCatalogPermissionEnabled();
        $isSharedCatalogPermissionMgtFixToggleEnabled = $this->checkCatalogPermissionToTemplate->isActive();

        if ($toogleStatus &&
            ($isSelfRegCustomerAdmin || ($isSharedCatalogPermissionMgtFixToggleEnabled && $isSharedCatalogPermission))
        ) {
            $toogleStatusFlag = true;
        }

        return $toogleStatusFlag;
    }

    /**
     * Get the status toggle status
     *
     * @return bool true|false
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

    /**
     * Toggle for B-2193925 Product updated at toggle
     * @return bool
     */
    public function getToggleStatusForNewProductUpdatedAtToggle()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_B2193925_PRODUCT_UPDATED_AT);
    }
}
