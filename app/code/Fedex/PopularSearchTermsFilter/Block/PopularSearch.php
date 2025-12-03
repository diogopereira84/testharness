<?php
declare(strict_types=1);

/**
 * @category Fedex
 * @package Fedex_PopularSearchTermsFilter
 * @copyright (c) 2023.
 * @author Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
namespace Fedex\PopularSearchTermsFilter\Block;

use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\LiveSearch\Api\ApiException;
use Magento\LiveSearch\Api\KeyInvalidException;
use Magento\LiveSearch\Api\ServiceClientInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Config\CacheInterface;

class PopularSearch extends Template
{
    /**
     * Config paths
     */
    public const BACKEND_PATH = 'live_search/backend_path';
    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $date;

    /**
     * @param Context $context
     * @param ServiceClientInterface $serviceClient
     * @param ScopeConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     * @param CacheInterface $cache
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private ServiceClientInterface $serviceClient,
        private ScopeConfigInterface $config,
        private StoreManagerInterface $storeManager,
        private LoggerInterface $logger,
        private DateTime $dateTime,
        private CacheInterface $cache,
        private readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        array $data = []
    )
    {
        parent::__construct($context,$data);
    }

    /**
     * Get popular search terms
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPopularSearchTerms(){

        if($this->addToCartPerformanceOptimizationToggle->isActive()){
            $cacheKey = 'popular_search_terms';
            $cacheLifetime = 3600;

            $cachedResponse = $this->cache->load($cacheKey);
            if ($cachedResponse !== false) {
                return json_decode($cachedResponse, true);
            }
        }

        $response = [];
        $headers = [
            'Magento-Website-Code' => $this->storeManager->getWebsite()->getCode(),
            'Magento-Store-Code' => $this->storeManager->getStore()->getCode(),
            'Magento-Store-View-Code' => $this->storeManager->getDefaultStoreView()->getCode(),
            'Magento-Is-Preview' => ''
        ];
        $endDate = substr($this->dateTime->gmtDate('Y-m-d\TH:i:s.u\Z'), 0, 23) . 'Z';
        $before7DayDate = "-7 days";
        $timeStamp = $this->dateTime->timestamp($before7DayDate);

        $startDate = substr($this->dateTime->gmtDate('Y-m-d\TH:i:s.u\Z', $timeStamp), 0, 23) . 'Z';;

        /* This payload is copied from https://search-admin-ui.magento-ds.com/v0/admin.js it can be change once it
        will update in original module*/

        $payload='{"operationName":"analytics","variables":{"start":"'.$startDate.'","end":"'.$endDate.'"},"query":"query analytics($start: String!, $end: String!) {\n  analytics(\n    input: {analyticsTypes: [POPULAR_RESULTS], timeframeInput: {start: $start, end: $end}}\n  ) {\n    summary {\n      uniqueSearches\n      clickThruRate\n      conversionRate\n      zeroResultsRate\n      averageClickPos\n}\n    popularResults {\n      productName\n      impressions\n      revenue\n      imageUrl\n    }\n    zeroResults {\n      searchQuery\n      count\n    }\n    uniqueSearchResults {\n      searchQuery\n      count\n    }\n  }\n}"}';
        $path = $this->config->getValue(static::BACKEND_PATH);


        try {
            $result = $this->serviceClient->request($headers, $path, $payload);
            $response  = $result['data']['analytics']['popularResults']??[];
            $response = array_slice($response, 0, 10);

            if ($this->addToCartPerformanceOptimizationToggle->isActive()) {
                $this->cache->save(json_encode($response), $cacheKey, [], $cacheLifetime);
            }
        } catch (KeyInvalidException|ApiException $e) {
            $this->logger->critical($e->getMessage());
        }
        return $response;
    }

    /**
     * Get Popular search Product redirect Url
     *
     * @param $productName
     * @return string
     * @throws NoSuchEntityException
     */
    public function getProductRedirectUrl($productName){
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $catalogSearchPath = 'catalogsearch/result/?q=';
        $productName = preg_replace('/\s+/', '+', $productName);
        return $baseUrl.$catalogSearchPath.$productName;
    }
}


