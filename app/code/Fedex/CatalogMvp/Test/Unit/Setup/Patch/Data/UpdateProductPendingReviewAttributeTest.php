<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Ayush Anand
 * @email      ayush.anand.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Setup\Patch\Data\UpdateProductPendingReviewAttribute;
 
class UpdateProductPendingReviewAttributeTest extends TestCase
{
    protected $pendingReviewAttributeMock;
    protected $moduleDataSetupInterfaceMock;
    protected $eavSetupFactoryMock;
    protected $loggerMock;
 
   /**
     * Test setup
    */
    public function setUp(): void
    {
        $this->moduleDataSetupInterfaceMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupFactoryMock = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(
                [
                    'create',
                    'updateAttribute',
                    'removeAttribute',
                    'getEntityTypeId',
                    'getAttributeId',
                    'getAttributeSetId',
                    'getAttributeGroupId',
                    'addAttributeToGroup',
                    'addAttributeSet'
                ]
            )->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

        $this->pendingReviewAttributeMock = $this->getMockForAbstractClass(
            UpdateProductPendingReviewAttribute::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupInterfaceMock,
                'eavSetupFactory' => $this->eavSetupFactoryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test apply function
     */
    public function testapply()
    {
        $this->eavSetupFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())->method('getAttributeId')->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())->method('updateAttribute')->willReturnSelf();

        $this->assertEquals(null, $this->pendingReviewAttributeMock->apply());
    }

    /**
     * Test apply function
     */
    public function testapplyWithException()
    {
        $exception = '';
        $this->eavSetupFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())->method('getAttributeId')->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())->method('updateAttribute')
        ->willThrowException(new \Exception($exception));

        $this->assertEquals(null, $this->pendingReviewAttributeMock->apply());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->pendingReviewAttributeMock->getAliases());
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $this->assertEquals([], $this->pendingReviewAttributeMock->getDependencies());
    }
}
