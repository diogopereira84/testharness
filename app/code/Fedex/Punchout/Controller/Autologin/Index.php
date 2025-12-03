<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Punchout\Controller\Autologin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Psr\Log\LoggerInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Fedex\Login\Helper\Login;

class Index extends Action implements CsrfAwareActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    protected $resultPageFactory;
    private \Magento\Framework\View\Result\PageFactory $_resultPageFactory;

    /**
     * Index Constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Fedex\Punchout\Helper\Data $helper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param CompanyFactory $companyFactory
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CookieManagerInterface $cookieManager
     * @param Login $loginHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        protected \Magento\Customer\Model\Session $customerSession,
        protected \Fedex\Punchout\Helper\Data $helper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        protected LoggerInterface $logger,
        protected CompanyFactory $companyFactory,
        private CookieMetadataFactory $cookieMetadataFactory,
        private CookieManagerInterface $cookieManager,
        protected Login $loginHelper
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * By pass CSRF Exception
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * By pass CSRF validation
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Controller Execute method
     *
     * @return \Magento\Backend\Model\View\Result\Page | String
     */
    public function execute()
    {
        $token = $this->getRequest()->getParam('token');
        if (empty($token)) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Missing Token.');
            return $this->accessDenied('Missing Token');
        }
        $response = $this->helper->autoLogin($token);
        if ($response['error']) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $response['msg']);
            return $this->accessDenied($response['msg']);
        } else {
            if (isset($response['loginData']['company_id'])) {
                $companyId = $response['loginData']['company_id'];
                $companyObj = $this->companyFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('entity_id', ['eq' => $companyId])->getFirstItem();
                if ($companyObj && is_array($companyObj->getData())) {

                    $companyData = $companyObj->getData();
                    $sessionData = [];
                    $sessionData['company_id'] = $companyId;
					$sessionData['company_data'] = $companyData;
					$sessionData['ondemand_url'] = true;
					$sessionData['url_extension'] = true;
                    $sessionData['company_type'] = 'epro';
                    $urlExtension = $companyData['company_url_extention'] ?? null;
                    $this->loginHelper->setUrlExtensionCookie($urlExtension);
                    $this->customerSession->setOndemandCompanyInfo($sessionData);
                }
            }
            // B-1445896
            return $this->doRedirection($response);
        }
    }

    // B-1445896
    public function doRedirection($response)
    {
        if ($response['allow'] == 0) {
            $this->customerSession->logout()
                ->setLastCustomerId($response['customer_id']);
            $page = $this->_resultPageFactory->create();
            $block = $page->getLayout()->getBlock('fedex_punchout_index');
            $block->setData('customer_id', $response['customer_id']);
            $block->setData('redirect_url', $response['url']);
            $block->setData('loginD', $response['loginData']);
            return $page;
        } else {
            return $this->allowAccess($response['url']);
        }
    }

    /**
     * Set response code 403
     *
     * @param String $msg
     *
     * @return String
     */
    public function accessDenied($msg)
    {
        $url_403 = '/403';
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setHttpResponseCode(403);
        $resultRedirect->setUrl($url_403);
        return $resultRedirect;
    }

    /**
     * Set response code 302
     *
     * @param String $url
     *
     * @return String
     */
    public function allowAccess($url)
    {
         /** Add URL extension in company URL before redirection B-1836797 */
       if ($this->customerSession->getOndemandCompanyInfo() != "" && $urlExtension = $this->customerSession->getOndemandCompanyInfo()['company_data']['company_url_extention'])
	    {
            if (!str_contains($url, $urlExtension . "/")) {
                $url = trim($url,"/");
                $url .= "/".$urlExtension . "/";
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);

            $publicCookieMetadata = $this->cookieMetadataFactory
                                    ->createPublicCookieMetadata()
                                    ->setPath("/")
                                    ->setHttpOnly(true)
                                    ->setDuration(time() + 86400)
                                    ->setSecure(true)
                                    ->setSameSite("None");
            $this->cookieManager->setPublicCookie(
                'PHPSESSID',
                $this->customerSession->getSessionId(),
                $publicCookieMetadata
            );
        return $resultRedirect;
    }
}
