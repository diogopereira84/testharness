<?php
/**
 * @category    Fedex
 * @package     Fedex_PageBuilder
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PageBuilder\Observer;

use Magento\Cms\Model\Block;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Observes the `cms_block_save_before` event.
 */
class CmsBlockSaveBeforeObserver implements ObserverInterface
{
    /**
     * Observer for cms_block_save_before.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Block $cmsBlock */
        $cmsBlock = $observer->getObject();
        if ($cmsBlock->getData() && str_contains($cmsBlock->getContent(), 'id="%identifier%"')) {
            $identifier = strtolower(str_replace(' ', '-', $cmsBlock->getIdentifier()));
            $content = str_replace('id="%identifier%"', 'id="'.$identifier.'"', $cmsBlock->getContent());
            $cmsBlock->setContent($content);
        }
    }
}
