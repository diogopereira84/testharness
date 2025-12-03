<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Helper;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Exception\LocalizedException;

class Sde extends AbstractHelper
{
    const IS_SENSITIVE_DATA_ENABLED = 'is_sensitive_data_enabled';

    public function __construct(
        protected Context                    $context,
        protected CustomerSession            $customerSession,
        protected CompanyRepositoryInterface $companyRepository,
        protected CompanyManagementInterface $companyManagement,
        protected HttpRequest                $httpRequest,
        protected SearchCriteriaBuilder      $searchCriteriaBuilder,
        protected ToggleConfig               $toggleConfig
    )
    {
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isSensitiveDataFlow(): bool
    {
        $company = $this->getCompany();
        if ($company instanceof CompanyInterface &&
            $company->getData(self::IS_SENSITIVE_DATA_ENABLED)) {
            return true;
        }
        return false;
    }

    public function getCompany(): ?CompanyInterface
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->companyManagement->getByCustomerId(
                $this->customerSession->getCustomerId()
            );
        }
        $urlExtension = $this->httpRequest->getParam('url');
        if (!empty($urlExtension)) {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                'company_url_extention',
                $urlExtension)->create();
            try {
                foreach ($this->companyRepository->getList($searchCriteria)->getItems() as $company) {
                    return $company;
                }
            } catch (LocalizedException|\Exception $e) {}
        }
        return null;
    }

}
