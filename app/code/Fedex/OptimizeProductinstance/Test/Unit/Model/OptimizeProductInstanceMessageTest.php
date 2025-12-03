<?php

namespace Fedex\OptimizeProductinstance\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\OptimizeProductinstance\Model\OptimizeProductInstanceMessage;

class OptimizeProductInstanceMessageTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $optimizeProductInstanceMessage;
    public function setUp():void
    {
        $this->objectManager = new ObjectManager($this);

        $this->optimizeProductInstanceMessage = $this->objectManager->getObject(
            OptimizeProductInstanceMessage::class,
            []
        );
    }

    public function testGetMessage()
    {
        $this->assertEquals(null, $this->optimizeProductInstanceMessage->getMessage());
    }

    public function testSetMessage()
    {
        $this->assertEquals('Some Text', $this->optimizeProductInstanceMessage->setMessage('Some Text'));
    }
}