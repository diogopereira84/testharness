<?php

/**
 * @category    Fedex
 * @package     Fedex_PersonalAddressBook
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Pallavi Kade <pallavi.kade.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\PersonalAddressBook\Controller\Index;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Fedex\PersonalAddressBook\Helper\Parties as Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * AddressValidate Controller
 *
 */
class SaveAddressBook implements ActionInterface
{
    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     * @param JsonFactory $resultJsonFactory
     * @param Data $partiesHelper
     */
    public function __construct(
        private LoggerInterface $logger,
        private RequestInterface $request,
        private ToggleConfig $toggleConfig,
        private JsonFactory $resultJsonFactory,
        private Data $partiesHelper
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->toggleConfig = $toggleConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->partiesHelper = $partiesHelper;
    }

    /**
     * Execute method to save addressbook
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
                $isSaveForEdit = (bool)$postData['isSaveForEdit'];
                if ($isSaveForEdit) {
                    $responseData = $this->partiesHelper->callPutParties($contactID, $postData);
                } else {
                    $responseData = $this->partiesHelper->callPostParties($postData);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                'Error found no data from PersonAddressBook API. ' . $e->getMessage());
            $responseData = ['error_msg' => "Error found no data from PersonAddressBook."  . $e->getMessage()];
        }
        return $resultJson->setData($responseData);
    }
}
