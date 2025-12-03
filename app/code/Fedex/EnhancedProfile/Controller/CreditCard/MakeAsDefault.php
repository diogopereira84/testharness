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

class MakeAsDefault implements ActionInterface
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
                            $creditCardInfoRequest = (array)$creditCardList[$i];
                        }
                        $i++;
                    }
                    $profileCardId = $creditCardInfoRequest['profileCreditCardId'];
                    $creditCardToken = $this->enhancedProfile->updateCreditCard([], $profileCardId, 'GET');
                    if (isset($creditCardToken->output->creditCard->creditCardToken)) {
                        $creditCardInfoRequest['creditCardToken'] =
                        $creditCardToken->output->creditCard->creditCardToken;
                    } else {
                        $creditCardInfoRequest['creditCardToken'] = '';
                    }
                    $creditCardInfoRequest['primary'] = true;
                    $creditCardInfoRequest = (object)$creditCardInfoRequest;
                    $creditCardInfo = $this->enhancedProfile->makeArrayForCerditCardJson($creditCardInfoRequest);
                    $cardRequestJson = $this->enhancedProfile->prepareUpdateCerditCardJson($creditCardInfo);
                    $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' Make as Default Credit Card Request: ' .
                    var_export($cardRequestJson, true));
                    $profileCardId = $creditCardInfoRequest->profileCreditCardId;
                    $apiResponse = $this->enhancedProfile->updateCreditCard($cardRequestJson, $profileCardId, 'PUT');
                    if (!isset($apiResponse->errors)) {
                        $this->enhancedProfile->setProfileSession();
                        $cardInfo = $apiResponse->output;
                        $response['info'] = json_encode($cardInfo);
                        $response['message'] = __("Default credit card has been successfully updated.");
                        $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' Make as Default Credit Card Response: ' .
                        var_export($cardRequestJson, true));
                    } else {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error(s) from api response: '
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
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while trying to remove credit card info: '
            . $e->getMessage());
            $response['status'] = self::ERROR;
            $response['message'] = self::SYSTEM_ERROR;
        }
    }
}
