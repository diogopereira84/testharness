<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\EnhancedProfile\Controller\CreditCard;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

class EditInfo implements ActionInterface
{
    public const ERROR = 'error';
    public const SYSTEM_ERROR = 'System error, Please try again.';

    /**
     * Initialize dependencies.
     *
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected RequestInterface $request
    )
    {
    }

    /**
     * Get credit cart information
     *
     * @return json
     */
    public function execute()
    {
        try {
            $cardId = trim($this->request->getParam('cardId'));
            $response = [];
            if ($cardId) {
                $profileInfo = $this->enhancedProfile->getLoggedInProfileInfo();
                if (isset($profileInfo->output->profile->creditCards)) {
                    $creditCardList = $profileInfo->output->profile->creditCards;
                    $i = 0;
                    foreach ($creditCardList as $list) {
                        if ($cardId == $list->profileCreditCardId) {
                            $response['cardInfo'] = $creditCardList[$i];
                        }
                        $i++;
                    }
                    if (!$response) {
                        $response['status'] = self::ERROR;
                        $response['message'] = self::SYSTEM_ERROR;
                    }
                } else {
                    $response['status'] = self::ERROR;
                    $response['message'] = self::SYSTEM_ERROR;
                }
            } else {
                $response['status'] = self::ERROR;
                $response['message'] = self::SYSTEM_ERROR;
            }
            $result = $this->jsonFactory->create();
            $result->setData($response);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Not able to get credit card information: '
            . $e->getMessage());
            $response['status'] = self::ERROR;
            $response['message'] = self::SYSTEM_ERROR;
        }
    }
}
