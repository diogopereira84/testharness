<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Test\Unit\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Shipment\Model\Carrier\FedexTracker;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Fedex\Shipment\Model\Carrier\FedexTracker
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class FedexTrackerTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scope;

    /**
     * @var ObjectManagerHelper|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var FedexTracker|MockObject
     */
    protected $fedexTracker;
    
    protected function setUp(): void
    {
        $this->scope = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'isSetFlag'])
            ->getMockForAbstractClass();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->fedexTracker = $this->objectManagerHelper->getObject(
            FedexTracker::class,
            [
                'scope' => $this->scope
            ]
        );
    }

    /**
     * Test testGetAllowedMethods method
     */
    public function testGetAllowedMethods()
    {
        $this->assertNotNull($this->fedexTracker->getAllowedMethods());
    }
}
