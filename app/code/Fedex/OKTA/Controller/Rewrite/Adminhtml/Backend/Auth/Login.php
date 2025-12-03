<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Controller\Rewrite\Adminhtml\Backend\Auth;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\App\BackendAppList;
use Magento\Backend\Model\UrlFactory;
use Magento\Backend\Controller\Adminhtml\Auth\Login as MagentoLogin;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\Result\Redirect as ControllerResultRedirect;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\Backend\LoginHandler;
use Fedex\OKTA\Model\Backend\LoginHandlerFactory;
use Fedex\OKTA\Model\Oauth\PostbackValidatorInterface;
use Fedex\OKTA\Model\Oauth\UrlBuilderInterface;
use Fedex\OKTA\Model\Oauth\OktaTokenInterface;
use Fedex\OKTA\Model\LoginContext;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use \Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Login extends MagentoLogin
{
    private const EMAIL_COOKIES_NAME = 'email';
    private const REQUEST_URL_COOKIES_NAME = 'requestUrl';

    /**
     * @var LoginHandlerFactory
     */
    private $loginHandlerFactory;

    /**
     * @var OktaHelper
     */
    private $oktaHelper;

    /**
     * @var PostbackValidatorInterface
     */
    private $postbackValidator;

    /**
     * @var UrlBuilderInterface
     */
    private $urlBuilder;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @var OktaTokenInterface
     */
    private $oktaToken;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoginContext $loginContext
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param FrontNameResolver|null $frontNameResolver
     * @param BackendAppList|null $backendAppList
     * @param UrlFactory|null $backendUrlFactory
     * @param Http|null $http
     */
    public function __construct(
        LoginContext $loginContext,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        Context $context,
        PageFactory $resultPageFactory,
        readonly protected ToggleConfig $toggleConfig,
        FrontNameResolver $frontNameResolver = null,
        BackendAppList $backendAppList = null,
        UrlFactory $backendUrlFactory = null,
        Http $http = null
    ) {
        parent::__construct(
            $context,
            $resultPageFactory,
            $frontNameResolver,
            $backendAppList,
            $backendUrlFactory,
            $http
        );
        $this->loginHandlerFactory = $loginContext->getLoginHandlerFactory();
        $this->oktaHelper = $loginContext->getOktaHelper();
        $this->postbackValidator = $loginContext->getPostbackValidator();
        $this->urlBuilder = $loginContext->getUrlBuilder();
        $this->urlInterface = $loginContext->getUrlInterface();
        $this->oktaToken = $loginContext->getOktaToken();
        $this->logger = $loginContext->getLogger();
    }

    /**
     * @return Redirect|Page|ControllerResultRedirect
     */
    public function execute(): Page|Redirect|ControllerResultRedirect
    {
        if (!$this->toggleConfig->getToggleConfigValue('tigers_b2185176_remove_adobe_commerce_overrides')) {
            /**
             * If OKTA module is disabled or if the admin user is already logged, execute the OOTB method
             */
            if (!$this->oktaHelper->isEnabled() || $this->_auth->isLoggedIn()) {
                return parent::execute();
            }

            $request = $this->getRequest();

            // Set email link value in cookie
            //@codeCoverageIgnoreStart
            if (!empty($request->getParams()['email'])) {
                $this->addCookiesVariable($request);
            }
            //@codeCoverageIgnoreEnd

            try {

                /**
                 * If GET method, redirect to OKTA page
                 */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $redirect = $resultRedirect->setUrl($this->getOktaUrl());

                /**
                 * Handle OKTA login via postback
                 */
                if ($request->isPost()) {
                    $this->doLogin($request);
                    $redirect = $this->getRedirect($this->_backendUrl->getStartupPageUrl());
                    $this->deleteCookies();
                }

                return $redirect;
            } catch (LocalizedException $e) {
                if ($this->oktaHelper->isToggleForEnhancedLoggingEnabled()) {
                    $params = $request->getParams() ?? [];
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage(), $params);
                } else {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                }
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                if ($this->oktaHelper->isToggleForEnhancedLoggingEnabled()) {
                    $params = $request->getParams() ?? [];
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage(), $params);
                } else {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                }
                $this->messageManager->addErrorMessage(
                    __('An unspecified error occurred. Please contact us for assistance.')
                );
            }
            return $this->getLoginErrorPage();
        }
        return parent::execute();
    }

    /**
     * @param RequestInterface $request
     * @throws LocalizedException
     * @throws AlreadyExistsException
     * @throws AuthenticationException
     */
    private function doLogin(RequestInterface $request)
    {
        $this->postbackValidator->validate($request);
        $code = $request->getParam(PostbackValidatorInterface::REQUEST_KEY_CODE);
        /**
         * get id_token from code received from redirect
         */
        $oktaResponse = json_decode($this->oktaToken->getToken($code), true);

        $this->oktaToken->validate($oktaResponse);
        $this->getLoginHandler()->loginByToken($oktaResponse[UrlBuilderInterface::TOKEN_TYPE]);
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getOktaUrl()
    {
        return $this->urlBuilder
            ->setClientId($this->oktaHelper->getClientId())
            ->setDomain($this->oktaHelper->getDomain())
            ->setNonce($this->oktaHelper->getNonce())
            ->setRedirectUrl($this->oktaHelper->getRedirectUrl())
            ->setScope($this->oktaHelper->getScope())
            ->setState($this->oktaHelper->getState())
            ->setResponseType($this->oktaHelper->getResponseType())
            ->setResponseMode($this->oktaHelper->getResponseMode())
            ->build();
    }

    /**
     * Remove original login form and render error message
     *
     * @return Page
     */
    private function getLoginErrorPage()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create(ResultFactory::TYPE_PAGE);

        $this->_view->loadLayout();
        $resultPage->getLayout()->unsetElement('admin.login');
        $resultPage->getLayout()->unsetElement('adminhtml_auth_login_buttons');
        $resultPage->getLayout()->unsetElement('adminhtml_auth_login_forgotpassword');

        return $resultPage;
    }

    /**
     * @return LoginHandler
     */
    private function getLoginHandler()
    {
        return $this->loginHandlerFactory->create();
    }

    /**
     * Get redirect response
     *
     * @param string $path
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    private function getRedirect($path)
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($path);
        return $resultRedirect;
    }

    /**
     * Delete cookies
     *
     * @return void
     */
    private function deleteCookies() : void
    {
        $this->cookieManager->deleteCookie(
            self::EMAIL_COOKIES_NAME,
            $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setPath('/')
        );
        $this->cookieManager->deleteCookie(
            self::REQUEST_URL_COOKIES_NAME,
            $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setPath('/')
        );
    }

    /**
     * Delete cookies
     *
     * @param RequestInterface $request
     * @return void
     * @codeCoverageIgnore
     */
    private function addCookiesVariable(RequestInterface $request) : void
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDurationOneYear();
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);
        $this->cookieManager->setPublicCookie(
            self::EMAIL_COOKIES_NAME,
            $request->getParam(self::EMAIL_COOKIES_NAME),
            $publicCookieMetadata
        );
        $this->cookieManager->setPublicCookie(
            self::REQUEST_URL_COOKIES_NAME,
            $request->getUri(),
            $publicCookieMetadata
        );
    }
}
