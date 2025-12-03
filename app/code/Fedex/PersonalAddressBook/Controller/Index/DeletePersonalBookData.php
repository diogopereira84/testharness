<?php

/**
 * @category    Fedex
 * @package     Fedex_PersonalAddressBook
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Pooja Tiwari <pooja.tiwari.osv@fedex.com>
 */

declare (strict_types = 1);

namespace Fedex\PersonalAddressBook\Controller\Index;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\PersonalAddressBook\Helper\Parties as Data;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;

/**
 * AddressValidate Controller
 *
 */
class DeletePersonalBookData implements ActionInterface
{
    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     * @param JsonFactory $resultJsonFactory
     * @param Data $partiesHelper
     * @param Session $customerSession
     */
    public function __construct(
        private LoggerInterface $logger,
        private RequestInterface $request,
        private ToggleConfig $toggleConfig,
        private JsonFactory $resultJsonFactory,
        private Data $partiesHelper,
        private Session $customerSession
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->toggleConfig = $toggleConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->partiesHelper = $partiesHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute method to delete addressbook
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $responseData = [];
        $personAddressBookToggle = $this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book');
        try {
            $postData = $this->request->getPostValue();
            $contactID = $postData['contactID'];
            if ($personAddressBookToggle && !empty($postData)) {
                $responseData = $this->partiesHelper->callDeletePartyFromAddressBookById($contactID);

                $partiesList = [];
                // Update Address data in customer session
                $partiesData = $this->partiesHelper->callGetPartiesList();
                if (!empty($partiesData['output']['partyList'])) {
                    $partiesList = $partiesData['output']['partyList'];
                    array_multisort(array_map('strtolower', array_column($partiesList, 'lastName')), SORT_ASC, $partiesList);
                    $this->customerSession->setPartiesList(
                        json_encode($partiesList)
                    );
                } else {
                    $this->customerSession->setPartiesList(
                        json_encode($partiesList)
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                'Error found no data from PersonAddressBook API. ' . $e->getMessage());
            $responseData = ['error_msg' => "Error found no data from PersonAddressBook." . $e->getMessage()];
        }
        return $resultJson->setData($responseData);
    }
}
