<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Block\OrderConfirmation;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Customer;
use Magento\Store\Model\Store;

class OrderConfirmationTest extends TestCase
{
    protected $scopeConfig;
    protected $storeManager;
    protected $storeMock;
    protected $customerSessionFactory;
    protected $customerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $blockData;
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionFactory = $this->getMockBuilder(SessionFactory::class)
            ->setMethods(['create', 'getCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->setMethods(['getId', 'getEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->blockData = $this->objectManager->getObject(
            OrderConfirmation::class,
            [
                'scopeConfig' => $this->scopeConfig,
                'storeManager' => $this->storeManager,
                'customerSessionFactory' => $this->customerSessionFactory
            ]
        );
    }

    public function testGetScopeConfigValue()
    {
        $isEnabled = 1;
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn($isEnabled);
        $this->assertNotNull($this->blockData->getScopeConfigValue('test'));
    }

    public function testAddCJOrderObjectScript()
    {
        $this->testgetScopeConfigValue();
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('getEmail')->willReturnSelf();

        $this->assertNotNull($this->blockData->addCJOrderObjectScript());
    }
}
