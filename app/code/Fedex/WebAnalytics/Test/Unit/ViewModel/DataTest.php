<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\ViewModel;

use Fedex\WebAnalytics\ViewModel\Data;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * DataTest for unit test case
 */
class DataTest extends TestCase
{
    const FULL_ACTION_NAME = "cms_index_index";
    const ENABLE = 'fedex/confirmit/enabled';
    const MIDFLOW_SCRIPT = 'fedex/confirmit/midflow_script';

    protected $company;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var Http|MockObject
     */
    protected $httpMock;

    /**
     * @var HttpRequest|MockObject
     */
    protected $requestHttpMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    /**
     * @var \Fedex\Company\Helper\Data|MockObject
     */
    protected $companyHelper;

    protected SecureHtmlRenderer|MockObject $secureHtmlRendererMock;

    protected function setUp(): void
    {
        $this->httpMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFullActionName'])
            ->getMock();

        $this->requestHttpMock = $this->createMock(HttpRequest::class);

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId', 'getRootCategoryId'])
            ->getMock();

        $this->companyHelper = $this->getMockBuilder(\Fedex\Company\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerCompany'])
            ->getMock();

        $this->company = $this->getMockBuilder(CompanyInterface::class)->setMethods(['getForsta'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->secureHtmlRendererMock = $this->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->dataHelper = $this->objectManager->getObject(
            Data::class,
            [
                'request' => $this->requestHttpMock,
                'scopeConfig' => $this->scopeConfigMock,
                'storeManager' => $this->storeManagerMock,
                'companyHelper' => $this->companyHelper,
                'secureHtmlRenderer' => $this->secureHtmlRendererMock,
            ]
        );
    }

    /**
     * @test Get Forsta configuration
     * @return void
     */
    public function testGetForstaConfig()
    {
        $storeId = 1;
        $isEnabled = 1;

        $this->scopeConfigMock->expects($this->any())->method('getValue')
            ->with(self::ENABLE)->willReturn($isEnabled);
        $this->assertEquals($isEnabled, $this->dataHelper->getForstaConfig(self::ENABLE, $storeId));
    }

    /**
     * @test Get current store id
     * @return void
     */
    public function testGetCurrentStoreId()
    {
        $storeId = 1;
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->assertEquals($storeId, $this->dataHelper->getCurrentStoreId());
    }

    /**
     * @test check if Forsta configuration enable or disable
     * @return void
     */
    public function testIsEnabledForsta()
    {
        $storeId = 1;

        $this->storeMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->once())->method('getValue')->with(self::ENABLE, 'store', $storeId)->willReturn(true);
        $this->assertTrue($this->dataHelper->isEnabledForsta());
    }

    /**
     * @test check if Forsta configuration enable or disable
     * @return void
     */
    public function testIsEnabledForstaAdminDisabledCompanyEnabled()
    {
        $storeId = 1;

        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::ENABLE, 'store', $storeId)->willReturn(false);
        $this->company->expects($this->any())->method('getForsta')->willReturn(true);
        $this->companyHelper->expects($this->once())->method('getCustomerCompany')->willReturn($this->company);
        $this->assertTrue($this->dataHelper->isEnabledForsta());
    }

    /**
     * @test check if Forsta configuration enable or disable
     * @return void
     */
    public function testIsEnabledForstaAdminDisabledCompanyDisabled()
    {
        $storeId = 1;
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::ENABLE, 'store', $storeId)->willReturn(false);
        $this->company->expects($this->any())->method('getForsta')->willReturn(false);
        $this->companyHelper->expects($this->once())->method('getCustomerCompany')->willReturn($this->company);
        $this->assertFalse($this->dataHelper->isEnabledForsta());
    }

    /**
     * @test Get confirm IT Midflow Script
     * @return void
     */
    public function testGetMidflowScript()
    {
        $storeId = 1;
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::MIDFLOW_SCRIPT, 'store', $storeId)->willReturn('<script></script>');
        $this->secureHtmlRendererMock->method('renderTag')
            ->willReturnCallback(
                function (string $tag, array $attributes, string $content): string {
                    $attributes = new DataObject($attributes);

                    return "<$tag {$attributes->serialize()}>$content</$tag>";
                }
            );
        $this->assertEquals('<script type="text/javascript" async="1"> </script>', $this->dataHelper->getMidflowScript());
    }

    /**
     * @test Get confirm IT Midflow Script
     * @return void
     */
    public function testGetMidflowScriptToggleOff()
    {
        $storeId = 1;
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::MIDFLOW_SCRIPT, 'store', $storeId)->willReturn('');
        $this->assertFalse($this->dataHelper->getMidflowScript());
    }

    /**
     * @test getFullActionName
     * return void
     */
    public function testGetFullActionName()
    {
        $this->requestHttpMock->expects($this->once())->method('getFullActionName')
        ->willReturn(self::FULL_ACTION_NAME);
        $this->assertEquals(self::FULL_ACTION_NAME, $this->dataHelper->getFullActionName());
    }
}
