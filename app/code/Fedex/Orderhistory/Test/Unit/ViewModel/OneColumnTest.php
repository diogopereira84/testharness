<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit\ViewModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\Orderhistory\Helper\Data;
use Fedex\Orderhistory\ViewModel\OneColumn;
use Fedex\EnvironmentManager\Helper\ModuleStatus;
use Fedex\EnhancedProfile\Helper\Account;
use Fedex\Delivery\Helper\Data as DeliveryHelper;

class OneColumnTest extends \PHPUnit\Framework\TestCase
{
   protected $moduleStatus;
    protected $accountHelper;
    protected $selfRegHelper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $onColumn;
    /**
    * @var Data
    */
    protected $helper;

    /**
     * @var DeliveryHelper $deliveryHelper
     */
    protected DeliveryHelper $deliveryHelper;

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(Data::class)
            ->setMethods([
                'isSetOneColumn',
                'getIsSdeStore',
                'isSetOneColumnRetail',
                'isEnhancementClass',
                'isRetailEnhancementClass',
                'isRetailOrderHistoryReorderEnabled',
                'isCommercialReorderEnabled'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleStatus = $this->getMockBuilder(ModuleStatus::class)
            ->setMethods(['isModuleEnable'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountHelper = $this->getMockBuilder(Account::class)
            ->setMethods(['getCompanyLoginType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegHelper = $this->getMockBuilder(\Fedex\SelfReg\Helper\SelfReg::class)
            ->setMethods(['isSelfRegCompany', 'isSelfRegCustomerWithFclEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['isEproCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->onColumn = $this->objectManager->getObject(
            OneColumn::class,
            [
                'helper' => $this->helper,
                'moduleStatus' => $this->moduleStatus,
                'selfRegHelper' => $this->selfRegHelper,
                'accountHelper' => $this->accountHelper,
                'deliveryHelper' => $this->deliveryHelper
            ]
        );
    }

    /**
     *  test case for testIsModuleEnable
     */
    public function testIsModuleEnable()
    {
        $this->moduleStatus->expects($this->any())->method('isModuleEnable')->willReturnSelf();
        $this->onColumn->isModuleEnable('reorder');
    }

    /**
     *  test case for testIsSetOneColumn
     */
    public function testIsSetOneColumn()
    {
        $this->helper->expects($this->any())->method('isSetOneColumn')->willReturn(true);
        $exectedResult = $this->onColumn->isSetOneColumn();
        $this->assertEquals(true, $exectedResult);
    }

    /**
     *  test case for testIsSdeStoreEnabled
     */
    public function testIsSdeStoreEnabled()
    {
        $this->helper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $exectedResult = $this->onColumn->isSdeStoreEnabled();
        $this->assertEquals(true, $exectedResult);
    }

    /**
     *  test case for testIsSetOneColumnRetail
     */
    public function testIsSetOneColumnRetail()
    {
        $this->helper->expects($this->any())->method('isSetOneColumnRetail')->willReturn(true);
        $exectedResult = $this->onColumn->isSetOneColumnRetail();
        $this->assertEquals(true, $exectedResult);
    }

    /**
     *  test case for IsEnhancementClass
     */
    public function testIsEnhancementClass()
    {
        $this->helper->expects($this->any())->method('isEnhancementClass')->willReturn(true);
        $exectedResult = $this->onColumn->isEnhancementClass();
        $this->assertEquals(true, $exectedResult);
    }

    /**
     *  test case for testIsRetailEnhancementClass
     */
    public function testIsRetailEnhancementClass()
    {
        $this->helper->expects($this->any())->method('isRetailEnhancementClass')->willReturn(true);
        $exectedResult = $this->onColumn->isRetailEnhancementClass();
        $this->assertEquals(true, $exectedResult);
    }

    /**
     *  test case for isSetOneColumnRetailReOrder
     */
    public function testIsSetOneColumnRetailReOrder()
    {
        $this->helper->expects($this->any())->method('isRetailOrderHistoryReorderEnabled')->willReturn(true);
        $exectedResult = $this->onColumn->isSetOneColumnRetailReOrder();
        $this->assertEquals(true, $exectedResult);
    }

    /**
     *  test case for isSetTwoColumnEproReOrder
     */
    public function testIsSetTwoColumnEproReOrder()
    {
        $this->helper->expects($this->any())->method('isCommercialReorderEnabled')->willReturn(true);
        $exectedResult = $this->onColumn->isSetTwoColumnEproReOrder();
        $this->assertEquals(true, $exectedResult);
    }
    
    /**
     *  test case for isSelfRegCompany
     *  B-1501794
     */
    public function testIsSelfRegCompany()
    {
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCompany')->willReturn(true);
        $exectedResult = $this->onColumn->isSelfRegCompany();
        $this->assertEquals(true, $exectedResult);
    }

    /**
     *  test case for isSelfRegCustomerWithFclEnabled
     */
    public function testIsSelfRegCustomerWithFclEnabled()
    {
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomerWithFclEnabled')->willReturn(true);
        $exectedResult = $this->onColumn->isSelfRegCustomerWithFclEnabled();
        $this->assertEquals(true, $exectedResult);
    }

    /**
     *  test case for getLoginType
     */
    public function testGetLoginType()
    {
        $this->accountHelper->expects($this->any())->method('getCompanyLoginType')->willReturn('sso');
        $exectedResult = $this->onColumn->getLoginType();
        $this->assertEquals('sso', $exectedResult);
    }

    /**
     * test isEproCustomer
     *
     * @return void
     */
    public function testIsEproCustomer()
    {
        $this->deliveryHelper->expects($this->once())->method('isEproCustomer')->willReturn(true);
        $exectedResult = $this->onColumn->isEproCustomer();
        
        $this->assertEquals(true, $exectedResult);
    }
}
