<?php

declare(strict_types=1);

namespace Fedex\Company\Model\SaveValidator;

use Magento\Company\Model\SaveValidator\RequiredFields as CoreRequiredFields;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\Exception\InputException;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CustomRequiredFields extends CoreRequiredFields
{

    private $customRequiredFields = [
        CompanyInterface::NAME,
        CompanyInterface::SUPER_USER_ID,
        CompanyInterface::CUSTOMER_GROUP_ID,
        CompanyInterface::STREET
    ];
    /**
     * {@inheritdoc}
     */
    public function __construct(
        private CompanyInterface $company,
        private InputException $exception,
        private ToggleConfig $toggleConfig
    ) {
        parent::__construct($company, $exception);
    }

    public function execute()
    {
        $isCompanyAdminSettingsToggle = $this->toggleConfig->getToggleConfigValue('sgc_company_settings_fields_updates');

        if (!$isCompanyAdminSettingsToggle) {
            return parent::execute();
        }

        foreach ($this->customRequiredFields as $field) {
            if (empty($this->company->getData($field))) {
                $this->exception->addError(
                    __(
                        '"%fieldName" is required. Enter and try again.',
                        ['fieldName' => $field]
                    )
                );
            }
        }
    }
}
