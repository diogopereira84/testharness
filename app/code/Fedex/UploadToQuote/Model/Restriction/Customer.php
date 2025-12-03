<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Model\Restriction;

use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface as NegotiableQuote;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\Restriction\Customer as NegotiableCustomer;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

/**
 * Class defines access restrictions for customer depending on the quote status.
 */
class Customer extends NegotiableCustomer
{
    /**
     * Allowed actions for statuses
     *
     * @var array
     */
    protected $allowedActionsByStatus = [
        NegotiableQuote::STATUS_CREATED => [
            RestrictionInterface::ACTION_SUBMIT,
            RestrictionInterface::ACTION_DUPLICATE,
            RestrictionInterface::ACTION_CLOSE,
            RestrictionInterface::ACTION_VIEW,
        ],
        NegotiableQuote::STATUS_PROCESSING_BY_ADMIN => [
            RestrictionInterface::ACTION_DUPLICATE,
            RestrictionInterface::ACTION_CLOSE,
            RestrictionInterface::ACTION_VIEW,
        ],
        NegotiableQuote::STATUS_PROCESSING_BY_CUSTOMER => [
            RestrictionInterface::ACTION_SUBMIT,
            RestrictionInterface::ACTION_DELETE,
            RestrictionInterface::ACTION_DUPLICATE,
            RestrictionInterface::ACTION_VIEW,
        ],
        NegotiableQuote::STATUS_SUBMITTED_BY_CUSTOMER => [
            RestrictionInterface::ACTION_DUPLICATE,
            RestrictionInterface::ACTION_CLOSE,
            RestrictionInterface::ACTION_VIEW,
        ],
        NegotiableQuote::STATUS_SUBMITTED_BY_ADMIN => [
            RestrictionInterface::ACTION_SUBMIT,
            RestrictionInterface::ACTION_PROCEED_TO_CHECKOUT,
            RestrictionInterface::ACTION_DELETE,
            RestrictionInterface::ACTION_DUPLICATE,
            RestrictionInterface::ACTION_CLOSE,
            RestrictionInterface::ACTION_VIEW,
        ],
        NegotiableQuote::STATUS_ORDERED => [
            RestrictionInterface::ACTION_DUPLICATE,
            RestrictionInterface::ACTION_VIEW,
        ],
        NegotiableQuote::STATUS_EXPIRED => [
            RestrictionInterface::ACTION_SUBMIT,
            RestrictionInterface::ACTION_DELETE,
            RestrictionInterface::ACTION_DUPLICATE,
            RestrictionInterface::ACTION_PROCEED_TO_CHECKOUT,
            RestrictionInterface::ACTION_VIEW,
        ],
        NegotiableQuote::STATUS_DECLINED => [
            RestrictionInterface::ACTION_SUBMIT,
            RestrictionInterface::ACTION_PROCEED_TO_CHECKOUT,
            RestrictionInterface::ACTION_DELETE,
            RestrictionInterface::ACTION_DUPLICATE,
            RestrictionInterface::ACTION_VIEW,
        ],
        NegotiableQuote::STATUS_CLOSED => [
            RestrictionInterface::ACTION_DUPLICATE,
            RestrictionInterface::ACTION_DELETE,
            RestrictionInterface::ACTION_VIEW,
        ],
        AdminConfigHelper::NBC_PRICED => [
            RestrictionInterface::ACTION_VIEW,
        ],
        AdminConfigHelper::NBC_SUPPORT => [
            RestrictionInterface::ACTION_VIEW,
        ]
    ];
}
