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
class SortAddressBook implements ActionInterface
{
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
        $personAddressBookToggle = $this->toggleConfig
            ->getToggleConfigValue('explorers_e_450676_personal_address_book');
        try {
            $postData = $this->request->getPostValue();
            if ($personAddressBookToggle && !empty($postData)) {
                $addressSearchOption = $postData['addressSortOption'];
                $addressSearchOrder = $postData['order'];
                $partiesListSessionData = $this->customerSession->getPartiesList() ?
                     json_decode($this->customerSession->getPartiesList(), true):[];
                $partiesList = $this->partiesHelper->parsePartiesData($partiesListSessionData);
                $partiesList = $this->sortPersonalAddressList($addressSearchOption, $addressSearchOrder, $partiesList);
                $this->customerSession->setPartiesList(json_encode($partiesList));
                $pageSize = $this->customerSession->getAddressBookPageSize();
                if (!empty($pageSize)) {
                    $partiesList = $this->partiesHelper->paginatedData($partiesList, $pageSize);
                }
                $partiesList = $this->partiesHelper->paginatedData($partiesList);
                $responseData = ['data' => array_values($partiesList)];
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                'Unable to sort data from PersonAddressBook. ' . $e->getMessage());
            $responseData = ['error_msg' => "Unable to filter data from PersonAddressBook"];
        }
        return $resultJson->setData($responseData);
    }

    /**
     * Execute method to Filter through Addressbook
     *
     * @param string $key
     * @param string $order
     * @param array $partiesList
     *
     * @return array
     */
    public function sortPersonalAddressList($key, $order, $partiesList)
    {
        if ($key == self::PERSONAL_ADDRESS_STREET) {
            $key = 'addressData';
        } elseif ($key == self::PERSONAL_ADDRESS_CITY) {
            $key = 'city';
        } elseif ($key == self::PERSONAL_ADDRESS_STATE) {
            $key = 'stateOrProvinceCode';
        } elseif ($key == self::PERSONAL_ADDRESS_ZIP) {
            $key = 'postalCode';
        }
        //storing the column for sort
        $sortingColumn = array_column($partiesList, $key);
        if ($order == "asc") {
            array_multisort(array_map('strtolower', $sortingColumn), SORT_ASC, $partiesList);
        } else {
            array_multisort(array_map('strtolower', $sortingColumn), SORT_DESC, $partiesList);
        }

        // Filtered AddressBook
        return $partiesList;
    }
}
