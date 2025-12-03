<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Plugin\Model\Status;

use Magento\NegotiableQuote\Model\Status\BackendLabelProvider as NegotiableBackendLabelProvider;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Framework\App\Request\Http;

/**
 * Class BackendLabelProvider
 */
class BackendLabelProvider
{
    /**
     * @param AdminConfigHelper $adminConfigHelper
     * @param Http $http
     */
    public function __construct(
        protected AdminConfigHelper $adminConfigHelper,
        protected Http $http
    )
    {
    }

    /**
     * Get status label
     *
     * @param object $subject
     * @param array $result
     * @return array
     */
    public function afterGetStatusLabels(NegotiableBackendLabelProvider $subject, $result)
    {
        if ($this->adminConfigHelper->isUploadToQuoteToggle()) {
            $quoteId = $this->http->getParam("quote_id");
            if ($quoteId) {
                $status = $this->adminConfigHelper->getNegotiableQuoteStatus($quoteId);
                $result["created"] = $status;
                $result["processing_by_admin"] = $status;
                $result["submitted_by_customer"] = $status;
                $result["submitted_by_admin"] = $status;
                $result["ordered"] = $status;
                $result["closed"] = $status;
                $result[AdminConfigHelper::NBC_PRICED] = $status;
                $result[AdminConfigHelper::NBC_SUPPORT] = $status;
            }
        }

        return $result;
    }
}
