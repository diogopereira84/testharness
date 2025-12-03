<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config implements ConfigInterface
{
    public const XML_PATH_TIGER_E468338_TOGGLE = 'tiger_e468338';
    public const XML_PATH_TITLE_STEP_ONE = 'web/bundling_howto_modal/title_step_one';
    public const XML_PATH_DESCRIPTION_STEP_ONE = 'web/bundling_howto_modal/description_step_one';
    public const XML_PATH_TITLE_STEP_TWO = 'web/bundling_howto_modal/title_step_two';
    public const XML_PATH_DESCRIPTION_STEP_TWO = 'web/bundling_howto_modal/description_step_two';
    public const XML_PATH_TITLE_STEP_THREE = 'web/bundling_howto_modal/title_step_three';
    public const XML_PATH_DESCRIPTION_STEP_THREE = 'web/bundling_howto_modal/description_step_three';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ToggleConfig $toggleConfig,
    ) {
    }

    /**
     * Check if the Tiger E468338 toggle is enabled.
     *
     * @return bool
     */
    public function isTigerE468338ToggleEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XML_PATH_TIGER_E468338_TOGGLE);
    }

    /**
     * Get the title for step one.
     *
     * @param string|null $scopeType
     * @param string|null $scopeCode
     * @return string|null
     */
    public function getTitleStepOne(?string $scopeType = null, ?string $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TITLE_STEP_ONE,
            $scopeType ?? \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getDescriptionStepOne(?string $scopeType = null, ?string $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DESCRIPTION_STEP_ONE,
            $scopeType ?? \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getTitleStepTwo(?string $scopeType = null, ?string $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TITLE_STEP_TWO,
            $scopeType ?? \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getDescriptionStepTwo(?string $scopeType = null, ?string $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DESCRIPTION_STEP_TWO,
            $scopeType ?? \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getTitleStepThree(?string $scopeType = null, ?string $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TITLE_STEP_THREE,
            $scopeType ?? \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getDescriptionStepThree(?string $scopeType = null, ?string $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DESCRIPTION_STEP_THREE,
            $scopeType ?? \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }
}
