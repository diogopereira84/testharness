<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare (strict_types = 1);

namespace Fedex\Customer\Test\Unit\Observer;

use Fedex\Customer\Observer\CustomerSaveAfter;
use Fedex\Customer\Helper\Customer as CustomerHelper;
use Magento\Framework\App\Request\Http;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Customer;

class CustomerSaveAfterTest extends TestCase
{
    protected $customerHelper;
    protected $request;
    protected $observer;
    protected $customer;
    /**
     * @var (\Fedex\Customer\Observer\CustomerSaveAfter & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerSaveAfter;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customerSave;
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->customerHelper = $this->getMockBuilder(CustomerHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isNewIdentifierLookUpActive', 'updateExternalIdentifier'])
            ->getMock();
        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMock();
        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer'])
            ->getMock();
        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $this->customerSaveAfter = $this->getMockBuilder(CustomerSaveAfter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);

        $this->customerSave = $this->objectManager->getObject(
            CustomerSaveAfter::class,
            [
                'customerHelper' => $this->customerHelper,
                'request' => $this->request
            ]
        );
    }

    /**
     * Test Execute method
     * @return void
     */
    public function testExecute()
    {
        $postValue = [];
        $postValue['customer']['external_identifier'] = 'l6site51_neeraj_himkinfogaincom@nol6site51.com';
        $this->customerHelper->expects($this->any())
            ->method('isNewIdentifierLookUpActive')->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParams')->willReturn($postValue);
        $this->customerHelper->expects($this->any())
            ->method('updateExternalIdentifier')->willReturn(true);
        $this->observer->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getId')->willReturn(23);
        $this->customerHelper->expects($this->any())
            ->method('updateExternalIdentifier')->willReturn(true);
        $this->assertEquals($this->customerSave, $this->customerSave->execute($this->observer));
    }
}
