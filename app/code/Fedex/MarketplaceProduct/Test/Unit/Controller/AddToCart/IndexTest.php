<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Controller\AddToCart;

use Fedex\Cart\Model\Quote\Product\Add as QuoteProductAdd;
use Fedex\MarketplaceProduct\Model\AddToCartContext;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Setup\Exception;
use Magento\Framework\Controller\Result\Redirect;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceProduct\Controller\AddToCart\Index;
use Magento\Framework\App\ResponseInterface;
use Fedex\MarketplaceCheckout\Helper\Data as CheckoutHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class IndexTest extends TestCase
{
    /** @var ManagerInterface  */
    private ManagerInterface $messageManager;

    /** @var RequestInterface  */
    private RequestInterface $request;

    /** @var QuoteProductAdd  */
    private QuoteProductAdd $quoteProductAdd;

    /** @var Session  */
    private Session $session;

    /** @var RedirectInterface  */
    private RedirectInterface $redirectInterface;

    /** @var RedirectFactory  */
    private RedirectFactory $redirectFactory;

    /** @var Redirect  */
    private Redirect $redirect;

    /** @var AddToCartContext  */
    private AddToCartContext $context;

    /** @var ProductRepositoryInterface  */
    private ProductRepositoryInterface $productRepository;

    /** @var LoggerInterface  */
    private LoggerInterface $logger;

    /** @var ResponseInterface  */
    private ResponseInterface $response;

    /**
     * @var ProductInterface|MockObject
     */
    private ProductInterface|MockObject $productInterface;

    /** @var Index  */
    private Index $index;

    /** @var CheckoutHelper  */
    private CheckoutHelper $checkoutHelper;

    public function setUp(): void
    {
        $this->quoteProductAdd = $this->createMock(QuoteProductAdd::class);
        $this->checkoutHelper = $this->createMock(CheckoutHelper::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->redirect = $this->createMock(Redirect::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->redirectFactory = $this->createMock(RedirectFactory::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->redirectInterface = $this->createMock(RedirectInterface::class);
        $this->context = $this->getMockBuilder(AddToCartContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productInterface = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['getUrlModel', 'getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->method('getRequestInterface')
            ->willReturn($this->request);
        $this->context->method('getQuoteProductAdd')
            ->willReturn($this->quoteProductAdd);
        $this->context->method('getProductRepositoryInterface')
            ->willReturn($this->productRepository);
        $this->context->method('getRedirectFactory')
            ->willReturn($this->redirectFactory);
        $this->context->method('getRedirectInterface')
            ->willReturn($this->redirectInterface);
        $this->context->method('getSession')
            ->willReturn($this->session);

        $this->redirectInterface->method('getRefererUrl')
            ->willReturn($this->response);

        $this->redirectFactory->method('create')
            ->willReturn($this->redirect);

        $this->index = new Index(
            $this->context,
            $this->logger,
            $this->messageManager,
            $this->response,
            $this->productRepository,
            $this->checkoutHelper
        );
    }

    /**
     * @throws NotFoundException
     */
    public function testExecute()
    {
        $this->request->method('getParams')
            ->willReturn(
                [
                    'sku' => 'test123',
                    'action' => 'notCancel'
                ]
            );
        $productMock = $this->createMock(Product::class);
        $productMock->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $productMock->method('getUrlModel')->willReturn($this->createMock(UrlInterface::class));
        $this->productRepository->method('get')->willReturn($productMock);
        $this->context->expects($this->once())
            ->method('getRequestInterface');
        $this->request->expects($this->once())
            ->method('getParams');
        $this->context->expects($this->exactly(2))
            ->method('getQuoteProductAdd');
        $this->context->expects($this->once())
            ->method('getSession');
        $this->session->expects($this->once())
            ->method('getQuote');
        $this->context->expects($this->any())
            ->method('getRedirectFactory');
        $this->redirectFactory->expects($this->any())
            ->method('create');

        $this->redirect->expects($this->once())
            ->method('setPath');

        $this->index->execute();
    }
    /**
     * @throws NotFoundException
     */
    public function testExecuteWithoutSku()
    {
        $this->request->method('getParams')
            ->willReturn(
                [
                    'action' => 'notCancel',
                    'sku' => 'product-sku'
                ]
            );
        $productMock = $this->createMock(Product::class);
        $productMock->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $productMock->method('getUrlModel')->willReturn($this->createMock(UrlInterface::class));
        $this->productRepository->method('get')->willReturn($productMock);
        $this->context->expects($this->once())
            ->method('getRequestInterface');
        $this->request->expects($this->once())
            ->method('getParams');
        $this->context->expects($this->any())
            ->method('getQuoteProductAdd');
        $this->context->expects($this->once())
            ->method('getSession');
        $this->session->expects($this->once())
            ->method('getQuote');
        $this->context->expects($this->any())
            ->method('getRedirectFactory');
        $this->redirectFactory->expects($this->any())
            ->method('create');

        $this->index->execute();
    }
    /**
     * @throws NotFoundException
     */
    public function testExecuteThrowsException()
    {
        $this->request->method('getParams')
            ->willReturn(
                [
                    'sku' => 'test123',
                    'action' => 'notCancel'
                ]
            );
        $this->quoteProductAdd->method('addItemToCart')
            ->willThrowException(new Exception('test exception'));

        $this->context->expects($this->once())
            ->method('getRequestInterface');
        $this->request->expects($this->once())
            ->method('getParams');
        $this->context->expects($this->exactly(2))
            ->method('getQuoteProductAdd');
        $this->context->expects($this->once())
            ->method('getSession');
        $this->session->expects($this->once())
            ->method('getQuote');
        $this->context->expects($this->any())
            ->method('getRedirectFactory');
        $this->redirectFactory->expects($this->any())
            ->method('create');

        $this->redirect->expects($this->once())
            ->method('setPath');
        $this->productInterface->expects($this->once())->method('getUrlModel')->willReturnSelf();
        $this->productInterface->expects($this->once())->method('getUrl')->willReturnSelf();
        $this->productRepository->expects($this->any())->method('get')->willReturn($this->productInterface);

        $this->index->execute();
    }

    public function testExecuteWithCancelAction()
    {
        $this->request->method('getParams')
            ->willReturn(
                [
                    'sku' => 'test123',
                    'action' => 'cancel'
                ]
            );

        $this->context->expects($this->once())
            ->method('getRequestInterface');
        $this->request->expects($this->once())
            ->method('getParams');
        $this->context->expects($this->once())
            ->method('getQuoteProductAdd');
        $this->context->expects($this->once())
            ->method('getSession');
        $this->session->expects($this->once())
            ->method('getQuote');
        $this->context->expects($this->any())
            ->method('getRedirectFactory');
        $this->redirectFactory->expects($this->any())
            ->method('create');

        $this->redirect->expects($this->once())
            ->method('setPath');

        $this->productInterface->expects($this->once())->method('getUrlModel')->willReturnSelf();
        $this->productInterface->expects($this->once())->method('getUrl')->willReturnSelf();
        $this->productRepository->expects($this->once())->method('get')->willReturn($this->productInterface);


        $this->index->execute();
    }
}
