<?php
/**
 * @category    Fedex
 * @package     Fedex_PageBuilder
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PageBuilder\Observer;

use Magento\Cms\Block\Page;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Observes the `cms_page_save_before` event.
 */
class CmsPageSaveBeforeObserver implements ObserverInterface
{
    /**
     * Observer for cms_page_save_before.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Page $cmsPage */
        $cmsPage = $observer->getObject();
        if ($cmsPage->getData() && str_contains($cmsPage->getContent(), 'id="%identifier%"')) {
            $content = str_replace('id="%identifier%"', '', $cmsPage->getContent());
            $cmsPage->setContent($content);
        }
    }
}
