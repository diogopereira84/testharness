<?php

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AttributeSet as AttributeSetCore;
use Fedex\CatalogMvp\Plugin\AttributeSet;
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Setup\EavSetup;

class AttributeSetTest extends TestCase
{
    protected $helperMock;
    protected $request;
    protected $eavSetup;
    protected $attributeSetCore;
    protected $attributeSet;
    protected function setUp(): void
    {
        $this->helperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpCtcAdminEnable', 'isProductAdminRefreshToggle'])
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->eavSetup = $this->getMockBuilder(EavSetup::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId'])
            ->getMock();
        $this->attributeSetCore = $this->getMockBuilder(AttributeSetCore::class)
        ->disableOriginalConstructor()
        ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->attributeSet = $objectManagerHelper->getObject(
            AttributeSet::class,
            [
                'helper' => $this->helperMock,
                'request' => $this->request,
                'eavSetup' => $this->eavSetup
            ]
        );
    }

    public function testAfterModifyMetaForIf()
    {
        $resParams = [];
        $resParams['product-details']['children']['container_external_prod']['arguments']['data']['config'] = [];
        $this->helperMock->expects($this->any())
            ->method('isMvpCtcAdminEnable')
            ->willReturn(true);
        $this->helperMock->expects($this->any())
            ->method('isProductAdminRefreshToggle')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturn(12);
        $this->eavSetup->expects($this->any())
            ->method('getAttributeSetId')
            ->willReturn(12);
        $this->assertNotNull($this->attributeSet->afterModifyMeta($this->attributeSetCore, $resParams));
    }
}
