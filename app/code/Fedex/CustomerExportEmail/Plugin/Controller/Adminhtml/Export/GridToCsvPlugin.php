<?php
declare(strict_types=1);
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerExportEmail\Plugin\Controller\Adminhtml\Export;

use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\Auth\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Message\ManagerInterface;
use Fedex\CIDPSG\Helper\Email;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\CustomerExportEmail\Model\Export\ExportInfoFactory;
use Fedex\CustomerExportEmail\Model\Component\MassAction\Filter;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Fedex\CustomerExportEmail\Helper\Data;

/**
 * Class Render
 */
class GridToCsvPlugin
{
    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param Session $adminSession
     * @param ToggleConfig $toggleConfig
     * @param ManagerInterface $messageManager
     * @param Email $email
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $configInterface
     * @param Http $request
     * @param PublisherInterface $messagePublisher
     * @param ExportInfoFactory $exportInfoFactory
     * @param LoggerInterface $logger
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Data $helperData
     */
    public function __construct(
        protected RedirectFactory $resultRedirectFactory,
        protected Session $adminSession,
        protected ToggleConfig $toggleConfig,
        protected ManagerInterface $messageManager,
        protected Email $email,
        protected StoreManagerInterface $storeManager,
        protected ScopeConfigInterface $configInterface,
        protected Http $request,
        private PublisherInterface $messagePublisher,
        private ExportInfoFactory $exportInfoFactory,
        private LoggerInterface $logger,
        private Filter $filter,
        private CollectionFactory $collectionFactory,
        protected Data $helperData
    ) {
    }

    /**
     * Send Customer Export Email
     */
    public function aroundExecute(
        \Magento\Ui\Controller\Adminhtml\Export\GridToCsv $subject,
        \Closure $proceed
    ) {
        $requestParams = $this->request->getParams();

        if ($requestParams['namespace'] == 'customer_listing') {
            $resultRedirect = $this->resultRedirectFactory->create();
            try {
                $customerExportEmailData = $this->prepareCustomerExportEmailData();

                $collection = $this->filter->getCollection($this->collectionFactory->create());

                $customerdata = $collection->getData();

                $inActivecolumns = $this->helperData->getInActiveColumns();

                /** @var ExportInfoFactory $dataObject */
                $dataObject = $this->exportInfoFactory->create(
                    $customerExportEmailData,
                    $customerdata,
                    $inActivecolumns
                );

                $this->messagePublisher->publish('customer_export.email', $dataObject);

                $this->messageManager->addSuccessMessage(__('Customer Export Data has been sent to queue. You will receive an email shortly.'));
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while export.'));
                $this->logger->critical(__METHOD__ . ':' . __LINE__
            . ": Failed to Customer's Export Email : " . $e->getMessage());
            }
            return $resultRedirect->setPath('customer/index/index');
        }
        return $proceed();
    }

    /**
     * Customer Export Email Data
     * @return string
     */
    public function prepareCustomerExportEmailData()
    {
        $adminUserEmail = $this->adminSession->getUser()->getEmail();

        $storeId = $this->storeManager->getStore()->getId();
        $customerExportTemplateContent = $this->email->
                loadEmailTemplate('fedex_customer_export_email', $storeId, [], true);
        $storeEmail = $this->configInterface->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE
        );

        return '{
                "templateData": "' . trim($customerExportTemplateContent) . '",
                "templateSubject": "Export from FedEx",
                "toEmailId": "' . $adminUserEmail . '",
                "fromEmailId": "' . $storeEmail . '",
                "retryCount": 0,
                "errorSupportEmailId": "",
                "attachment": ""
            }';
    }

}
