<?php

 /**
 * Fedex_Login
 *
 * @category   Fedex
 * @package    Fedex_Login
 */

declare(strict_types=1);

namespace Fedex\Company\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\Login\Model\UserPreferenceFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Company\Api\CompanyManagementInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * UserPreferenceHelper Helper Class
 */
class UserPreferenceHelper extends AbstractHelper
{
    /**
     * UserPreferenceHelper constructor
     *
     * @param Context $context
     */
    public function __construct(
        private UserPreferenceFactory $userPreference,
        Context $context,
        protected CustomerSession $customerSession,
        protected CompanyManagementInterface $companyManagement,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Update user Profile Data if data exist in db
     */
    public function updateProfileResponse($userProfileData = null)
    {
        try {
            if ($this->customerSession->isLoggedIn()) {
                if (!$userProfileData) {
                    $userProfileData = $this->customerSession->getProfileSession();
                }

                if ($userProfileData) {
                    $customer = $this->customerSession->getCustomer();
                    $email = $customer->getData("secondary_email");
                    $urlExt = $this->getCompanyExtensionUrl(
                        $this->customerSession->getCustomerId()
                    );

                    if ($email && $urlExt) {
                        $userPreferenceModel = $this->userPreference->create();
                        $userPreferenceData = $userPreferenceModel->getCollection()->addFieldToFilter('email', ['eq' => $email])
                        ->addFieldToFilter('company_url', ['eq' => $urlExt]);

                        $currentUserData = $userPreferenceData->getData();

                        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_fix_billing_field_from_acc_api')) {
                            if(!isset($userProfileData->output->profile->accounts) && !count($currentUserData)){
                                foreach($userProfileData->output->profile->preferences as $preferenceEach){
                                    if (strtoupper($preferenceEach->name) == "INVOICE_NUMBER" && property_exists($preferenceEach, "values") && $preferenceEach->values[0]->name == 'defaultValue' && !empty($preferenceEach->values[0]->value)
                                ) {
                                        $userProfileData->output->profile->accounts = [];
                                  }
                                }
                            }
                        }

                        if (is_array($currentUserData) && count($currentUserData) > 0) {
                            $newPreferences = [];
                            foreach ($currentUserData as $item) {
                                $newPreferences[] = [
                                    'name' => $item['key'],
                                    'values' => [
                                        [
                                            'name' => 'defaultValue',
                                            'value' => $item['value']
                                        ]
                                    ]
                                ];
                                if ((strtoupper($item['key']) == "INVOICE_NUMBER") && !isset($userProfileData->output->profile->accounts)) {
                                      $userProfileData->output->profile->accounts = [];
                                }
                            }
                            
                            if (isset($userProfileData->output) && isset($userProfileData->output->profile)) {
                                $newPreferencesObjects = array_map(function($preference) {
                                    return (object)[
                                        'name' => $preference['name'],
                                        'values' => array_map(function($value) {
                                            return (object)$value;
                                        }, $preference['values'])
                                    ];
                                }, $newPreferences);
                                $userProfileData->output->profile->preferences = $newPreferencesObjects;
                                $this->customerSession->setProfileSession($userProfileData);

                                return true;
                            }
                        
                        }
                    }
                }
            }

            return false;
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in update profile preference' . $e->getMessage());
        }
    }
    
    /**
     * Get company url extension
     *
     * @param int $customerId
     * @return string
     */
    protected function getCompanyExtensionUrl($customerId)
    {
        $company = $this->companyManagement->getByCustomerId($customerId);
	if (is_object($company)) {
	    return $company->getCompanyUrlExtention();
	}

	return;
    }

     /**
     * Validates the data from a sheet.
     */
    public function validateSheetData($datas, $extUrl)
    {
        $headerArray = [];
        foreach($datas[0] as $key => $header){
        $headerArray[$key] = $header;
        }

        if (count(array_unique($headerArray))<count($headerArray)) {
            $result = [
                "status" => false,
                "message" =>__('Csv File Header Duplicate')
            ];
            return $result;
        }

        array_shift($datas);

        if(sizeof($datas)<1){
            $result = [
                "status" => false,
                "message" =>__('Empty Data File')
            ];
            return $result;
        }

        $errorRow = [];
        foreach($datas as $dataskey => $data){

            foreach($data as $key => $value){
                //skip loop if email is blank
                if(empty($data[0])){
                    $errorRow [$dataskey+2] = __('Email');
                    break;
                }

                if($key >0){

                    $userPreferenceModel = $this->userPreference->create();
                    $userPreferenceData = $userPreferenceModel->getCollection()->addFieldToFilter('email', ['eq' => $data[0]])->addFieldToFilter('company_url', ['eq' => $extUrl])->addFieldToFilter('key', ['eq' => $headerArray[$key]])->getFirstItem();

                    if ($headerArray[$key] == "invoice_number") {
                        if (strpos($data[$key], 'E') !== false) {
                            $data[$key] = null;
                            $errorRow [$dataskey+2] = $headerArray[$key];
                            break;
                        }
                    }

                    try {
                        if($userPreferenceData->getId()){
                        //Update Data (update only value field)
                        $userPreferenceModel->load($userPreferenceData->getId(), "id");
                        
                        if(empty($data[$key])){
                            $userPreferenceModel->delete();
                        } else {
                            $userPreferenceModel->setValue($data[$key]);
                            $userPreferenceModel->save();
                        }

                        }elseif (!empty($data[$key])) {
                        //Insert Data
                        $userPreferenceModel->setEmail($data[0]);
                        $userPreferenceModel->setCompanyUrl($extUrl);
                        $userPreferenceModel->setKey($headerArray[$key]);
                        $userPreferenceModel->setValue($data[$key]);
                        $userPreferenceModel->save();
                        }
                    } catch (Exception $e) {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in import user profile preference for user ' . $data[0] . " for field " . $headerArray[$key] . $e->getMessage());
                    }
                }
            }
        }

        if(sizeof($errorRow)>0){
            $errorMesssage = __('Missing data in some rows columns ');
            foreach($errorRow as $errorRowKey => $errorRowKeyValue){
                $errorMesssage .= '<b>Row</b> ' . $errorRowKey . ' : ' . $errorRowKeyValue . ', ';
            }
            $result = [
                "status" => false,
                "message" => $errorMesssage
            ];
        }else{
            $result = [
                "status" => true,
                "message" => __('Import Successfully ')
            ];
        }

        return $result;
    }

}
