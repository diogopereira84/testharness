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
 * Set Queue Controller
 */
class SetQueue implements ActionInterface
{
    /**
     * SetQueue class constructor
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
     * Set upload to quote action queue
     *
     * @return json
     */
    public function execute()
    {
        $post = $this->context->getRequest()->getPostValue();
        $response = [
            "status" => 200,
            'message' => 'Upload to quote action queue is not set',
            'Queue' => false
        ];
        try {
            if (isset($post['action']) && $post['action']) {
                $response = $this->queueHelper->setQueue($post);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__
            .': Upload to quote action queue is not set: ' . $e->getMessage());
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }
}
