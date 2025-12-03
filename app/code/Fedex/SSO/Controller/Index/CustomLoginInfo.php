<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Controller\Index;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ActionInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;

/**
 * CustomerLoginInfo Block class
 */
class CustomLoginInfo implements ActionInterface
{
    /** @var string */
    private const LOGIN_INFO_TEMPLATE = 'Fedex_SSO::header/login_info.phtml';

    /** @var string */
    private const SINGIN_SIGNUP_TEMPLATE = 'Fedex_SSO::header/singin_signup.phtml';

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
        $template = $this->ssoConfiguration->isFclCustomer() ? self::LOGIN_INFO_TEMPLATE : self::SINGIN_SIGNUP_TEMPLATE;
        return $resultPage->getLayout()
            ->createBlock($blockClass)
            ->setTemplate($template)
            ->toHtml();
    }
}
