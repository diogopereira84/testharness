<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\CIDPSG\Model\Message;

/**
 * Test class for Fedex\CIDPSG\Model\Message
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
