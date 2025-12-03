<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model;

use Fedex\SharedCatalogCustomization\Model\Message;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    protected $messageMock;
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->messageMock = $this->objectManager->getObject(
            Message::class,
            []
        );
    }

    /**
     * @test getMessage
     * *
     * @return string
     */
    public function testGetMessage()
    {
        $this->assertEquals(null, $this->messageMock->getMessage());
    }

    /**
     * @test setMessage
     * *
     * @return string
     */
    public function testSetMessage()
    {
        $this->assertEquals("Test", $this->messageMock->setMessage("Test"));
    }
}