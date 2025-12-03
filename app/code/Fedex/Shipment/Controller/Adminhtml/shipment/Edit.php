<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Controller\Adminhtml\shipment;

use Fedex\Shipment\Model\Shipment;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Edit implements ActionInterface
{
    /** @var string */
    private const ID_PARAM = 'id';

    /** @var string */
    public const EDIT_ITEM = 'Edit Item';

    /** @var string */
    public const NEW_SHIPMENT_STATUS = 'New Shipment Status';

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param Shipment $shipment
     * @param Session $session
     */
    public function __construct(
        private RedirectFactory $resultRedirectFactory,
        private RequestInterface $request,
        private ManagerInterface $messageManager,
        private LoggerInterface $logger,
        private PageFactory $resultPageFactory,
        private Registry $coreRegistry,
        private Shipment $shipment,
        private Session $session
    ) {
    }

    /**
     * {@inheritdoc}
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
        // load layout, set active menu and breadcrumbs
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Fedex_Shipment::Shipment')
            ->addBreadcrumb(__('Fedex Shipment Status'), __('Fedex Shipment Status'))
            ->addBreadcrumb(__('Manage Shipment Status'), __('Manage Shipment Status'));
        return $resultPage;
    }

    /**
     * Edit Item
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->request->getParam(self::ID_PARAM);

        // 2. Initial checking
        if ($id) {
            $this->shipment->load($id);
            if (!$this->shipment->getId()) {
                $this->messageManager->addError(__('This item no longer exists.'));
                $this->logger->error(__METHOD__.':'.__LINE__.':'.$id.' This item no longer exists.');
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        // 3. Set entered data if was error when we do save
        $data = $this->session->getFormData(true);
        if (!empty($data)) {
            $this->shipment->setData($data);
        }

        // 4. Register model to use later in blocks
        $this->coreRegistry->register('shipment', $this->shipment);

        // 5. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->setActiveMenu('Fedex_Shipment::shipment');
        $resultPage->addBreadcrumb(__('Fedex'), __('Fedex'));
        $resultPage->addBreadcrumb(
            $id ? __(self::EDIT_ITEM) : __(self::NEW_SHIPMENT_STATUS),
            $id ? __(self::EDIT_ITEM) : __(self::NEW_SHIPMENT_STATUS)
        );
        $resultPage->getConfig()->getTitle()->prepend($id ? __(self::EDIT_ITEM) : __(self::NEW_SHIPMENT_STATUS));

        return $resultPage;
    }
}
