<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PersonalAddressBook\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Fedex\PersonalAddressBook\Helper\Parties;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

/**
 * AddressBookPage details Controller
 */
class AddressBookPage implements ActionInterface
{
    /**
     * View class constructor
     *
     * @param Context $context
     * @param Parties $partiesHelper
     * @param Session $customerSession
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(
        private Context $context,
        private Parties $partiesHelper,
        private Session $customerSession,
        private JsonFactory $resultJsonFactory,
        private LoggerInterface $logger,
        private RequestInterface $request
    ) {
        $this->context = $context;
        $this->partiesHelper = $partiesHelper;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * Seach Data in Parties List
     *
     * @return array
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $responseData = [];
        try {
            $setPageSize = $this->request->getPost('setPageSize');
            $currentPage = $this->request->getPost('currentPage');
            $pageSize = $this->request->getPost('pageSize');
            if ($pageSize) {
                if ($setPageSize) {
                    $this->customerSession->setAddressBookPageSize($pageSize);
                }
                $partiesList = $this->customerSession->getPartiesList()? json_decode($this->customerSession->getPartiesList()):[];
                $totalRecords = count($partiesList);
                $totalPages   = ceil($totalRecords / $pageSize);
                if ($currentPage > $totalPages) {
                    $currentPage = $totalPages;
                }
                if ($currentPage < 1) {
                    $currentPage = 1;
                }
                $offset = ($currentPage - 1) * $pageSize;
                $partiesList = array_slice($partiesList, $offset, $pageSize);
                $responseData = ['data'=> array_values($partiesList)];
            }         
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                'Error from Address Book Pagination for '. $currentPage . $e->getMessage());
            $responseData = ['error_msg' => "Error from Adress Book Pagination for "  . $currentPage . $e->getMessage()];
        }

        return $resultJson->setData($responseData);
    }
}
