<?php

declare (strict_types = 1);

namespace Fedex\CatalogMvp\Test\Unit\Controller\Index;

use Fedex\CatalogMvp\Controller\Index\UpdateCatalogExpiryDate;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Stdlib\DateTime;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class UpdateCatalogExpiryDateTest extends TestCase
{
    protected $requestMock;
    protected $jsonFactoryMock;
    protected $productRepositoryMock;
    protected $productMock;
    /**
     * @var (\Magento\Framework\Stdlib\DateTime & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dateTime;
    protected $helperMock;
    protected $updateCatalogExpiryDate;
    protected $context;
    protected $jsonFactory;
    protected $productRepository;
    protected $toggleConfigMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
             
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create','setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->setMethods(['get', 'getCategoryIds','getSku', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData','setProductUpdatedDate','save'])
            ->getMock();
        
        $this->dateTime = $this->createMock(DateTime::class);

        $this->helperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertProductActivity'])
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->updateCatalogExpiryDate = $objectManager->getObject(
            UpdateCatalogExpiryDate::class,
            [
                'context' => $this->context,
                'jsonFactory' => $this->jsonFactoryMock,
                'productRepository' => $this->productRepositoryMock,
                'helper' => $this->helperMock,
                'toggleConfig'=>$this->toggleConfigMock
            ]
        );

    }

    /**
     * Test Case for execute function
     */
    public function testExecute(): void
    {
        $productId = 8449;
        $todayDate = new \DateTime();
        $toDate = $todayDate->format('Y-m-d H:i:s');
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->productRepositoryMock->expects($this->once())
        ->method('getById')
        ->with($productId)
        ->willReturn($this->productMock);
        $this->requestMock->expects($this->any())->method('getParam')
        ->withConsecutive(['id'])
        ->willReturnOnConsecutiveCalls(8449);

        $this->productMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->productMock->expects($this->any())
            ->method('setProductUpdatedDate')
            ->with($toDate);

        $this->productMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->helperMock->expects($this->any())->method('insertProductActivity')->willReturn('RENEW');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals(null,$this->updateCatalogExpiryDate->execute());
    }

    public function testExecuteException(): void
    {
        $exception = new \Exception();
        $productId = 8449;
        $todayDate = new \DateTime();
        $toDate = $todayDate->format('Y-m-d H:i:s');
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->productRepositoryMock->expects($this->once())
        ->method('getById')
        ->with($productId)
        ->willThrowException($exception);
        $this->requestMock->expects($this->any())->method('getParam')
        ->withConsecutive(['id'])
        ->willReturnOnConsecutiveCalls(8449);

        $this->productMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->productMock->expects($this->any())
            ->method('setProductUpdatedDate')
            ->with($toDate);

        $this->productMock->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->helperMock->expects($this->any())->method('insertProductActivity')->willReturn('RENEW');

        $this->assertEquals(null,$this->updateCatalogExpiryDate->execute());
    }
}
