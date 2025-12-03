<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Adminhtml\Index;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Fedex\CIDPSG\Model\Customer as PsgCustomer;
use Magento\Backend\Model\Session;
use Magento\Framework\Message\ManagerInterface;

/**
 * Edit controller class
 */
class Edit implements ActionInterface
{
    /**
     * @param LoggerInterface $logger
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param PsgCustomer $customer
     * @param Session $session
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $requestInterface
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected PageFactory $resultPageFactory,
        protected Registry $coreRegistry,
        protected PsgCustomer $customer,
        protected Session $session,
        protected RedirectFactory $resultRedirectFactory,
        protected RequestInterface $requestInterface,
        protected ManagerInterface $messageManager
    ) {
    }

    /**
     * For Permission
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Fedex_CIDPSG::Psgcustomers')
            ->addBreadcrumb(__('FedEx PSG Customer Status'), __('FedEx PSG Customer Status'))
            ->addBreadcrumb(__('Manage PSG Customer Status'), __('Manage PSG Customer Status'));

        return $resultPage;
    }

    /**
     * Edit Psg customer data using entity id
     *
     * @return mixed
     */
    public function execute()
    {
        $id = $this->requestInterface->getParam('entity_id');
        if ($id) {
            $this->customer->load($id);
            if (!$this->customer->getId()) {
                $this->messageManager->addError(__('This item no longer exists.'));
                $this->logger->error(__METHOD__.':'.__LINE__.':'.$id.' This item no longer exists.');
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->session->getFormData(true);
        if (!empty($data)) {
            $this->customer->setData($data);
        }

        $this->coreRegistry->register('customer', $this->customer);

        $resultPage = $this->_initAction();
        $resultPage->setActiveMenu('Fedex_CIDPSG::psgcustomers');
        $resultPage->addBreadcrumb(__('FedEx'), __('FedEx'));
        $editItem = 'Edit PSG Customer Data';
        $newCustomerStatus = 'Add New PSG Customer';
        $resultPage->addBreadcrumb(
            $id ? __($editItem) : __($newCustomerStatus),
            $id ? __($editItem) : __($newCustomerStatus)
        );
        $resultPage->getConfig()->getTitle()->prepend($id ? __($editItem) : __($newCustomerStatus));

        return $resultPage;
    }
}
