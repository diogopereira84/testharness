<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CategoryLayout\Plugin\Result;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Context;

/**
 * Page class is used to add class in category
 */
class Page
{
    /**
     * Constructor to add class in category layout
     *
     * @param Context $context
     */
    public function __construct(
        private Context $context
    )
    {
    }

    /**
     * Add class in category layout
     *
     * @param Page $subject
     * @param ResponseInterface $response
     * @return array
     */
    public function beforeRenderResult(
        \Magento\Framework\View\Result\Page $subject,
        ResponseInterface $response
    ) {
        if ($subject->getConfig()->getPageLayout() == 'custom-category-full-width') {
            $subject->getConfig()->addBodyClass('page-layout-1column');
        }
        return [$response];
    }
}
