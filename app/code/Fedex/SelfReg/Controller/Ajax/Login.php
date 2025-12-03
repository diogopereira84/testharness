<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;
use Fedex\Commercial\ViewModel\CommercialSsoConfiguration;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\Cart\ViewModel\UnfinishedProjectNotification;
use Fedex\UploadToQuote\ViewModel\QuoteHistory;

class Login extends \Magento\Framework\App\Action\Action
{
    /**
     * Constructor
     * @param Context  $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        private CommercialSsoConfiguration $commercialSsoConfiguration,
        private UploadToQuoteViewModel $uploadToQuoteViewModel,
        private QuoteHistory $quoteHistory,
        private UnfinishedProjectNotification $unfinishedProjectNotification
    ) {
        parent::__construct($context);
    }

    /**
     * Get left menu
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();

        $blockContent = $resultPage->getLayout()
            ->createBlock(\Magento\Framework\View\Element\Template::class)
            ->setTemplate('Fedex_Commercial::header/login_info.phtml')
            ->setData('commercial_login', $this->commercialSsoConfiguration)
            ->setData('upload_to_quote_viewmodel', $this->uploadToQuoteViewModel)
            ->setData('upload_to_quote_history_viewmodel', $this->quoteHistory)
            ->setData('unfinished_project_viewmodel', $this->unfinishedProjectNotification)
            ->toHtml();

        return $this->getResponse()->setBody($blockContent);
    }
}
