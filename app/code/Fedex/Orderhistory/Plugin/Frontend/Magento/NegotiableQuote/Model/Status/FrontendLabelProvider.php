<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\NegotiableQuote\Model\Status;

use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\Orderhistory\Helper\Data;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

class FrontendLabelProvider
{

    /**
     * @inheritDoc
     */
    public function __construct(
        protected Data $helper
    )
    {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetStatusLabels(
        \Magento\NegotiableQuote\Model\Status\FrontendLabelProvider $subject,
        $result
    ) {
        if ($this->helper->isModuleEnabled()) {
            return [
                NegotiableQuoteInterface::STATUS_CREATED => __('Submitted'),
                NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER => __('Open'),
                NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN => __('Submitted'),
                NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER => __('Submitted'),
                NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN => __('Submitted'),
                NegotiableQuoteInterface::STATUS_ORDERED => __('Ordered'),
                NegotiableQuoteInterface::STATUS_EXPIRED => __('Expired'),
                NegotiableQuoteInterface::STATUS_DECLINED => __('Declined'),
                NegotiableQuoteInterface::STATUS_CLOSED => __('Closed'),
                AdminConfigHelper::NBC_PRICED => __('NBC Priced'),
                AdminConfigHelper::NBC_SUPPORT => __('NBC Support'),
            ];
        }
        return $result;
    }
}
