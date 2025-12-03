<?php
/**
 * @category Fedex
 * @package  Fedex_SubmitOrderSidebar
 * @copyright  Copyright (c) 2022 Fedex
 * @author  Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Plugin;

use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi;
use Magento\NewRelicReporting\Model\NewRelicWrapper;

class NewRelicCustomAttributeOrderFlowOptimized
{
    const ERROR = 'error';
    const MESSAGE = 'msg';
    const RESPONSE = 'response';
    const TRANSACTION_SUCCESS = 'transaction_success';
    const ORDER_SUCCESS = 'order_success';
    const FAILURE_MSG = 'failure_msg';
    const FAILURE_RESPONSE = 'failure_response';

    /**
     * @param NewRelicWrapper $newRelicWrapper
     */
    public function __construct(
        protected NewRelicWrapper $newRelicWrapper
    )
    {
    }

    /**
     * Decode canva sizes
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param SubmitOrderApi $subject
     * @param array $result
     * @return array
     */
    public function afterCallFujitsuRateQuoteApi(SubmitOrderApi $subject, array $result): array
    {
        if ($this->newRelicWrapper->isExtensionInstalled()) {

            $errorFound = $result[self::ERROR] ?? false;
            $orderError = isset($result[self::ERROR]) && $result[self::ERROR] ? $result[self::ERROR] === true : false;
            $msg = $result[self::MESSAGE] ?? '';
            $response = isset($result[self::RESPONSE]) && $result[self::RESPONSE]
            ? json_encode($result[self::RESPONSE]) : '';
            switch ((int)$errorFound) {

                case 1:
                    $this->newRelicWrapper->addCustomParameter(self::TRANSACTION_SUCCESS, $orderError);
                    $this->newRelicWrapper->addCustomParameter(self::ORDER_SUCCESS, false);

                    $this->newRelicWrapper->addCustomParameter(
                        self::FAILURE_MSG,
                        $orderError ? $result['message'] : $msg
                    );
                    $this->newRelicWrapper->addCustomParameter(self::FAILURE_RESPONSE, $response);
                    break;

                case 2:
                    $this->newRelicWrapper->addCustomParameter(self::TRANSACTION_SUCCESS, false);
                    $this->newRelicWrapper->addCustomParameter(self::ORDER_SUCCESS, false);

                    $this->newRelicWrapper->addCustomParameter(self::FAILURE_MSG, $msg);
                    $this->newRelicWrapper->addCustomParameter(self::FAILURE_RESPONSE, $response);
                    break;

                default:
                    $this->newRelicWrapper->addCustomParameter(self::TRANSACTION_SUCCESS, true);
                    $this->newRelicWrapper->addCustomParameter(self::ORDER_SUCCESS, true);

                    $this->newRelicWrapper->addCustomParameter(self::FAILURE_MSG, '');
                    $this->newRelicWrapper->addCustomParameter(self::FAILURE_RESPONSE, '');
                    break;
            }
        }

        return $result;
    }
}
