<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);
namespace Fedex\LiveSearchFacets\Model\Resolver;

use Fedex\LiveSearchFacets\Model\Cache\Type;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\LiveSearch\Api\ServiceClientInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Cache\Manager;

class DataProvider
{
    /**
     * Config paths 
     */
    public const BACKEND_PATH = 'live_search/backend_path';
    /**
     * @var DataProvider
     */
    private DataProvider $tooltipDataProvider;
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    /**
     * @param ServiceClientInterface $serviceClient
     * @param ScopeConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param Logger $logger
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param Manager $cacheManager
     */
    public function __construct(
        private ServiceClientInterface $serviceClient,
        private ScopeConfigInterface $config,
        private StoreManagerInterface $storeManager,
        ProductAttributeRepositoryInterface $attributeRepository,
        private Logger $logger,
        private CacheInterface $cache,
        private SerializerInterface $serializer,
        private Manager $cacheManager
    ) {
        $this->productAttributeRepository = $attributeRepository;
    }

    /**
     * Get tooltipdata
     *
     * @return array|bool|int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTooltipData()
    {
        $attributes = [];
        try {
            if (!$this->getFacetsData()) {
                $facets = $this->getFacets();
                foreach ($facets as $key => $value) {
                    if ($value['attributeCode']=='categories') {
                        $attributes[$key]['attribute_code']= 'categories';
                        $attributes[$key]['tooltip']= '';
                    } else {
                        $attribute = $this->productAttributeRepository->get($value['attributeCode']);
                        if ($attribute) {
                            $attributes[$key]['attribute_code']= $attribute->getAttributeCode();
                            $attributes[$key]['tooltip']= $attribute->getFacetTooltip();
                        }
                    }
                }
                $this->saveFacetsDataInCache($attributes);
            } else {
                $attributes = $this->getFacetsData();
            }

        } catch (NoSuchEntityException $e) {
             $this->logger->error(__METHOD__ . ':' . __LINE__ . 'No livesearch facets attribute found');
        }
        return $attributes;
    }

    /**
     * Get Facets
     *
     * @return array
     * @throws Exception|LocalizedException
     */
    public function getFacets()
    {
        $headers = [
            'Magento-Website-Code' => $this->storeManager->getWebsite()->getCode(),
            'Magento-Store-Code' => $this->storeManager->getGroup()->getCode(),
            'Magento-Store-View-Code' => $this->storeManager->getDefaultStoreView()->getCode(),
            'Magento-Is-Preview' => ''
        ];
         $payload = '{"operationName":"getFacets","variables":{},"query":"query getFacets {\n  facetsConfiguration {\n    facetsConfig {\n      title\n      attributeCode\n      facetType\n      dataType\n      maxValue\n      multiSelect\n      multiSelectOperator\n      numeric\n      sortType\n      aggregationType\n      aggregationRanges {\n        from\n        to\n      }\n      frontendInput\n    }\n  }\n}"}';

        $path = $this->config->getValue(static::BACKEND_PATH);

        $result = [];
        try {
            $result = $this->serviceClient->request($headers, $path, $payload);
            $result = $result['data']['facetsConfiguration']['facetsConfig']??[];
        } catch (\Exception $e) {
             $this->logger->error(__METHOD__ . ':' . __LINE__ . 'error occurred');
        }
        return $result;
    }

    /**
     * Save Factes data in cache
     *
     * @param array $attributes
     * @return void
     */
    public function saveFacetsDataInCache($attributes)
    {
        $cacheKey  = Type::TYPE_IDENTIFIER;
        $cacheTag  = Type::CACHE_TAG;
        if (in_array($cacheKey, $this->cacheManager->getAvailableTypes()))
        {
            $cacheValue = $this->cache->load($cacheKey);
            if ($cacheValue == "") {
                    $cacheData = $attributes;
                    $this->cache->save($this->serializer->serialize($cacheData), $cacheKey, [$cacheTag]);
            }
        }
    }

    /**
     * Get feature facets value from cache
     *
     * @return int|boolean 1|0|false
     */
    public function getFacetsData()
    {
        if (in_array(Type::TYPE_IDENTIFIER, $this->cacheManager->getAvailableTypes()))
        {
            $cacheKey = Type::TYPE_IDENTIFIER;
            $cacheValueArray = [];
            $cacheValue = $this->cache->load($cacheKey);
            if ($cacheValue != "") {
                $cacheValueArray = $this->serializer->unserialize($cacheValue);
            }

            if (!empty($cacheValueArray)) {
                return $cacheValueArray;
            } else {
                return false;
            }
        }
        return false;
    }
}
