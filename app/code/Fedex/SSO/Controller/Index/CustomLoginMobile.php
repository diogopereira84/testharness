<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Controller\Index;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;

/**
 * CustomerLoginInfo Block class
 */
class CustomLoginMobile implements ActionInterface
{
    /** @var string */
    private const FCL_LOGIN_MOBILE_INFO = 'Fedex_SSO::header/fcl_login_mobile_info.phtml';

    /** @var string */
    private const FCL_SIGNUP_MOBILE = 'Fedex_SSO::header/fcl_signup_mobile.phtml';

    /**
     * Customer Login Info constructor
     *
     * @param ResponseInterface $response
     * @param PageFactory $resultPageFactory
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        private ResponseInterface $response,
        protected PageFactory $resultPageFactory,
        protected SsoConfiguration $ssoConfiguration
    )
    {
    }

    /**
     * Customer header signin and signup or profile info
     *
     * @return void
     */
    public function execute()
    {
        $block = $this->getFclBlock();
        $this->response->setBody($block);
    }

    /**
     * @return mixed
     */
    private function getFclBlock()
    {
        $resultPage = $this->resultPageFactory->create();
        $blockClass = \Fedex\SSO\Block\LoginInfo::class;
        $template = $this->ssoConfiguration->isFclCustomer() ? self::FCL_LOGIN_MOBILE_INFO : self::FCL_SIGNUP_MOBILE;
        return $resultPage->getLayout()
            ->createBlock($blockClass)
            ->setTemplate($template)
            ->toHtml();
    }
}
