<?php
/**
 * @category Fedex
 * @package  Fedex_Punchout
 * @copyright   Copyright (c) 2024 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Punchout\Model;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Punchout\Api\Data\ConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config.
 */
class Config implements ConfigInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ToggleConfig $toggleConfig
     * @param CompanyHelper $companyHelper
     */
    public function __construct(
        private readonly CompanyHelper $companyHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMigrateEproNewPlatformOrderCreationToggle(int|string|null $companyId): bool|int|null
    {
            $company = $this->companyHelper->getCustomerCompany($companyId);
            if (is_object($company) && $company->getExtensionAttributes()
                && $company->getExtensionAttributes()->getCompanyAdditionalData()) {
                $companyAdditionalData = $company->getExtensionAttributes()->getCompanyAdditionalData();
                return !empty($companyAdditionalData->getEproNewPlatformOrderCreation())
                    && (bool)$companyAdditionalData->getEproNewPlatformOrderCreation();
            }

        return false;
    }
}
