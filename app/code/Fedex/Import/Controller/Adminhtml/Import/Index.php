<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Controller\Adminhtml\Import;

/**
 * Controller Class Index
 */
class Index extends \Magento\ImportExport\Controller\Adminhtml\Import\Index
{
    /**
     * Execute method for controller class
     *
     * @return mixed
     */
    public function execute()
    {
        $resultPage = parent::execute();
        $resultPage->getLayout()->getBlock('menu')->setActive('Fedex_Import::import_product_import');
        return $resultPage;
    }

    /**
     * Function to check for authorization
     *
     * @return mixed
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Fedex_Import::import_product_import');
    }
}
