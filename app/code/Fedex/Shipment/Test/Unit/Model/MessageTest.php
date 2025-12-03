<?php

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Shipment\Model\Message;

/**
 * Test class for Fedex\Shipment\Model\Message
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class MessageTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager |MockObject */
    protected $objectManagerHelper;

    /** @var Message |MockObject */
    protected $message;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->message = $this->objectManagerHelper->getObject(
            Message::class
        );
    }

    /**
     * Test testGetMessage
     */
    public function testGetMessage()
    {
        $this->assertEquals(null, $this->message->getMessage());
    }

    /**
     * Test testSetMessage
     */
    public function testSetMessage()
    {
        $this->assertEquals("Test", $this->message->setMessage("Test"));
    }
}
