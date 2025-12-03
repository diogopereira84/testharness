<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ExpressCheckout\Controller\Customer;

use Fedex\EnhancedProfile\ViewModel\AccountHandler;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class FedexAccountList extends Action
{
    /**
     * @var PageFactory $resultPageFactory
     */
    protected $resultPageFactory;

    /**
     * @var AccountHandler $accountHandlerViewModel
     */
    protected AccountHandler $accountHandlerViewModel;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param AccountHandler $accountHandlerViewModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AccountHandler $accountHandlerViewModel
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->accountHandlerViewModel = $accountHandlerViewModel;
    }

    /**
     * Get FedEx account list
     *
     * @return void
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $blockClass = \Fedex\ExpressCheckout\Block\CustomerDetails::class;

        $block = $resultPage->getLayout()
            ->createBlock(
                $blockClass,
                '',
                [
                    'data' => [
                        'account_handler_view_model' => $this->accountHandlerViewModel
                    ]
                ]
            )
            ->setTemplate('Fedex_ExpressCheckout::customer/fedex_account_list.phtml')
            ->toHtml();

        $this->getResponse()->setBody($block);
    }
}
