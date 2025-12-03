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
 * Search SearchbyAlphabet details Controller
 */
class SearchbyAlphabet implements ActionInterface
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
            $searchAlphabet = $this->request->getPost('alphabet');
            $clearFilter = (bool) $this->request->getPost('clear');
            $alphabetSearched = (bool) $this->request->getPost('alphabetSearched');

            if ($alphabetSearched && !$clearFilter) {
                $data = $this->partiesHelper->callGetPartiesList();
                if (!empty($data['output']['partyList'])) {
                    $partiesList = $data['output']['partyList'];
                }
            } else {
                $partiesList = $this->customerSession->getPartiesList()? json_decode($this->customerSession->getPartiesList()):[];
            }
            if (!empty($searchAlphabet) && !$clearFilter) {
                $responseData = ['data'=> $this->searchAddress($searchAlphabet, 'lastName', $partiesList)];
            } else {
                $data = $this->partiesHelper->callGetPartiesList();
                if (!empty($data['output']['partyList'])) {
                    $partiesList = $data['output']['partyList'];
                    array_multisort(array_map('strtolower',array_column($partiesList, 'lastName')), SORT_ASC, $partiesList);
                    $this->customerSession->setPartiesList(json_encode($partiesList));
                }
                $pageSize = $this->customerSession->getAddressBookPageSize();
                if (!empty($pageSize)) {
                    $partiesList = $this->partiesHelper->paginatedData($partiesList, $pageSize);
                }
                $partiesList = $this->partiesHelper->paginatedData($partiesList);
                $responseData = ['data'=> array_values($partiesList)];
            }
            
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                'Error from Alphabet Search. ' . $e->getMessage());
            $responseData = ['error_msg' => "Error from Alphabet Search."  . $e->getMessage()];
        }

        return $resultJson->setData($responseData);
    }

    /**
     * Search Address by Alphabet
     * @param  string $alphabet
     * @param  string $key
     * @param  array $partiesList
     * @return array
     */
    public function searchAddress($alphabet, $key, $partiesList)
    {
        $searchResult = [];
        $count = 0;
        foreach ($partiesList as $address) {
            if (is_object($address)) {
                if (substr(strtolower($address->$key), 0, 1) === strtolower($alphabet)) {
                    $searchResult[$count] = $address;
                }
            } else {
                if (substr(strtolower($address[$key]), 0, 1) === strtolower($alphabet)) {
                    $searchResult[$count] = $address;
                }
            }
            $count++;
        }
        array_multisort(array_column($searchResult, $key), SORT_ASC, $searchResult);
        $this->customerSession->setPartiesList(
            json_encode(array_values($searchResult))
        );
        
        return array_values($searchResult);
    }
}
