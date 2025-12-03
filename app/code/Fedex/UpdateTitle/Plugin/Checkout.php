<?php

/**
* Copyright © FedEX, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

declare(strict_types=1);

namespace Fedex\UpdateTitle\Plugin;

use Magento\Checkout\Controller\Index\Index;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Checkout
{
    /**
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        protected PageFactory $resultPageFactory
    )
    {
    }

    /**
     * @param Index $subject
     * @param $result
     * @return Page
     */
    public function afterExecute(Index $subject, $result): Page
    {
        $result = $this->resultPageFactory->create();
        $result->getConfig()->getTitle()->set(__('Cart Summary | FedEx Office'));
        $pageMainTitle = $result->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            $pageMainTitle->setTitle(__('Cart Summary | FedEx Office'));
        }
        return $result;
    }
}
