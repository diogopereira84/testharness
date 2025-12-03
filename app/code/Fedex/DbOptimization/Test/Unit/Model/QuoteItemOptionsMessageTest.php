<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\DbOptimization\Test\Unit\Model;

use Fedex\DbOptimization\Model\QuoteItemOptionsMessage;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class QuoteItemOptionsMessageTest extends TestCase
{

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $qioMessageMock;
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->qioMessageMock = $this->objectManager->getObject(
            QuoteItemOptionsMessage::class,
            []
        );
    }

    /**
     * Test GetMessage
     *
     * @return null
     */
    public function testGetMessage()
    {
        $this->assertEquals(null, $this->qioMessageMock->getMessage());
    }
    
    /**
     * Test SetMessage
     *
     * @return null
     */
    public function testSetMessage()
    {
        $this->assertEquals(null, $this->qioMessageMock->setMessage(null));
    }
}
