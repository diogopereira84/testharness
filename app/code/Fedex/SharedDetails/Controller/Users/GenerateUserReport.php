<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SharedDetails\Controller\Users;

use Magento\Framework\App\Action\Context;
use Magento\Company\Model\ResourceModel\Users\Grid\CollectionFactory;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Fedex\SharedDetails\Helper\CommercialReportHelper;
use Fedex\SelfReg\Ui\Component\Listing\Column\UserGroup;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\ResourceModel\Users\Grid\Collection as UsersGridCollection;

class GenerateUserReport extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{
    private UsersGridCollection|CollectionFactory $users;
    private Filesystem\Directory\WriteInterface $directory;

    /**
     * Constructor
     *
     * @param Context                          $context
     * @param CollectionFactory                $userCollectionFactory
     * @param SessionFactory                   $customerSession
     * @param LoggerInterface                  $logger
     * @param PageFactory                      $resultPageFactory
     * @param Filesystem                       $filesystem
     * @param CommercialReportHelper           $commercialReportHelper
     * @param UserGroup                        $userGroupModel
     * @param CompanyRepositoryInterface       $companyRepository
     */
    public function __construct(
        Context $context,
        readonly private CollectionFactory $userCollectionFactory,
        readonly private SessionFactory $customerSession,
        readonly private LoggerInterface $logger,
        readonly private PageFactory     $resultPageFactory,
        Filesystem $filesystem,
        readonly private CommercialReportHelper $commercialReportHelper,
        readonly private UserGroup $userGroupModel,
        readonly private CompanyRepositoryInterface $companyRepository
    ) {
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        parent::__construct($context);
    }

    /**
     * Retrieve Headers row array for Export
     *
     * @return array
     */
    public function _getExportHeaders() : array
    {
        return [
            'name' => __('Name'),
            'email' => __('Email'),
            'user_group' => __('User Group'),
            'status' => __('Status'),
            'created_at' => __('Date Added'),
            'last_login_at' => __('Last log in date')
        ];
    }

    /**
     * Get a row data of the particular columns
     *
     * @param array $users
     * @return array
     */
    public function getUserRowRecord(array $users) : array
    {
        $customerSession = $this->customerSession->create();
        $customerGroupId = $customerSession->getCustomer()->getGroupId();
        $userGroupCode = $this->userGroupModel->getUserGroupName($customerGroupId);
        $statusLabels = [
            0 => __('Inactive'),
            1 => __('Active'),
            2 => __('Pending Approval'),
            3 => __('Email Verification Pending'),
        ];
        return [
            $users['name'] ?? '',
            $users['secondary_email'] ?? '',
            $userGroupCode ?? '',
            $statusLabels[$users['customer_status']] ?? '',
            !empty($users['created_at']) ? date('M d, Y', strtotime($users['created_at'])) : '',
            !empty($users['last_login_at']) ? date('M d, Y', strtotime($users['last_login_at'])) : '',
        ];
    }

    /**
     * Generate Report Execute action.
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\PageFactory $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $emailData = $this->getRequest()->getParam('emailData');
        $userIds = [];
        if(!empty($this->getRequest()->getParam('userIds'))) {
            $userIds = explode(',', $this->getRequest()->getParam('userIds'));
        }

        try {
            $customerSession = $this->customerSession->create();
            $companyId = $customerSession->getCustomerCompany();
            $customerEmail = $customerSession->getCustomer()->getSecondaryEmail() ?
                $customerSession->getCustomer()->getSecondaryEmail() : $customerSession->getCustomer()->getEmail();
            if (!empty($emailData)) {
                $emailData = $customerEmail .','. $emailData;
            } else {
                $emailData = $customerEmail;
            }

            $usersCollection = $this->userCollectionFactory->create();
            $usersCollection->addFieldToSelect('*');
            if (!empty($userIds)) {
                $usersCollection->addFieldToFilter('entity_id', ['in' => $userIds]);
            }
            // Join customer Log table to get last login date time
            $usersCollection->getSelect()->joinLeft(
                ['user_log' => 'customer_log'],
                'user_log.customer_id = main_table.entity_id',
                ['user_log.last_login_at']
            );

            $this->users = $usersCollection;
            $this->users->setOrder('created_at', 'DESC');
            $itemObj = new \ArrayObject();
            if (!empty($this->users->toArray()['items'])) {
                foreach ($this->users->toArray()['items'] as $userItem) {
                    $itemObj->append($userItem);
                }
            }
            $itemIteratorObj = $itemObj->getIterator();
            $convert = new \Magento\Framework\Convert\Excel($itemIteratorObj, [$this, 'getUserRowRecord']);
            $convert->setDataHeader($this->_getExportHeaders());

            $this->directory->create('export');
            $time = microtime();
            $fileName = 'export/user_report_' . $companyId . '_' . $time . '.xls';

            $stream = $this->directory->openFile($fileName, 'w+');
            $stream->lock();
            $convert->write($stream, 'Sheet1');
            $stream->unlock();
            $stream->close();
            // Send Email and remove files
            if ($fileName) {
                $this->commercialReportHelper->sendUserReportEmail($fileName, $emailData);
            }
            return $resultPage;
        } catch (\Exception $error) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . ' Error while exporting users data for company id:'.
                $companyId .' is: ' . $error->getMessage()
            );
        }

        return $resultPage;
    }

}
