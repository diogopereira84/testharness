<?php
namespace Fedex\InBranch\Test\Unit\Observer;

use Fedex\InBranch\Observer\RestrictAddToCart;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Message\ManagerInterface;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Fedex\FedexCsp\Stdlib\Cookie\PhpCookieManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RestrictAddToCartTest extends TestCase
{
        protected $productRepositoryMock;
    /**
     * @var (\Magento\Framework\Message\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageManagerMock;
    protected $inBranchValidationMock;
    /**
     * @var (\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cookieMetadataFactoryMock;
    /**
     * @var (\Fedex\FedexCsp\Stdlib\Cookie\PhpCookieManager & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cookieManagerMock;
    /**
     * @var (\Magento\Framework\App\Response\RedirectInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $redirectMock;
    protected $observer;
    protected $productMock;
    protected $restrictAddToCart;
    /**
         * Setup mock objects
         */
    protected function setUp(): void
    {
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
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieManagerMock = $this
            ->getMockBuilder(PhpCookieManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectMock = $this
            ->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->observer = $this->getMockBuilder(Observer::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->restrictAddToCart = (new ObjectManager($this))->getObject(
            RestrictAddToCart::class,
            [
                'productRepository' => $this->productRepositoryMock,
                'messageManager' => $this->messageManagerMock,
                'inBranchValidation' => $this->inBranchValidationMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'cookieManager' => $this->cookieManagerMock,
                'redirect' => $this->redirectMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam','setParam','isAjax'])
            ->getMockForAbstractClass();
         $this->observer->expects($this->any())->method('getRequest')->willReturn($request);
         $request->expects($this->any())->method('getParam')->with('product')->willReturn('235');
         $this->productRepositoryMock->expects($this->once())->method('getById')->willReturn($this->productMock);
         $this->inBranchValidationMock->expects($this->once())->method('isInBranchValid')->willReturn(true);
          $request->expects($this->any())->method('isAjax')->willReturn(true);
          $request->expects($this->any())->method('setParam')->with('product', false)
              ->willReturnSelf();
          $request->expects($this->any())->method('setParam')->with('inBranchProductExist', true)
              ->willReturnSelf();
         $this->assertEquals(null, $this->restrictAddToCart->execute($this->observer));
    }

    /**
     * @return void
     */
    public function testExecuteIfNotAjax()
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam','setParam','isAjax'])
            ->getMockForAbstractClass();
         $this->observer->expects($this->any())->method('getRequest')->willReturn($request);
         $request->expects($this->any())->method('getParam')->with('product')->willReturn('235');
         $this->productRepositoryMock->expects($this->once())->method('getById')->willReturn($this->productMock);
          $this->inBranchValidationMock->expects($this->once())->method('isInBranchValid')->willReturn(true);
          $request->expects($this->any())->method('isAjax')->willReturn(false);
          $request->expects($this->any())->method('setParam')->with('product', false)
              ->willReturnSelf();
          $request->expects($this->any())->method('setParam')->with('inBranchProductExist', true)
              ->willReturnSelf();
          $request->expects($this->any())->method('setParam')->with('return_url', 'https://fedex.com/checkout/cart')
              ->willReturnSelf();
         $this->assertEquals(null, $this->restrictAddToCart->execute($this->observer));
    }
}
