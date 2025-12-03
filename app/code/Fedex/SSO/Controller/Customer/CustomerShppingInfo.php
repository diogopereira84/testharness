<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SSO\Controller\Customer;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\LayoutFactory;

/**
 * CustomerShppingInfo Controller class
 */
class CustomerShppingInfo implements ActionInterface
{
    /**
     * Initialize dependencies.
     *
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     * @param ManagerInterface $messageManager
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        private readonly RawFactory $resultRawFactory,
        private readonly LayoutFactory $layoutFactory,
        private readonly ManagerInterface $messageManager,
        private readonly SsoConfiguration $ssoConfiguration,
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * My account section shipping information
     *
     * @return \Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();
        $blockClass = \Fedex\SSO\Block\LoginInfo::class;

        if ($this->ssoConfiguration->isFclCustomer()
            || $this->ssoConfiguration->getIsRequestFromSdeStoreFclLogin()
            || $this->ssoConfiguration->isSelfRegCustomerWithFclEnabled()) {

            $layout = $this->layoutFactory->create();
            $block = $layout->createBlock($blockClass)
                ->setTemplate('Fedex_SSO::customer/shipping_info.phtml')
                ->toHtml();
            return $resultRaw->setContents($block);
        }

        if (!$this->toggleConfig->getToggleConfigValue('techtitans_d_196604')) {
            $this->messageManager->addErrorMessage(__('Access denied.'));
        }

        return $resultRaw;
    }
}