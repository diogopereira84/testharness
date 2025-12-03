<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Catalog\Test\Unit\Plugin\Model;

use PHPUnit\Framework\TestCase;
use Fedex\Catalog\Plugin\Model\BlockRepositoryPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class BlockRepositoryPluginTest extends TestCase
{
   
    /**
     * @var (\Magento\Framework\DomDocument\DomDocumentFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $domFactoryMock;
    protected $productRepositoryMock;
    protected $subject;
    protected $block;
    protected $isDataObjectMock;
    protected $BlockRepositoryPlugin;
    protected function setUp(): void
    {
        $this->domFactoryMock = $this->getMockBuilder(DomDocumentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->subject = $this->getMockBuilder(BlockRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->block = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent','setContent','save'])
            ->getMockForAbstractClass();

        $this->isDataObjectMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getSku'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->BlockRepositoryPlugin = $objectManagerHelper->getObject(
            BlockRepositoryPlugin::class,
            [
                'domFactory' => $this->domFactoryMock,
                'productRepository' => $this->productRepositoryMock
            ]
        );
    }

   /*
   * testAfterSave
   *
   */
    public function testAfterSave()
    {
    	$content = "<div id_path='product/123' condition_option_value='1534436209752-2,1592421958159-4' name='breadcrumb-skus' value='1534434635598-4'></div>";

        $this->block->expects($this->any())->method('getContent')->willReturn($content);

        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->isDataObjectMock);

        $this->isDataObjectMock->expects($this->any())
            ->method('getSku')
            ->willReturn('1534434635598-4');

        $this->assertEquals(
            $this->block,
            $this->BlockRepositoryPlugin->afterSave($this->subject, $this->block)
        );
    }

   /*
   * testAfterSavewithCondition
   *
   */
    public function testAfterSavewithCondition()
    {
    	$content = '<div condition_option_value="1534436209752-2,1592421958159-4" name="breadcrumb-skus" value="1534434635598-4"></div>';

        $this->block->expects($this->any())->method('getContent')->willReturn($content);

        $this->assertEquals(
            $this->block,
            $this->BlockRepositoryPlugin->afterSave($this->subject, $this->block)
        );
    }

   /*
   * testAfterSavewithException
   *
   */
    public function testAfterSavewithException()
    {
    	$content = "<div id_path='product/123' condition_option_value='1534436209752-2,1592421958159-4' name='breadcrumb-skus' value='1534434635598-4'></div>";

        $this->block->expects($this->any())->method('getContent')->willReturn($content);

        $phrase = new Phrase(__('Exception message'));

        $exception = new LocalizedException($phrase);

        $this->productRepositoryMock->expects($this->any())
            ->method('getById')
            ->willThrowException($exception);


        $this->assertEquals(
            $this->block,
            $this->BlockRepositoryPlugin->afterSave($this->subject, $this->block)
        );
    }

}
