<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CmsImportExport\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Fedex\CmsImportExport\Model\Import\Cms;
use Magento\Backend\Model\View\Result\RedirectFactory;

class Save implements ActionInterface
{

    /**
     * Call to Import CSV Data
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param Cms $cms
     */
    public function __construct(
        protected RedirectFactory $resultRedirectFactory,
        protected Cms $cms
    )
    {
    }
    
    /**
     * Call model to import Csv data
     *
     * @return string
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $result = $this->cms->importData();
        return $resultRedirect->setPath('importexportcms/index/import');
    }
}
