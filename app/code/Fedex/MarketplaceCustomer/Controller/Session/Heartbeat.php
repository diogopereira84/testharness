<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceCustomer
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCustomer\Controller\Session;

use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Mirakl\Core\Model\Shop as MiraklShop;
use Mirakl\Core\Model\ResourceModel\ShopFactory as MiraklShopResourceFactory;
use Mirakl\Core\Model\ShopFactory as MiraklShopFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Fedex\SSO\Helper\Data as SSOHelper;
use Fedex\SSO\Model\Config;
use Fedex\Canva\Model\CanvaCredentials;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Event\ManagerInterface as EventManager;

class Heartbeat extends Action implements CsrfAwareActionInterface
{
    const ENABLE_DEBUG_HEADER = true;
    protected $debugInfo = '';

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param MiraklShopFactory $miraklShopFactory
     * @param MiraklShopResourceFactory $miraklShopResourceFactory
     * @param PageFactory $resultPageFactory
     * @param Json $serializer
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param Http $http
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SSOHelper $ssoHelper
     * @param Config $ssoConfig
     * @param CanvaCredentials $canvaCredentials
     * @param ToggleConfig $toggleConfig
     * @param EventManager $eventManagerMock
     */
    public function __construct(
        protected Context                   $context,
        protected CustomerSession           $customerSession,
        protected MiraklShopFactory         $miraklShopFactory,
        protected MiraklShopResourceFactory $miraklShopResourceFactory,
        protected PageFactory               $resultPageFactory,
        protected Json                      $serializer,
        protected LoggerInterface           $logger,
        protected RequestInterface          $request,
        protected Http                      $http,
        protected CookieManagerInterface    $cookieManager,
        protected CookieMetadataFactory     $cookieMetadataFactory,
        protected SSOHelper                 $ssoHelper,
        protected Config                    $ssoConfig,
        protected CanvaCredentials          $canvaCredentials,
        protected ToggleConfig              $toggleConfig,
        protected EventManager              $eventManagerMock
    )
    {
        parent::__construct($context);
    }

    /**
     * @return Http
     */
    public function execute(): Http
    {
        try {
            $requestData = $this->serializer->unserialize(
                $this->request->getContent()
            );
            $sellerId = !empty($requestData['seller_id']) ? (int)$requestData['seller_id'] : null;
            if ($this->customerSession->isLoggedIn()) {
                if ($sellerId) {
                    $shop = $this->getShop($sellerId);
                    if ($shop->getId()) {
                        return $this->response200();
                    } else {
                        $this->logout();
                        $this->debugInfo = 'Unable to locate seller ' . $sellerId . ' for customer ID ' . $this->customerSession->getCustomerId();
                        $this->logger->debug(__METHOD__ . ':' . __LINE__ . ':' . $this->debugInfo);
                        return $this->response403();
                    }
                } else {
                    $this->logout();
                    $this->debugInfo = 'Missing "seller_id" parameter, session has been terminated for customer ID ' . $this->customerSession->getCustomerId();
                    $this->logger->debug(__METHOD__ . ':' . __LINE__ . ':' . $this->debugInfo);
                    return $this->response403();
                }
            } else {
                $this->debugInfo = 'Attempt to extend session with no active session for seller ID ' . $sellerId;
                $this->logger->debug(__METHOD__ . ':' . __LINE__ . ':' . $this->debugInfo);
                return $this->response403();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->debugInfo = $e->getMessage();
            return $this->response403();
        }
    }

    /**
     * @return Http
     */
    public function response403(): Http
    {
        $this->http->getHeaders()->clearHeaders();
        $this->http->setHeader('Content-Type', 'text/plain');
        $this->http->setHeader('Status', '403 Forbidden');
        if(self::ENABLE_DEBUG_HEADER){
            $this->http->setHeader('HB-Debug', $this->debugInfo);
        }
        $this->http->setStatusHeader(403, '1.1', 'Forbidden');
        return $this->http;
    }

    /**
     * @return Http
     */
    public function response200(): Http
    {
        $this->http->getHeaders()->clearHeaders();
        $this->http->setHeader('Content-Type', 'text/plain');
        $this->http->setHeader('Status', '200 Success');
        if(self::ENABLE_DEBUG_HEADER){
            $this->http->setHeader('HB-Debug', 'Heart is beating!');
        }
        $this->http->setStatusHeader(200, '1.1', 'Success');
        return $this->http;
    }

    /**
     * @param int $shopId
     * @return MiraklShop
     */
    public function getShop(int $shopId): MiraklShop
    {
        $shop = $this->miraklShopFactory->create();
        $this->miraklShopResourceFactory->create()->load($shop, $shopId);
        return $shop;
    }

    private function logout(): void
    {
        try {
            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDomain(".fedex.com")
                ->setPath("/")
                ->setHttpOnly(false)
                ->setSecure(true)
                ->setSameSite("None");
            $sdeCookieMetadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setPath("/")
                ->setHttpOnly(false);
            $customerId = $this->customerSession->getCustomerId();
            if ($customerId) {
                $this->cookieManager->deleteCookie(SSOHelper::SDE_COOKIE_NAME, $metadata);
                $this->cookieManager->deleteCookie(SSOHelper::FORGE_ROCK_COOKIE_NAME, $metadata);
                $this->cookieManager->deleteCookie(SdeHelper::CUSTOMER_ACTIVE_SESSION_COOKIE_NAME, $sdeCookieMetadata);
                $this->customerSession->unsFclFdxLogin();
                if ($this->ssoHelper->getFCLCookieNameToggle()) {
                    $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
                    $this->cookieManager->deleteCookie($cookieName, $metadata);
                }
                $this->cookieManager->deleteCookie("fdx_login", $metadata);
                $this->cookieManager->deleteCookie("fcl_customer_login_success", $metadata);
                $this->cookieManager->deleteCookie("fcl_customer_login", $metadata);
                $this->canvaCredentials->fetch();
                $this->cookieManager->deleteCookie('b2ef1b160e192c2', $metadata);
                $this->cookieManager->deleteCookie('ab45335bc623e59', $metadata);
                $this->ssoHelper->callFclLogoutApi();
                $this->customerSession->logout()->setLastCustomerId($customerId);
                $this->eventManagerMock->dispatch('user_logout_success', []);
            }
        } catch (\Exception $e) {
            $this->customerSession->unsFclFdxLogin();
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ':Unable to do logout for ' . $this->customerSession->getCustomerId() . ' with error: ' . $e->getMessage());
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

