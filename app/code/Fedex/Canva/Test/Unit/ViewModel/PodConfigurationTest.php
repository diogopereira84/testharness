<?php

declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\ViewModel;

use Fedex\Canva\Model\Service\CurrentProductService;
use Fedex\Canva\ViewModel\BlockProvider;
use Fedex\Canva\ViewModel\PodConfiguration;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Punchout\Helper\Data as PunchOutHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Magento\Cms\Block\Block;
use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PodConfigurationTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    public const CMS_BLOCK_ID_CANVA_PDP = 'canva-page-header';
    public const CMS_BLOCK_ID_CANVA_HOME = 'header_promo_block';

    private PodConfiguration $podConfigurationMock;
    private LoggerInterface|MockObject $loggerMock;
    private CurrentProductService|MockObject $currentProductServiceMock;
    private DeliveryHelper|MockObject $deliveryHelperMock;
    private PunchOutHelper|MockObject $punchOutHelperMock;
    private Http|MockObject $requestMock;
    private ProductInterface|MockObject $productInterfaceMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->currentProductServiceMock = $this->getMockBuilder(CurrentProductService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->onlyMethods(['getCompanySite'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->punchOutHelperMock = $this->getMockBuilder(PunchOutHelper::class)
            ->onlyMethods(['getTazToken'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->onlyMethods(['getParams'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->onlyMethods(['getSku'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->podConfigurationMock = $this->objectManager->getObject(
            PodConfiguration::class,
            [
                'logger' => $this->loggerMock,
                'currentProductService' => $this->currentProductServiceMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'punchOutHelper' => $this->punchOutHelperMock,
                'request' => $this->requestMock
            ]
        );
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetSku(): void
    {
        $test = 'sku-test';
        $this->productInterfaceMock->expects($this->once())->method('getSku')->willReturn('sku-test');
        $this->currentProductServiceMock->expects($this->once())->method('getProduct')->willReturn($this->productInterfaceMock);

        $this->assertEquals($test, $this->podConfigurationMock->getSku());
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetSkuException(): void
    {
        $this->currentProductServiceMock->expects($this->once())
            ->method('getProduct')->willThrowException(new NoSuchEntityException(
                __("The product that was requested doesn't exist. Verify the product and try again.")
            ));

        $errorLogMsg = 'Fedex\Canva\ViewModel\PodConfiguration::getSku:50 The product that was requested doesn\'t exist. Verify the product and try again.';
        $this->loggerMock->expects($this->once())->method('error')->with($errorLogMsg);

        $this->assertEquals('', $this->podConfigurationMock->getSku());
    }

    /**
     * @return void
     */
    public function testGetSiteName(): void
    {
        $siteName = 'fedex';
        $this->deliveryHelperMock->expects($this->once())->method('getCompanySite')->willReturn($siteName);

        $this->assertEquals($siteName, $this->podConfigurationMock->getSiteName());
    }

    /**
     * @return void
     */
    public function testGetSiteNameNull(): void
    {
        $this->deliveryHelperMock->expects($this->once())->method('getCompanySite')->willReturn(null);

        $this->assertEquals('', $this->podConfigurationMock->getSiteName());
    }

    /**
     * @return void
     */
    public function testGetTazToken(): void
    {
        $token = 'asdasjhdjash123';
        $this->punchOutHelperMock->expects($this->once())->method('getTazToken')->willReturn('asdasjhdjash123');

        $this->assertEquals($token, $this->podConfigurationMock->getTazToken());
    }

    public function testGetDesignId(): void
    {
        $params = ["edit" => 'design-id'];
        $this->requestMock->expects($this->once())->method('getParams')->willReturn($params);

        $this->assertEquals('design-id', $this->podConfigurationMock->getDesignId());
    }

    public function testGetDesignIdEmpty(): void
    {
        $params = ["no-edit" => 'no-design-id'];
        $this->requestMock->expects($this->once())->method('getParams')->willReturn($params);

        $this->assertEquals('', $this->podConfigurationMock->getDesignId());
    }
}
