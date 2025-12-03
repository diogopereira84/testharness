<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Layout\Element;
use Magento\Framework\View\Layout\Reader\Block;
use Magento\Framework\View\Layout\Reader\Context;
use Magento\LiveSearchProductListing\Model\LayoutElementsRemover;
use Fedex\LiveSearch\Model\SharedCatalogSkip;
/**
 * Plugin for layout blocks reader to change elements visibility depends on Live Search admin configuration.
 */
class BlockReader
{
    /**
     * @param LayoutElementsRemover $layoutElementsRemover
     * @param SharedCatalogSkip $sharedCatalogCheck
     * @param string[] $blocksToRemove
     */
    public function __construct(
        private LayoutElementsRemover $layoutElementsRemover,
        private SharedCatalogSkip $sharedCatalogCheck,
        private array $blocksToRemove
    )
    {
    }

    /**
     * Mark specific block as removed if configuration in admin active for specific Live Search feature
     *
     * @param Block $subject
     * @param Context $readerContext
     * @param Element $currentElement
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function beforeInterpret(Block $subject, Context $readerContext, Element $currentElement): array
    {
        //B-1877722
        if($this->sharedCatalogCheck->checkCommercialStoreWithArea())
        {
            if($this->sharedCatalogCheck->checkIsSharedCatalogPage()){
                $this->blocksToRemove = [];
            }
        }
        //B-1877722

        $this->layoutElementsRemover->removeLayoutElements(
            $currentElement,
            $this->blocksToRemove,
            \get_class($this)
        );
        return [$readerContext, $currentElement];
    }
}
