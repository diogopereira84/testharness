<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Punchout\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Api\AddressInterface;
use Fedex\Punchout\Api\CustomerInterface;
use Fedex\Punchout\Helper\Data;
use Fedex\Purchaseorder\Model\Po;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Fedex\Customer\Helper\Customer as CustomerHelper;

class Customer implements CustomerInterface
{
    const INVALID_COMPANY_MSG = 'Invalid Company';

    const TECH_TITANS_B_2294849 = 'tech_titans_b_2294849';
    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    protected $addressFactory;
    private CountryFactory $_countryFactory;

    /**
     * CustomerAddress constructor.
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        protected RequestInterface $request,
        private CustomerFactory $customerFactory,
        private ResponseInterface $response,
        private Data $helper,
        private JsonFactory $jsonResultFactory,
        private Po $po,
        protected LoggerInterface $logger,
        private AddressRepositoryInterface $addressRepository,
        private AddressInterfaceFactory $dataAddressFactory,
        CountryFactory $countryFactory,
        private RegionFactory $regionFactory,
        protected CustomerHelper $customerHelper,
        private ToggleConfig $toggleConfig,
    ) {
        $this->_countryFactory = $countryFactory;
    }

    /**
     * Get customer.
     *
     * return array|String
     */
    public function getCustomer()
    {
        $boolFalg = '';
        $xml = simplexml_load_string($this->request->getContent());

        $json = json_encode($xml);
        $output = json_decode($json, true);

        if (!empty($output)) {
            // Verify the company, if verified the proceed further.
            $verified = $this->helper->verifyCompany($xml, 'customer');
            if ($verified['status'] == 'ok') {
                unset($this->helper->_data);
                $isvalid = $this->isValidPunchoutCustomer($verified, $xml);
                $customerData =  $this->ValidateCustomerObject($verified, $isvalid);
                if ($isvalid || isset($customerData['error']) && $customerData['error'] == 1) {
                    return $isvalid ?? $customerData;
                }
                $customerD = $this->customerFactory->create()->getCollection()
                    ->addAttributeToSelect("*")
                    ->addAttributeToFilter("unique_id", array("eq" => $customerData['unique_id']))
                    ->addAttributeToFilter("website_id", array("eq" => $verified['website_id']))
                    ->addAttributeToFilter("store_id", array("eq" => $verified['store_id']))
                    ->load();
                $responseCustomer = $this->getCustomerResponse($customerD, $customerData, $verified);
                if ($responseCustomer) {
                    return $responseCustomer;
                }

            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . self::INVALID_COMPANY_MSG);
                $this->response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
                $this->response->sendHeaders();
                $this->response->setBody(self::INVALID_COMPANY_MSG);
                $this->response->sendResponse();
                $boolFalg = true;
            }
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid Input Data.');
            //send 403 error message
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid Input Data');
            $this->response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
            $this->response->sendHeaders();
            $this->response->setBody('Invalid Input Data');
            $this->response->sendResponse();
            $boolFalg = true;
        }
        return $boolFalg;
    }

    /**
     * Initiate Punchout request and Order request
     *
     * @return string
     */
    public function doPunchOut()
    {
        $xml = simplexml_load_string($this->request->getContent());
        $json = json_encode($xml);
        $output = json_decode($json, true);

        if (!empty($output)) {
            if (isset($output['Request']['PunchOutSetupRequest'])) {
                $verified = $this->helper->verifyCompany($xml, 'customer');
                if ($verified['status'] == 'ok') {
                    $punchoutResponse = $this->punchoutRequest($verified, $xml, $output);
                } else {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . self::INVALID_COMPANY_MSG);
                    $punchoutResponse = $this->helper->throwError(self::INVALID_COMPANY_MSG);
                }
                return $punchoutResponse;

            } elseif (isset($output['Request']['OrderRequest'])) {
                $verified = $this->helper->verifyCompany($xml, 'order');
                if ($verified['status'] == 'ok') {
                    $responseVarified = $this->po->getPoxml($verified, $output);
                } else {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . self::INVALID_COMPANY_MSG);
                    $responseVarified = $this->helper->throwError(self::INVALID_COMPANY_MSG);
                }
                return $responseVarified;
            }
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid Input Data.');
            return $this->helper->throwError('Invalid Input Data'); //send 403 error message
        }

    }

    /**
     * Initiate Punchout reuest
     *
     * @param array $verified
     * @param Object $xml
     * @param array $output
     *
     * @return string
     */
    public function punchoutRequest($verified, $xml, $output)
    {
        unset($this->helper->_data);
        unset($this->helper->_extrinsicData);
        foreach ($verified['type'] as $type) {
            if (isset($verified['rule'][$type])) {
                $validate = $this->helper->validateXmlRuleData($xml, $type, $verified['rule'][$type]);
                if ($validate == 0) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Authentication failed.');
                    return $this->helper->throwError('Authentication failed');
                }
            }
        }

        $customerData = $this->helper->extractCustomerData($verified['company_name'], true);

        if (isset($customerData['error']) && $customerData['error'] == 1) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $customerData['msg']);
            return $this->helper->throwError($customerData['msg']);
        }
        $externalIdentifier = array_key_exists('external_identifier', $customerData) ?
        $customerData['external_identifier'] : $customerData['email'];

        if($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B_2294849)){
            $externalIdentifierId = $customerData['external_identifier_id'] ?? '';

            if ($customerEntityData = $this->findCustomerInEntity($externalIdentifierId)) {
                return $this->isCustomerValid($customerEntityData, $verified, $customerData);
            }

        } else {
            if ($customerEntityData = $this->findCustomerInEntity($externalIdentifier)) {
                return $this->isCustomerValid($customerEntityData, $verified, $customerData);
            }
        }

        $customerD = $this->customerFactory->create()->getCollection()
            ->addAttributeToSelect("*")
            ->addAttributeToFilter("external_identifier", array("eq" => $externalIdentifier))
            ->addAttributeToFilter("website_id", array("eq" => $verified['website_id']))
            ->load();

        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ePro user new identifier is : '.
        $externalIdentifier);

        if (!$customerD || !is_array($customerD->getData()) || !isset($customerD->getData()[0])) {
            $customerD = $this->updateOldExternalIdentifer($verified, $externalIdentifier, $customerD);
        }
        if($customerD->getSize() > 0) {
            $customerNewData = $customerD->getFirstItem();
            $this->customerHelper->updateExternalIdentifier($externalIdentifier, $customerNewData->getId());
        }

        return $this->isCustomerValid($customerD, $verified, $customerData);

    }

    public function findCustomerInEntity($externalIdentifier)
    {
        if($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B_2294849)){
            $customerCollection = $this->customerFactory->create()->getCollection()
                ->addAttributeToSelect("*");

            $filteredCustomer = [];

            foreach ($customerCollection as $customer) {
                $externalIdentifierId = $customer->getData('external_identifier');
                if ($externalIdentifierId) {
                    preg_match('/_(.*?)@/', $externalIdentifierId, $matches);
                    if (!empty($matches[1]) && $matches[1] === $externalIdentifier) {
                        $filteredCustomer[] = $customer;
                    }
                }
            }

            if($filteredCustomer){
                $customerId  = $filteredCustomer[0]['entity_id'];
                $customerD = $this->customerFactory->create()->getCollection()
                    ->addAttributeToSelect("*")
                    ->addAttributeToFilter("entity_id", array("eq" => $customerId));

                if($customerD->getSize() > 0){
                    return $customerD;
                }

                return false;
            }

        } else {
            $customerD = $this->customerFactory->create()->getCollection()
                ->addAttributeToSelect("*");
            $customerD->getSelect()->where("external_id = '$externalIdentifier'");
            if($customerD->getSize() > 0) {
                return $customerD;
            }
            return false;
        }

    }

    public function updateOldExternalIdentifer($verified, $externalIdentifier, $customerD)
    {
        $oldcustomerData = $this->helper->extractCustomerData($verified['company_name'], false);
        $oldexternalIdentifier = array_key_exists('external_identifier', $oldcustomerData) ?
        $oldcustomerData['external_identifier'] : $oldcustomerData['email'];

        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ePro user old identifier is : '.
            $oldexternalIdentifier);

        if ($externalIdentifier != $oldexternalIdentifier) {
            $oldcustomerD = $this->customerFactory->create()->getCollection()
                ->addAttributeToSelect("*")
                ->addAttributeToFilter("external_identifier", array("eq" => $oldexternalIdentifier))
                ->addAttributeToFilter("website_id", array("eq" => $verified['website_id']))
                ->load();
            // @codeCoverageIgnoreStart
            if ($oldcustomerD && is_array($oldcustomerD->getData()) && isset($oldcustomerD->getData()[0])) {

                $oldCustomer = $oldcustomerD->getData()[0];
                $customerObj = $this->customerFactory->create()->load($oldCustomer['entity_id']);
                $customerObj->setData('external_identifier', $externalIdentifier);
                $customerObj->save();

                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Epro Punchout Old External Identifier : '.
                    $oldexternalIdentifier." is modified with new External Identifer : ".
                    $externalIdentifier. " and customer id is : " .$oldCustomer['entity_id']
                );

                $customerD = $this->customerFactory->create()->getCollection()
                    ->addAttributeToSelect("*")
                    ->addAttributeToFilter("external_identifier", array("eq" => $externalIdentifier))
                    ->addAttributeToFilter("website_id", array("eq" => $verified['website_id']))
                    ->load();

            }
            // @codeCoverageIgnoreEnd
        }
        return $customerD;
    }
    /**
     * Check address.
     *
     * @param Object $address
     * @param array $output
     * @param Object $customer
     */
    public function checkAddresses($address, $output, $customer)
    {
        $savestreet = '';
        //Check if DeliverTo tag exists and if its not blank
        if (isset($output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['DeliverTo'])
            && !empty($output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['DeliverTo'])) {
            $deliverTo = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['DeliverTo'];
        } else { //Get name from Address tag if DeliverTo is not available
            $deliverTo = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['Name'];
        }

        // D-82139 || remove space before exploding it
        // B-1299552 - Cleanup Toggle Feature - remove_space_from_name
        $deliverTo = trim($deliverTo);
        $customerName = explode(" ", $deliverTo);
        $firstName = preg_replace('/\W\w+\s*(\W*)$/', '$1', $deliverTo);
        $lastName = $customerName[count($customerName) - 1];
        $streetInfo = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['Street'] ?? '';
        $street = [0 => '', 1 => ''];
        $street = $this->getStreet($streetInfo, $street);

        $city = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['City'];
        $state = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['State'];
        $postalcode = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['PostalCode'];
        $countryEmptyName = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['Country'];
        $countryname = (!empty($countryEmptyName)) ? 'United States' : $countryEmptyName;
        $country = $this->getCountryname($countryname);
        $regionid = $this->getRegionIdByCode($state, $country);
        $regionName = $this->getRegionName($regionid);
        $email = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['Email'];
        $areaCode = !empty($output['Request']['PunchOutSetupRequest']['ShipTo']['Address']
            ['Phone']['TelephoneNumber']['AreaOrCityCode']) ? $output['Request']['PunchOutSetupRequest']
        ['ShipTo']['Address']['Phone']['TelephoneNumber']['AreaOrCityCode'] : '';
        $pnumber = !empty($output['Request']['PunchOutSetupRequest']['ShipTo']['Address']
            ['Phone']['TelephoneNumber']['Number']) ? $output['Request']['PunchOutSetupRequest']
        ['ShipTo']['Address']['Phone']['TelephoneNumber']['Number'] : '';
        $phonenumber = $areaCode . '' . $pnumber;
        $customerAddress[] = $address->toArray();
        $astreet = $address->getStreet();

        $savestreet = count($astreet) > 1 ? $astreet[0] . ',' . $astreet[1] : $astreet[0];

        $addressId = $customerAddress[0]['entity_id'];
        $customerId = $customerAddress[0]['customer_id'];
        if ($savestreet == $street && $customerAddress[0]['city'] == $city
            && $customerAddress[0]['region'] == $regionName && $customerAddress[0]['postcode'] == $postalcode &&
            $customerAddress[0]['country_id'] == $country) {
            try {
                $address = $this->addressRepository->getById($addressId)->setCustomerId($customerId);
                $address->setIsDefaultShipping(true);

                $this->addressRepository->save($address);
            } catch (\Exception $exception) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
            }
        } else {
            $data = $customer->getAddressCollection()
                ->addFieldToFilter('parent_id', $customerId)
                ->addFieldToFilter('postcode', $postalcode)
                ->addFieldToFilter('region', $regionName)
                ->addFieldToFilter('street', $street)
                ->addFieldToFilter('city', $city)
                ->addFieldToFilter('country_id', $country)
                ->getData();
            if (empty($data)) {
                $this->saveCustomerAddress(
                    $firstName,
                    $lastName,
                    $regionid,
                    $regionName,
                    $country,
                    $city,
                    $postalcode,
                    $customerId,
                    $street,
                    $phonenumber,
                    $email
                );
            }
        }
    }

    /**
     * Get country name.
     *
     * @param String $countryname
     *
     * @return Int
     */
    public function getCountryname($countryname)
    {
        $countryName = $countryname;
        $countryCollection = $this->_countryFactory->create()->getCollection();
        $countryId = '';
        foreach ($countryCollection as $country) {
            if ($countryName == $country->getName()) {
                $countryId = $country->getCountryId();
                break;
            }
        }

        return $countryId;
    }

    /**
     * Save address. (Might be used in future, was put on hold due to requirement change)
     *
     */
    public function saveCustomerAddress(
        $firstName,
        $lastName,
        $regionId,
        $regionName,
        $countryId,
        $city,
        $postalcode,
        $customerId,
        $street,
        $telephone,
        $email
    ) {
        $address = $this->dataAddressFactory->create();
        $streetAddress = [$street];
        $address->setFirstname($firstName)
            ->setLastname($lastName)
            ->setCountryId($countryId)
            ->setRegionId($regionId)
            ->setCity($city)
            ->setPostcode($postalcode)
            ->setCustomerId($customerId)
            ->setStreet($streetAddress)
            ->setTelephone($telephone)
            ->setIsDefaultShipping(1)
            ->setCustomAttribute('email_id', $email);
        $this->addressRepository->save($address);
    }

    /**
     * Save customer address
     *
     * @param array $output
     * @param Int   $customerId
     */
    public function saveCustomerNewAddress($output, $customerId)
    {
        $deliverTo = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['DeliverTo'];

        // D-82139 || remove space before exploding it
        // B-1299552 - Cleanup Toggle Feature - remove_space_from_name
        $deliverTo = trim($deliverTo);

        $customerName = explode(" ", $deliverTo);
        $firstName = preg_replace('/\W\w+\s*(\W*)$/', '$1', $deliverTo);
        $lastName = $customerName[count($customerName) - 1];
        $streetInfo = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['Street'] ?? '';
        $street = [0 => '', 1 => ''];

        if (!is_array($streetInfo)) {
            $street = $streetInfo;
        } else {
            $street = !empty($street[0]) ? $street[0] : $street[1];
        }
        $city = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['City'];
        $state = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['State'];
        $postalcode = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['PostalCode'];
        $countryname = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['PostalAddress']['Country'];
        if (!empty($countryname)) {
            $countryname = 'United States';
        }
        $country = $this->getCountryname($countryname);
        $regionid = $this->getRegionIdByCode($state, $country);
        $regionName = $this->getRegionName($regionid);
        $email = $output['Request']['PunchOutSetupRequest']['ShipTo']['Address']['Email'];
        $areacode = !empty($output['Request']['PunchOutSetupRequest']['ShipTo']['Address']
            ['Phone']['TelephoneNumber']['AreaOrCityCode']) ? $output['Request']['PunchOutSetupRequest']
        ['ShipTo']['Address']['Phone']['TelephoneNumber']['AreaOrCityCode'] : '';
        $pnumber = !empty($output['Request']['PunchOutSetupRequest']['ShipTo']['Address']
            ['Phone']['TelephoneNumber']['Number']) ? $output['Request']['PunchOutSetupRequest']
        ['ShipTo']['Address']['Phone']['TelephoneNumber']['Number'] : '';
        $phonenumber = $areacode . '' . $pnumber;
        $this->saveCustomerAddress(
            $firstName,
            $lastName,
            $regionid,
            $regionName,
            $country,
            $city,
            $postalcode,
            $customerId,
            $street,
            $phonenumber,
            $email
        );
    }

    /**
     * Get Region id by code
     *
     * @param String $code
     * @param Int $countryId
     * @return Int
     */
    public function getRegionIdByCode($code, $countryId)
    {
        $region = $this->regionFactory->create();
        return $region->loadByCode($code, $countryId)->getId();
    }

    /**
     * Get Region Name.
     *
     * @param Int $regionid
     *
     * @return String
     */
    public function getRegionName($regionId)
    {
        $region = $this->regionFactory->create();
        return $region->load($regionId)->getName();
    }

    /**
     * getStreet.
     * @param array $streetInfo
     * @param array $street
     * @return bool
     */
    public function getStreet($streetInfo, $street)
    {
        $strStreet = '';
        if (!is_array($streetInfo)) {
            $strStreet = $streetInfo;
        } elseif (!empty($street[0]) && !empty($street[1])) {
            $strStreet = $street[0] . ',' . $street[1];
        } else {
            $strStreet = !empty($street[0]) ? $street[0] : $street[1];
        }
        return $strStreet;
    }

    /**
     * isCustomerValid.
     * @param array $customerD
     * @param array $verified
     * @param array $customerData
     * @return bool
     */
    public function isCustomerValid($customerD, $verified, $customerData)
    {
        if (count($customerD)) {
            //Autologin code logic
            foreach ($customerD as $customer) {
                $validCompanyUser = $this->helper->validateCustomer($customer, $verified['company_id']);
                if ($validCompanyUser) {
                    return $this->isValidCustomer($validCompanyUser, $customer, $verified);
                }
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid Company User');
                return $this->helper->throwError('Invalid Company User');
            }
        }

        return $this->customerResponse($customerData, $verified);
    }

    /**
     * customerResponse.
     * @param array $verified
     * @param array $customerData
     * @return bool
     */
    public function customerResponse($customerData, $verified)
    {
        $token = '';
        $response = $this->helper->lookUpDetails($customerData, $verified);
        if ($response['error'] == 0) {
            $token = $this->helper->sendToken($verified, $response['token']);
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $response['msg']);
            $token = $this->helper->throwError($response['msg']);
        }
        return $token;
    }

    /**
     * customerResponse.
     * @param array $validCompanyUser
     * @param array $customer
     * @param array $verified
     * @return bool
     */
    public function isValidCustomer($validCompanyUser, $customer, $verified)
    {
            if ($customer->getData('is_active') && $this->helper->isActiveCustomer($customer)) {
                $response = $this->helper->getToken($customer, $verified);
                $errorMsg = $response['error'] == 0 ? $this->helper->sendToken($verified, $response['token']) :
                $this->helper->throwError($response['msg']);

            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Inactive customer.');
                $errorMsg = $this->helper->throwError('In-active Customer');
            }
            return $errorMsg;
    }

    /**
     * getCustomerResponse.
     * @param array $customerD
     * @param array $customerData
     * @param array $verified
     * @return bool
     */
    public function getCustomerResponse($customerD, $customerData, $verified)
    {
        $returnResponse = '';

        if (count($customerD)) {
            //Autologin code logic
            foreach ($customerD as $customer) {

                if ($customer->getData('is_active') && $this->helper->isActiveCustomer($customer)) {
                    $response = $this->helper->getToken($customer, $verified);
                    if ($response['error'] == 0) {
                        $returnResponse = $this->helper->sendToken($verified, $response['token']);
                    } else {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $response['msg']);
                        $this->response
                            ->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
                        $this->response->sendHeaders();
                        $this->response->setBody($response['msg']);
                        $this->response->sendResponse();
                        $returnResponse = true;
                    }
                    return $returnResponse;
                } else {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Inactive customer.');
                    $this->response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
                    $this->response->sendHeaders();
                    $this->response->setBody('In-active Customer');
                    $this->response->sendResponse();
                    return true;
                }
            }
        } else {
            //registeration code logic
            return $this->customerRegistor($customerData, $verified);
        }
    }

    /**
     * customerRegistor.
     * @param array $customerData
     * @param array $verified
     * @return bool
     */
    public function customerRegistor($customerData, $verified)
    {
        $response = $this->helper->lookUpDetails($customerData, $verified);

        if (!empty($response) && array_key_exists('error', $response) && $response['error'] == 0) {
            return $this->helper->sendToken($verified, $response['token']);
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $response['msg']);
            $this->response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
            $this->response->sendHeaders();
            $this->response->setBody($response['msg']);
            $this->response->sendResponse();
            return true;
        }
    }

    /**
     * isValidPunchoutCustomer.
     * @param array $customerData
     * @param array $verified
     * @param array $xml
     * @return bool
     */
    public function isValidPunchoutCustomer($verified, $xml)
    {
        foreach ($verified['type'] as $type) {
            if (isset($verified['rule'][$type])) {
                $validate = $this->helper->validateXmlRuleData($xml, $type, $verified['rule'][$type]);
                if ($validate == 0) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid cxml data');
                    $this->response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
                    $this->response->sendHeaders();
                    $this->response->setBody('Invalid cxml data');
                    $this->response->sendResponse();
                    return true;
                }
            }
        }

        return false;
    }
    /**
     * ValidateCustomerObject.
     * @param array $verified
     * @param bool $isvalid
     * @return bool | string
     */
    public function ValidateCustomerObject($verified, $isvalid)
    {
        $customerData = [];
        if (!$isvalid) {
            $customerData = $this->helper
                        ->extractCustomerData($verified['company_name'],true);
            if (isset($customerData['error']) && $customerData['error'] == 1) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with customer data.');
                return $customerData;
            }
            return $customerData;
        }

    }

}
