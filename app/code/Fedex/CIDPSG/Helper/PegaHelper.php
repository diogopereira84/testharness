<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Helper;

use Fedex\Header\Helper\Data as HeaderData;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\CIDPSG\Helper\AdminConfigHelper;

/**
 * CIDPSG PegaHelper class
 */
class PegaHelper extends AbstractHelper
{
    public const PEGA_API_REQUEST = 'pega_api_request';
    public const PEGA_API_RESPONSE = 'pega_api_response';

    public $formData;

    /**
     * AdminConfigHelper Constructor
     *
     * @param Context $context
     * @param PunchoutHelper $punchoutHelper
     * @param LoggerInterface $logger
     * @param Curl $curl
     * @param TimezoneInterface $timezoneInterface
     * @param AdminConfigHelper $adminConfigHelper
     * @param HeaderData $headerData
     */
    public function __construct(
        Context $context,
        protected PunchoutHelper $punchoutHelper,
        protected LoggerInterface $logger,
        protected Curl $curl,
        protected TimezoneInterface $timezoneInterface,
        protected AdminConfigHelper $adminConfigHelper,
        protected HeaderData $headerData
    ) {
        parent::__construct($context);
    }

    /**
     * To set account form data in PEGA API.
     *
     * @param string $formData
     * @return array
     */
    public function getPegaApiResponse($formData)
    {
        $dataString = '';
        try {
            $authenticationDetails = $this->getAuthenticationDetails();
            $dataString = $this->prepareData($formData);
            $setupURL = $this->adminConfigHelper->getPegaAccountCreateApiUrl();

            if (!$authenticationDetails['gateWayToken'] || !$authenticationDetails['accessToken']) {
                $response = [
                    "errors" => [
                        [
                            "code" => "Token_Error",
                            "message" => "Some error occured in generating token"
                        ]
                    ]
                ];
                if ($this->adminConfigHelper->isLogEnabled()) {
                    $this->printLog($dataString, $response);
                }
                $this->setPegaRequestResponse($dataString, $response);

                return $response;
            }
            $authHeaderVal = $this->headerData->getAuthHeaderValue();
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Content-Length: " . strlen($dataString),
                $authHeaderVal . $authenticationDetails['gateWayToken'],
                "Cookie: Bearer=" . $authenticationDetails['accessToken'],
                /*Except header we are sending here becuase this will be set by curl automatically
                when request size is large and target server doesn't support this header at this moment.
                The deafult value for this header is 100-continue and we are overwriting it with empty value.*/
                "Expect: ",
            ];

            $response  = $this->callPegaAccountCreateApi($setupURL, $headers, $dataString);

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' PEGA API ERROR:');
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Account Request Form Data:');
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . var_export($formData, true));
            $response = [
                "errors" => [
                    [
                        "code" => "PEGA_API_Error",
                        "message" => "Some Error Occured"
                    ]
                ]
            ];
            $this->printLog($dataString, $response);
        }
        $this->setPegaRequestResponse($dataString, $response);

        return $response;
    }

    /**
     * Get authentication details
     *
     * @return array
     */
    public function getAuthenticationDetails()
    {
        $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        $accessToken = $this->punchoutHelper->getTazToken();

        return [
            'gateWayToken' => $gateWayToken,
            'accessToken' => $accessToken
        ];
    }

    /**
     * Call PEGA API to create CIDPSG account
     *
     * @param string $setupURL
     * @param array $headers
     * @param string $dataString
     * @return array
     */
    public function callPegaAccountCreateApi($setupURL, $headers, $dataString)
    {
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
                CURLOPT_POSTFIELDS => $dataString
            ]
        );

        $this->curl->post($setupURL, $dataString);
        $output = $this->curl->getBody();
        $response = json_decode($output, true);

        if ($this->adminConfigHelper->isLogEnabled()) {
            $this->printLog($dataString, $response);
        }

        return $response;
    }

    /**
     * To prepare PEGA API json data
     *
     * @param array $formData
     * @return string
     */
    public function prepareData($formData)
    {
        $currDate = $this->timezoneInterface->date()->format("Y-m-d\TH:i:s\Z");
        $invoiceRequired = isset($formData['charge_acc_bill_checkbox_val']) ? 1 : 0;
        $phoneFaxDefault = ['areaCd' => '', 'lineNum' => ''];
        $physicalPhone = !empty($formData['phoneno']) ? $this->splitPhone($formData['phoneno']) : $phoneFaxDefault;
        $physicalFax = !empty($formData['fax']) ? $this->splitPhone($formData['fax']) : $phoneFaxDefault;
        $corPhone = !empty($formData['corr_phoneno']) ?
        $this->splitPhone($formData['corr_phoneno']) : $phoneFaxDefault;
        $corFax = !empty($formData['corr_fax']) ? $this->splitPhone($formData['corr_fax']) : $phoneFaxDefault;
        $tcPhone = !empty($formData['tc_phoneno']) ? $this->splitPhone($formData['tc_phoneno']) : $phoneFaxDefault;
        $corrAdd = $formData['corr_add_checkbox_val'];
        $countryCode = $corrAdd ? $formData['cid_psg_country'] : $formData['corr_cid_psg_country'];

        $dataArr = [
            'caseTypeID' => 'FXO-ECAM-Work-AccountCreation',
            'processID' => 'pyStartCase',
            'applicationSource' => $formData['app_source'] ?? '',
            'legalCompanyName' => $formData['legal_company_name'] ?? '',
            'federalId' => $formData['federal_id'] ?? '',
            'numOfEmpNationwide' => $formData['employees_no_nationwide'] ?? '',
            'duns' => $formData['dun_bradstreet_no'] ?? '',
            'nameOnAccount' => $formData['pre_acc_name'] ? $formData['pre_acc_name'] : $formData['legal_company_name'],
            'isInvoicingRequired' => $invoiceRequired ? 'Yes' : 'No',
            'isTaxexemptRequired' => $formData['tax_exempt_checkbox_val'] ? 'Yes' : 'No',
            'isAdditionalAuthdUserReq' => 'No',
            'isTermAndConditionAccepted' => $formData['tc_checkbox_val'] ? 'Yes' : 'No',
            'isMoreThanSixUserRequired' => 'No',
            'preferredCommunication' => 'Mail',
            'address' => [
                [
                    'type' => 'Physical',
                    'addressLine1' => $formData['street_add'] ?? '',
                    'addressLine2' => $formData['add_line2'] ?? '',
                    'suite' => $formData['suite_other'] ?? '',
                    'city' => $formData['city'] ?? '',
                    'inCityLimitsFlag' => '',
                    'stateOrProvince' => $formData['cid_psg_state'] ?? '',
                    'county' => '',
                    'country' => $formData['cid_psg_country']=='US' ? 'USA' : 'CAN',
                    'zipOrPostalCode' => $formData['zip'] ?? '',
                    'geoCodeValue' => '',
                    'isAddressOverriden' => 'No',
                    'addressVerificationStatus' => 'Unverified'
                ],
                [
                    'type' => 'Correspondence',
                    'addressLine1' => $corrAdd ? $formData['street_add'] : $formData['corr_street_add'],
                    'addressLine2' => $corrAdd ? $formData['add_line2'] : $formData['corr_suite_other'],
                    'suite' => $corrAdd ? $formData['suite_other'] : $formData['corr_suite_other'],
                    'city' => $corrAdd ? $formData['city'] : $formData['corr_city'] ?? '',
                    'inCityLimitsFlag' => '',
                    'stateOrProvince' => $corrAdd ? $formData['cid_psg_state'] : $formData['corr_cid_psg_state'],
                    'county' => '',
                    'country' => $countryCode == 'US' ? 'USA' : 'CAN',
                    'zipOrPostalCode' => $corrAdd ? $formData['zip'] : $formData['corr_postal_code'],
                    'geoCodeValue' => '',
                    'isAddressOverriden' => 'No',
                    'addressVerificationStatus' => 'Unverified'
                ]
            ],
            'contact' => [
                [
                    'type' => 'Physical',
                    'title' => '',
                    'firstName' => $formData['contact_fname'] ?? '',
                    'lastName' => $formData['contact_lname'] ?? '',
                    'emailAddress' => $formData['email'] ?? '',
                    'comments' => '',
                    'dateAdded' => $currDate,
                    'phone' => [
                        'areaCd' => $physicalPhone['areaCd'],
                        'lineNum' => $physicalPhone['lineNum']
                    ],
                    'fax' => [
                        'areaCd' => $physicalFax['areaCd'],
                        'lineNum' => $physicalFax['lineNum']
                    ]
                ],
                [
                    'type' => 'Correspondence',
                    'title' => '',
                    'firstName' => $corrAdd ? $formData['contact_fname'] : $formData['corr_fname'],
                    'lastName' => $corrAdd ? $formData['contact_lname'] : $formData['corr_lname'],
                    'emailAddress' => $corrAdd ? $formData['email'] : $formData['corr_email'],
                    'comments' => '',
                    'dateAdded' => $currDate,
                    'phone' => [
                        'areaCd' => $corrAdd ? $physicalPhone['areaCd'] : $corPhone['areaCd'],
                        'lineNum' => $corrAdd ? $physicalPhone['lineNum'] : $corPhone['lineNum']
                    ],
                    'fax' => [
                        'areaCd' =>  $corrAdd ? $physicalFax['areaCd'] : $corFax['areaCd'],
                        'lineNum' =>  $corrAdd ? $physicalFax['lineNum'] : $corFax['lineNum']
                    ]
                ]
            ],
            'questionnaire' => [
                'estimatedMonthlySpending' => $formData['office_spent_amount'] ?? '',
                'percentageMonthlySpendingWithFXK' => $formData['spent_percent_with_fedex'] ?? '',
                'estimatedMonthlyGroundShipment' => $formData['ground_ship_amount'] ?? '',
                'percentageMonthlyGroundShipmentWithFXK' => $formData['ground_ship_perrcent_with_fedex'] ?? '',
                'estimatedMonthlyExpressShipment' => $formData['express_ship_amount'] ?? '',
                'percentMonthlyExpressShipmentWithFXK' => $formData['express_ship_percent_with_fedex'] ?? '',
                'estimatedMonthlyInternationalShipment' => $formData['inter_ship_amount'] ?? '',
                'percentageMonthlyInternationalShipmentWithFXK' => $formData['inter_ship_percent_with_fedex'] ?? ''
            ],
            'existingFedExAccountList' => [
                'acctNum' => $formData['fedex_office_acc_no'] ?? '',
                'nameOnAcct' =>  $formData['company_name_on_acc'] ?? '',
                'type' => 'US_NDC'
            ]
        ];

        if ($invoiceRequired) {
            $dataArr = $this->getInvoicingData(
                $dataArr,
                $formData,
                $currDate,
                $phoneFaxDefault,
                $physicalPhone,
                $physicalFax
            );
        }

        /* Moved it before Billing if invoicing set to yes
        to resolve issue in PEGA API ECAM UI billing address */
        $dataArr['contact'][] = [
            'type' => 'Account_Owner',
            'title' => $formData['tc_title'] ?? '',
            'firstName' => $formData['tc_fname'] ?? '',
            'lastName' => $formData['tc_lname'] ?? '',
            'emailAddress' => $formData['tc_email'] ?? '',
            'dateAdded' => $currDate,
            'phone' => [
                'areaCd' =>  $tcPhone['areaCd'],
                'lineNum' => $tcPhone['lineNum']
            ],
            'fax' => [
                'type' => 'Account_Owner'
            ]
        ];

        if ($formData['is_card_required']) {
            $dataArr = $this->getAuthorizedUserData($dataArr, $formData);
        }

        if ($formData['tax_exempt_checkbox_val']) {
            $dataArr = $this->getTaxExemptData($dataArr, $formData);
        }

        return json_encode($dataArr, JSON_UNESCAPED_SLASHES);
    }

    /**
     * To create array data for Invoicing
     *
     * @param array $dataArr
     * @param array $formData
     * @param string $currDate
     * @param array $phoneFaxDefault
     * @param array $physicalPhone
     * @param array $physicalFax
     * @return array
     */
    public function getInvoicingData(
        $dataArr,
        $formData,
        $currDate,
        $phoneFaxDefault,
        $physicalPhone,
        $physicalFax
    ) {
        $billAdd = $formData['charge_acc_bill_checkbox_val'];
        $chargePhone = !empty($formData['charge_phoneno']) ?
        $this->splitPhone($formData['charge_phoneno']) : $phoneFaxDefault;
        $chargeFax = !empty($formData['charge_fax']) ? $this->splitPhone($formData['charge_fax']) : $phoneFaxDefault;

        $dataArr['invoicing'] = [
            "dateOfIncorporation" => $formData['date_of_incorp'] ?
            $this->timezoneInterface->date($formData['date_of_incorp'])->format("Y-m-d") : '',
            "stateOfIncorporation" => $formData['state_of_incorp'] ?? '',
            "inBusinessSince" => $formData['in_buiseness_since'] ?
            $this->timezoneInterface->date($formData['in_buiseness_since'])->format("Y-m-d") : '',
            "stateOfBusiness" => $formData['state_of_business'] ?? '',
            "businessType" => $formData['nature_of_business'] ?? '',
            "accountUsingCountry" => $formData['business_acc_used_in'] ?? '',
        ];

        if ($formData['charge_special_requirements']) {
            $dataArr['invoicing']['poCodes'] = [
                $formData['applicable_requirements'] ?? ''
            ];
        }

        $countryCode = $billAdd ? $formData['cid_psg_country'] : $formData['charge_cid_psg_country'];

        $dataArr['address'][] = [
            'type' => 'Billing',
            'addressLine1' => $billAdd ? $formData['street_add'] : $formData['charge_street_add'],
            'addressLine2' => $billAdd ? $formData['add_line2'] : $formData['charge_add_line2'],
            'suite' =>  $billAdd ? $formData['suite_other'] : $formData['charge_suite_other'],
            'city' =>  $billAdd ? $formData['city'] : $formData['charge_city'],
            'inCityLimitsFlag' => '',
            'stateOrProvince' =>  $billAdd ? $formData['cid_psg_state'] : $formData['charge_cid_psg_state'],
            'county' => '',
            'country' =>  $countryCode == 'US' ? 'USA' : 'CAN',
            'zipOrPostalCode' => $billAdd ? $formData['zip'] : $formData['charge_postal_code'],
            'geoCodeValue' => '',
            'isAddressOverriden' => 'No',
            'addressVerificationStatus' => 'Unverified'
        ];

        $dataArr['contact'][] = [
            'type' => 'Billing',
            'title' => '',
            'firstName' => $billAdd ? $formData['contact_fname'] : $formData['charge_fname'],
            'lastName' => $billAdd ? $formData['contact_lname'] : $formData['charge_lname'],
            'emailAddress' => $billAdd ? $formData['email'] : $formData['charge_email'],
            'dateAdded' => $currDate,
            'phone' => [
              'areaCd' => $billAdd ? $physicalPhone['areaCd'] : $chargePhone['areaCd'],
              'lineNum' => $billAdd ? $physicalPhone['lineNum'] : $chargePhone['lineNum']
            ],
            'fax' => [
              'areaCd' => $billAdd ? $physicalFax['areaCd'] : $chargeFax['areaCd'],
              'lineNum' => $billAdd ? $physicalFax['lineNum'] : $chargeFax['lineNum']
            ]
        ];

        return $dataArr;
    }

    /**
     * To create array data for Authorized User
     *
     * @param array $dataArr
     * @param array $formData
     * @return array
     */
    public function getAuthorizedUserData($dataArr, $formData)
    {
        $dataArr['authorizedUser'] = [
            [
                "cardDisplayName" => $formData['pre_acc_name'] ?? '',
                "emailAddress" => '',
                "isUserCardRequired" => $formData['is_card_required'] ? 'Yes' : 'No',
                "isBulkDelivery" => 'No',
                "isCustomInstruction" => 'No',
                "isAuthorizedUserCommunicationSuppressed" => 'No',
            ]
        ];

        return $dataArr;
    }

    /**
     * To create array data for Tax exempt status
     *
     * @param array $dataArr
     * @param array $formData
     * @return array
     */
    public function getTaxExemptData($dataArr, $formData)
    {
        if (isset($formData['state_of_exemption']) && !empty($formData['state_of_exemption'])) {
            foreach ($formData['state_of_exemption'] as $stateCode) {
                $taxExemptData[] = [
                    'nameOnCertificate' => $formData['name_on_certificate'],
                    'certificateNumber' => $formData['no_of_certificate'],
                    'stateOfExemption' => $stateCode,
                    'nameInitial' => $formData['initials']
                ];
            }
            $dataArr['taxExempt'] = $taxExemptData;
        }

        return $dataArr;
    }

    /**
     * To print log
     *
     * @param string $request
     * @param array $response
     * @return void
     */
    public function printLog($request, $response)
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' PEGA API End Point URL:');
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $this->adminConfigHelper->getPegaAccountCreateApiUrl());
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' PEGA API Account Create Request:');
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $request);
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' PEGA API Account Create Response:');
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . json_encode($response));
    }

    /**
     * To persist request and response
     *
     * @param string $req
     * @param array $resp
     * @return void
     */
    public function setPegaRequestResponse($req, $resp)
    {
        $this->adminConfigHelper->setValue(self::PEGA_API_REQUEST, $req);
        $this->adminConfigHelper->setValue(self::PEGA_API_RESPONSE, json_encode($resp));
    }

    /**
     * To break area code and line no for Phone and fax
     *
     * @param string $str
     * @return array
     */
    public function splitPhone($str)
    {
        $str = trim(str_replace(['(', ')', '-'], '', $str));
        $phoneData['areaCd'] = trim(substr($str, 0, 3));
        $phoneData['lineNum'] = trim(substr($str, 3));

        return $phoneData;
    }
}
