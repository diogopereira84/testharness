<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;

class GetProfileSession extends Action
{
    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Get customer profile session
     *
     * @return object
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $result->setData($this->customerSession->getProfileSession());

        return $result;
    }
}
