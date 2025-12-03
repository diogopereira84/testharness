<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Helper;

use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Company\Helper\Data as CompanyHelper;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\Company\Model\AdditionalData;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;

/**
 * AdminConfigHelperTest Class for test
 */
class AdminConfigHelperTest extends TestCase
{
    public const XML_PATH_B2B_ORDER_REQUEST_CONFIRMATION_EMAIL_TEMPLATE =
        'fedex/transactional_email/b2b_order_request_confirmation_email_template';
    public const XML_PATH_B2B_ORDER_REQUEST_CONFIRMATION_EMAIL_ENABLE =
        'fedex/transactional_email/b2b_order_request_confirmation_email_enable';
    public const XML_PATH_B2B_ORDER_REQUEST_DECLINE_EMAIL_TEMPLATE =
        'fedex/transactional_email/b2b_order_request_decline_email_template';
    public const XML_PATH_B2B_ORDER_REQUEST_DECLINE_EMAIL_ENABLE =
        'fedex/transactional_email/b2b_order_request_decline_email_enable';
    public const XML_PATH_B2B_ORDER_ADMIN_REVIEW_EMAIL_TEMPLATE =
        'fedex/transactional_email/b2b_order_admin_review_email_template';
    public const XML_PATH_B2B_ORDER_ADMIN_REVIEW_EMAIL_ENABLE =
        'fedex/transactional_email/b2b_order_admin_review_email_enable';
    public const CONFIG_BASE_PATH = 'fedex/upload_to_quote_config/';

    /**
     * @var AdminConfigHelper|MockObject
     */
    protected $adminConfigHelperMock;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var CompanyHelper|MockObject
     */
    protected $companyHelperMock;

    /**
     * @var CompanyInterface|MockObject
     */
    protected $companyMock;

    /**
     * @var AdditionalData|MockObject
     */
    protected $additionalDataMock;

    /**
     * @var Session|MockObject
     */
    private $customerSessionMock;

    /**
     * @var customerRepository|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    protected $scopeConfigMock;

    /**
     * @var CheckoutHelper $checkoutHelper
     */
    protected $checkoutHelper;

   /**
    * @var Http $request
    */
    protected $request;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->companyHelperMock = $this
            ->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerCompany'])
            ->getMock();

        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(
                [
                    'getExtensionAttributes'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCompanyAdditionalData',
                'getIsApprovalWorkflowEnabled'
            ])->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(
                [
                    'getById',
                    'getExtensionAttributes',
                    'getCompanyAttributes',
                    'getCompanyId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->checkoutHelper = $this->getMockBuilder(CheckoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['convertPrice'])
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->adminConfigHelperMock = $objectManagerHelper->getObject(
            AdminConfigHelper::class,
            [
                'toggleConfig' => $this->toggleConfig,
                'companyHelper' => $this->companyHelperMock,
                'customerSession' => $this->customerSessionMock,
                'customerRepository' => $this->customerRepositoryMock,
                'scopeConfig' => $this->scopeConfigMock,
                'checkoutHelper' => $this->checkoutHelper,
                'request' => $this->request
            ]
        );
    }

    /**
     * Test isOrderApprovalB2bEnabled
     *
     * @return void
     */
    public function testIsOrderApprovalB2bEnabled()
    {
        $this->toggleConfig->expects($this->once())->method('getToggleConfigValue')->willReturn(1);
        $this->testIsOrderApprovalB2bCompanySettingEnabled();

        $this->assertTrue($this->adminConfigHelperMock->isOrderApprovalB2bEnabled());
    }

    /**
     * Test method for pending order approval Config value
     *
     * @return void
     */
    public function testGetB2bOrderApprovalConfigValue()
    {
        $storeId = 1;
        $key = 'order_success_toast_msg';
        $expectedResult = self::CONFIG_BASE_PATH . $key;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::CONFIG_BASE_PATH . $key);

        $this->assertEquals(
            $expectedResult,
            $this->adminConfigHelperMock->getB2bOrderApprovalConfigValue($key, $storeId)
        );
    }

    /**
     * Test isOrderApprovalB2bCompanySettingEnabled
     *
     * @return void
     */
    public function testIsOrderApprovalB2bCompanySettingEnabled()
    {
        $this->customerSessionMock->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();
        $this->customerRepositoryMock->expects($this->any())->method('getExtensionAttributes')->willReturnSelf();
        $this->customerRepositoryMock->expects($this->any())->method('getCompanyAttributes')->willReturnSelf();
        $this->customerRepositoryMock->expects($this->any())->method('getCompanyId')->willReturn('12345');
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCompanyAdditionalData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('getIsApprovalWorkflowEnabled')->willReturn(true);

        $this->assertTrue($this->adminConfigHelperMock->isOrderApprovalB2bCompanySettingEnabled());
    }

    /**
     * Test isOrderApprovalB2bCompanySettingEnabled
     *
     * @return void
     */
    public function testIsOrderApprovalB2bCompanySettingEnabledWithoutCustomer()
    {
        $this->customerSessionMock->expects($this->any())->method('getCustomerId')->willReturn(false);

        $this->assertFalse($this->adminConfigHelperMock->isOrderApprovalB2bCompanySettingEnabled());
    }

    /**
     * Test isOrderApprovalB2bCompanySettingEnabled
     *
     * @return void
     */
    public function testIsOrderApprovalB2bCompanySettingEnabledWithDefaultReturn()
    {
        $this->customerSessionMock->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();
        $this->customerRepositoryMock->expects($this->any())->method('getExtensionAttributes')->willReturnSelf();
        $this->customerRepositoryMock->expects($this->any())->method('getCompanyAttributes')->willReturn(false);

        $this->assertFalse($this->adminConfigHelperMock->isOrderApprovalB2bCompanySettingEnabled());
    }

    /**
     * Test getB2bOrderEmailTemplate
     *
     * @return void
     */
    public function testGetB2bOrderEmailTemplate()
    {
        $this->assertNotNull(
            $this->adminConfigHelperMock->getB2bOrderEmailTemplate(
                self::XML_PATH_B2B_ORDER_REQUEST_CONFIRMATION_EMAIL_TEMPLATE
            )
        );
    }

    /**
     * Test getB2bOrderEmailTemplateDecline
     *
     * @return void
     */
    public function testGetB2bOrderEmailTemplateDecline()
    {
        $this->assertNotNull(
            $this->adminConfigHelperMock->getB2bOrderEmailTemplate(
                self::XML_PATH_B2B_ORDER_REQUEST_DECLINE_EMAIL_TEMPLATE
            )
        );
    }

    /**
     * Test getB2bOrderEmailTemplateReview
     *
     * @return void
     */
    public function testGetB2bOrderEmailTemplateReview()
    {
        $this->assertNotNull(
            $this->adminConfigHelperMock->getB2bOrderEmailTemplate(
                self::XML_PATH_B2B_ORDER_ADMIN_REVIEW_EMAIL_TEMPLATE
            )
        );
    }

    /**
     * Test isB2bOrderEmailEnabledConfirmed False
     *
     * @return void
     */
    public function testIsB2bOrderEmailEnabledConfirmedFalse()
    {
        $this->assertFalse(
            $this->adminConfigHelperMock->isB2bOrderEmailEnabled(
                static::XML_PATH_B2B_ORDER_REQUEST_CONFIRMATION_EMAIL_ENABLE
            )
        );
    }

    /**
     * Test isB2bOrderEmailEnabledDecline False
     *
     * @return void
     */
    public function testIsB2bOrderEmailEnabledDeclineFalse()
    {
        $this->assertFalse(
            $this->adminConfigHelperMock->isB2bOrderEmailEnabled(
                static::XML_PATH_B2B_ORDER_REQUEST_DECLINE_EMAIL_ENABLE
            )
        );
    }

    /**
     * Test isB2bOrderEmailEnabledReview False
     *
     * @return void
     */
    public function testIsB2bOrderEmailEnabledReviewFalse()
    {
        $this->assertFalse(
            $this->adminConfigHelperMock->isB2bOrderEmailEnabled(
                static::XML_PATH_B2B_ORDER_ADMIN_REVIEW_EMAIL_ENABLE
            )
        );
    }

    /**
     * Test convertPrice
     *
     * @return void
     */
    public function testConvertPrice()
    {
        $price = '0.6100';
        $returnValue = '$0.61';
        $this->checkoutHelper->expects($this->once())->method('convertPrice')
            ->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->adminConfigHelperMock->convertPrice($price));
    }

    /**
     * Test checkIsReviewActionSet
     *
     * @return void
     */
    public function testCheckIsReviewActionSet()
    {
        $this->request->expects($this->once())->method('getParam')->willReturn('review');

        $this->assertTrue($this->adminConfigHelperMock->checkIsReviewActionSet());
    }

    /**
     * Test isshippingAddressvalidationEnable
     *
     * @return void
     */
    public function testIsB2bDeclineReorderEnabled()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('xmen_b2b_order_declined_reorder')
            ->willReturn(true);

        $this->assertNotNull($this->adminConfigHelperMock->isB2bDeclineReorderEnabled());
    }
}
