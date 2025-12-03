<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Ui\Component\MassAction\Filter;
use Fedex\CIDPSG\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * Controller MassDelete Action
 */
class MassDelete implements ActionInterface
{
    /**
     * Initialize dependencies
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        protected RedirectFactory $resultRedirectFactory,
        protected Filter $filter,
        protected CollectionFactory $collectionFactory,
        protected ManagerInterface $messageManager
    )
    {
    }

    /**
     * Execute method
     *
     * @return mixed
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();
        $defaultCustomerCount = 0;

        foreach ($collection as $page) {
            if ($page->getClientId() != 'default') {
                $page->delete();
            } else {
                $defaultCustomerCount++;
            }
        }

        if ($collectionSize > 1 && $defaultCustomerCount > 0) {
            $this->messageManager->addSuccessMessage(
                __(
                    'A total of %1 customer record(s) have been deleted except default customer.',
                    $collectionSize - 1
                )
            );
        } elseif ($collectionSize == 1 && $defaultCustomerCount > 0) {
            $this->messageManager->addErrorMessage(__('Default customer can not be deleted.'));
        } else {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 customer record(s) have been deleted.', $collectionSize)
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}
