<?php

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AttributeSet as AttributeSetCore;
use Fedex\CatalogMvp\Plugin\AdaptUpdateStockStatusBySkuPlugin;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

class AdaptUpdateStockStatusBySkuPluginTest extends TestCase
{
    protected $StockItemInterfaceMock;
    protected $StockRegistryInterfaceMock;
    /**
     * @var (\Magento\CatalogInventory\Model\Stock & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $StockMock;
    protected $SetDataToLegacyStockStatusMock;
    protected $GetProductTypesBySkusInterfaceMock;
    /**
     * @var (\Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $CacheStorageMock;
    protected $GetStockItemConfigurationInterfaceMock;
    protected $IsSourceItemManagementAllowedForProductTypeInterfaceMock;
    protected $StockItemConfigurationInterfaceMock;
    protected $AdaptUpdateStockStatusBySkuPlugin;
    protected function setUp(): void
    {
        $this->StockItemInterfaceMock = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQty','getIsInStock'])
            ->getMockForAbstractClass();

            $this->StockRegistryInterfaceMock = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpCtcAdminEnable'])
            ->getMockForAbstractClass();

            $this->StockMock = $this->getMockBuilder(Stock::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpCtcAdminEnable'])
            ->getMock();

            $this->SetDataToLegacyStockStatusMock = $this->getMockBuilder(SetDataToLegacyStockStatus::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

            $this->GetProductTypesBySkusInterfaceMock = $this->getMockBuilder(GetProductTypesBySkusInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMockForAbstractClass();

            $this->CacheStorageMock = $this->getMockBuilder(CacheStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpCtcAdminEnable'])
            ->getMock();

            $this->GetStockItemConfigurationInterfaceMock = $this->getMockBuilder(GetStockItemConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['stockItemConfiguration','isManageStock','isUseConfigManageStock','execute'])
            ->getMockForAbstractClass();

            $this->IsSourceItemManagementAllowedForProductTypeInterfaceMock = $this->getMockBuilder(IsSourceItemManagementAllowedForProductTypeInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();

            $this->StockItemConfigurationInterfaceMock = $this->getMockBuilder(\Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();
        
        $objectManagerHelper = new ObjectManager($this);
        $this->AdaptUpdateStockStatusBySkuPlugin = $objectManagerHelper->getObject(
            AdaptUpdateStockStatusBySkuPlugin::class,
            [
                'setDataToLegacyStockStatus' => $this->SetDataToLegacyStockStatusMock,
                'getProductTypesBySkus' => $this->GetProductTypesBySkusInterfaceMock,
                'isSourceItemManagementAllowedForProductType' => $this->IsSourceItemManagementAllowedForProductTypeInterfaceMock,
                'getStockItemConfiguration' =>$this->GetStockItemConfigurationInterfaceMock,
               'legacyStockItemCacheStorage' => $this->CacheStorageMock
            ]
        );
    }

    public function testafterUpdateStockItemBySkuIf()
    {

        $this->GetProductTypesBySkusInterfaceMock->expects($this->any())->method('delete')->willReturnSelf();   

       $this->GetStockItemConfigurationInterfaceMock->expects($this->any())->method('execute')->willReturn($this->StockItemConfigurationInterfaceMock);
       $this->GetStockItemConfigurationInterfaceMock->expects($this->any())->method('isManageStock')->willReturn(false);
       $this->GetStockItemConfigurationInterfaceMock->expects($this->any())->method('isUseConfigManageStock')->willReturn(false);
       $this->SetDataToLegacyStockStatusMock->expects($this->any())->method('execute')->willReturnSelf();

        $this->assertNull($this->AdaptUpdateStockStatusBySkuPlugin->afterUpdateStockItemBySku($this->StockRegistryInterfaceMock, null,'1234567890',$this->StockItemInterfaceMock));
    }

    public function testafterUpdateStockItemBySkuelse()
    {
        $this->GetProductTypesBySkusInterfaceMock->expects($this->any())->method('delete')->willReturnSelf();         
      $this->GetStockItemConfigurationInterfaceMock->expects($this->any())->method('execute')->willReturn($this->StockItemConfigurationInterfaceMock);    
       $this->GetStockItemConfigurationInterfaceMock->expects($this->any())->method('isManageStock')->willReturn(null);
       $this->GetStockItemConfigurationInterfaceMock->expects($this->any())->method('isUseConfigManageStock')->willReturn(null);
       $this->IsSourceItemManagementAllowedForProductTypeInterfaceMock->expects($this->any())->method('execute')->willReturnSelf();
       $this->StockItemInterfaceMock->expects($this->any())->method('getQty')->willReturn(12);
       $this->SetDataToLegacyStockStatusMock->expects($this->any())->method('execute')->willReturnSelf();


        $this->assertNull($this->AdaptUpdateStockStatusBySkuPlugin->afterUpdateStockItemBySku($this->StockRegistryInterfaceMock, null,'1234567890',$this->StockItemInterfaceMock));
    }
}
