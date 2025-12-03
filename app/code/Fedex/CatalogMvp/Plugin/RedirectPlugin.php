<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Manish Chaubey
 * @email      manish.chaubey.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Fedex\SelfReg\Block\Landing;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\ResponseFactory;

class RedirectPlugin
{
    /**
     * Constructor
     * 
     * @param ResponseFactory $redirect
     * @param CustomerSession $customerSession
     * @param Landing $selfRegLanding
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        readonly private ResponseFactory $responseFactory,
        readonly private CustomerSession $customerSession,
        readonly private Landing $selfRegLanding,
        readonly private UrlInterface $urlInterface,
        readonly private ToggleConfig $toggleConfig
    ) {
    }

    /**
     * Around execure method for login page redirection
     * 
     * @param ActionInterface $subject
     * @param $proceed
     * @return void|ResultInterface|ResponseInterface
     */
    public function aroundExecute(ActionInterface $subject, callable $proceed)
    {
        $isNonStandardCatalogToggleEnable = $this->toggleConfig->getToggleConfigValue(
            'explorers_non_standard_catalog'
        );
        $redirectUrl = $this->urlInterface->getCurrentUrl();
        if (
            $isNonStandardCatalogToggleEnable &&
            !empty($redirectUrl) &&
            isset(parse_url($redirectUrl)['query']) && 
            str_contains($redirectUrl, 'email') &&
            !$this->customerSession->isLoggedIn()
        ) {
            parse_str(parse_url($redirectUrl)['query'], $params);
            $loginUrl = $this->selfRegLanding->getLoginUrl().'index/index/rc/'.base64_encode(str_replace('?email=1','',$redirectUrl));
            if (isset($params['email']) && $params['email'] == 1 && str_contains($redirectUrl, 'ondemand')) {
                $responseFactoryObject = $this->responseFactory->create();
                $responseFactoryObject->setRedirect($loginUrl);
                $responseFactoryObject->sendResponse();
                exit();
            }
        }
        return $proceed();
    }
}
