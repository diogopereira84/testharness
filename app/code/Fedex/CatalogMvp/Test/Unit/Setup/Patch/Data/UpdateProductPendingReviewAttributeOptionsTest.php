<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Pooja Prakash Tiwari <pooja.tiwari@osv.com>
 */
declare (strict_types = 1);

use Fedex\CatalogMvp\Setup\Patch\Data\UpdateProductPendingReviewAttributeOptions;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateProductPendingReviewAttributeOptionsTest extends TestCase
{

    /**
     * @var (\Magento\Framework\Setup\ModuleDataSetupInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $moduleDataSetupMock;
    protected $eavSetupFactoryMock;
    protected $loggerMock;
    protected $updateProductPendingReviewAttributeOptionsMock;
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->eavSetupFactoryMock = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['create', 'getAttributeId', 'addAttributeOption'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->updateProductPendingReviewAttributeOptionsMock = $objectManagerHelper->getObject(
            UpdateProductPendingReviewAttributeOptions::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupMock,
                'eavSetupFactory' => $this->eavSetupFactoryMock,
                'logger' => $this->loggerMock,
            ]
        );
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $expectedDependencies = [
            \Fedex\CatalogMvp\Setup\Patch\Data\CatalogPendingReviewAttribute::class,
        ];
        $this->assertEquals($expectedDependencies, $this->updateProductPendingReviewAttributeOptionsMock->getDependencies());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->updateProductPendingReviewAttributeOptionsMock->getAliases());
    }

    /**
     * Test apply function
     */
    public function testapply()
    {
        $this->eavSetupFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())->method('getAttributeId')->willReturnSelf();

        $this->assertEquals(null, $this->updateProductPendingReviewAttributeOptionsMock->apply());
    }
    /**
     * Test apply function
     */
    public function testapplyWithException()
    {
        $exceptionMessage = 'Test exception';
        $this->eavSetupFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())->method('getAttributeId')->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->once())
            ->method('addAttributeOption')
            ->willThrowException(new \Exception($exceptionMessage));
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exceptionMessage);
        $this->assertEquals(null, $this->updateProductPendingReviewAttributeOptionsMock->apply());
    }
}
