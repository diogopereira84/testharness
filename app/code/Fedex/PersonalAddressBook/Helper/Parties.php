<?php

/**
 * @category    Fedex
 * @package     Fedex_PersonalAddressBook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Pallavi Kade <pallavi.kade.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\PersonalAddressBook\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use Fedex\SSO\Helper\Data as SSOHelper;

class Parties extends AbstractHelper
{
    public const OUTPUT = 'output';
    private const ERRORS = 'errors';

    /**
     * @param Context $context
     * @param ScopeConfigInterface $configInterface
     * @param EncryptorInterface $encryptorInterface
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param CookieManagerInterface $cookieManager
     * @param ToggleConfig $toggleConfig
     * @param SSOHelper $ssoHelper
     */
    public function __construct(
        Context $context,
        private ScopeConfigInterface $configInterface,
        private EncryptorInterface $encryptorInterface,
        private Curl $curl,
        private LoggerInterface $logger,
        protected CookieManagerInterface $cookieManager,
        protected ToggleConfig $toggleConfig,
        protected SSOHelper $ssoHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Curl Post Data for Parties
     *
     * @param string|array $postData
     * return array
     */
    public function callPostParties($postData)
    {
        $url = $this->getPartiesUrl();
        $dataString = $this->prepareData($postData);

        $headers = $this->getHeaders();
        if ($this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book')) {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );
            try {
                $this->curl->post($url, $dataString);
                $output = $this->curl->getBody();
                $response = json_decode($output, true);
                $addressBookLog = $this->toggleConfig->getToggleConfigValue('enable_personal_addressbook_log');
                if ($addressBookLog || isset($response[self::ERRORS]) || !isset($response[self::OUTPUT])) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Post Parties API Request:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Post Parties API Response:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                }
                return $response;
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                    ' Exception occurred while calling post parties to addressbook API: ' . $e->getMessage());
            }
        }
    }

    /**
     * Curl Get Data for Parties By Id
     *
     * @param string $contactId
     */
    public function callGetPartyFromAddressBookById($contactId)
    {
        $url = $this->getPartiesUrl();
        $url = $this->getPartiesUrl() . "/" . $contactId . "/";
        $params = [
            'summary' => 'complete',
            'partytype' => 'RECIPIENT',
            'countrycode' => 'US'
        ];
        $headers = $this->getHeaders();
        $paramString = http_build_query($params);
        $url = $url.'?'.$paramString;
        if ($this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book')) {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );

            try {
                $this->curl->get($url);
                $output = $this->curl->getBody();
                $response = json_decode($output, true);
                $addressBookLog = $this->toggleConfig->getToggleConfigValue('enable_personal_addressbook_log');
                if ($addressBookLog || isset($response[self::ERRORS]) || !isset($response[self::OUTPUT])) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ .
                        ' Get Party By Id From AddressBook API Request:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $paramString);
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Get Party By Id From AddressBook API:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                }
                return $response;
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                    ' Exception occurred while getting party from addressBook by id API: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get Parties List url
     *
     * @return string
     */
    public function getPartiesUrl()
    {
        return $this->configInterface->getValue(
            'fedex/general/personal_addressbook_parties_api_url',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To prepare post parties v2 API request data.
     *
     * @param array $postData
     * @return string
     */
    public function prepareData($postData)
    {
        $dataArr = [
            'partyInfoVO' => [
                'party' => [
                    'tins' => [],
                    'contact' => [
                        'personName' => [
                            'firstName'     => $postData['firstName'],
                            'lastName'      => $postData['lastName'],
                            'fullName'      => $postData['firstName'] . ' ' . $postData['lastName']
                        ],
                        'emailAddress' => [
                            ['address' => !empty($postData['email']) ? $postData['email'] : '']
                        ],
                        'phoneNumberDetails' => [
                            [
                                'type' => $postData['type'],
                                'number' => [
                                    "localNumber" => $postData['localNumber'],
                                    'extension'   => !empty($postData['ext']) ? $postData['ext'] : ''
                                ]
                            ]
                        ],
                        'companyName' => [
                            'name' => $postData['companyName']
                        ],
                        'contactId' => 0,
                        'nickName' => $postData['nickName']
                    ],
                    'address' => [
                        'streetLines' => $postData['streetLines'],
                        'city' => $postData['city'],
                        'stateOrProvinceCode' => $postData['stateOrProvinceCode'],
                        'postalCode' => $postData['postalCode'],
                        'countryCode' => $postData['countryCode'],
                        'residential' => $postData['residential']
                    ],
                ],
                'addressCheckDetail' => [
                    'acsClientVerifiedFlg' => true,
                    'acsVerifiedStatusCD' => 'N',
                    'acsBypassFlg' => false
                ],
                'partyType' => 'RECIPIENT',
                'hoursOfOperation' => [],
                'addressAncillaryDetail' => [
                    'opCoTypeCD' => $postData['opCoTypeCD'],
                    'validFlg' => 'Y',
                    'sharedFlg' => 'Y',
                    'acceptedFlg' => 'Y',
                    'einCd' => 'E'
                ],
            ]
        ];
        return json_encode($dataArr);
    }

    /**
     * Get Headers for curl request
     *
     * @return array
     */
    public function getHeaders()
    {
        /* Check with "XMEN E-391561 - FCL Session Cookie Name" toggle off as
        fcl_cookie_name is still not working*/

        if ($this->ssoHelper->getFCLCookieNameToggle()) {
            $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
            $fdxLogin = $this->cookieManager->getCookie($cookieName);
        } else {
            $fdxLogin = $this->cookieManager->getCookie('fdx_login');
            $cookieName = 'fdx_login';
        }
        
        $token = $this->getClientId();
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $token . "",
            "Cookie: " . $cookieName . "=" . $fdxLogin . "",
        ];
        return $headers;
    }

    /**
     * Get params for curl request
     *
     * @return array
     */
    public function getParams()
    {
        $params = [
            'summary' => 'complete',
            'partytype' => 'RECIPIENT',
            'addressbooktype' => 'CENTRAL',
            'countrycode' => 'US'
        ];
        return $params;
    }

    /**
     * Get Parties List url
     *
     * @return string
     */
    public function getPartiesListUrl()
    {
        return $this->configInterface->getValue(
            'fedex/general/personal_addressbook_parties_list_api_url',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Parties List url
     *
     * @return string
     */
    public function partiesDeleteUrl()
    {
        return $this->configInterface->getValue(
            'fedex/general/personal_addressbook_parties_delete_api_url',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Parties List using Curl
     *
     * @param string
     */
    public function callGetPartiesList()
    {
        $url = $this->getPartiesListUrl();

        $params = [
            'summary' => 'complete',
            'partytype' => 'RECIPIENT',
            'countrycode' => 'US'
        ];
        $headers = $this->getHeaders();
        $paramString = http_build_query($params);
        $url = $url .'?'.$paramString;
        if ($this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book')) {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );

            try {
                $this->curl->get($url);
                $output = $this->curl->getBody();
                $response = json_decode($output, true);
                $addressBookLog = $this->toggleConfig->getToggleConfigValue('enable_personal_addressbook_log');
                if ($addressBookLog || isset($response[self::ERRORS]) || !isset($response[self::OUTPUT])) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Parties List API Request:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $paramString);
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Parties List API Response:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                }
                return $response;
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                    ' Exception occurred while getting Address From Parties List API: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get Contact Search Url
     *
     * @return string
     */
    public function getContactSearchUrl()
    {
        return $this->configInterface->getValue(
            'fedex/general/personal_addressbook_contacts_search',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Contact Search Data
     *
     * @param array $postData
     * @return string
     */
    public function prepareContactSearchData($postData)
    {
        $dataArray = [
            "searchValueList" => [$postData["searchValue"]],
            "searchType" => $postData["searchType"],
            "addressBookType" => ["CENTRAL", "PERSONAL"],
            "addressType" => ["SENDER", "RECIPIENT", "BROKER", "IMPORTER_OF_RECORD"],
            "sortField" => "Contact_Name",
            "sortOrder" => "DSC",
            "pageNumber" => "1",
            "resultsPerPage" => "100",
            "processingParameters" => null,
        ];

        return json_encode($dataArray);
    }

    /**
     * Curl Post Data for Contact Search
     *
     * @param array $postData
     * @return array
     */
    public function callPostContactSearch($postData)
    {
        $url = $this->getContactSearchUrl();
        $dataString = $this->prepareContactSearchData($postData);

        $headers = $this->getHeaders();
        if ($this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book')) {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );
            try {
                $this->curl->post($url, $dataString);
                $output = $this->curl->getBody();
                $response = json_decode($output, true);
                $addressBookLog = $this->toggleConfig->getToggleConfigValue('enable_personal_addressbook_log');
                if ($addressBookLog || isset($response[self::ERRORS]) || !isset($response[self::OUTPUT])) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Contact Search API Request:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Contact Search API Response:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                }
                return $response;
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                    ' Exception occurred while getting Address From Contact Search API: ' . $e->getMessage());
            }
        }
    }

    /**
     * Curl Put Data for Parties
     *
     * @param string $contactId
     * @param array $postData
     * @return array
     */
    public function callPutParties($contactId, $postData)
    {
        $url = $this->getPartiesUrl() . '/' . $contactId . '/';
        $dataString = $this->prepareData($postData);
 
        $headers = $this->getHeaders();
        if ($this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book')) {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "PUT",
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );
            try {
                $this->curl->post($url, $dataString);
                $output = $this->curl->getBody();
                $response = json_decode($output, true);
                $addressBookLog = $this->toggleConfig->getToggleConfigValue('enable_personal_addressbook_log');
                if ($addressBookLog || isset($response[self::ERRORS]) || !isset($response[self::OUTPUT])) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Parties API Request:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Parties API Response:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                }
                return $response;
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                    ' Exception occurred while getting Address From Parties API: ' . $e->getMessage());
            }
        }
    }

    /**
     * Curl Delete Data for Parties By Id
     *
     * @param string $contactId
     * @return array
     */
    public function callDeletePartyFromAddressBookById($contactId)
    {
        $url = $this->partiesDeleteUrl() . "?contactId=" . $contactId;
        $params = [
            'contactId' => 'contactId',
        ];
        $headers = $this->getHeaders();
        $paramString = http_build_query($params);
        $dataString    = json_encode($params);

        if ($this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book')) {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "DELETE",
                    CURLOPT_POSTFIELDS => $paramString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );

            try {
                $this->curl->post($url, $dataString);
                $output = $this->curl->getBody();
                $response = json_decode($output, true);
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Data for contactId '.$contactId.' is deleted');
                $addressBookLog = $this->toggleConfig->getToggleConfigValue('enable_personal_addressbook_log');
                if ($addressBookLog || isset($response[self::ERRORS]) || !isset($response[self::OUTPUT])) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Parties API Request:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Parties API Response:');
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                }
                return $response;
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                    ' Exception occurred while getting Address From Parties API: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get Client ID.
     *
     * @return mixed
     */
    public function getClientId()
    {
        return $this->encryptorInterface->decrypt(
            $this->configInterface->getValue(
                "fedex/fedex_rate_quotes/client_id",
                ScopeInterface::SCOPE_STORE
            )
        );
    }

    /**
     * Paginate Data
     *
     * @param array   $data
     * @param integer $pageSize
     * @return array
     */
    public function paginatedData($data, $pageSize = 10)
    {
        $totalRecords = count($data);
        if ($totalRecords) {
            $totalPages   = ceil($totalRecords / $pageSize);
            $data = array_slice($data, 0, (int)$pageSize);
            return array_values($data);
        }
        return [];
    }

    /**
     * Parse parties data for rendering
     *
     * @param array $partiesList
     * @return array $partiesList
     * @return array
     */
    public function parsePartiesData($partiesList)
    {
        $searchResult = [];
        $count = 0;
        foreach ($partiesList as $address) {
            //Condition for filtering address
            $searchResult[$count]['lastName'] = array_key_exists('lastName', $address) ? $address['lastName']: '';
            $searchResult[$count]['firstName'] = array_key_exists('firstName', $address) ? $address['firstName']: '';
            $searchResult[$count]['companyName'] = array_key_exists('companyName', $address) ?
                $address['companyName']: '';
            $searchResult[$count]['contactID'] = array_key_exists('contactID', $address) ?
                $address['contactID']: '';
            $searchResult[$count]['phoneNumber'] = array_key_exists('phoneNumber', $address) ?
                $address['phoneNumber']: '';
            $searchResult[$count]['phoneNumberExten'] = array_key_exists('phoneNumberExten', $address) ?
                $address['phoneNumberExten']: '';

            $addressLine1 = !empty($address['address']['streetLines'][0]) ? $address['address']['streetLines'][0] : '';
            $addressLine2 = !empty($address['address']['streetLines'][1]) ? $address['address']['streetLines'][1] : '';

            $searchResult[$count]['addressData'] = $addressLine1 . ' ' . $addressLine2;
            $searchResult[$count]['address']['streetLines'][0] = $addressLine1;
            $searchResult[$count]['address']['streetLines'][1] = $addressLine2;

            $city = '';
            if (isset($address['address']['city'])) {
                $city = $address['address']['city'];
            } elseif (isset($address['city'])) {
                $city = $address['city'];
            }
            $stateOrProvinceCode = '';
            if (isset($address['address']['stateOrProvinceCode'])) {
                $stateOrProvinceCode = $address['address']['stateOrProvinceCode'];
            } elseif (isset($address['stateOrProvinceCode'])) {
                $stateOrProvinceCode = $address['stateOrProvinceCode'];
            }
            $postalCode = '';
            if (isset($address['address']['postalCode'])) {
                $postalCode = $address['address']['postalCode'];
            } elseif (isset($address['postalCode'])) {
                $postalCode = $address['postalCode'];
            }
            $residential = '';
            if (isset($address['address']['residential'])) {
                $residential = $address['address']['residential'];
            } elseif (isset($address['residential'])) {
                $residential = $address['residential'];
            }
            $searchResult[$count]['city'] = $city;
            $searchResult[$count]['stateOrProvinceCode'] = $stateOrProvinceCode;
            $searchResult[$count]['postalCode'] = $postalCode;
            $searchResult[$count]['residential'] = $residential;

            $count++;
        }
        
        // Filtered AddressBook
        return $searchResult;
    }
}
