<?php

/**
 * @category    Fedex
 * @package     Fedex_PersonalAddressBook
 * @copyright   Copyright (c) 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\PersonalAddressBook\Controller\Index;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Fedex\PersonalAddressBook\Helper\Parties;

/**
 * PersonalAddressBook Controller
 *
 */
class Index extends Action
{

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param Data $helperData
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Context $context,
        private JsonFactory $resultJsonFactory,
        private RequestInterface $request,
        private Parties $helperData,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Function to Validate Address Data
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $apiName = $this->request->getParam('api_name');
        $responseData = null;
        $postData = [
            "nickName" => "pk",
            "firstName" => "Dev",
            "lastName" => "Admin2",
            "localNumber" => "4695888740",
            "streetLines" => ["8287 Legacy Drive", ""],
            "city" => "Plano",
            "stateOrProvinceCode" => "TX",
            "postalCode" => "75024",
            "countryCode" => "US",
            "residential" => false,
            "type" => "HOME",
            "localNumber" => 4695888740,
            "companyName" => "INFOGAIN",
            "nickName" => "PK",
            "opCoTypeCD" => "All"
        ];
        $contactID = 1115024718;
        $resultJson = $this->resultJsonFactory->create();
        if($apiName == "callGetPartyFromAddressBookById") {
            $responseData = $this->helperData->callGetPartyFromAddressBookById($contactID);
        } elseif($apiName == "callPostParties") {
            $responseData = $this->helperData->callPostParties($postData);
        } elseif($apiName == "callPostContactSearch") {
            $postDataSearch = ["searchValue" => "Plano",
            "searchType"=> "City_Name"];
            $responseData = $this->helperData->callPostContactSearch($postDataSearch);
        } elseif($apiName == "callGetPartiesList") {
            $responseData = $this->helperData->callGetPartiesList();
        } elseif($apiName == "callPutParties") {
            $responseData = $this->helperData->callPutParties($contactID, $postData);
        } elseif($apiName == "callDeletePartyFromAddressBookById") {
            $responseData = $this->helperData->callDeletePartyFromAddressBookById($contactID);
        }
        if ($responseData && isset($responseData['output'])) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Personal AddressBook API response:');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . json_encode($responseData));
            return $resultJson->setData([$responseData]);
        } elseif (isset($responseData['errors']) || !isset($response['output'])) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Personal AddressBook API response:');
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . json_encode($responseData));
            return $resultJson->setData([$responseData]);
        }
    }
}
