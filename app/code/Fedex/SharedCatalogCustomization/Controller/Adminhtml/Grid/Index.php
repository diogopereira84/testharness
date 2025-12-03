<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Controller\Adminhtml\Grid;

use Magento\Framework\View\Result\Page;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index implements ActionInterface
{
    /**
     * Constructor
     *
     * @param PageFactory $pageFactory
     */
    public function __construct(
        private readonly PageFactory $pageFactory
    ) {
    }

    /**
     * Add the main Admin Grid page
     *
     */
    public function execute(): Page
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Fedex_SharedCatalogCustomization::index');
        $resultPage->getConfig()->getTitle()->prepend(__('Catalog sync grid'));

        return $resultPage;
    }
}
