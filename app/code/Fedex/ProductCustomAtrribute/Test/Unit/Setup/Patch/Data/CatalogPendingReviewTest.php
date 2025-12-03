<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Manish Chaubey
 * @email      manish.chaubey.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\ProductCustomAtrribute\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Fedex\ProductCustomAtrribute\Setup\Patch\Data\CatalogPendingReview;
 
class CatalogPendingReviewTest extends TestCase
{

    protected $catalogPendingReview;
    protected $moduleDataSetupInterfaceMock;
    protected $eavSetupFactoryMock;
    protected $dltThresholdMock;
 
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
                'addAttribute',
                'getEntityTypeId',
                'getAttributeSetId',
                'getAttributeGroupId',
                'addAttributeToGroup',
                'addAttributeSet'
            ]
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogPendingReview = $this->getMockForAbstractClass(
            CatalogPendingReview::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupInterfaceMock,
                'eavSetupFactory' => $this->eavSetupFactoryMock,
            ]
        );
    }

    /**
     * Test apply function
     */
    public function testapply()
    {
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('create')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('addAttribute')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getEntityTypeId')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getAttributeSetId')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getAttributeGroupId')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('addAttributeToGroup')
        ->willReturnSelf();
        $this->assertEquals(null,$this->catalogPendingReview->apply());
    }

    /**
     * Test apply function
     */
    public function testapplyWithException()
    {
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('create')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('addAttribute')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getEntityTypeId')
        ->willReturnSelf();
        $exception = '';
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getAttributeSetId')
        ->willThrowException(new \Exception($exception));
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getAttributeGroupId')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('addAttributeToGroup')
        ->willReturnSelf();
        $this->assertEquals(null,$this->catalogPendingReview->apply());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->catalogPendingReview->getAliases());
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $this->assertEquals([], $this->catalogPendingReview->getDependencies());
    }
}
