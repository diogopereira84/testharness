<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Helper class for review order
 */
class RevieworderHelper extends AbstractHelper
{
    /**
     * @var Data $deliveryDataHelper
     */
    protected $deliveryDataHelper;

    /**
     * initializing Constructor
     *
     * @param Context $context
     * @param PriceHelper $priceHelper
     * @param TimezoneInterface $timezoneInterface
     * @param DeliveryHelper $deliveryDataHelper
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        protected PriceHelper $priceHelper,
        protected TimezoneInterface $timezoneInterface,
        DeliveryHelper $deliveryDataHelper,
        protected CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->deliveryDataHelper = $deliveryDataHelper;
    }

    /**
     * Get formatted price
     *
     * @param string $price
     * @return string
     */
    public function getFormattedPrice($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * Get formatted date
     *
     * @param string $dateString
     * @param string $format
     * @return string
     */
    public function getFormattedDate($dateString, $format = 'm/d/Y')
    {
        return $this->timezoneInterface->date($dateString)->format($format);
    }

    /**
     * Check if user has a permission for review orders
     *
     * @return string
     */
    public function checkIfUserHasReviewOrderPermission()
    {
        if ($this->deliveryDataHelper->isSelfRegCustomerAdminUser()) {
            return true;
        }
            
        return $this->deliveryDataHelper->checkPermission('review_orders');
    }

    /**
     * Retrieve company id
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        return isset($this->customerSession->getOndemandCompanyInfo()['company_id'])
        ? trim($this->customerSession->getOndemandCompanyInfo()['company_id']) : '';
    }
    
    /**
     * Retrieve customer id
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * To send Response in json.
     *
     * @param array $resData
     * @param object $resultJson
     * @return object
     */
    public function sendResponseData($resData, $resultJson)
    {
        $this->customerSession->setSuccessErrorData($resData);

        return $resultJson->setData($resData);
    }
}
