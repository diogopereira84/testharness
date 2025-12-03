<?php

use PHPUnit\Framework\TestCase;
use Fedex\PersonalAddressBook\Block\PersonalAddressBook;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Fedex\SSO\Helper\Data as SSODataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;

class PersonalAddressBookTest extends TestCase
{
    private $context;
    private $urlBuilder;
    private $deliveryHelper;
    private $ssoDataHelper;
    private $toggleConfig;
    private $eventManager;
    private $scopeConfig;
    private $escaper;
    private $personalAddressBook;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->deliveryHelper = $this->createMock(DeliveryDataHelper::class);
        $this->ssoDataHelper = $this->createMock(SSODataHelper::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->eventManager = $this->createMock(ManagerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->escaper = $this->createMock(Escaper::class);

        $this->context->method('getEventManager')->willReturn($this->eventManager);
        $this->context->method('getScopeConfig')->willReturn($this->scopeConfig);
        $this->context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $this->context->method('getEscaper')->willReturn($this->escaper);

        $this->personalAddressBook = new PersonalAddressBook(
            $this->context,
            $this->urlBuilder,
            $this->deliveryHelper,
            $this->ssoDataHelper,
            $this->toggleConfig
        );
    }

    public function testGetSortOrder()
    {
        $this->personalAddressBook->setData(PersonalAddressBook::SORT_ORDER, 10);
        $this->assertEquals(10, $this->personalAddressBook->getSortOrder());
    }

    public function testToHtmlReturnsEmptyStringWhenSSOLogin()
    {
        $this->ssoDataHelper->method('isSSOlogin')->willReturn(true);
        $this->scopeConfig->method('getValue')->willReturn(false);
        $this->assertEquals('', $this->personalAddressBook->toHtml());
    }

    public function testToHtmlReturnsCorrectHtmlForCommercialCustomer()
    {
        $this->ssoDataHelper->method('isSSOlogin')->willReturn(false);
        $this->deliveryHelper->method('isCommercialCustomer')->willReturn(true);
        $this->urlBuilder->method('getCurrentUrl')->willReturn('http://example.com/personaladdressbook/index/view');
        $this->urlBuilder->method('getUrl')->willReturn('http://example.com/personaladdressbook/index/view');
        $this->scopeConfig->method('getValue')->willReturn(false);
        $this->escaper->method('escapeHtml')->willReturnCallback(function ($value) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        });
        $this->personalAddressBook->setPath('personaladdressbook/index/view');

        $expectedHtml = '<li class="nav item  current current"><a href="http://example.com/personaladdressbook/index/view" >' . htmlspecialchars(PersonalAddressBook::PERSONAL_ADDRESS_BOOK_COMMERCIAL) . '</a></li>';
        $this->assertEquals($expectedHtml, $this->personalAddressBook->toHtml());
    }

    public function testToHtmlReturnsCorrectHtmlForRetailCustomer()
    {
        $this->ssoDataHelper->method('isSSOlogin')->willReturn(false);
        $this->deliveryHelper->method('isCommercialCustomer')->willReturn(false);
        $this->urlBuilder->method('getCurrentUrl')->willReturn('http://example.com/personaladdressbook/index/view');
        $this->urlBuilder->method('getUrl')->willReturn('http://example.com/personaladdressbook/index/view');
        $this->scopeConfig->method('getValue')->willReturn(false);
        $this->escaper->method('escapeHtml')->willReturnCallback(function ($value) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        });
        $this->personalAddressBook->setPath('personaladdressbook/index/view');

        $expectedHtml = '<li class="nav item  current current"><a href="http://example.com/personaladdressbook/index/view" >' . htmlspecialchars(PersonalAddressBook::ADDRESS_BOOK_RETAIL) . '</a></li>';
        $this->assertEquals($expectedHtml, $this->personalAddressBook->toHtml());
    }

    public function testToHtmlReturnsCorrectHtmlWhenNotCurrentUrl()
    {
        $this->ssoDataHelper->method('isSSOlogin')->willReturn(false);
        $this->deliveryHelper->method('isCommercialCustomer')->willReturn(true);
        $this->urlBuilder->method('getCurrentUrl')->willReturn('http://example.com/otherpage');
        $this->urlBuilder->method('getUrl')->willReturn('http://example.com/personaladdressbook/index/view');
        $this->scopeConfig->method('getValue')->willReturn(false);
        $this->escaper->method('escapeHtml')->willReturnCallback(function ($value) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        });
        $this->personalAddressBook->setPath('personaladdressbook/index/view');

        $expectedHtml = '<li class="nav item "><a href="http://example.com/personaladdressbook/index/view" >' . htmlspecialchars(PersonalAddressBook::PERSONAL_ADDRESS_BOOK_COMMERCIAL) . '</a></li>';
        $this->assertEquals($expectedHtml, $this->personalAddressBook->toHtml());
    }
}
