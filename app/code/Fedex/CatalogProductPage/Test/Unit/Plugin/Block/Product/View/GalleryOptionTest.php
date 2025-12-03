<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\CatalogProductPage\Test\Unit\Plugin\Block\Product\View;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Block\Product\View\GalleryOptions;
use Fedex\CatalogProductPage\Plugin\Block\Product\View\GalleryOption;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Fedex\Catalog\Model\Config;

class GalleryOptionTest extends TestCase
{
   /*
   * testAfterGetOptionsJson( function test case
   *
   */
    public function testAfterGetOptionsJson()
    {
        $result = '{"maxheight":"100","maxwidth":"100","thumbborderwidth":"100"}';
        $jsonSerializer = $this->createMock(Json::class);
        $catalogHelper = $this->createMock(CatalogHelper::class);
        $config = $this->createMock(Config::class);
        $subject = $this->createMock(GalleryOptions::class);
        $galleryOption = new GalleryOption($jsonSerializer, $catalogHelper, $config);

        $jsonSerializer->expects($this->once())->method('unserialize')->willReturn([]);
        $subject->expects($this->any())->method('getVar')->withConsecutive(
            ['gallery/maxheight'],
            ['gallery/maxwidth',],
            ['gallery/thumbborderwidth',]
        )->willReturnOnConsecutiveCalls(
            '100',
            '100',
            '100'
        );
        $jsonSerializer->expects($this->once())->method('serialize')->willReturn($result);

        $this->assertEquals($result, $galleryOption->afterGetOptionsJson($subject, $result));
    }
}
