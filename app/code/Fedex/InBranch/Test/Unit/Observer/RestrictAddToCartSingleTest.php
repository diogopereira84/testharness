<?php

namespace Fedex\InBranch\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Fedex\InBranch\Observer\RestrictAddToCartSingle;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RestrictAddToCartSingleTest extends TestCase
{
    protected $productMock;
    private RestrictAddToCartSingle $testObject;
    private ObjectManager|ObjectManagerInterface $objectManager;

    /** @var ProductRepository|MockObject */
    private ProductRepository|MockObject $productRepositoryMock;

    /** @var ManagerInterface|MockObject */
    private ManagerInterface|MockObject $messageManagerMock;

    /** @var InBranchValidation|MockObject */
    private InBranchValidation|MockObject $inBranchValidationMock;

    /** @var RedirectInterface|MockObject */
    private RedirectInterface|MockObject $redirectMock;

    /** @var Observer|MockObject */
    private Observer|MockObject $observerMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inBranchValidationMock = $this
            ->getMockBuilder(InBranchValidation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectMock = $this
            ->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testObject = $this->objectManager->getObject(
            RestrictAddToCartSingle::class,
            [
                'productRepository' => $this->productRepositoryMock,
                'messageManager' => $this->messageManagerMock,
                'inBranchValidation' => $this->inBranchValidationMock,
                'redirect' => $this->redirectMock
            ]
        );

        parent::setUp();
    }

    public function testExecute(): void
    {
        $productId = 452;

        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requestMock->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn($productId);

        $requestMock->expects($this->any())
            ->method('getParam')
            ->with('isAjax')
            ->willReturn(false);

        $this->observerMock->expects($this->any())
            ->method('getData')
            ->with('request')
            ->willReturn($requestMock);

        $this->productRepositoryMock
            ->expects($this->any())
            ->method('getById')
            ->with($productId)
            ->willReturn($this->productMock);

        $this->inBranchValidationMock
            ->expects($this->any())
            ->method('isInBranchValid')
            ->with($this->productMock)
            ->willReturn(true);

        $this->redirectMock
            ->expects($this->any())
            ->method('getRefererUrl');

        $this->assertInstanceOf(RestrictAddToCartSingle::class, $this->testObject->execute($this->observerMock));
    }


}
