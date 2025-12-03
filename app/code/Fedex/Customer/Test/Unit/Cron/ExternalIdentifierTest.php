<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare (strict_types = 1);

namespace Fedex\Customer\Test\Unit\Cron;

use Fedex\Customer\Cron\ExternalIdentifier;
use Fedex\Customer\Helper\Customer as CustomerHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ExternalIdentifierTest extends TestCase
{
    protected $customerHelper;
    protected $customer;
    protected $customerCollection;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $externalIdentifier;
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->customerHelper = $this->getMockBuilder(CustomerHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isNewIdentifierLookUpActive', 'updateExternalIdentifier'])
            ->getMock();
        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getData'])
            ->getMock();
        $this->customerCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'addAttributeToFilter', 'getSelect', 'where',
             'setPageSize', 'setCurPage','getIterator'])
            ->getMock();
        $this->objectManager = new ObjectManager($this);

        $this->externalIdentifier = $this->objectManager->getObject(
            ExternalIdentifier::class,
            [
                'customerHelper' => $this->customerHelper,
                'customer' => $this->customer,
            ]
        );
    }

    /**
     * Test Execute method
     * @return void
     */
    public function testExecute()
    {
        $this->customerHelper->expects($this->any())
            ->method('isNewIdentifierLookUpActive')->willReturn(true);
        $this->customer->expects($this->any())
            ->method('getCollection')->willReturn($this->customerCollection);
        $this->customerCollection->expects($this->any())
            ->method('addFieldToFilter')->willReturnSelf();
        $this->customerCollection->expects($this->any())
            ->method('addAttributeToFilter')->willReturnSelf();
        $this->customerCollection->expects($this->any())
            ->method('getSelect')->willReturnSelf();
        $this->customerCollection->expects($this->any())
            ->method('where')->willReturnSelf();
        $this->customerCollection->expects($this->any())
            ->method('setPageSize')->willReturnSelf();
        $this->customerCollection->expects($this->any())
            ->method('setCurPage')->willReturnSelf();
        $this->customerCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->customer]));
        $this->customer->expects($this->any())
            ->method('getData')->with('external_identifier')
            ->willReturn('l6site51_neeraj_himkinfogaincom@nol6site51.com');
        $this->customerHelper->expects($this->any())
            ->method('updateExternalIdentifier')->willReturn(true);
        $this->assertEquals(null, $this->externalIdentifier->execute());
    }
}
