<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Fedex\MarketplacePunchout\Model\Redirect;

class RedirectTest extends TestCase
{
    /**
     * @var RedirectFactory
     */
    private RedirectFactory $resultRedirectFactory;

    /**
     * @var ResultRedirect
     */
    private ResultRedirect $resultRedirect;

    /**
     * @var Redirect
     */
    private Redirect $redirect;

    /**
     * @var MockObject|RedirectInterface
     */
    private MockObject|RedirectInterface $responseRedirect;

    /**
     * @var MockObject|CustomerSession
     */
    private MockObject|CustomerSession $customerSession;

    /** @var UrlInterface  */
    private UrlInterface $urlBuilder;

    public function setUp(): void
    {
        $this->resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $this->resultRedirect = $this->createMock(ResultRedirect::class);
        $this->responseRedirect = $this->createMock(RedirectInterface::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['setMarketplaceError'])
            ->getMock();
        $this->resultRedirectFactory->method('create')
            ->willReturn($this->resultRedirect);

        $this->redirect = new Redirect(
            $this->resultRedirectFactory,
            $this->responseRedirect,
            $this->customerSession,
            $this->urlBuilder
        );
    }

    public function testRedirectToMarketplace()
    {
        $this->resultRedirectFactory->expects($this->once())
            ->method('create');
        $this->resultRedirect->expects($this->once())
            ->method('setUrl');
        $this->redirect->redirect(true, 'http://www.testnavitor.url');
    }

    public function testRedirectError()
    {
        $this->resultRedirectFactory->expects($this->once())
            ->method('create');
        $this->urlBuilder->expects($this->once())
            ->method('getUrl');
        $this->responseRedirect->expects($this->once())
            ->method('getRefererUrl');
        $this->resultRedirect->expects($this->once())
            ->method('setUrl');
        $this->redirect->redirect(false, '', true);
    }

    public function testRedirect()
    {
        $this->resultRedirectFactory->expects($this->once())
            ->method('create');
        $this->resultRedirect->expects($this->once())
            ->method('setUrl');
        $this->redirect->redirect();
    }
}
