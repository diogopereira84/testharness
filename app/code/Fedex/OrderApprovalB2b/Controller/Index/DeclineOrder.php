<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Fedex\OrderApprovalB2b\Helper\DeclineHelper;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use Exception;

/**
 * DeclineOrder Controller
 */
class DeclineOrder implements ActionInterface
{

    /**
     * Initializing constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param DeclineHelper $declineHelper
     * @param LoggerInterface $logger
     * @param RevieworderHelper $revieworderHelper
     */
    public function __construct(
        protected Context $context,
        protected JsonFactory $jsonFactory,
        protected DeclineHelper $declineHelper,
        protected LoggerInterface $logger,
        protected RevieworderHelper $revieworderHelper
    )
    {
    }

    /**
     * Decline order action
     *
     * @return json
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $resData = [
            'success' => false,
            'msg' => "System error. Please Try Again"
        ];
        if (!$this->revieworderHelper->checkIfUserHasReviewOrderPermission()) {
            $resData['msg'] = "You are not authorized to access this request.";
            return $this->revieworderHelper->sendResponseData($resData, $resultJson);
        }
        $post = $this->context->getRequest()->getParams();
        try {
            if (!empty($post['orderId']) && isset($post['additionalComments'])) {
                $resData = $this->declineHelper->declinedOrder(
                    $post['orderId'],
                    $post['additionalComments']
                );
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__
            .': Exception during declining the order => ' . $e->getMessage());
        }

        return $this->revieworderHelper->sendResponseData($resData, $resultJson);
    }
}
