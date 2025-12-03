<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Model\Status;

use Magento\NegotiableQuote\Model\Status\BackendLabelProvider as NegotiableBackendLabelProvider;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

/**
 * Quote label provider for backend.
 */
class BackendLabelProvider extends NegotiableBackendLabelProvider
{
    /**
     * @inheritDoc
     */
    public function getStatusLabels()
    {
        return [
            NegotiableQuoteInterface::STATUS_DRAFT_BY_ADMIN => __('Draft'),
            NegotiableQuoteInterface::STATUS_CREATED => __('New'),
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER => __('Client reviewed'),
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN => __('Open'),
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER => __('Updated'),
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN => __('Submitted'),
            NegotiableQuoteInterface::STATUS_ORDERED => __('Ordered'),
            NegotiableQuoteInterface::STATUS_EXPIRED => __('Expired'),
            NegotiableQuoteInterface::STATUS_DECLINED => __('Declined'),
            NegotiableQuoteInterface::STATUS_CLOSED => __('Closed'),
            AdminConfigHelper::NBC_PRICED => __(AdminConfigHelper::STATUS_NBC_PRICED),
            AdminConfigHelper::NBC_SUPPORT => __(AdminConfigHelper::STATUS_NBC_SUPPORT),
        ];
    }
}
