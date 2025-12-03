<?php

namespace Fedex\Shipment\Test\Unit\Block\Adminhtml\Index;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Backend\Block\Widget\Context;
use Fedex\Shipment\Block\Adminhtml\Index\Index;

class IndexTest extends TestCase
{
    /**
     * @var (\Magento\Backend\Block\Widget\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    /**
     * @var object
     */
    protected $shipment;
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->shipment = $this->objectManagerHelper->getObject(Index::class);
    }

    /**
     * Empty Test Case
     */
    public function testConstruct()
    {
        // Null Test case;
    }
    
}
