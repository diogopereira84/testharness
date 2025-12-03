<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CmsImportExport\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Import implements ActionInterface
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * Import Constructor
     *
     * @param PageFactory $rawFactory
     */
    public function __construct(
        PageFactory $rawFactory
    ) {
        $this->pageFactory = $rawFactory;
    }

    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Fedex_CmsImportExport::import_cms');
        $resultPage->getConfig()->getTitle()->prepend(__('Import CMS Contents'));
        return $resultPage;
    }
}
