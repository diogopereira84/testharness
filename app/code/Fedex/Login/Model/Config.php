<?php
/**
 * @category    Fedex
 * @package     Fedex_Login
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Login\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const IS_CONFIRMATION_EMAIL_REQUIRED = 'customer/create_account/confirm';
    private const VERIFICATION_EMAIL_FROM = 'sso/user_email_verification/verification_from_email';
    private const VERIFICATION_EMAIL_SUBJECT = 'sso/user_email_verification/verification_email_subject';
    private const LINK_EXPIRATION_TIME = 'sso/user_email_verification/verification_email_expiry_duration';
    private const EMAIL_VERIFICATION_TEMPLATE = 'fedex_fcl_user_email_verification_template';
    private const INACTIVE_USER_ERROR_MESSAGE = 'sso/user_email_verification/inactive_user_error_message';
    private const INACTIVE_USER_LANDING_PAGE_LINK = 'sso/user_email_verification/inactive_user_landingpage_link';
    private const SGC_INACTIVE_ERROR_MESSAGE = 'environment_toggle_configuration/environment_toggle/sgc_inactive_error_message';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * Check Require Email Confirmation value in admin
     *
     * @return boolean
     */
    public function isConfirmationEmailRequired()
    {
        return $this->scopeConfig->getValue(self::IS_CONFIRMATION_EMAIL_REQUIRED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get email from field for email verification
     *
     * @return string
     */
    public function getVerificationEmailFrom()
    {
        return $this->scopeConfig->getValue(self::VERIFICATION_EMAIL_FROM, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get email verification subject
     *
     * @return string
     */
    public function getVerificationEmailSubject()
    {
        return $this->scopeConfig->getValue(self::VERIFICATION_EMAIL_SUBJECT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get link expiration time
     *
     * @return string
     */
    public function getLinkExpirationTime()
    {
        return $this->scopeConfig->getValue(self::LINK_EXPIRATION_TIME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get inactive user error message
     *
     * @return string
     */
    public function getInactiveUserErrorMessage(): string
    {
        return $this->scopeConfig->getValue(self::INACTIVE_USER_ERROR_MESSAGE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get inactive user landing page link
     *
     * @return string
     */
    public function getInactiveUserLandingPageLink(): string
    {
        return $this->scopeConfig->getValue(self::INACTIVE_USER_LANDING_PAGE_LINK, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Email Verification Template
     *
     * @return string
     */
    public function getEmailVerificationTemplate()
    {
        return self::EMAIL_VERIFICATION_TEMPLATE;
    }

     /**
     * Check if SGC Inactive Error Message is enabled
     *
     * @return bool
     */
    public function isSgcInactiveErrorMessageEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::SGC_INACTIVE_ERROR_MESSAGE, ScopeInterface::SCOPE_STORE);
    }
}
