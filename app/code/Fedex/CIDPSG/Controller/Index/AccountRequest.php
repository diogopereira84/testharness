<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Fedex\CIDPSG\Helper\AdminConfigHelper;

/**
 * AccountRequest Controller class
 */
class AccountRequest implements ActionInterface
{
    public $canadaStates;

    /**
     * Initialize dependencies.
     *
     * @param RequestInterface $requestInterface
     * @param AdminConfigHelper $adminConfigHelper
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        protected RequestInterface $requestInterface,
        protected AdminConfigHelper $adminConfigHelper,
        protected ResultFactory $resultFactory
    )
    {
    }

    /**
     * To get states list for US and Canada
     *
     * @return mixed
     */
    public function execute()
    {
        $countryCode = $this->requestInterface->getPost('country_code');
        $data = $this->adminConfigHelper->getAllStates($countryCode);
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}
