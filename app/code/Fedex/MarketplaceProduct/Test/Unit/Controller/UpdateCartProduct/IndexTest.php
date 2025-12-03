<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Controller\UpdateCartProduct;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\Update;
use Magento\Quote\Model\ResourceModel\QuoteItemRetriever;
use Fedex\MarketplaceProduct\Model\Context;
use Fedex\MarketplaceProduct\Controller\UpdateCartProduct\Index;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Quote\Model\Quote\Item;
use Exception;

class IndexTest extends TestCase
{
    /** @var Context  */
    private Context $context;

    /** @var QuoteItemRetriever  */
    private QuoteItemRetriever $quoteItemRetriever;

    /** @var Update  */
    private Update $update;

    /** @var LoggerInterface  */
    private LoggerInterface $logger;

    /** @var ManagerInterface  */
    private ManagerInterface $messageManager;

    /** @var RequestInterface  */
    private RequestInterface $request;

    /** @var RedirectFactory  */
    private RedirectFactory $resultRedirectFactory;

    /** @var RedirectInterface  */
    private RedirectInterface $redirectInterface;

    /** @var Session  */
    private Session $session;

    /** @var Redirect  */
    private Redirect $redirect;

    /** @var Item  */
    private Item $item;

    /**
     * @var ProductInterface|MockObject
     */
    private ProductInterface|MockObject $productInterface;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private ProductRepositoryInterface|MockObject $productRepository;

    /** @var Index  */
    private Index $index;

    /**
     * @var \Fedex\EnvironmentManager\ViewModel\ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleConfig;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->quoteItemRetriever = $this->createMock(QuoteItemRetriever::class);
        $this->update = $this->createMock(Update::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $this->redirectInterface = $this->createMock(RedirectInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->redirect = $this->createMock(Redirect::class);
        $this->item = $this->createMock(Item::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->productInterface = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['getUrlModel', 'getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->method('getLogger')
            ->willReturn($this->logger);
        $this->context->method('getManagerInterface')
            ->willReturn($this->messageManager);
        $this->context->method('getRequestInterface')
            ->willReturn($this->request);
        $this->context->method('getRedirectFactory')
            ->willReturn($this->resultRedirectFactory);
        $this->context->method('getRedirectInterface')
            ->willReturn($this->redirectInterface);
        $this->context->method('getSession')
            ->willReturn($this->session);
        $this->resultRedirectFactory->method('create')
            ->willReturn($this->redirect);
        $this->request->method('getParam')
            ->willReturn(1234);
        $this->redirect->method('setPath')
            ->willReturn($this->redirect);
        $this->redirectInterface->method('getRefererUrl')
            ->willReturn('/');

        $this->index = new Index(
            $this->context,
            $this->quoteItemRetriever,
            $this->update,
            $this->productRepository,
            $this->toggleConfig
        );
    }

    public function testExecute()
    {
        $params = [
            'supplierPartAuxiliaryID' => 1234,
            'action' => 'notCancel'
        ];
        $this->toggleConfig->method('getToggleConfigValue')
            ->with('hawks_D224800_reorder_toggle')
            ->willReturn(true);
        $this->request->method('getParams')
            ->willReturn($params);
        $this->quoteItemRetriever->method('getById')
            ->willReturn($this->item);
        $this->request->expects($this->once())
            ->method('getParams');
        $this->resultRedirectFactory->expects($this->once())
            ->method('create');
        $this->quoteItemRetriever->expects($this->once())
            ->method('getById');
        $this->request->expects($this->once())
            ->method('getParam');
        $this->update->expects($this->once())
            ->method('updateThirdPartyItemSellerPunchout');
        $this->redirect->expects($this->once())
            ->method('setPath')
            ->with('checkout/cart')
            ->willReturnSelf();

        $result = $this->index->execute();

        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
        $this->assertEquals($this->redirect, $result);
        $this->assertArrayHasKey('action', $params);
        $this->assertEquals(1234, $params['supplierPartAuxiliaryID']);
    }

    public function testExecuteWithoutSupplierID()
    {
        $params = ['action' => 'update', 'sku' => 'some_sku'];

        $this->request->method('getParams')
        ->willReturn($params);

        $this->request->expects($this->once())
            ->method('getParams');
        $this->resultRedirectFactory->expects($this->once())
            ->method('create');

        $this->request->expects($this->never())
            ->method('getParam');
        $this->update->expects($this->never())
            ->method('updateThirdPartyItemSellerPunchout');
        $this->messageManager->expects($this->never())
            ->method('addSuccessMessage');

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage');
        $this->redirect->expects($this->once())
            ->method('setPath')
            ->with('checkout/cart')
            ->willReturnSelf();

        $result = $this->index->execute();

        $this->assertArrayHasKey('sku', $params);
        $this->assertEquals('some_sku', $params['sku']);
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    public function testExecuteActionCancel()
    {
        $params = [
            'supplierPartAuxiliaryID' => 1234,
            'action' => 'cancel'
        ];

        $this->request->method('getParams')
            ->willReturn($params);

        $this->request->expects($this->once())
            ->method('getParams');
        $this->resultRedirectFactory->expects($this->once())
            ->method('create');

        $this->quoteItemRetriever->expects($this->never())
            ->method('getById');
        $this->request->expects($this->never())
            ->method('getParam');
        $this->update->expects($this->never())
            ->method('updateThirdPartyItemSellerPunchout');
        $this->messageManager->expects($this->never())
            ->method('addSuccessMessage');

        $this->messageManager->expects($this->never())
            ->method('addErrorMessage');
        $this->redirect->expects($this->once())
            ->method('setPath')
            ->with('checkout/cart')
            ->willReturnSelf();

        $this->productInterface->expects($this->never())
            ->method('getUrlModel')->willReturnSelf();
        $this->productInterface->expects($this->never())
            ->method('getUrl')->willReturnSelf();
        $this->productRepository->expects($this->never())
            ->method('get')->willReturn($this->productInterface);

        $result = $this->index->execute();

        $this->assertArrayHasKey('action', $params);
        $this->assertEquals('cancel', $params['action']);
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    public function testExecuteWithException()
    {
        $params = [
            'sku' => 'sku3214',
            'supplierPartAuxiliaryID' => 1234,
            'action' => 'notCancel'
        ];

        $this->request->method('getParams')
            ->willReturn($params);
        $this->quoteItemRetriever->method('getById')
            ->willThrowException(new Exception('something went wrong?'));

        $this->request->expects($this->once())
            ->method('getParams');
        $this->resultRedirectFactory->expects($this->once())
            ->method('create');
        $this->quoteItemRetriever->expects($this->once())
            ->method('getById');
        $this->request->expects($this->once())
            ->method('getParam');

        $this->update->expects($this->never())
            ->method('updateThirdPartyItemSellerPunchout');
        $this->messageManager->expects($this->never())
            ->method('addSuccessMessage');

        $this->logger->expects($this->exactly(2))
            ->method('error');

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage');
        $this->redirect->expects($this->once())
            ->method('setPath');

        $this->productInterface->expects($this->once())->method('getUrlModel')->willReturnSelf();
        $this->productInterface->expects($this->once())->method('getUrl')->willReturnSelf();
        $this->productRepository->expects($this->once())->method('get')->willReturn($this->productInterface);

        $result = $this->index->execute();

        $this->assertArrayHasKey('sku', $params);
        $this->assertEquals('sku3214', $params['sku']);
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }
}
