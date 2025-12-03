<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session;
use Fedex\ShipTo\Helper\Data as ShipToHelper;
use Psr\Log\LoggerInterface;

class ExpressCheckout implements ArgumentInterface
{
    /**
     * @var EnhancedProfile $enhancedProfile
     */
    protected $enhancedProfile;

    /**
     * @var SsoConfiguration $ssoConfiguration
     */
    protected $ssoConfiguration;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * @var ShipToHelper $shipToHelper
     */
    protected $shipToHelper;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * Express Checkout Constructor
     *
     * @param EnhancedProfile $enhancedProfile
     * @param SsoConfiguration $ssoConfiguration
     * @param ToggleConfig $toggleConfig
     * @param Session $customerSession
     * @param ShipToHelper $shipToHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        EnhancedProfile $enhancedProfile,
        SsoConfiguration $ssoConfiguration,
        ToggleConfig $toggleConfig,
        Session $customerSession,
        ShipToHelper $shipToHelper,
        LoggerInterface $logger
    ) {
        $this->enhancedProfile = $enhancedProfile;
        $this->ssoConfiguration = $ssoConfiguration;
        $this->toggleConfig = $toggleConfig;
        $this->customerSession = $customerSession;
        $this->shipToHelper = $shipToHelper;
        $this->logger = $logger;
    }

    /**
     * Get customer profile session
     *
     * @return array
     */
    public function getCustomerProfileSession()
    {
        return $this->enhancedProfile->getLoggedInProfileInfo();
    }

    /**
     * Set Customer Profile Session With Expiry Token
     *
     * @return array
     */
    public function getCustomerProfileSessionWithExpiryToken()
    {
        if ($this->customerSession->getProfileSession()) {
            $profileInfo = $this->customerSession->getProfileSession();
            $creditCardList = [];
            if (isset($profileInfo->output->profile->creditCards)) {
                $creditCardList = $profileInfo->output->profile->creditCards;
            }
            foreach ($creditCardList as $cardInfo):
                $isTokenExpiry = $this->enhancedProfile->getTokenIsExpired($cardInfo->tokenExpirationDate);
                if ($isTokenExpiry) {
                    $cardInfo->tokenExpired = true;
                } else {
                    $cardInfo->tokenExpired = false;
                }
            endforeach;
            $this->getCustomerProfileSessionWithExpiryAccount();
            $this->setDeliveryZipCode();
            return $this->customerSession->getProfileSession();
        } else {
            return [];
        }
    }

    /**
     * Set Customer Profile Session With Expiry Account
     *
     * @return void
     */
    public function getCustomerProfileSessionWithExpiryAccount()
    {
        $accountList = [];
        $profileInfo = $this->customerSession->getProfileSession();
        if (isset($profileInfo->output->profile->accounts)) {
            $accountList = $profileInfo->output->profile->accounts;
        }
        foreach ($accountList as $accountInfo):
            $accountSummary = $this->enhancedProfile->getAccountSummary($accountInfo->accountNumber);
            if (isset($accountSummary['account_status']) && (strtolower($accountSummary['account_status']) == 'active'
                    || $accountSummary['account_status'] == null)) {
                $accountInfo->accountValid = true;
            } else {
                $accountInfo->accountValid = false;
            }
        endforeach;
    }

    /**
     * To identify the retail store
     *
     * @return boolean true|false
     */
    public function getIsRetail()
    {
        return $this->ssoConfiguration->isRetail();
    }

    /**
     * Checks if the customer is retail
     *
     * @return boolean true|false
     */
    public function getIsFclCustomer()
    {
        return $this->ssoConfiguration->isFclCustomer();
    }

    /**
     * Set delivery zipcode in profile session
     *
     * @return void
     */
    public function setDeliveryZipCode()
    {
        if ($this->customerSession->getProfileSession()) {
            $profileInfo = $this->customerSession->getProfileSession();
            if (isset($profileInfo->output->profile->delivery->preferredDeliveryMethod)
            && isset($profileInfo->output->profile->delivery->preferredStore)
            ) {
                $postalCode = '';
                $city = null;
                $state = null;
                $pickupAddress = null;
                $locationId = $profileInfo->output->profile->delivery->preferredStore;
                $addressInfo = $this->shipToHelper->getAddressByLocationId($locationId);
                if (isset($addressInfo['address'])) {
                    $address = json_decode($addressInfo['address']);
                    if ($address->address->postalCode) {
                        $postalCode = $address->address->postalCode;
                        $city = $address->address->city;
                        $state = $address->address->stateOrProvinceCode;
                        $pickupAddress = $this->getPickupAddress($address, $postalCode);
                    }
                } else {
                    $this->logger->error(__METHOD__.':'.__LINE__.':'.
                    'Error in API while getting address by location id : '. $locationId);
                }
                $profileInfo->output->profile->delivery->postalCode = $postalCode;
                $profileInfo->output->profile->delivery->city = $city;
                $profileInfo->output->profile->delivery->state = $state;
                $profileInfo->output->profile->delivery->pickupAddress = $pickupAddress;
            }
        }
    }

    /**
     * Set delivery zipcode in profile session
     *
     * @param object $address
     * @param int $postalCode
     *
     * @return string
     */
    public function getPickupAddress($address, $postalCode)
    {
        return $address->address->address1 . " , " . $address->address->city .
            " , " . $address->address->stateOrProvinceCode . " , " . $address->address->postalCode;
    }

    /**
     * Get if request from SDE with FCL On
     *
     * @return bool
     */
    public function getIsRequestFromSdeStoreFclLogin()
    {
        return $this->ssoConfiguration->getIsRequestFromSdeStoreFclLogin();
    }

    /**
     * Get if request from SelfReg with FCL Enabled
     *
     * @return bool
     */
    public function isSelfRegCustomerWithFclEnabled()
    {
        return $this->ssoConfiguration->isSelfRegCustomerWithFclEnabled();
    }
}
