<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote;

use Fedex\Cart\Model\Quote\IntegrationNote;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

class IntegrationNoteTest extends TestCase
{
    /**
     * @var IntegrationNote
     */
    private IntegrationNote $instance;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);
        $registryMock = $this->createMock(Registry::class);
        $extensionAttributesFactoryMock = $this->createMock(ExtensionAttributesFactory::class);
        $attributeValueFactoryMock = $this->createMock(AttributeValueFactory::class);
        $this->instance = new IntegrationNote(
            $contextMock,
            $registryMock,
            $extensionAttributesFactoryMock,
            $attributeValueFactoryMock,
            null,
            null
        );
    }

    public function testSetAndGetParentId(): void
    {
        $parentId = 1;
        $this->instance->setParentId($parentId);
        static::assertEquals($parentId, $this->instance->getParentId());
    }

    public function testSetAndGetNote(): void
    {
        $note = "Test Note";
        static::assertNull($this->instance->getNote());
        $this->instance->setNote($note);
        static::assertEquals($note, $this->instance->getNote());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $createdAt = "2023-01-01 00:00:00";
        static::assertNull($this->instance->getCreatedAt());
        $this->instance->setCreatedAt($createdAt);
        static::assertEquals($createdAt, $this->instance->getCreatedAt());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $updatedAt = "2023-01-02 00:00:00";
        static::assertNull($this->instance->getUpdatedAt());
        $this->instance->setUpdatedAt($updatedAt);
        static::assertEquals($updatedAt, $this->instance->getUpdatedAt());
    }

    public function testGetIdentities(): void
    {
        $result = $this->instance->getIdentities();
        static::assertIsArray($result);
    }
}
