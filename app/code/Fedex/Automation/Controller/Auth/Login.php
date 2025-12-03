<?php /** @noinspection PhpUndefinedMethodInspection */
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Controller\Auth;

use Fedex\Automation\Gateway\Okta;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Login\Helper\Login as LoginHelper;
use Fedex\OktaMFTF\Model\Config\General as GeneralConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Company\Api\CompanyRepositoryInterface as CompanyRepository;
use Magento\Company\Model\Company;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;

/**
 * Need to extend from deprecated "Action" to be compatible with
 * Fedex/SSO/Observer/Frontend/Controller/ActionPredispatch.php
 */
class Login implements HttpGetActionInterface, CsrfAwareActionInterface
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    public function __construct(
        Context                          $context,
        protected ToggleConfig           $toggleConfig,
        protected GeneralConfig          $generalConfig,
        protected Okta                   $oktaGateway,
        protected RequestInterface       $request,
        protected Customer               $customer,
        protected CustomerRepository     $customerRepository,
        protected Company                $company,
        protected CompanyRepository      $companyRepository,
        protected CookieMetadataFactory  $cookieMetadataFactory,
        protected CookieManagerInterface $cookieManager,
        protected CustomerSession        $customerSession,
        protected PunchoutHelper         $punchoutHelper,
        protected LoginHelper            $loginHelper,
        protected StoreManager           $storeManager,
        protected LoggerInterface        $logger
    )
    {
        $this->resultFactory = $context->getResultFactory();
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(): ?ResultInterface
    {
        if (!$this->generalConfig->isEnabled()) {
            header("HTTP/1.1 404 Not Found");
            die;
        }
        $tokenCredentials = $this->request->getParam('token');
        if (
            !empty($tokenCredentials) &&
            base64_encode(base64_decode($tokenCredentials, true)) === $tokenCredentials
        ) {
            $token = $this->oktaGateway->token();
            $introspect = $this->oktaGateway->introspect($token->getAccessToken());
            if ($introspect->isActive()) {
                if(!$this->generalConfig->getAdminUser()) {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Admin User not set.');
                    header("HTTP/1.1 401 Admin User not set");
                    die;
                }
                $eproCustomerId = $this->generalConfig->getAdminUser();
                /**
                 * Method setCustomerAsLoggedIn only takes Customer model as parameter,
                 * hence returned object (Customer API model) by repository won't cut,
                 * need to retrieve with deprecated "load" for now.
                 */
                $eproCustomer = $this->customer->load($eproCustomerId);
                if ($eproCustomer->getId()) {
                    $companyData = $this->customerRepository->getById($eproCustomerId)
                        ?->getExtensionAttributes()
                        ->getCompanyAttributes();
                    $companyId = $companyData->getCompanyId();
                    if ($companyId) {
                        $eproCompany = $this->companyRepository->get($companyId);
                        return $this->doLogin($eproCustomer, $eproCompany);
                    } else {
                        $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Company with Id "' . $companyId . '" not found.');
                        header("HTTP/1.1 401 Company not found");
                        die;
                    }
                } else {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Customer with Id "' . $eproCustomerId . '" not found.');
                    header("HTTP/1.1 401 Customer not found");
                    die;
                }
            } else {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Instrospect is not active.');
                header("HTTP/1.1 401 Introspect is not active");
                die;
            }
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid base64 credentials.');
            header("HTTP/1.1 401 Invalid base64 credentials");
            die;
        }
    }

    protected function doLogin($eproCustomer, $eproCompany): ResultInterface
    {
        $sessionData = [];
        $companyData = $eproCompany->getData();
        $sessionData['company_id'] = $eproCompany->getId();
        $sessionData['company_data'] = $companyData;
        $sessionData['ondemand_url'] = true;
        $sessionData['url_extension'] = true;
        $sessionData['company_type'] = 'epro';
        $urlExtension = $companyData['company_url_extention'] ?? null;
        $this->customerSession->logout()->setLastCustomerId($eproCustomer->getId());
        $this->customerSession->regenerateId();
        $this->customerSession->setCustomerAsLoggedIn($eproCustomer);
        $this->customerSession->setCustomerCompany($eproCompany->getId());
        $this->customerSession->setCompanyName($eproCompany->getCompanyName());
        $this->customerSession->setApiAccessToken($this->punchoutHelper->getTazToken());
        $this->customerSession->setApiAccessType('Bearer');
        $this->customerSession->setOndemandCompanyInfo($sessionData);
        $this->loginHelper->setUrlExtensionCookie($urlExtension);
        $this->setIframeCookieFix();
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setPath('ondemand/' . $urlExtension);
        return $redirect;
    }

    private function setIframeCookieFix(): void
    {
        try {
            $publicCookieMetadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setPath("/")
                ->setHttpOnly(false)
                ->setSecure(true)
                ->setSameSite("None");
            $baseStoreUrl = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            $baseStoreUrlWithoutHttps = str_replace("https://", "", $baseStoreUrl);
            $explodedStoreUrl = explode("/", $baseStoreUrlWithoutHttps);
            $storeUrl = $explodedStoreUrl[0];
            $cookieDomain = "." . $storeUrl;
            $publicCookieMetadata->setDomain($cookieDomain);
            $this->cookieManager->setPublicCookie(
                'PHPSESSID',
                $this->customerSession->getSessionId(),
                $publicCookieMetadata
            );
        } catch (NoSuchEntityException|FailureToSendException|CookieSizeLimitReachedException|InputException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . $e->getMessage() . ' : ' . $e->getTraceAsString());
            header("HTTP/1.1 500 " . $e->getMessage());
            die;
        }

    }

    /**
     * Bypass CSRF Exception
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Bypass CSRF validation
     * @param RequestInterface $request
     * @return bool
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

}
