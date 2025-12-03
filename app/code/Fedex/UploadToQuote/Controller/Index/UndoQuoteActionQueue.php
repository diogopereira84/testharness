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
 * Undo quote action queue controller
 */
class UndoQuoteActionQueue implements ActionInterface
{
    /**
     * UndoQuoteActionQueue class constructor
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
     * Undo upload to quote action queue
     *
     * @return json
     */
    public function execute()
    {
        $post = $this->context->getRequest()->getPostValue();
        $response = [
            "status" => 200,
            "undoAction" => false,
            'message' => 'Undo is failed'
        ];
        try {
            if (isset($post['undoAction']) && isset($post['quoteId'])
            && isset($post['itemId']) && isset($post['changeRequestedItemIds'])) {
                $response = $this->queueHelper->undoActionQueue(
                    $post['undoAction'],
                    $post['quoteId'],
                    $post['itemId'],
                    $post['changeRequestedItemIds']
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__
            .': Undo is failed: ' . $e->getMessage());
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }
}
