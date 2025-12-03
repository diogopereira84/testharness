<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CmsImportExport\Controller\Adminhtml\CmsExport;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index implements ActionInterface
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * Index Constructor
     *
     * @param PageFactory $rawFactory
     */
    public function __construct(
        PageFactory $rawFactory
    ) {
        $this->pageFactory = $rawFactory;
    }

    /**
     * Add the main Admin Grid page
     *
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Fedex_CmsImportExport::cmscontents_export');
        $resultPage->getConfig()->getTitle()->prepend(__('Cms content export'));

        return $resultPage;
    }
}
