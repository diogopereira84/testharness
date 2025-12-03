<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Fedex\CIDPSG\Helper\PegaHelper;
use Psr\Log\LoggerInterface;

/**
 * AccountRequestSubmit Controller class
 */
class AccountRequestSubmit implements ActionInterface
{
    public $formData;

    public $errorData;

    /**
     * Initialize dependencies.
     *
     * @param RequestInterface $requestInterface
     * @param PegaHelper $pegaHelper
     * @param ResultFactory $resultFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected RequestInterface $requestInterface,
        protected PegaHelper $pegaHelper,
        protected ResultFactory $resultFactory,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * To submit account request form data
     *
     * @return json
     */
    public function execute()
    {
        $formData = $this->requestInterface->getPostValue();
        $data = $this->pegaHelper->getPegaApiResponse($formData);
        if (isset($data['errors'])) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                ' CID Account Creation Failure for '.$formData['contact_fname'].' '.$formData['contact_lname']
            );
        }
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}
