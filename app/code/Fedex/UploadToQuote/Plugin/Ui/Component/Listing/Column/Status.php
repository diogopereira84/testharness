<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Plugin\Ui\Component\Listing\Column;

use Magento\NegotiableQuote\Ui\Component\Listing\Column\Status as NegotiableStatus;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

/**
 * Class Status
 */
class Status
{
    /**
     * @param AdminConfigHelper $adminConfigHelper
     */
    public function __construct(
        protected AdminConfigHelper $adminConfigHelper
    )
    {
    }

    /**
     * Prepare Data Source
     *
     * @param object $subject
     * @param array $result
     * @param array $dataSource
     * @return array
     */
    public function afterPrepareDataSource(NegotiableStatus $subject, $result, array $dataSource)
    {
        if ($this->adminConfigHelper->isUploadToQuoteToggle() && isset($result['data']['items'])) {
            foreach ($result['data']['items'] as &$item) {
                $negotiableStatus = $this->adminConfigHelper
                ->getNegotiableQuoteStatus($item["entity_id"]);
                
                if ($negotiableStatus) {
                    $item["status"] = $negotiableStatus;
                }
            }
        }

        return $result;
    }
}
