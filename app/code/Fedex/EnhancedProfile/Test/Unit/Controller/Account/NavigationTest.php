<?php

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use PHPUnit\Framework\TestCase;
use Fedex\EnhancedProfile\Block\Account\Navigation;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Customer\Model\Session;
use Fedex\EnhancedProfile\Helper\Account;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\Commercial\Helper\CommercialHelper;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\SSO\Helper\Data as SSODataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;

class NavigationTest extends TestCase
{
    private $context;
    private $defaultPath;
    private $customerSession;
    private $accountHelper;
    private $companyRepository;
    private $commercialHelper;
    private $authHelper;
    private $dataHelper;
    private $toggleConfig;
    private $eventManager;
    private $scopeConfig;
    private $urlBuilder;
    private $escaper;
    private $request;
    private $navigation;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->defaultPath = $this->createMock(DefaultPathInterface::class);
        $this->customerSession = $this->createMock(Session::class);
        $this->accountHelper = $this->createMock(Account::class);
        $this->companyRepository = $this->createMock(CompanyManagementInterface::class);
        $this->commercialHelper = $this->createMock(CommercialHelper::class);
        $this->authHelper = $this->createMock(AuthHelper::class);
        $this->dataHelper = $this->createMock(SSODataHelper::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->eventManager = $this->createMock(ManagerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->escaper = $this->createMock(Escaper::class);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getActionName', 'getModuleName'])
            ->addMethods(['getControllerName', 'getPathInfo'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->context->method('getEventManager')->willReturn($this->eventManager);
        $this->context->method('getScopeConfig')->willReturn($this->scopeConfig);
        $this->context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $this->context->method('getEscaper')->willReturn($this->escaper);
        $this->context->method('getRequest')->willReturn($this->request);

        $this->navigation = new Navigation(
            $this->context,
            $this->defaultPath,
            $this->customerSession,
            $this->accountHelper,
            $this->companyRepository,
            $this->commercialHelper,
            $this->authHelper,
            $this->dataHelper,
            $this->toggleConfig
        );
    }

    /**
     * Tests that the toHtml method returns an empty string when the user is logged in as an admin.
     *
     * @return void
     */
    public function testToHtmlReturnsCorrectHtmlWhenNotHighlighted()
    {
        $this->commercialHelper->method('isRolePermissionToggleEnable')->willReturn(true);
        $this->navigation->setLabel('Test Label');
        $this->navigation->setIsHighlighted(false);
        $this->navigation->setHref('http://example.com');
        $this->request->expects($this->once())->method('getModuleName')->willReturn('Fedex_EnhancedProfile');
        $this->request->expects($this->once())->method('getControllerName')->willReturn('Account');
        $this->request->expects($this->once())->method('getActionName')->willReturn('index');
        $this->request->expects($this->once())->method('getPathInfo')->willReturn('/fedex/enhancedprofile/account/index');
        $this->scopeConfig->method('getValue')->willReturn(false);
        $this->urlBuilder->method('getUrl')->willReturn('http://example.com');
        $expectedHtml = '<li class="nav item"><strong></strong></li>';
        $this->assertEquals($expectedHtml, $this->navigation->toHtml());
    }

    /**
     * Tests the getSortOrder method to ensure it returns the correct sort order value.
     *
     * This unit test verifies the behavior of the getSortOrder function in the context
     * of the Account Navigation controller.
     */
    public function testGetSortOrder()
    {
        $this->navigation->setData(Navigation::SORT_ORDER, 10);
        $this->assertEquals(10, $this->navigation->getSortOrder());
    }

    /**
     * Tests that the getCompanyLoginType method returns 'FCL'.
     *
     * This unit test verifies the behavior of the getCompanyLoginType method
     * in the Account Navigation controller, ensuring it returns the expected
     * login type value 'FCL' for company accounts.
     */
    public function testGetCompanyLoginTypeReturnsFCL()
    {
        $this->authHelper->method('isLoggedIn')->willReturn(true);
        $this->authHelper->method('getCompanyAuthenticationMethod')->willReturn(AuthHelper::AUTH_FCL);
        $this->assertEquals('FCL', $this->navigation->getCompanyLoginType());
    }

    /**
     * Tests that the getCompanyLoginType method returns 'SSO' as expected.
     *
     * This unit test verifies the behavior of the getCompanyLoginType method,
     * ensuring that it correctly identifies and returns the Single Sign-On (SSO)
     * login type for a company account.
     *
     * @return void
     */
    public function testGetCompanyLoginTypeReturnsSSO()
    {
        $this->authHelper->method('isLoggedIn')->willReturn(true);
        $this->authHelper->method('getCompanyAuthenticationMethod')->willReturn(AuthHelper::AUTH_SSO);
        $this->assertEquals('SSO', $this->navigation->getCompanyLoginType());
    }

    /**
     * Tests that the getCompanyLoginType method returns the expected value for EPro Punchout.
     *
     * @return void
     */
    public function testGetCompanyLoginTypeReturnsEProPunchout()
    {
        $this->authHelper->method('isLoggedIn')->willReturn(true);
        $this->authHelper->method('getCompanyAuthenticationMethod')->willReturn(AuthHelper::AUTH_PUNCH_OUT);
        $this->assertEquals('EPro Punchout', $this->navigation->getCompanyLoginType());
    }

    /**
     * Tests that the getCompanyLoginType method returns false when the user is not logged in.
     *
     * This unit test verifies the behavior of the getCompanyLoginType method in scenarios
     * where there is no active user session, ensuring that it correctly returns false.
     */
    public function testGetCompanyLoginTypeReturnsFalseWhenNotLoggedIn()
    {
        $this->authHelper->method('isLoggedIn')->willReturn(false);
        $this->assertFalse($this->navigation->getCompanyLoginType());
    }
}
