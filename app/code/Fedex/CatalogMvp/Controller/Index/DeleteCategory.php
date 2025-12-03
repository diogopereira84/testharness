<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * Class DeleteCategory
 * Handle the delete category from kebab of the CatalogMvp
 */
class DeleteCategory  implements ActionInterface
{
    /**
     * DeleteCategory Constructor
     *
     * @param Registry $Registry
     * @param JsonFactory $jsonFactory
     * @param Context $context
     */
    public function __construct(
        protected Context $context,
        protected Registry $registry,
        protected JsonFactory $jsonFactory,
        protected CatalogMvp $helper
    )
    {
    }

    public function execute()
    {
        $isToggleEnable = $this->helper->isMvpSharedCatalogEnable();
        $isAdminUser = $this->helper->isSharedCatalogPermissionEnabled();
        if ($isToggleEnable && $isAdminUser) {
            $categoryId = $this->context->getRequest()->getParam('cid');

            $json = $this->jsonFactory->create();
           
            $data = [];
            $categoryDelete = 0;
            $this->registry->register('isSecureArea', true);

            $categoryDelete = $this->helper->deleteCategory($categoryId);

            $data['delete'] = $categoryDelete;
            $data['message'] = __("Folder has been deleted from shared catalog.");

            $json->setData($data);
            return $json;
        }
    }
}
