<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\ProductCustomAtrribute\Test\Unit\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Eav\Api\AttributeRepositoryInterface;
use \Fedex\ProductCustomAtrribute\Setup\Patch\Data\InsertProductAdditionalFields;
use PHPUnit\Framework\TestCase;

/**
 * Test class InsertProductAdditionalFieldsTest
 */
class InsertProductAdditionalFieldsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    const CREATE = 'create';

    const GETATTRIBUTEID = 'getAttributeId';
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var InsertProductAdditionalFields
     */
    private $insertProductAdditionalFields;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods([self::CREATE, 'addAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeRepository = $this->getMockBuilder(AttributeRepositoryInterface::class)
            ->setMethods(['get', 'getList', 'save', 'delete', 'deleteById', self::GETATTRIBUTEID])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->insertProductAdditionalFields = $this->objectManager->getObject(
            InsertProductAdditionalFields::class,
            [
                'eavSetupFactory' => $this->eavSetupFactory,
                'attributeRepository' => $this->attributeRepository
            ]
        );
    }

    /**
     * Test apply function
     *
     * @return void
     */
    public function testApply()
    {
        $attribute = $this->getMockBuilder(\Magento\Eav\Api\Data\AttributeInterface::class)
            ->setMethods(['save', self::GETATTRIBUTEID])
            ->getMockForAbstractClass();
        $attribute->expects($this->any())->method(self::GETATTRIBUTEID)->willReturn("1");
        $attribute->method('save')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method(self::CREATE)->willReturnSelf();
        $this->attributeRepository->expects($this->any())->method('get')->willReturn($attribute);
        $this->assertEquals(true, $this->insertProductAdditionalFields->apply());
    }

    /**
     * Test apply function with exception
     *
     * @return void
     */
    public function testApplyWithException()
    {
        $this->eavSetupFactory->expects($this->any())->method(self::CREATE)->willReturnSelf();
        $this->attributeRepository->expects($this->any())->method('get')->willReturnSelf();
        $this->attributeRepository->expects($this->any())->method(self::GETATTRIBUTEID)->willReturn("");
        $this->assertEquals(null, $this->insertProductAdditionalFields->apply());
    }

    /**
     * Test getAliases function
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->insertProductAdditionalFields->getAliases());
    }

    /**
     * Test getDependencies function
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->insertProductAdditionalFields->getDependencies());
    }
}
