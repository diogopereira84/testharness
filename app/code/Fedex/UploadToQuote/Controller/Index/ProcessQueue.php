<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Controller\Index;

use Fedex\UploadToQuote\Helper\QueueHelper;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

/**
 * Process Queue Controller
 */
class ProcessQueue implements ActionInterface
{
    /**
     * ProcessQueue class constructor
     *
     * @param Context $context
     * @param QueueHelper $queueHelper
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Context $context,
        protected QueueHelper $queueHelper,
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Process Upload to Quote action queue
     *
     * @return json
     */
    public function execute()
    {
        $response = [
            "status" => 200,
            "isQueueStop" => true,
            'message' => 'Upload to quote action queue is processed'
        ];
        try {
            $response = $this->queueHelper->processQueue();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__
            .': Upload to quote action queue is not processed: ' . $e->getMessage());
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }
}
