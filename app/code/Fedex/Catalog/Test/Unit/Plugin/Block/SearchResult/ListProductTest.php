<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Catalog\Test\Unit\Plugin\Block\SearchResult;

use Magento\Catalog\Block\Product\ListProduct as ListProductCore;
use Magento\Catalog\Model\Layer\Search;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use PHPUnit\Framework\TestCase;
use Fedex\Catalog\Plugin\Block\SearchResult\ListProduct;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ListProductTest extends TestCase
{
   
    protected $searchMock;
    protected $subject;
    protected $result;
    protected $ListProductPlugin;
    protected function setUp(): void
    {
        $this->searchMock = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getMockBuilder(ListProductCore::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLayer'])
            ->getMock();

        $this->result = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->setMethods(['clear','getSize'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->ListProductPlugin = $objectManagerHelper->getObject(ListProduct::class);
    }

   /*
   * testAfterGetLoadedProductCollection
   *
   */
    public function testAfterGetLoadedProductCollection()
    {
    	$this->subject->expects($this->any())->method('getLayer')->willReturn($this->searchMock);

    	$this->result->expects($this->any())->method('clear')->willReturnSelf();

    	$this->result->expects($this->any())->method('getSize')->willReturn(10);

        $this->assertEquals(
            $this->result,
            $this->ListProductPlugin->afterGetLoadedProductCollection($this->subject, $this->result)
        );
    }

}
