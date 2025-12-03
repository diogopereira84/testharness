<?php
namespace Fedex\Company\Block\Adminhtml;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;

/**
 * Block class to get google api script url
 */
class Script extends \Magento\Framework\View\Element\Template
{
    public const FEDEX_ACCOUNT_CC_TOGGLE = 'explorers_enable_disable_fedex_account_cc_commercial';

    private const TECHTITANS_ERROR_ON_CHANGE_COMPANY_EMAIL = "techtitans_D171411_error_on_change_company_email";

    private const GENDER_ATTRIBUTE_CODE = 'gender';

    /**
     * @var Context
     */
    protected Context $context;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ToggleConfig $toggleConfig
     * @param ConfigInterface $configInterface
     * @param Config $eavConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected ScopeConfigInterface $scopeConfig,
        protected ToggleConfig $toggleConfig,
        protected Config $eavConfig,
        protected ConfigInterface $configInterface,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    public function getApiKey()
    {
        return $this->scopeConfig->getValue('fedex/general/google_maps_api_url');
    }

    /**
     * Check CustomerExport Toggle Enable
     *
     * @return boolean
     */
    public function fedexAccountCCToggleEnable()
    {
        return $this->toggleConfig->getToggleConfigValue(self::FEDEX_ACCOUNT_CC_TOGGLE)?1:0;
    }

    /**
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGenderDefaultOption()
    {
        $defaultOptionId = 0;
        $genderAttribute = $this->eavConfig->getAttribute('customer', self::GENDER_ATTRIBUTE_CODE);
        $allGenderOptions = $genderAttribute->getSource()->getAllOptions(false);
        foreach ($allGenderOptions as $option) {
            if (isset($option['label'])) {
                if ($option['label'] == 'Male') {
                    $defaultOptionId = $option['option_id'];
                } elseif ($option['label'] == 'Not Specified') {
                    $defaultOptionId = $option['option_id'];
                }
            }
        }

        return $defaultOptionId;
    }
}
