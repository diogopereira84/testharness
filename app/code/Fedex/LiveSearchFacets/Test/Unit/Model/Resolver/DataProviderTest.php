<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);
namespace Fedex\LiveSearchFacets\Unit\Test\Model\Resolver;

use Magento\LiveSearch\Api\ServiceClientInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Fedex\LiveSearchFacets\Model\Resolver\DataProvider;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\App\Cache\Manager;

class DataProviderTest extends TestCase
{
    protected $serviceClientMock;
    protected $configMock;
    protected $storeManagerMock;
    /**
     * @var (\Magento\Catalog\Api\ProductAttributeRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productAttributeRepositoryMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\App\CacheInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cacheMock;
    /**
     * @var (\Magento\Framework\Serialize\SerializerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializerMock;
    /**
     * @var (\Magento\Framework\App\Cache\Manager & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cacheManagerMock;
    /**
     * @var (\Magento\Catalog\Api\Data\ProductAttributeInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eavAttribute;
    protected $storeMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $dataProvider;
    /**
     * Config paths
     */
    public const BACKEND_PATH = 'live_search/backend_path';

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->serviceClientMock = $this->getMockBuilder(ServiceClientInterface::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $this->configMock = $this->getMockBuilder(ScopeConfigInterface::class)
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->productAttributeRepositoryMock = $this->getMockBuilder(ProductAttributeRepositoryInterface::class)
                                            ->disableOriginalConstructor()
                                            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(Logger::class)
                                        ->disableOriginalConstructor()
                                        ->getMockForAbstractClass();

        $this->cacheMock = $this->getMockBuilder(CacheInterface::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();

         $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
         $this->cacheManagerMock = $this->getMockBuilder(Manager::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
         $this->eavAttribute = $this->getMockForAbstractClass(ProductAttributeInterface::class);
         $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();



        $this->objectManager = new ObjectManager($this);
        $this->dataProvider= $this->objectManager->getObject(
            DataProvider::class,
            [
                'serviceClient' => $this->serviceClientMock,
                'config' => $this->configMock,
                'storeManager' => $this->storeManagerMock,
                'productAttributeRepository' => $this->productAttributeRepositoryMock,
                'logger' => $this->loggerMock,
                'cache' => $this->cacheMock,
                'serializer' => $this->serializerMock,
                'cacheManager'=> $this->cacheManagerMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetFacets()
    {
       $headers = [
            'Magento-Website-Code' => 'base',
            'Magento-Store-Code' => 'default',
            'Magento-Store-View-Code' => 'default',
            'Magento-Is-Preview' => ''
        ];
        $path = 'search-admin/graphql';
        $payload = '{"operationName":"getFacets","variables":{},"query":"query getFacets {\n  facetsConfiguration {\n    facetsConfig {\n      title\n      attributeCode\n      facetType\n      dataType\n      maxValue\n      multiSelect\n      multiSelectOperator\n      numeric\n      sortType\n      aggregationType\n      aggregationRanges {\n        from\n        to\n      }\n      frontendInput\n    }\n  }\n}"}';
        $this->storeManagerMock->expects($this->any())->method('getWebsite')->willReturn($this->storeMock);
        $this->storeManagerMock->expects($this->any())->method('getGroup')->willReturn($this->storeMock);
        $this->storeManagerMock->expects($this->any())->method('getDefaultStoreView')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturnOnConsecutiveCalls('base','default','default');
        $this->configMock->expects($this->any())->method('getValue')->willReturn($path);
        $this->serviceClientMock->expects($this->any())->method('request')->with($headers,$path,$payload)->willReturn([]);
        $this->assertEquals([],$this->dataProvider->getFacets());
    }

//    /**
//     * @return void
//     */
//    public function testSaveFacetsDataInCache()
//    {
//        $cacheData = ['attribute_code'=>'categories','tooltip'=>''];
//        $this->cacheMock->expects($this->any())->method('load')->willReturn('');
//        $this->cacheMock->expects($this->any())->method('save')->willReturn('');
//        $this->assertEquals('', $this->dataProvider->saveFacetsDataInCache($cacheData));
//    }
//
//    /**
//     * @return void
//     */
//    public function testGetFacetsData()
//    {
//        $cacheValue = '{"attribute_code":"categories","tooltip":""}';
//        $cacheData = ['attribute_code'=>'categories','tooltip'=>''];
//        $this->cacheMock->expects($this->any())->method('load')->willReturn($cacheValue);
//        $this->serializerMock->expects($this->any())->method('unserialize')->willReturn($cacheData);
//        $this->assertEquals($cacheData, $this->dataProvider->getFacetsData());
//    }
//
//    /**
//     * @return void
//     */
//    public function testGetTooltipData(){
//         $cacheData = ['attribute_code'=>'categories','tooltip'=>''];
//         $this->testGetFacetsData();
//         $this->testSaveFacetsDataInCache();
//         $this->testGetFacets();
//         $this->productAttributeRepositoryMock->expects($this->any())->method('get')->willReturn($this->eavAttribute);
//         $this->assertEquals($cacheData, $this->dataProvider->getTooltipData());
//    }
}
