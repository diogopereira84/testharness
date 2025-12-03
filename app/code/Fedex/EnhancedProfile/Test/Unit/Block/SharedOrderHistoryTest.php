<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\EnhancedProfile\Test\Unit\Block;
use Fedex\Delivery\Helper\Data;
use Fedex\EnhancedProfile\Block\SharedOrderHistory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Block\Account\SortLinkInterface;
use Fedex\Commercial\Helper\CommercialHelper;

class SharedOrderHistoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $commercialHelperMock;
    protected $urlInterfaceMock;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaperMock;
    protected $deliveryDataHelper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customBlock;
    /**
     * @var \Fedex\Delivery\Helper\Data $helperDataMock
     */
    protected $helperDataMock;

    const SORT_ORDER = 95;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->commercialHelperMock = $this
            ->getMockBuilder(CommercialHelper::class)
            ->setMethods(['isRolePermissionToggleEnable'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->setMethods(['getUrl', 'getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->escaperMock = $this
            ->getMockBuilder(\Magento\Framework\Escaper::class)
            ->setMethods(['escapeHtml'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryDataHelper = $this->getMockBuilder(Data::class)
            ->setMethods(['getCustomer', 'getCustomAttribute', 'getValue', 'getToggleConfigurationValue', 'isCompanyAdminUser', 'checkPermission','isCustomerAdminUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customBlock = $this->objectManager->getObject(
            SharedOrderHistory::class,
            [
                'context' => $this->context,
                '_urlBuilder' => $this->urlInterfaceMock,
                'urlBuilder' => $this->urlInterfaceMock,
                '_escaper' => $this->escaperMock,
                'commercialHelper' => $this->commercialHelperMock,
                'helperData' => $this->deliveryDataHelper,
            ]
        );
    }

    /**
     * Assert _toHtml.
     *
     * @return string
     */
    public function testToHtml()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\EnhancedProfile\Block\SharedOrderHistory::class,
            '_toHtml',
        );
        $this->deliveryDataHelper->expects($this->any())->method('isCustomerAdminUser')->willReturn(true);
        $this->deliveryDataHelper->method('getToggleConfigurationValue')->willReturn(true);
        $this->deliveryDataHelper->method('checkPermission')->willReturn(true);
        $this->urlInterfaceMock->method('getCurrentUrl')->willReturn('https://staging3.office.fedex.com/ondemand/mgs/shared/order/history');
        $testMethod->setAccessible(true);
        $this->commercialHelperMock->expects($this->any())->method('isRolePermissionToggleEnable')->willReturn(true);
        $expectedResult = $testMethod->invoke($this->customBlock);
    }

    /**
     * Assert _toHtml in Negative case
     *
     * @return ''
     */
    public function testToHtmlWhenModuleDisable()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\EnhancedProfile\Block\SharedOrderHistory::class,
            '_toHtml',
        );
        $this->deliveryDataHelper->expects($this->any())->method('isCustomerAdminUser')->willReturn(true);
        $testMethod->setAccessible(true);
        $this->commercialHelperMock->expects($this->any())->method('isRolePermissionToggleEnable')->willReturn(false);
        $expectedResult = $testMethod->invoke($this->customBlock);
        $this->assertIsString($expectedResult);
    }

    /**
     * Test Case getSortOrder()
     */
    public function testGetSortOrder()
    {
        $this->customBlock->getSortOrder();
    }

}
