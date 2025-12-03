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

class RemoveInfo implements ActionInterface
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
     * Remove credit cart information
     *
     * @return json
     */
    public function execute()
    {
        try {
            $cardId = $this->request->getParam('cardId');
            $response = [];
            if ($cardId) {
                $profileInfo = $this->enhancedProfile->getLoggedInProfileInfo();
                if (isset($profileInfo->output->profile->creditCards)) {
                    $creditCardList = $profileInfo->output->profile->creditCards;
                    $i = 0;
                    foreach ($creditCardList as $list) {
                        if ($cardId == $list->profileCreditCardId) {
                            $creditCardInfoRequest = $creditCardList[$i];
                        }
                        $i++;
                    }
                    $cardRequestJson = '';
                    $profileCardId = $creditCardInfoRequest->profileCreditCardId;
                    $apiResponse = $this->enhancedProfile->updateCreditCard($cardRequestJson, $profileCardId, "DELETE");
                    if (!isset($apiResponse->errors)) {
                        $this->enhancedProfile->setProfileSession();
                        $profileInfo = $this->enhancedProfile->getLoggedInProfileInfo();
                        $creditCount = false;
                        if (!isset($profileInfo->output->profile->creditCards)) {
                            $creditCount = true;
                        }
                        $response['info'] = json_encode($apiResponse);
                        $response['message'] = __("Credit card has been successfully removed.");
                        $response['creditCount'] = $creditCount;
                        $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' Credit Card Remove Response: ' .
                        var_export($cardRequestJson, true));
                    } else {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error(s) while removing credit card info: '
                        . var_export($apiResponse->errors, true));
                        $response['status'] = self::ERROR;
                        $response['message'] = $apiResponse->errors[0]->message;
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
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while removing card info: '
            . $e->getMessage());
            $response['status'] = self::ERROR;
            $response['message'] = self::SYSTEM_ERROR;
        }
    }
}
