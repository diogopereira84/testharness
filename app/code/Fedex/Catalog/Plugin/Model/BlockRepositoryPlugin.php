<?php

namespace Fedex\Catalog\Plugin\Model;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Cms block repository plugin
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BlockRepositoryPlugin
{
    /**
     * BlockRepositoryPlugin constructor.
     * @param DomDocumentFactory $domFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        public DomDocumentFactory $domFactory,
        public ProductRepositoryInterface $productRepository
    )
    {
    }

    /**
     * After save cms block plugin to find product sku and create hidden field.
     *
     * @param BlockRepositoryInterface $subject
     * @param BlockInterface $block
     * @return BlockInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(BlockRepositoryInterface $subject, BlockInterface $block): BlockInterface
    {
        $blockContent = $block->getContent();
        $skus = [];
        $matches2 = [];

        // Shop by Type Blocks
        $pattern1 = '/condition_option_value="(.*?)"/i';
        preg_match_all($pattern1, $blockContent, $matches1);

        if (count($matches1[1]) > 0) {
            foreach ($matches1[1] as $sku) {
                $skus[] = $sku;
            }
        }

        // Product Pricing Blocks
        $pattern2 = "/id_path='product\/(.*?)'/i";
        preg_match_all($pattern2, $blockContent, $matches2);

        if (count($matches2[1]) > 0) {
            foreach ($matches2[1] as $productId) {
                try {
                    $product = $this->productRepository->getById($productId);
                    if ($product) {
                        $skus[] = $product->getSku();
                    }
                } catch (\Exception $exception) {
                    //do nothing
                }
            }
        }
        $pageSkus = implode(",", array_unique($skus));

        // Store skus in hidden element on the block
        $pattern3 = '/name="breadcrumb-skus" value="(.*?)"/';
        if (preg_match($pattern3, $blockContent)) {
            $replace = 'name="breadcrumb-skus" value="' . $pageSkus . '"';
            $blockContent = preg_replace($pattern3, $replace, $blockContent);
        } elseif (!empty($pageSkus)) {
            $hiddenField = ' <div data-content-type="row" data-appearance="contained"
                 data-element="main" style="display: none;">
            <div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" 
            data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" 
            data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" 
            style="justify-content: flex-start; display: flex; flex-direction: column; 
            background-position: left top; background-size: cover; background-repeat: no-repeat; 
            background-attachment: scroll; border-style: none; border-width: 1px; 
            border-radius: 0px; margin: 0px 0px 10px; padding: 10px;"><div data-content-type="html" 
            data-appearance="default" data-element="main" style="border-style: none; border-width: 1px; 
            border-radius: 0px; margin: 0px; padding: 0px;">
            &lt;input type="hidden" 
            id="breadcrumb-skus" name="breadcrumb-skus" value="' . $pageSkus . '" /&gt;</div></div></div>';
            $blockContent .= $hiddenField;
        }

        $block->setContent($blockContent);
        $block->save();

        return $block;
    }
}
