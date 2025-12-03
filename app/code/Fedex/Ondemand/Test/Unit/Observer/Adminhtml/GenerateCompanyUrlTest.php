<?php

namespace Fedex\Ondemand\Test\Unit\Observer\Adminhtml;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\Group;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\UrlRewrite\Model\UrlRewrite;
use Magento\UrlRewrite\Model\UrlRewrite\ResourceModel\UrlRewrite\Collection;
use Fedex\Ondemand\Observer\Adminhtml\GenerateCompanyUrl;
use Magento\Framework\Event\Observer;
use Magento\Company\Model\CompanyFactory;
use Magento\Framework\Phrase;
use Magento\Framework\App\Request\Http;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\Shipto\Model\ProductionLocation;
use Fedex\Shipto\Model\ResourceModel\ProductionLocation\Collection as ProductionLocationCollection;

class GenerateCompanyUrlTest extends TestCase
{
    protected $storeGroupFactoryMock;
    protected $storeGroupMock;
    protected $urlRewriteFactoryMock;
    protected $urlRewriteMock;
    protected $urlRewriteCollectionMock;
    protected $toggleConfigMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $observerMock;
    protected $companyMock;
    protected $productionLocationFactoryMock;
    protected $productionLocationMock;
    protected $productionLocationCollection;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    protected $generateCompanyUrlMock;
    private Http|MockObject $requestMock;

    public const PARAMS = [
        'general'             => ['company_id' => 1],
        'production_location' => ['production_location_option' => 'geographical']
    ];

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeGroupFactoryMock  = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->storeGroupMock  = $this->getMockBuilder(Group::class)
            ->setMethods(['load', 'getGroupId', 'getStoreIds'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->urlRewriteFactoryMock  = $this->getMockBuilder(UrlRewriteFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->urlRewriteMock  = $this->getMockBuilder(UrlRewrite::class)
            ->setMethods(['getCollection', 'setData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->urlRewriteCollectionMock  = $this->getMockBuilder(Collection::class)
            ->setMethods(['addFieldToFilter', 'getSize'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->observerMock  = $this->getMockBuilder(Observer::class)
            ->setMethods(['getRequest', 'getCompany'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->companyMock  = $this->getMockBuilder(Company::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->onlyMethods(['getParams'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productionLocationFactoryMock = $this->getMockBuilder(ProductionLocationFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        
        $this->productionLocationMock = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'load', 'save', 'delete', 'getCollection','addFieldToFilter'])
            ->getMock();

        $this->productionLocationCollection = $this->createMock(ProductionLocationCollection::class);

        $this->objectManagerHelper = new ObjectManager($this);
        
        $this->generateCompanyUrlMock = $this->objectManagerHelper->getObject(
            GenerateCompanyUrl::class,
				[
                    'groupFactory' => $this->storeGroupFactoryMock,
                    'urlRewriteFactory' => $this->urlRewriteFactoryMock,
                    'toggleConfig' => $this->toggleConfigMock,
                    'logger' => $this->loggerMock,
                    'productionLocationFactory' => $this->productionLocationFactoryMock
                ]
        );
    }
    
    public function testExecute()
    {
		$urlExt = 'me';
		$storeIds = [108];
        
        $this->observerMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->testHandleLocationRemoval();
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->observerMock->expects($this->any())->method('getCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getData')->willReturn($urlExt);
        
        // get ondemand store id
        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);
        
        // url rewrite
        $this->urlRewriteFactoryMock->expects($this->any())->method('create')->willReturn($this->urlRewriteMock);
        $this->urlRewriteMock->expects($this->any())->method('getCollection')
            ->willReturn($this->urlRewriteCollectionMock);
        $this->urlRewriteCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->urlRewriteCollectionMock->expects($this->any())->method('getSize')->willReturn(false);
        
        $this->urlRewriteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->urlRewriteMock->expects($this->any())->method('save')->willReturnSelf();
        
        $this->assertNull($this->generateCompanyUrlMock->execute($this->observerMock));
    }
    
    public function testExecuteWithExecption()
    {
        $this->observerMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->testHandleLocationRemoval();

		$exception = new \Exception();
		
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->observerMock->expects($this->any())->method('getCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getData')->willThrowException($exception);
        
        $this->assertNull($this->generateCompanyUrlMock->execute($this->observerMock));
    }

    /**
     * Test method for handleLocationRemoval
     */
    public function testHandleLocationRemoval()
    {
        $this->requestMock->expects($this->any())->method('getParams')->willReturn(static::PARAMS);

        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);
        
        $this->productionLocationMock->expects($this->any())->method('getCollection')
            ->will($this->returnValue($this->productionLocationCollection));
        
        $this->productionLocationCollection->expects($this->any())->method('addFieldToFilter')
            ->will($this->returnSelf());
        $this->productionLocationCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->productionLocationCollection->expects($this->any())->method('getData')->willReturn([['id' => 1]]);
        $this->productionLocationMock->expects($this->any())->method('load')->willReturnSelf();
        $this->productionLocationMock->expects($this->any())->method('delete')->willReturnSelf();

        $this->assertNull($this->generateCompanyUrlMock->handleLocationRemoval($this->requestMock));
    }

    /**
     * Test for handleLocationRemoval method with Exception.
     *
     * @return void
     */
    public function testHandleLocationRemovalWithException()
    {
        $this->requestMock->expects($this->any())->method('getParams')->willReturn(static::PARAMS);

        $phrase = new Phrase(__('Error occured while removing production locations for the company id.'));
        $exception = new \Exception($phrase);
        
        $this->productionLocationFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->productionLocationMock);
        
        $this->productionLocationMock->expects($this->once())->method('getCollection')
            ->willThrowException($exception);

        $this->assertNull($this->generateCompanyUrlMock->handleLocationRemoval($this->requestMock));
    }
}
