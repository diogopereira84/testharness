<?php
declare(strict_types=1);

use Fedex\CatalogMvp\Plugin\PriceBoxTagsPlugin;
use Magento\Catalog\Block\Category\Plugin\PriceBoxTags;
use Magento\Framework\Pricing\Render\PriceBox;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class PriceBoxTagsPluginTest extends TestCase
{
    protected  PriceBoxTagsPlugin $boxTagsPlugin;
    protected  PriceBox $priceBox;
    protected PriceBoxTags $priceBoxTags;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->boxTagsPlugin = $objectManagerHelper->getObject(
            PriceBoxTagsPlugin::class
        );
        $this->priceBox = $objectManagerHelper->getObject(
            PriceBox::class
        );
        $this->priceBoxTags = $objectManagerHelper->getObject(
            PriceBoxTags::class
        );
    }

    public function testAfterAfterGetCacheKey()
    {
        $this->assertIsString(
            $this->boxTagsPlugin->aroundGetCacheKey(
                $this->priceBox,
                function () {
                    return '';
                }
            )
        );
    }

    public function testisFixForcedPriceCacheToggleEnabled()
    {
        $this->assertIsBool(
            $this->boxTagsPlugin->isFixForcedPriceCacheToggleEnabled()
        );
    }

}
