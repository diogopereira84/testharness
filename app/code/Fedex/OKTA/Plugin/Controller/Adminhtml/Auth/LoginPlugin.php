<?php
/**
 * @category  Fedex
 * @package   Fedex_OKTA
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\OKTA\Plugin\Controller\Adminhtml\Auth;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\OKTA\Model\Backend\LoginHandler;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\Oauth\PostbackValidatorInterface;
use Fedex\OKTA\Model\Oauth\UrlBuilderInterface;
use Fedex\OKTA\Model\Oauth\OktaTokenInterface;
use Fedex\OKTA\Model\Backend\LoginHandlerFactory;
use Magento\Backend\Controller\Adminhtml\Auth\Login;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect as ControllerResultRedirect;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Psr\Log\LoggerInterface;

class LoginPlugin
{
    private const EMAIL_COOKIES_NAME = 'email';
    private const REQUEST_URL_COOKIES_NAME = 'requestUrl';

    public function __construct(
        readonly private ToggleConfig               $toggleConfig,
        readonly private OktaHelper                 $oktaHelper,
        readonly private CookieManagerInterface     $cookieManager,
        readonly private CookieMetadataFactory      $cookieMetadataFactory,
        readonly private UrlBuilderInterface        $urlBuilder,
        readonly private PostbackValidatorInterface $postbackValidator,
        readonly private OktaTokenInterface         $oktaToken,
        readonly private ViewInterface              $view,
        readonly private PageFactory                $resultPageFactory,
        readonly private LoginHandlerFactory        $loginHandlerFactory,
        readonly private RedirectFactory            $resultRedirectFactory,
        readonly private RequestInterface           $request,
        readonly private Auth                       $auth,
        readonly private ResultFactory              $resultFactory,
        readonly private BackendUrlInterface        $backendUrl,
        readonly private LoggerInterface            $logger,
        readonly private MessageManagerInterface    $messageManager
    )
    {

    }

    /**
     * @throws FailureToSendException
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     */
    public function aroundExecute(Login $subject, callable $proceed)
    {
        if ($this->toggleConfig->getToggleConfigValue('tigers_b2185176_remove_adobe_commerce_overrides')) {
            /**
             * If OKTA module is disabled or if the admin user is already logged, execute the OOTB method
             */
            if (!$this->oktaHelper->isEnabled() || $this->auth->isLoggedIn()) {
                return $proceed();
            }

            $request = $this->request;
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
                    $redirect = $this->getRedirect($this->backendUrl->getStartupPageUrl());
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
        return $proceed();
    }


    /**
     * @param RequestInterface $request
     * @throws LocalizedException
     * @throws AlreadyExistsException
     * @throws AuthenticationException
     */
    private function doLogin(RequestInterface $request): void
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
    private function getOktaUrl(): string
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
     * @return Page
     */
    private function getLoginErrorPage(): Page
    {
        $resultPage = $this->resultPageFactory->create(ResultFactory::TYPE_PAGE);
        $this->view->loadLayout();
        $resultPage->getLayout()->unsetElement('admin.login');
        $resultPage->getLayout()->unsetElement('adminhtml_auth_login_buttons');
        $resultPage->getLayout()->unsetElement('adminhtml_auth_login_forgotpassword');
        return $resultPage;
    }

    /**
     * @return LoginHandler
     */
    private function getLoginHandler(): LoginHandler
    {
        return $this->loginHandlerFactory->create();
    }

    /**
     * Get redirect response
     *
     * @param string $path
     * @return ControllerResultRedirect
     */
    private function getRedirect(string $path): ControllerResultRedirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($path);
        return $resultRedirect;
    }

    /**
     * @return void
     * @throws FailureToSendException
     * @throws InputException
     */
    private function deleteCookies(): void
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
     * @param RequestInterface $request
     * @return void
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     * @throws InputException
     */
    private function addCookiesVariable(RequestInterface $request): void
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
