<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\DbOptimization\Test\Unit\Model;

use Fedex\DbOptimization\Model\SalesOrderItemMessage;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SalesOrderItemMessageTest extends TestCase
{

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $soiMessageMock;
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->soiMessageMock = $this->objectManager->getObject(
            SalesOrderItemMessage::class,
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
        $this->assertEquals(null, $this->soiMessageMock->getMessage());
    }
    
    /**
     * Test SetMessage
     *
     * @return null
     */
    public function testSetMessage()
    {
        $this->assertEquals(null, $this->soiMessageMock->setMessage(null));
    }
}
