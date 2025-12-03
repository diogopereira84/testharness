<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\ViewModel;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\Canva\Api\Data\LoginConfigInterface;
use Fedex\SSO\Api\Data\ConfigInterface as SSOConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Asset\Repository;

class ConfigAdapter implements ArgumentInterface
{
    /**
     * Toggle for Enabling new Back, Browser Back and Publish Button functions.
     */
    private const TIGER_E424480_TOGGLE = 'tiger_e424480';
    private const TOGGLE_FEATURE_KEY = 'enable_canva_buttons_new_flow';
    private const CANVA_LOGIN_PATH = 'canva/index/login';
    private const LABEL = 'label';
    private const HREF = 'href';

    /**
     * @param UrlInterface $url
     * @param SSOConfig $ssoConfig
     * @param LoginConfigInterface $config
     * @param ToggleConfig $toggleConfig
     * @param Repository $assetRepository
     */
    public function __construct(
        private UrlInterface $url,
        private SSOConfig $ssoConfig,
        private LoginConfigInterface $loginConfig,
        private ToggleConfig $toggleConfig,
        private Repository $assetRepository
    ) {
    }

    /**
     * Return the login modal title
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->ssoConfig->isEnabled() ?? false;
    }

    /**
     * Return the login modal title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->loginConfig->getTitle() ?? '';
    }

    /**
     * Return the login modal description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->loginConfig->getDescription() ?? '';
    }

    /**
     * Return the login modal register button label
     *
     * @return string
     */
    public function getRegisterButtonLabel(): string
    {
        return $this->loginConfig->getRegisterButtonLabel() ?? '';
    }

    /**
     * Return the register href link
     *
     * @return string
     */
    public function getRegisterButtonHref(): string
    {
        return $this->ssoConfig->getRegisterUrl()
        . '?' . $this->ssoConfig->getRegisterUrlParameter()
        . '='.$this->url->getBaseUrl() .
        'oauth/index/index/rc/'.base64_encode($this->url->getUrl(static::CANVA_LOGIN_PATH)) ?? '';
    }

    /**
     * Return the login modal login button label
     *
     * @return string
     */
    public function getLoginButtonLabel(): string
    {
        return $this->loginConfig->getLoginButtonLabel() ?? '';
    }

    /**
     * Return the login href link
     *
     * @return string
     */
    public function getLoginButtonHref(): string
    {
        return $this->ssoConfig->getLoginPageURL()
        . '?' . $this->ssoConfig->getQueryParameter()
        . '='.$this->url->getBaseUrl() .
        'oauth/index/index/rc/'.base64_encode($this->url->getUrl(static::CANVA_LOGIN_PATH)) ?? '';
    }

    /**
     * Return the login modal continue button label
     *
     * @return string
     */
    public function getContinueButtonLabel(): string
    {
        return $this->loginConfig->getContinueButtonLabel() ?? '';
    }

    /**
     * Return toggle configuration
     *
     * @return bool
     */
    public function getFeatureToggle(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TOGGLE_FEATURE_KEY);
    }

    /**
     * @return string
     */
    public function getLabelText(): string
    {
        return self::LABEL;
    }

    /**
     * @return string
     */
    public function getHrefText(): string
    {
        return self::HREF;
    }

    /**
     * @return string
     */
    public function getWarningIcon(): string {
        return  $this->assetRepository->getUrl("images/warning.svg");
    }

    /**
     * @return bool
     */
    public function getTigerE424480Toggle(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_E424480_TOGGLE);
    }

}
