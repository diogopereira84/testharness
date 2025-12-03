<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\UploadToQuote\Block\QuoteDetails;

/**
 * ReviewRequest Controller class
 */
class ReviewRequest implements ActionInterface
{
    /**
     * ReviewRequest class constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerSession $customerSession
     */
    public function __construct(
        protected Context $context,
        protected PageFactory $resultPageFactory,
        protected CustomerSession $customerSession
    )
    {
    }

    /**
     * To show review request modal html
     *
     * @return json
     */
    public function execute()
    {
        $formData = $this->context->getRequest()->getPostValue();
        $this->customerSession->setSiItems($formData['siItems']);
        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()
                        ->createBlock(QuoteDetails::class)
                        ->setTemplate('Fedex_UploadToQuote::review_request_popup.phtml')
                        ->setData('siItems', $formData['siItems'])
                        ->toHtml();
        $result = [
            "success" => true,
            "block" => $block
        ];
        $resultJson = $this->context->getResultFactory()->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);

        return $resultJson;
    }
}
