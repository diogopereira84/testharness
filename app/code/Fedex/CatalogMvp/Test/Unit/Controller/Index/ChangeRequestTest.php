<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Controller\Index;

use Exception;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Controller\Index\ChangeRequest;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Helper\EmailHelper;
use Magento\Customer\Model\SessionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Catalog\Model\Category;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\CategoryRepository;

/**
 * Class ChangeRequestTest
 *
 */
class ChangeRequestTest extends TestCase
{
    protected $productRepositoryMock;
    protected $categoryModelMock;
    protected $productInterfaceMock;
    protected $requestMock;
    protected $messageManagerInterfaceMock;
    protected $toggleConfigMock;
    /**
     * @var (\Fedex\CatalogMvp\Helper\EmailHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $emailHelperMock;
    protected $catalogMvpMock;
    protected $sessionFactoryMock;
    protected $resultJsonMock;
    protected $changeRequest;
    private const EXTERNAL_PROD = '{
        "priceable": "true",
        "properties": {
          "1": {
            "name": "USER_SPECIAL_INSTRUCTIONS",
            "value": ""
          }
        }
      }';

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactoryMock;
    protected $cateogyRepositoryMock;
    protected function setUp(): void
    {
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById', 'save', 'get'])
            ->getMockForAbstractClass();

        $this->categoryModelMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getUrl'])
            ->getMock();

        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExternalProd','setStatus', 'getFolderPath', 'getName',
                'setPublished', 'setData', 'setVisibility','setExternalProd', 'getId', 'getCategoryIds', 'setSentToCustomer'])
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->requestMock);

        $this->messageManagerInterfaceMock = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addSuccess', 'addError'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManagerInterfaceMock->expects($this->any())->method('addSuccess')->willReturnSelf();

        $this->messageManagerInterfaceMock->expects($this->any())->method('addError')->willReturnSelf();

        $this->contextMock->expects($this->any())->method('getMessageManager')
            ->willReturn($this->messageManagerInterfaceMock);

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->emailHelperMock = $this->getMockBuilder(EmailHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendReadyForReviewEmail','getSpecialInstruction'])
            ->getMock();

        $this->catalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertProductActivity'])
            ->getMock();

        $this->sessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getCustomer', 'getName', 'getCustomerCompany', 'getSecondaryEmail', 'getEmail'])
            ->getMock();

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->resultJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
        $this->cateogyRepositoryMock = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->changeRequest = $objectManagerHelper->getObject(
            ChangeRequest::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'productRepository' => $this->productRepositoryMock,
                'emailHelper' => $this->emailHelperMock,
                'sessionFactory' => $this->sessionFactoryMock,
                'categoryModel' => $this->categoryModelMock,
                'catalogMvp' => $this->catalogMvpMock,
                'context' => $this->contextMock,
                'request' => $this->requestMock,
                'categoryRepository'=>$this->cateogyRepositoryMock
            ]
        );
    }

    /**
     * @test Execute if case
     */
    public function testExecuteTryCase()
    {
        $this->productRepositoryMock->expects($this->any())->method('get')->willReturn($this->productInterfaceMock);
        $this->productInterfaceMock->expects($this->any())->method('getExternalProd')->willReturn(self::EXTERNAL_PROD);
        $this->productInterfaceMock->expects($this->any())->method('getCategoryIds')->willReturn([1,2,3]);
        $this->categoryModelMock->expects($this->any())->method('load')->willReturnSelf();
        $this->categoryModelMock->expects($this->any())->method('getUrl')->willReturn('test/test');
        $this->requestMock->method('getParam')->willReturnMap([
            ['id', null, 395],
            ['specialInstruction', null, 'test'],
            ['userWorkSpace', null, '{"userWorkspace":{"files":[],"projects":[]}}']
        ]);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->resultJsonMock);
        $this->productRepositoryMock->expects($this->any())->method('save')->willReturnSelf();
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->sessionFactoryMock->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->catalogMvpMock->expects($this->any())->method('insertProductActivity')->willReturnSelf();
        $this->resultJsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->cateogyRepositoryMock->expects($this->any())->method('get')->willReturn($this->categoryModelMock);
        $this->assertNotNull($this->changeRequest->execute());
    }

    /**
     * @test Execute if case
     */
    public function testExecuteTryCaseWithException()
    {
        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['id', null, 395],
                ['specialInstruction', null, 'test'],
                ['userWorkSpace', null, '{"userWorkspace":{"files":[],"projects":[]}}']
            ]);
        $exception = new \Exception();
        $this->productRepositoryMock->expects($this->any())->method('get')->willThrowException($exception);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->resultJsonMock);
        $this->resultJsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->changeRequest->execute());
    }
}