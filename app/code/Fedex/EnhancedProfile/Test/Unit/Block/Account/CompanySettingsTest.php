<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Block\Account;
use Fedex\Delivery\Helper\Data;
use Fedex\EnhancedProfile\Block\Account\CompanySettings;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
class CompanySettingsTest extends \PHPUnit\Framework\TestCase

{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
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

        $this->urlInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->setMethods(['getUrl','getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->escaperMock = $this
            ->getMockBuilder(\Magento\Framework\Escaper::class)
            ->setMethods(['escapeHtml'])
            ->disableOriginalConstructor()
            ->getMock();

            $this->deliveryDataHelper = $this->getMockBuilder(Data::class)
            ->setMethods(['getCustomer','getCustomAttribute','getValue','getToggleConfigurationValue','isCompanyAdminUser','checkPermission','isCustomerAdminUser','isCommercialCustomer','isSelfRegCustomerAdminUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customBlock = $this->objectManager->getObject(
            CompanySettings::class,
            [
                'context' => $this->context,
                '_urlBuilder' => $this->urlInterfaceMock,
                'urlBuilder' => $this->urlInterfaceMock,
                '_escaper' => $this->escaperMock,
                'helperData' =>$this->deliveryDataHelper,
            ]
        );
        
       
    }

    /**
     * Test Case for _toHtml.
     *
     * @return string
     */
    public function testToHtml()
    {
        $testMethod = new \ReflectionMethod(
            CompanySettings::class,
            '_toHtml',
        );
         $this->deliveryDataHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
         $this->deliveryDataHelper->method('getToggleConfigurationValue')->willReturn(true);
         $this->deliveryDataHelper->method('checkPermission')->willReturn(true);
         $this->urlInterfaceMock->method('getCurrentUrl')->willReturn('https://staging3.office.fedex.com/ondemand/mgs/company/users');
         $testMethod->setAccessible(true);
         $expectedResult = $testMethod->invoke($this->customBlock);
         $this->assertIsString($expectedResult);
    }

    /**
     * Assert _toHtml.
     *
     * @return string
     */
    public function testToHtmlwithAdmin()
    {
        $testMethod = new \ReflectionMethod(
            CompanySettings::class,
            '_toHtml',
        );
         $this->deliveryDataHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
         $this->deliveryDataHelper->expects($this->any())->method('isSelfRegCustomerAdminUser')->willReturn(true);
         $this->deliveryDataHelper->method('getToggleConfigurationValue')->willReturn(true);
         $this->deliveryDataHelper->method('checkPermission')->willReturn(true);
         $this->urlInterfaceMock->method('getCurrentUrl')->willReturn('https://staging3.office.fedex.com/ondemand/mgs/company/users');
         $testMethod->setAccessible(true);
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
