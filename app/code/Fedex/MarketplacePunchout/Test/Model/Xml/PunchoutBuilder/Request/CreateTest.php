<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model\Xml\PunchoutBuilder\Request;

use Fedex\Base\Helper\Auth;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplacePunchout\Api\CustomerPunchoutUniqueIdRepositoryInterface;
use Fedex\MarketplacePunchout\Model\Config;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request\Create;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request\XmlContext;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\Simplexml\ElementFactory;
use Magento\Framework\Stdlib\Cookie\CookieReaderInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{

    protected $customerPunchoutUniqueIdRepositoryMock;
    /**
     * @var (\Fedex\MarketplaceProduct\Model\NonCustomizableProduct & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $nonCustomizableProductModel;
    /** @var MockObject|SessionManagerInterface */
    private SessionManagerInterface|MockObject $session;

    /** @var CookieReaderInterface|MockObject */
    private CookieReaderInterface|MockObject $cookieReader;

    /** @var MockObject|RequestInterface */
    private RequestInterface|MockObject $request;

    /** @var CustomerSession|MockObject */
    private CustomerSession|MockObject $customerSession;

    /** @var Customer|MockObject */
    private Customer|MockObject $customer;

    /** @var ProductRepositoryInterface|MockObject */
    private ProductRepositoryInterface|MockObject $productRepository;

    /** @var Create */
    private Create $create;
    protected Auth|MockObject $baseAuthMock;

    private ToggleConfig $toggleConfig;

    private Config $config;

    public function setUp(): void
    {
        $this->session = $this->createStub(SessionManagerInterface::class);
        $this->request = $this->createStub(RequestInterface::class);
        $this->customerSession = $this->createStub(CustomerSession::class);
        $this->toggleConfig = $this->createStub(ToggleConfig::class);
        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->customerPunchoutUniqueIdRepositoryMock = $this->createMock(
            CustomerPunchoutUniqueIdRepositoryInterface::class
        );
        $this->nonCustomizableProductModel = $this->createMock(
            NonCustomizableProduct::class
        );

        $xmlFactory = $this->createMock(ElementFactory::class);
        $xml = new Element('<Request/>');
        $this->cookieReader = $this->createStub(CookieReaderInterface::class);
        $config = $this->createMock(MarketplaceConfig::class);
        $urlBuilder = $this->createMock(UrlInterface::class);
        $context = $this->createMock(XmlContext::class);
        $formKey = $this->createMock(FormKey::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getEmail', 'getSellerConfiguratorUuid'])
            ->getMock();
        $this->config = $this->createMock(Config::class);

        $context->method('getElementFactory')
            ->willReturn($xmlFactory);
        $context->method('getCookieReaderInterface')
            ->willReturn($this->cookieReader);
        $context->method('getCustomerSession')
            ->willReturn($this->customerSession);
        $context->method('getMarketplaceConfig')
            ->willReturn($config);
        $context->method('getRequestInterface')
            ->willReturn($this->request);
        $context->method('getUrlInterface')
            ->willReturn($urlBuilder);

        $xmlFactory->method('create')
            ->willReturn($xml);
        $this->customerSession->method('getCustomer')
            ->willReturn($this->customer);

        $this->create = new Create(
            $context,
            $formKey,
            $this->productRepository,
            $this->baseAuthMock,
            $this->customerPunchoutUniqueIdRepositoryMock,
            $this->nonCustomizableProductModel,
            $this->customerSession,
            $this->toggleConfig,
            $this->config
        );
    }

    /**
     * @return void
     */
    public function testBuild(): void
    {
        $this->baseAuthMock->method('isLoggedIn')
            ->willReturn(true);
        $this->request->method('getParam')
            ->willReturn('test123');
        $this->customer->method('getEmail')
            ->willReturn('testemail@email.test');
        $productInterface = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCategoryPunchout'])
            ->getMockForAbstractClass();
        $this->productRepository->method('get')->willReturn($productInterface);

        $this->cookieReader->expects($this->once())
            ->method('getCookie');
        $this->assertInstanceOf(Element::class, $this->create->build());
    }

    public function testBuildCustomerSellerUuidToggleOn(): void
    {
        $this->baseAuthMock->method('isLoggedIn')
            ->willReturn(true);

        $this->request->method('getParam')
            ->willReturn('test123');
        $this->customerPunchoutUniqueIdRepositoryMock->method('retrieveCustomerUniqueId')
            ->with($this->customer)
            ->willReturn('123asbd');
        $productInterface = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCategoryPunchout'])
            ->getMockForAbstractClass();
        $this->productRepository->method('get')->willReturn($productInterface);

        $this->cookieReader->expects($this->once())
            ->method('getCookie');
        $this->assertInstanceOf(Element::class, $this->create->build());
    }

}
