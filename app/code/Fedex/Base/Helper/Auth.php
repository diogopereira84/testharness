<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Helper;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;

class Auth extends AbstractHelper
{
    const LOGIN_METHOD_SSO = 'commercial_store_sso';
    const LOGIN_METHOD_EPRO = 'commercial_store_epro';
    const LOGIN_METHOD_FCL = 'commercial_store_wlgn';
    const LOGIN_METHOD_SSO_FCL = 'commercial_store_sso_with_fcl';
    const AUTH_SSO = 'sso';
    const AUTH_FCL = 'fcl';
    const AUTH_PUNCH_OUT = 'punchout';
    const AUTH_SSO_FCL = 'sso_fcl';
    const AUTH_NONE = 'none';

    /**
     * @param Context $context
     * @param HttpContext $httpContext
     * @param CustomerSession $customerSession
     * @param CompanyManagementInterface $companyManagement
     * @param CompanyRepositoryInterface $companyRepository
     * @param Http $httpRequest
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected Context                    $context,
        protected HttpContext                $httpContext,
        protected CustomerSession            $customerSession,
        protected CompanyManagementInterface $companyManagement,
        protected CompanyRepositoryInterface $companyRepository,
        protected Http                       $httpRequest,
        protected SearchCriteriaBuilder      $searchCriteriaBuilder,
        protected CookieManagerInterface     $cookieManager,
        protected ToggleConfig               $toggleConfig
    )
    {
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn() ||
            $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * @return string
     */
    public function getCompanyAuthenticationMethod(): string
    {
        $loginMethod = '';
        $company = $this->getCompany();
        if ($company instanceof CompanyInterface && $company->getId()) {
            $loginMethod = $company->getData('storefront_login_method_option');
        }
        return match ($loginMethod) {
            self::LOGIN_METHOD_SSO => self::AUTH_SSO,
            self::LOGIN_METHOD_EPRO => self::AUTH_PUNCH_OUT,
            self::LOGIN_METHOD_SSO_FCL => self::AUTH_SSO_FCL,
            self::LOGIN_METHOD_FCL => self::AUTH_FCL,
            default => self::AUTH_NONE,
        };
    }

    /**
     * @return CompanyInterface|null
     */
    public function getCompany(): ?CompanyInterface
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->companyManagement->getByCustomerId(
                $this->customerSession->getCustomerId()
            );
        }
        $urlExtension = $this->httpRequest->getParam('url');
        if(is_null($urlExtension)){
            $urlExtension = $this->cookieManager->getCookie('url_extension');
        }
        if (!empty($urlExtension)) {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                'company_url_extention',
                $urlExtension)->create();
            try {
                foreach ($this->companyRepository->getList($searchCriteria)->getItems() as $company) {
                    return $company;
                }
            } catch (LocalizedException|\Exception $e) {
            }
        }
        return null;
    }

}
