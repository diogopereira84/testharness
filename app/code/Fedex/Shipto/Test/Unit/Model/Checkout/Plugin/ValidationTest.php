<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Shipto\Test\Unit\Model\Checkout\Plugin;

use Magento\Checkout\Api\AgreementsValidatorInterface;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface;
use Magento\CheckoutAgreements\Model\AgreementsProvider;
use Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter;
use Fedex\Shipto\Model\Checkout\Plugin\Validation;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentExtension;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ValidationTest validates the agreement based on the payment method
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidationTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Session\SessionManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $coreSessionMock;
    protected $requestMock;
    /**
     * @var Validation
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $agreementsValidatorMock;

    /**
     * @var MockObject
     */
    protected $subjectMock;

    /**
     * @var MockObject
     */
    protected $paymentMock;

    /**
     * @var MockObject
     */
    protected $addressMock;

    /**
     * @var MockObject
     */
    protected $extensionAttributesMock;

    /**
     * @var MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var MockObject
     */
    private $checkoutAgreementsListMock;

    /**
     * @var MockObject
     */
    private $agreementsFilterMock;

    /**
     * @var MockObject
     */
    private $quoteMock;

    /**
     * @var MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        
        $this->coreSessionMock = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
      
        $this->requestMock = $this
            ->getMockBuilder(RequestInterface::class)
            ->setMethods(['getContent'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
         $this->agreementsValidatorMock = $this->getMockForAbstractClass(AgreementsValidatorInterface::class);
        $this->subjectMock = $this->getMockForAbstractClass(PaymentInformationManagementInterface::class);
        $this->paymentMock = $this->getMockForAbstractClass(PaymentInterface::class);
        $this->addressMock = $this->getMockForAbstractClass(AddressInterface::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getIsMultiShipping'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepositoryMock = $this->getMockForAbstractClass(CartRepositoryInterface::class);
                   
        $this->extensionAttributesMock = $this
            ->getMockBuilder(PaymentExtension::class)
            ->setMethods(['getAgreementIds'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->extensionAttributesMock = $this
            ->getMockBuilder(PaymentExtension::class)
            ->setMethods(['getAgreementIds'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->checkoutAgreementsListMock = $this->createMock(
            CheckoutAgreementsListInterface::class
        );
        $this->agreementsFilterMock = $this->createMock(
            ActiveStoreAgreementsFilter::class
        );

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->model = new Validation(
            $this->agreementsValidatorMock,
            $this->scopeConfigMock,
            $this->checkoutAgreementsListMock,
            $this->agreementsFilterMock,
            $this->quoteRepositoryMock,
            $this->coreSessionMock,
            $this->requestMock,
            $this->loggerMock
        );
    }

    public function testBeforeSavePaymentInformationAndPlaceOrder()
    {
        $paymentData['paymentMethod'] = '';
        $paymentData = json_encode($paymentData);
        
        $this->requestMock
            ->expects($this->any())
            ->method('getContent')
            ->willReturn($paymentData);
            
        $cartId = 100;
        $agreements = [1, 2, 3];
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('isSetFlag')
            ->with(AgreementsProvider::PATH_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->quoteMock
            ->expects($this->any())
            ->method('getIsMultiShipping')
            ->willReturn(false);
        $this->quoteRepositoryMock
            ->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);
        $this->agreementsFilterMock->expects($this->any())
            ->method('buildSearchCriteria')
            ->willReturn($searchCriteriaMock);
        $this->checkoutAgreementsListMock->expects($this->any())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn([1]);
        $this->extensionAttributesMock->expects($this->any())->method('getAgreementIds')->willReturn($agreements);
        $this->agreementsValidatorMock->expects($this->any())->method('isValid')->with($agreements)->willReturn(true);
        $this->paymentMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $this->model->beforeSavePaymentInformationAndPlaceOrder(
            $this->subjectMock,
            $cartId,
            $this->paymentMock,
            $this->addressMock
        );
    }

    public function testBeforeSavePaymentInformationAndPlaceOrderIfAgreementsNotValid()
    {
        $paymentData['paymentMethod']['po_number'] = '';
        $paymentData = json_encode($paymentData);
        
        $this->requestMock
            ->expects($this->any())
            ->method('getContent')
            ->willReturn($paymentData);
            
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $cartId = 100;
        $agreements = [1, 2, 3];
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('isSetFlag')
            ->with(AgreementsProvider::PATH_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->quoteMock
            ->expects($this->any())
            ->method('getIsMultiShipping')
            ->willReturn(false);
        $this->quoteRepositoryMock
            ->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);
        $this->agreementsFilterMock->expects($this->any())
            ->method('buildSearchCriteria')
            ->willReturn($searchCriteriaMock);
        $this->checkoutAgreementsListMock->expects($this->any())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn([1]);
        $this->extensionAttributesMock->expects($this->any())->method('getAgreementIds')->willReturn($agreements);
        $this->agreementsValidatorMock->expects($this->any())->method('isValid')->with($agreements)->willReturn(false);
        $this->paymentMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $this->model->beforeSavePaymentInformation(
            $this->subjectMock,
            $cartId,
            $this->paymentMock,
            $this->addressMock
        );

        $this->expectExceptionMessage(
            "The order wasn't placed. First, agree to the terms and conditions, then try placing your order again."
        );
    }

    public function testBeforeSavePaymentInformation()
    {
        $paymentData['paymentMethod']['po_number'] = '123';
        $paymentData = json_encode($paymentData);
        
        $this->requestMock
            ->expects($this->any())
            ->method('getContent')
            ->willReturn($paymentData);
            
        $cartId = 100;
        $agreements = [1, 2, 3];
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('isSetFlag')
            ->with(AgreementsProvider::PATH_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->quoteMock
            ->expects($this->any())
            ->method('getIsMultiShipping')
            ->willReturn(false);
        $this->quoteRepositoryMock
            ->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->agreementsFilterMock->expects($this->any())
            ->method('buildSearchCriteria')
            ->willReturn($searchCriteriaMock);
        $this->checkoutAgreementsListMock->expects($this->any())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn([1]);
        $this->extensionAttributesMock->expects($this->any())->method('getAgreementIds')->willReturn($agreements);
        $this->agreementsValidatorMock->expects($this->any())->method('isValid')->with($agreements)->willReturn(true);
        $this->paymentMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
        $this->model->beforeSavePaymentInformation($this->subjectMock, $cartId, $this->paymentMock, $this->addressMock);
    }
}
