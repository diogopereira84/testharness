<?php
declare(strict_types=1);

/**
 * @category Fedex
 * @package Fedex_PopularSearchTermsFilter
 * @copyright (c) 2023.
 * @author Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
namespace Fedex\PopularSearchTermsFilter\Test\Unit\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\LiveSearch\Api\ServiceClientInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\PopularSearchTermsFilter\Block\PopularSearch;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class PopularSearchTest extends TestCase
{
    protected $serviceClientMock;
    protected $configMock;
    protected $storeManagerMock;
    protected $loggerMock;
    protected $dateTimeMock;
    protected $storeMock;
    protected $popularTermsProvider;
    /**
     * Config paths
     */
    public const BACKEND_PATH = 'live_search/backend_path';
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->serviceClientMock = $this->getMockBuilder(ServiceClientInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['gmtDate','timestamp'])
            ->getMockForAbstractClass();
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();


        $ObjectManagerHelper = new ObjectManager($this);
        $this->popularTermsProvider = $ObjectManagerHelper->getObject(
            PopularSearch::class,
            [
                'serviceClient' => $this->serviceClientMock,
                'storeManager' => $this->storeManagerMock,
                'config' => $this->configMock,
                'logger' => $this->loggerMock,
                'dateTime' => $this->dateTimeMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetPopularSearchTerms(){
        $headers = [
            'Magento-Website-Code' => 'base',
            'Magento-Store-Code' => 'default',
            'Magento-Store-View-Code' => 'default',
            'Magento-Is-Preview' => ''
        ];
        $path = 'search-admin/graphql';
        $payload='{"operationName":"analytics","variables":{"start":"2023-07-12T06:06:12.622Z","end":"2023-07-12T06:06:12.622Z"},"query":"query analytics($start: String!, $end: String!) {\n  analytics(\n    input: {analyticsTypes: [POPULAR_RESULTS], timeframeInput: {start: $start, end: $end}}\n  ) {\n    summary {\n      uniqueSearches\n      clickThruRate\n      conversionRate\n      zeroResultsRate\n      averageClickPos\n}\n    popularResults {\n      productName\n      impressions\n      revenue\n      imageUrl\n    }\n    zeroResults {\n      searchQuery\n      count\n    }\n    uniqueSearchResults {\n      searchQuery\n      count\n    }\n  }\n}"}';
        $this->storeManagerMock->expects($this->any())->method('getWebsite')->willReturn($this->storeMock);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeManagerMock->expects($this->any())->method('getDefaultStoreView')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturnOnConsecutiveCalls('base','default','default');
        $this->dateTimeMock->expects($this->any())->method('gmtDate')->willReturn('2023-07-12T06:06:12.622Z');
        $this->dateTimeMock->expects($this->any())->method('timestamp')->willReturn('1688631318');
        $this->configMock->expects($this->any())->method('getValue')->willReturn($path);
        $this->serviceClientMock->expects($this->any())->method('request')->with($headers,$path,$payload)->willReturn([]);
        $this->assertEquals([],$this->popularTermsProvider->getPopularSearchTerms());

    }

    /**
     * @return void
     */
    public function testGetPopularSearchTermsWithException()
    {
        $this->serviceClientMock->method('request')
            ->willThrowException(
                new \Exception("An error occured.")
            );
    }

    /**
     * @return void
     */
    public function testGetProductRedirectUrl(){
        $productName = 'test';
        $baseUrl = 'base-url';
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $catalogSearchPath = 'catalogsearch/result/?q=';
        $productName = preg_replace('/\s+/', '+', $productName);
        $this->assertEquals($baseUrl.$catalogSearchPath.$productName,
            $this->popularTermsProvider->getProductRedirectUrl($productName));
    }

}

