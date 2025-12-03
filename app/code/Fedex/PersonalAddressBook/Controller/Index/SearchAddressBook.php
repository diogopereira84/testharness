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
use Magento\Customer\Model\Session;

/**
 * AddressValidate Controller
 *
 */
class SearchAddressBook implements ActionInterface
{
    public const PERSONAL_ADDRESS = 'personal_address';
    public const PERSONAL_ADDRESS_STREET = 'personal_address_address';
    public const PERSONAL_ADDRESS_CITY = 'personal_address_city';
    public const PERSONAL_ADDRESS_STATE = 'personal_address_state';
    public const PERSONAL_ADDRESS_ZIP = 'personal_address_zip';

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
        protected Session $customerSession
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->toggleConfig = $toggleConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->partiesHelper = $partiesHelper;
    }

    /**
     * Execute method to Search Addressbook
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $responseData = [];
        $partiesList = [];
        $personAddressBookToggle = $this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book');
        try {
            $postData = $this->request->getPostValue();
            if ($personAddressBookToggle && !empty($postData)) {
                $addressSearchOption = $postData['addressSearchOption'];
                $searchFieldData = $postData['searchField'];
                $partiesData = $this->partiesHelper->callGetPartiesList();
                if (!empty($partiesData['output']['partyList'])) {
                    $partiesList = $partiesData['output']['partyList'];
                }                
                $newPartiesList = $this->searchPersonalAddressList($searchFieldData, $addressSearchOption, $partiesList);
                $totalRecords = count($newPartiesList);
                $this->customerSession->setPartiesList(json_encode(array_values($newPartiesList)));
                $pageSize = $this->customerSession->getAddressBookPageSize();
                if (!empty($pageSize)) {
                    $newPartiesList = $this->partiesHelper->paginatedData($newPartiesList, $pageSize);
                }
                $newPartiesList = $this->partiesHelper->paginatedData($newPartiesList);
                $responseData = ['data'=> array_values($newPartiesList), 'totalRecords'=> $totalRecords];
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                'Unable to filter data from PersonAddressBook. ' . $e->getMessage());
            $responseData = ['error_msg' => "Unable to filter data from PersonAddressBook"];
        }
        return $resultJson->setData($responseData);
    }

    /**
     * Execute method to Filter through Addressbook
     * @param String $searchFieldData
     * @param String $key
     * @param Array $resultJsonFactory
     *
     * @return ResultInterface
     */
    public function searchPersonalAddressList($searchFieldData, $key, $partiesList)
    {
        $searchResult = [];
        $count = 0;
        foreach ($partiesList as $address) {
            //Condition for filtering address
            if(str_starts_with($key, self::PERSONAL_ADDRESS)) {
                if ($key == self::PERSONAL_ADDRESS_STREET) {
                    if (str_starts_with(strtolower($address['address']['streetLines'][0]), strtolower($searchFieldData))) {
                        $searchResult[$count] = $address;
                    }
                } elseif ($key == self::PERSONAL_ADDRESS_CITY) {
                    if (str_starts_with(strtolower($address['address']['city']), strtolower($searchFieldData))) {
                        $searchResult[$count] = $address;
                    }
                } elseif ($key == self::PERSONAL_ADDRESS_STATE) {
                    if (str_starts_with(strtolower($address['address']['stateOrProvinceCode']), strtolower($searchFieldData))) {
                        $searchResult[$count] = $address;
                    }
                } elseif ($key == self::PERSONAL_ADDRESS_ZIP) {
                    if (str_starts_with(strtolower($address['address']['postalCode']), strtolower($searchFieldData))) {
                        $searchResult[$count] = $address;
                    }
                }
            } elseif(array_key_exists($key, $address)) {
                //Condition for filtering non Address keys
                if (str_starts_with(strtolower($address[$key]), strtolower($searchFieldData))) {
                    $searchResult[$count] = $address;
                }
            }
            $count++;
        }
        // Filtered AddressBook
        return $searchResult;
    }
}
