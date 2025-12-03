<?php
/**
 * @category  Fedex
 * @package   Fedex_LiveSearch
 * @author    Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Layout\Element;
use Magento\Framework\View\Layout\Reader\Container;
use Magento\Framework\View\Layout\Reader\Context;
use Magento\LiveSearchProductListing\Model\LayoutElementsRemover;
use Fedex\LiveSearch\Model\SharedCatalogSkip;

/**
 * Plugin for layout containers reader to change elements visibility depends on Live Search admin configuration.
 */
class ContainerReader
{
    /**
     * @param LayoutElementsRemover $layoutElementsRemover
     * @param SharedCatalogSkip $sharedCatalogCheck
     * @param string[] $containersToRemove
     */
    public function __construct(
        private LayoutElementsRemover $layoutElementsRemover,
        private SharedCatalogSkip $sharedCatalogCheck,
        private array $containersToRemove
    )
    {
    }

    /**
     * Mark specific container as removed if configuration in admin active for specific Live Search feature.
     *
     * @param Container $subject
     * @param Context $readerContext
     * @param Element $currentElement
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function beforeInterpret(Container $subject, Context $readerContext, Element $currentElement): array
    {
        //B-1877722
        if($this->sharedCatalogCheck->checkCommercialStoreWithArea())
        {
            if($this->sharedCatalogCheck->checkIsSharedCatalogPage()){
                $this->containersToRemove = [];
            }
        }
        //B-1877722
        $this->layoutElementsRemover->removeLayoutElements(
            $currentElement,
            $this->containersToRemove,
            \get_class($this)
        );
        return [$readerContext, $currentElement];
    }
}
