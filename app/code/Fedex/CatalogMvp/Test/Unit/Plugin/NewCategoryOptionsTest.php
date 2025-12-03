<?php

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Plugin\NewCategoryOptions;
use Magento\Backend\Model\Session as AdminSession;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Ui\Component\Product\Form\Categories\Options;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;

class NewCategoryOptionsTest extends TestCase
{
    protected $newCategoryOptions;
    protected $objectManager;
    protected $categoriesInstance;
    protected $categoryCollectionFactory;
    protected $categoryCollection;
    protected $options;
    protected $catalogMvp;
    protected $categoryModel;
    protected $locatorInterface;
    protected $adminSession;
    protected $product;
    protected $storeInterface;

    protected function setUp(): void
    {

        $this->categoryCollectionFactory = $this->getMockBuilder(CategoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->categoryCollection = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToSelect', 'addAttributeToFilter', 'setStoreId','getIterator'])
            ->getMock();
        $this->options = $this->getMockBuilder(Options::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();
        $this->catalogMvp = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpCtcAdminEnable', 'getAttributeSetName',
                'getRootCategoryFromStore', 'getScopeConfigValue', 'getSubCategoryByParentID','isProductPodEditAbleById'])
            ->getMock();

        $this->categoryModel = $this->getMockBuilder(CategoryModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath','getParentId','getId','getIsActive','getName'])
            ->getMock();

        $this->locatorInterface = $this->getMockBuilder(LocatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->storeInterface = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->adminSession = $this->getMockBuilder(AdminSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'unsetAttributeSetId',
                'getProductId', 'unsetProductId'])
            ->getMock();
        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getData'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->newCategoryOptions = $objectManagerHelper->getObject(
            NewCategoryOptions::class,
            [
                'helper' => $this->catalogMvp,
                'categoryCollectionFactory' => $this->categoryCollectionFactory,
                'locator' => $this->locatorInterface,
                'adminSession' => $this->adminSession,
                'product' => $this->product,
            ]
        );
    }
    /**
     * Test Case for aftertoOptionArray
     */
    public function testAftertoOptionArray()
    {
        $toggle = true;
        $attributeSetName = "PrintOnDemand";
        $podEditable = true;
        $this->toOptionArray($toggle, $attributeSetName, $podEditable);
        $this->assertIsArray($this->newCategoryOptions->aftertoOptionArray($this->options, []));
    }
    /**
     * Test Case for aftertoOptionArray With Toggle Off
     */
    public function testAftertoOptionArrayWithToggleOff()
    {
        $toggle = false;
        $attributeSetName = "Test";
        $podEditable = true;
        $this->toOptionArray($toggle, $attributeSetName, $podEditable);
        $this->assertIsArray($this->newCategoryOptions->aftertoOptionArray($this->options, []));
    }
    /**
     * Test Case for aftertoOptionArray With Different Attribute Set name
     */
    public function testAftertoOptionArrayWithPodEditableFalse()
    {
        $toggle = true;
        $attributeSetName = "PrintOnDemand";
        $podEditable = false;
        $this->toOptionArray($toggle, $attributeSetName, $podEditable);
        $this->assertIsArray($this->newCategoryOptions->aftertoOptionArray($this->options, []));
    }

    /**
     * Test Case for aftertoOptionArray With POD Editable False
     */
    public function testAftertoOptionArrayWithAttributename()
    {
        $toggle = true;
        $attributeSetName = "Test";
        $podEditable = true;
        $this->toOptionArray($toggle, $attributeSetName, $podEditable);
        $this->assertIsArray($this->newCategoryOptions->aftertoOptionArray($this->options, []));
    }

    public function toOptionArray($toggle, $attributeSetName, $podEditable)
    {
        $this->catalogMvp->expects($this->any())->method('isMvpCtcAdminEnable')->willReturn($toggle);
        $this->adminSession->expects($this->any())->method('getAttributeSetId')->willReturn(12);
        $this->adminSession->expects($this->any())->method('unsetAttributeSetId')->willReturnSelf();
        $this->catalogMvp->expects($this->any())->method('getAttributeSetName')->willReturn($attributeSetName);
        $this->adminSession->expects($this->any())->method('getProductId')->willReturn(1245);
        $this->adminSession->expects($this->any())->method('unsetProductId')->willReturnSelf();
        $this->locatorInterface->expects($this->any())->method('getStore')->willReturn($this->storeInterface);
        $this->storeInterface->expects($this->any())->method('getId')->willReturn(2);
        $this->catalogMvp->expects($this->any())->method('getRootCategoryFromStore')->willReturn(29);
        $this->catalogMvp->expects($this->any())->method('getScopeConfigValue')->willReturn(4);
        $this->catalogMvp->expects($this->any())->method('getSubCategoryByParentID')->willReturn([4, 45, 67]);
        $this->catalogMvp->expects($this->any())->method('isProductPodEditAbleById')->willReturn($podEditable);
        $this->categoryCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->categoryCollection);
        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->categoryCollection->expects($this->any())->method('setStoreId')
            ->willReturnSelf();
        $categortIterator = new \ArrayIterator([0 => $this->categoryModel]);
        $this->categoryCollection->expects($this->any())->method('getIterator')->willReturn($categortIterator);
        $this->categoryModel->expects($this->any())->method('getPath')->willReturn('0/1/34');
        $this->categoryModel->expects($this->any())->method('getId')->willReturn('23');
        $this->categoryModel->expects($this->any())->method('getParentId')->willReturn('3');
        $this->categoryModel->expects($this->any())->method('getName')->willReturn('Print Products');
        $this->categoryModel->expects($this->any())->method('getIsActive')->willReturn(true);

    }
}
