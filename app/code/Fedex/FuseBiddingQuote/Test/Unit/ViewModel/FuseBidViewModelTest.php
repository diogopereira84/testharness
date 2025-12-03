<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\NegotiableQuote\Model\NegotiableCartRepository;
use Psr\Log\LoggerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Registry;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\Quote\Model\Quote;

/**
 * Test class for FuseBidViewModel
 */
class FuseBidViewModelTest extends TestCase
{

    /**
     * @var SessionManagerInterface $sessionMock
     */
    private $sessionMock;

    /**
     * @var FuseBidHelper|MockObject $fuseBidHelper
     */
    protected $fuseBidHelper;

    /**
     * @var SsoConfiguration|MockObject $ssoConfiguration
     */
    protected $ssoConfiguration;

    /**
     * @var NegotiableCartRepository|MockObject $negotiableCartRepository
     */
    protected $negotiableCartRepository;

    /**
     * @var FuseBidViewModel|MockObject $fuseBidViewModel
     */
    protected $fuseBidViewModel;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var CustomerFactory|MockObject
     */
    protected $customerFactoryMock;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected $quoteRepositoryMock;

    /**
     * @var QuoteFactory|MockObject
     */
    protected $quoteFactoryMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagementMock;

    /**
     * @var Quote|MockObject
     */
    protected $quoteMock;

    /**
     * Setup method
     */
    protected function setUp(): void
    {
        $this->fuseBidHelper = $this->createMock(FuseBidHelper::class);
        $this->ssoConfiguration = $this
            ->getMockBuilder(SsoConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger =  $this->getMockBuilder(LoggerInterface::class)
        ->disableOriginalConstructor()
        ->setMethods([
            'error',
            'info'
        ])
        ->getMockForAbstractClass();
        $this->negotiableCartRepository = $this->getMockBuilder(NegotiableCartRepository::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'get',
                'getCustomerEmail'
            ])
            ->getMock();

        $this->sessionMock = $this->getMockBuilder(SessionManagerInterface::class)
            ->setMethods(['getData', 'setData', 'start'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getStore',
                'getId'
            ])
            ->getMockForAbstractClass();
        $this->sessionMock = $this->getMockBuilder(SessionManagerInterface::class)
            ->setMethods(['getData', 'setData', 'start'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->setMethods(['register'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->setMethods([
                'create',
                'load',
                'getSecondaryEmail',
                'getEmail',
                'getData',
                'getId',
                'getFirstname',
                'getLastname',
                'getGroupId'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getIsBid',
                'getExtensionAttributes',
                'getCustomerId',
                'load',
                'setCustomerFirstname',
                'setCustomerLastname',
                'setCustomerEmail',
                'setCustomerIsGuest',
                'setCustomerId',
                'setCustomerGroupId'
            ])
            ->getMock();

        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteFactoryMock = $this->getMockBuilder(QuoteFactory::class)
            ->setMethods(['create', 'load','getCustomerEmail','getCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['deleteById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->negotiableQuoteManagementMock = $this->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->setMethods(['recalculateQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fuseBidViewModel = new FuseBidViewModel(
            $this->fuseBidHelper,
            $this->negotiableCartRepository,
            $this->logger,
            $this->sessionMock,
            $this->storeManager,
            $this->customerFactoryMock,
            $this->quoteRepositoryMock,
            $this->quoteFactoryMock,
            $this->registryMock,
            $this->customerRepositoryMock,
            $this->negotiableQuoteManagementMock
        );
    }

    /**
     * Test testIsFuseBidToggleEnabled
     *
     * @return void
     */
    public function testIsFuseBidToggleEnabled()
    {
        $this->fuseBidHelper->method('isFuseBidGloballyEnabled')->willReturn(true);
        $this->storeManager->method('getStore')->willReturnSelf();
        $this->storeManager->method('getId')->willReturn(2);
        $this->fuseBidHelper->method('getUploadToQuoteConfigValue')->willReturn(true);

        $result = $this->fuseBidViewModel->isFuseBidToggleEnabled();

        $this->assertTrue($result);
    }

    /**
     * Test testValidateCustomerQuote
     *
     * @return void
     */
    public function testValidateCustomerQuoteSuccess()
    {
        $this->negotiableCartRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->customerFactoryMock->method('getSecondaryEmail')->willReturn(null);
        $this->customerFactoryMock->method('getEmail')->willReturn('test@example.com');
        $this->quoteFactoryMock->method('getCustomerEmail')->willReturn('test@example.com');

        $result = $this->fuseBidViewModel->validateCustomerQuote($this->customerFactoryMock, '123');

        $this->assertEquals('uploadtoquote/index/quotehistory', $result);
    }

    /**
     * Test testValidateCustomerQuoteNoSuchEntity
     *
     * @return void
     */
    public function testValidateCustomerQuoteNoSuchEntity()
    {
        $quoteId = '123';
        $email = 'test@test.com';

        $this->negotiableCartRepository->expects($this->once())
            ->method('get')
            ->with($quoteId)
            ->willThrowException(new NoSuchEntityException(__('Quote not found')));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('quote with quote id 123 error =>'));

        $result = $this->fuseBidViewModel->validateCustomerQuote($email, $quoteId);

        $this->assertEquals('', $result);
    }

    /**
     * Test displayPopupForLoginError
     *
     * @return void
     */
    public function testDisplayPopupForLoginError()
    {
        $this->sessionMock->method('getData')->willReturn('true');
        $result = $this->fuseBidViewModel->displayPopupForLoginError();

        $this->assertEquals('true', $result);
    }

    /**
     * Test getGeneralConfig
     *
     * @param string $code
     * @return void
     */
    public function testGetGeneralConfig($code = 'wlgn_login_page_url')
    {
        $mixedValue = 'https://wwwtest.fedex.com/secure-login/en-us/#/login-credentials';
        $this->fuseBidHelper->expects($this->any())
            ->method('getSsoConfigValue')
            ->with("wlgn_login_page_url")
            ->willReturn($mixedValue);

        $this->assertEquals($mixedValue, $this->fuseBidViewModel->getGeneralConfig($code));
    }

    /**
     * Test getErrorPopupSessionValue
     *
     * @return void
     */
    public function testGetErrorPopupSessionValue()
    {
        $sessionName = 'error_popup_session';
        $this->sessionMock->expects($this->once())->method('start')->willReturnSelf();
        $this->sessionMock->expects($this->any())
            ->method('getData')
            ->with('error_popup_session')
            ->willReturn(true);

        $this->assertEquals($sessionName, $this->fuseBidViewModel->getErrorPopupSessionValue());
    }

    /**
     * Test setErrorPopupSessionValue
     *
     * @param string $value
     * @return void
     */
    public function testSetErrorPopupSessionValue($value = true)
    {
        $this->sessionMock->expects($this->once())->method('start')->willReturnSelf();
        $this->sessionMock->expects($this->any())
            ->method('setData')
            ->with('error_popup_session')
            ->willReturn(true);

        $this->assertEquals(null, $this->fuseBidViewModel->setErrorPopupSessionValue(true));
    }

    /**
     * Test deactivateQuote
     *
     * @return void
     */
    public function testDeactivateQuote()
    {
        $this->fuseBidHelper->expects($this->once())->method('deactivateQuote')->willReturn(null);

        $this->assertNUll($this->fuseBidViewModel->deactivateQuote());
    }

     /**
      * Test associateQuoteWithCustomer method for success scenario.
      */
    public function testAssociateQuoteWithCustomerSuccess()
    {
        $this->customerFactoryMock->method('getData')->with('external_id')->willReturn('external123');
        $this->quoteFactoryMock->method('getCustomerId')->willReturn(1);

        $this->customerFactoryMock
            ->method('create')
            ->willReturn($this->customerFactoryMock);

        $this->customerFactoryMock->method('load')->with(1)->willReturnSelf();
        $this->customerFactoryMock->method('getData')->with('external_id')->willReturn(null);

        $this->logger->expects($this->any())
            ->method('info')
            ->with($this->stringContains('Quote updated to customer ID'));

        $this->fuseBidViewModel->associateQuoteWithCustomer($this->customerFactoryMock, $this->quoteFactoryMock);
    }

    /**
     * Test associateQuoteWithCustomer method with exception.
     */
    public function testAssociateQuoteWithCustomerException()
    {
        $this->customerFactoryMock->method('getData')
        ->with('external_id')->willThrowException(new \Exception('Test exception'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error associating quote with customer'));

        $this->fuseBidViewModel->associateQuoteWithCustomer($this->customerFactoryMock, $this->quoteFactoryMock);
    }

     /**
      * Test updateQuoteWithCustomerInfo method for success.
      */
    public function testUpdateQuoteWithCustomerInfoSuccess()
    {
        $this->quoteFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->quoteRepositoryMock->method('save')->with($this->quoteFactoryMock);
        $this->customerFactoryMock->method('getId')->willReturn(2);
        $this->customerFactoryMock->method('getFirstname')->willReturn('John');
        $this->customerFactoryMock->method('getLastname')->willReturn('Doe');
        $this->customerFactoryMock->method('getGroupId')->willReturn(1);
        $this->quoteFactoryMock->method('getCustomerId')->willReturn(1);
        $this->quoteRepositoryMock->method('save')->willReturnSelf();
        $this->negotiableQuoteManagementMock->method('recalculateQuote')->willReturnSelf();
        $this->registryMock->expects($this->any())
            ->method('register')
            ->with('isSecureArea', true);
        $this->customerRepositoryMock->method('deleteById')->willReturnSelf();

        $this->fuseBidViewModel->updateQuoteWithCustomerInfo(1, $this->customerFactoryMock);
    }

    /**
     * Test updateQuoteWithCustomerInfo method with exception.
     */
    public function testUpdateQuoteWithCustomerInfoException()
    {
        $this->quoteFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->quoteRepositoryMock
            ->method('save')
            ->willThrowException(new \Exception('Test exception'));

        $this->logger->expects($this->any())
            ->method('error')
            ->with($this->stringContains('Test exception'));

        $this->fuseBidViewModel->updateQuoteWithCustomerInfo(1, $this->customerFactoryMock);
    }

    /**
     * Test isRateQuoteDetailApiEnabed
     *
     * @return void
     */
    public function testIsRateQuoteDetailApiEnabed()
    {
        $this->fuseBidHelper->expects($this->once())->method('isRateQuoteDetailApiEnabed')->willReturn(true);

        $this->assertTrue($this->fuseBidViewModel->isRateQuoteDetailApiEnabed());
    }

    /**
     * Test isSendRetailLocationIdEnabled
     *
     * @return void
     */
    public function testIsSendRetailLocationIdEnabled()
    {
        $this->fuseBidHelper->expects($this->once())->method('isSendRetailLocationIdEnabled')->willReturn(true);

        $this->assertTrue($this->fuseBidViewModel->isSendRetailLocationIdEnabled());
    }

    /**
     * Test isBidCheckoutEnabled
     *
     * @return void
     */
    public function testIsBidCheckoutEnabled()
    {
        $this->fuseBidHelper->expects($this->once())
            ->method('isBidCheckoutEnabled')
            ->willReturn(true);

        $this->assertTrue($this->fuseBidViewModel->isBidCheckoutEnabled());
    }

    /**
     * Test isToggleD215974Enabled
     *
     * @return void
     */
    public function testIsToggleD215974Enabled()
    {
        $this->fuseBidHelper->expects($this->once())
            ->method('isToggleD215974Enabled')
            ->willReturn(true);

        $this->assertTrue($this->fuseBidViewModel->isToggleD215974Enabled());
    }
}
