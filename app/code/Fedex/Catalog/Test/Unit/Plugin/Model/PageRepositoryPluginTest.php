<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Catalog\Test\Unit\Plugin\Model;

use PHPUnit\Framework\TestCase;
use Fedex\Catalog\Plugin\Model\PageRepositoryPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Fedex\Catalog\Helper\Breadcrumbs as BreadcrumbsHelper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DomDocument\DomDocumentFactory;

class PageRepositoryPluginTest extends TestCase
{
   
    /**
     * @var (\Magento\Framework\DomDocument\DomDocumentFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $domFactoryMock;
    protected $breadCrumbHelperMock;
    protected $subject;
    /**
     * @var (\Magento\Framework\App\Cache\TypeListInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cacheTypeListMock;
    protected $blockRepositoryMock;
    protected $isDataObjectMock;
    protected $page;
    protected $PageRepositoryPlugin;
    protected function setUp(): void
    {
        $this->domFactoryMock = $this->getMockBuilder(DomDocumentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->breadCrumbHelperMock = $this->getMockBuilder(BreadcrumbsHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControlJson','setControlJson'])
            ->getMock();

        $this->subject = $this->getMockBuilder(PageRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cacheTypeListMock = $this->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->blockRepositoryMock = $this->getMockBuilder(BlockRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent','getById'])
            ->getMockForAbstractClass();

        $this->isDataObjectMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->page = $this->getMockBuilder(PageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent','getIdentifier','getTitle'])
            ->getMockForAbstractClass();


        $objectManagerHelper = new ObjectManager($this);

        $this->PageRepositoryPlugin = $objectManagerHelper->getObject(
            PageRepositoryPlugin::class,
            [
                'helper' => $this->breadCrumbHelperMock,
                'cacheTypeList' => $this->cacheTypeListMock,
                'blockRepository' => $this->blockRepositoryMock,
                'domFactory' => $this->domFactoryMock
            ]
        );
    }

   /*
   * testAfterSave
   *
   */
    public function testAfterSave()
    {
    	$content = '<div block_id="#Custom-Breadcrumb" name="breadcrumb-skus" value="1534434635598-4"></div>';

        $this->page->expects($this->any())->method('getContent')->willReturn($content);

        $control = [['label'=>'Nk Template Test','url'=>'nk-template-test','skus'=>'1614105200640-4,1593103993699-4,1594830761054-4,1534434635598-4,1534436209752-2,1592421958159-4']];

        $controlJson = json_encode($control);

        $this->breadCrumbHelperMock->expects($this->any())
            ->method('getControlJson')
            ->willReturn($controlJson);

        $this->page->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('nk-template-test');

        $this->page->expects($this->any())
            ->method('getTitle')
            ->willReturn('Nk Template Test');

        $this->blockRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->isDataObjectMock);

        $this->isDataObjectMock->expects($this->any())
            ->method('getContent')
            ->willReturn($content);

        $this->assertEquals(
            $this->page,
            $this->PageRepositoryPlugin->afterSave($this->subject, $this->page)
        );
    }

  /*
   * testAfterSavewithElse
   *
   */
    public function testAfterSavewithElse()
    {
        $content = '<div block_id="#Custom-Breadcrumb" name="breadcrumb-skus" value="1534434635598-4"></div>';

        $this->page->expects($this->any())->method('getContent')->willReturn($content);

        $control = [['label'=>'Nk Template Test','url'=>'nk-template-test','skus'=>'1614105200640-4,1593103993699-4,1594830761054-4,1534434635598-4,1534436209752-2,1592421958159-4']];

        $controlJson = json_encode($control);

        $this->breadCrumbHelperMock->expects($this->any())
            ->method('getControlJson')
            ->willReturn($controlJson);

        $this->page->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('nk-template-test');

        $this->page->expects($this->any())
            ->method('getTitle')
            ->willReturn('Nk Template Tes');

        $this->blockRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->isDataObjectMock);

        $this->isDataObjectMock->expects($this->any())
            ->method('getContent')
            ->willReturn($content);

        $this->assertEquals(
            $this->page,
            $this->PageRepositoryPlugin->afterSave($this->subject, $this->page)
        );
    }

   /*
   * testAfterSavewithElseIdentifier
   *
   */
    public function testAfterSavewithElseIdentifier()
    {
        $content = '<div block_id="#Custom-Breadcrumb" name="breadcrumb-skus" value="1534434635598-4"></div>';

        $this->page->expects($this->any())->method('getContent')->willReturn($content);

        $control = [['label'=>'Nk Template Test','url'=>'nk-template-test','skus'=>'1614105200640-4,1593103993699-4,1594830761054-4,1534434635598-4,1534436209752-2,1592421958159-4']];

        $controlJson = json_encode($control);

        $this->breadCrumbHelperMock->expects($this->any())
            ->method('getControlJson')
            ->willReturn($controlJson);

        $this->page->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('nk-template-tes');

        $this->page->expects($this->any())
            ->method('getTitle')
            ->willReturn('Nk Template Test');

        $this->blockRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->isDataObjectMock);

        $this->isDataObjectMock->expects($this->any())
            ->method('getContent')
            ->willReturn($content);

        $this->assertEquals(
            $this->page,
            $this->PageRepositoryPlugin->afterSave($this->subject, $this->page)
        );
    }

    /*
   * testAfterSavewithElseIdentifier
   *
   */
    public function testAfterSavewithoutBlock()
    {
        $content = '<div block_id="#Custom-Breadcrumb"></div>';

        $this->page->expects($this->any())->method('getContent')->willReturn($content);

        $control = [['label'=>'Nk Template Test','url'=>'nk-template-test','skus'=>'1614105200640-4,1593103993699-4,1594830761054-4,1534434635598-4,1534436209752-2,1592421958159-4']];

        $controlJson = json_encode($control);

        $this->breadCrumbHelperMock->expects($this->any())
            ->method('getControlJson')
            ->willReturn($controlJson);

        $this->page->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('nk-template-tes');

        $this->page->expects($this->any())
            ->method('getTitle')
            ->willReturn('Nk Template Test');

        $this->blockRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->isDataObjectMock);

        $this->isDataObjectMock->expects($this->any())
            ->method('getContent')
            ->willReturn($content);

        $this->assertEquals(
            $this->page,
            $this->PageRepositoryPlugin->afterSave($this->subject, $this->page)
        );
    }

}
