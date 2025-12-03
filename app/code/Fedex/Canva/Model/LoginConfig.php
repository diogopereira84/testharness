<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model;

use Fedex\Canva\Api\Data\LoginConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class LoginConfig implements LoginConfigInterface
{
    /**
     * Canva login title xml path
     */
    public const XML_PATH_FEDEX_CANVA_LOGIN_TITLE = 'sso/canva_login/title';

    /**
     * Canva login description xml path
     */
    public const XML_PATH_FEDEX_CANVA_LOGIN_DESCRIPTION = 'sso/canva_login/description';

    /**
     * Canva login register button label xml path
     */
    public const XML_PATH_FEDEX_CANVA_LOGIN_REGISTER_BUTTON_LABEL = 'sso/canva_login/register_button_label';

    /**
     * Canva login login button label xml path
     */
    public const XML_PATH_FEDEX_CANVA_LOGIN_LOGIN_BUTTON_LABEL = 'sso/canva_login/login_button_label';

    /**
     * Canva login continue button label xml path
     */
    public const XML_PATH_FEDEX_CANVA_LOGIN_CONTINUE_BUTTON_LABEL = 'sso/canva_login/continue_button_label';

    /**
     * Canva Warning Login Modal Toggle
     */
    public const TOGGLE_CANVA_LOGIN_MODAL = 'environment_toggle_configuration/environment_toggle/tiger_warning_canva_login_modal';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_LOGIN_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_LOGIN_DESCRIPTION,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getRegisterButtonLabel(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_LOGIN_REGISTER_BUTTON_LABEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getLoginButtonLabel(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_LOGIN_LOGIN_BUTTON_LABEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getContinueButtonLabel(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_LOGIN_CONTINUE_BUTTON_LABEL,
            ScopeInterface::SCOPE_STORE
        );
    }
}
