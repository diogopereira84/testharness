<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SSO\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class AjaxCallForSessionRefresh implements ActionInterface
{
    /**
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        private JsonFactory $resultJsonFactory
    )
    {
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'result' => 'true'
        ]);
    }
}
