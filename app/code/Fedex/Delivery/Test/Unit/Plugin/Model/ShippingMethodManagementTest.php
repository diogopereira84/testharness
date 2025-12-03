<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Test\Unit\Plugin\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Delivery\Plugin\Model\ShippingMethodManagement;
use Magento\Framework\DataObject;
use Magento\Quote\Model\ShippingMethodManagement as QuoteShippingMethodManagement;

/**
 * ShippingMethodManagement Model plugin
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class ShippingMethodManagementTest extends \PHPUnit\Framework\TestCase
{
    protected $shippingMethodManagement;
    protected $shippingMethod;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->shippingMethodManagement = $this->getMockBuilder(QuoteShippingMethodManagement::class)
            ->setMethods(['getShipping'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->shippingMethod = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCarrierCode'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->shippingMethodManagement = $this->objectManager->getObject(
            ShippingMethodManagement::class,
            []
        );
    }

    /**
     * function afterEstimateByExtendedAddress
     */
    public function testafterEstimateByExtendedAddress()
    {
        $this->shippingMethod->expects($this->any())
        ->method('getCarrierCode')
        ->willReturn('fedexshipping');

        $this->assertNotNull(
            $this->shippingMethodManagement
            ->afterEstimateByExtendedAddress(
                $this->shippingMethodManagement,
                [$this->shippingMethod]
            )
        );
    }
}
