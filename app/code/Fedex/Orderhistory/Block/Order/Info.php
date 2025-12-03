<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Block\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Fedex\Orderhistory\Helper\Data;

/**
 * Invoice view  comments form
 *
 * @api
 * @author Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Info extends \Magento\Sales\Block\Order\Info
{
    /**
     * @var string
     */
    protected $template = 'Magento_Sales::order/info.phtml';

    /**
     * Core
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;

    public const BR_TAG = '</br>';
    public const REMOVE_STRING = '!\d+!';

    /**
     * @param TemplateContext $context
     * @param Registry $registry
     * @param PaymentHelper $paymentHelper
     * @param AddressRenderer $addressRenderer
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
        protected Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $paymentHelper, $addressRenderer, $data);
    }

    /**
     * @inheritdoc
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        if ($this->helper->isModuleEnabled() && $this->helper->isEnhancementEnabeled()
        ) {
            $this->pageConfig->getTitle()->set(__('Order Number #%1', $this->getOrder()->getRealOrderId()));
        } else {
            $this->pageConfig->getTitle()->set(__('Order # %1', $this->getOrder()->getRealOrderId()));
        }
        $infoBlock = $this->paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        $this->setChild('payment_info', $infoBlock);
    }

    /**
     * @inheritDoc
     */
    public function isPrintReceiptRetail()
    {
        return $this->helper->isPrintReceiptRetail();
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        $template = $this->_template;
        if ($this->isPrintReceiptRetail()) {
            $template = '';
        }

        return $template;
    }

    /**
     * Get estimated pickup date time
     *
     * @return string
     */
    public function getEstimatedPickUpDateTime()
    {
        $estimatedPickupDateTime = $this->getOrder()->getEstimatedPickupTime();
        $estimatePickupDateTime = '';

        if (!empty($estimatedPickupDateTime)) {
            if (substr_count($estimatedPickupDateTime, ',') == 2 && substr_count($estimatedPickupDateTime, 'at') == 0) {
                /* Monday, April 24, 4:00pm */
                $estimatePickupDateTime = trim($estimatedPickupDateTime);
            } elseif (substr_count($estimatedPickupDateTime, ',') == 2
            && substr_count($estimatedPickupDateTime, 'at') == 1) {
                /* Thursday, February 16th, 2023 at 5:00 p.m. */
                $pickupDateArray = explode(' ', $estimatedPickupDateTime);
                preg_match_all(self::REMOVE_STRING, $pickupDateArray[2], $matches);
                $estimatePickupDateTime = $this->getOldPickupDateFormat($pickupDateArray, $matches);
            } elseif (substr_count($estimatedPickupDateTime, ',') == 1
            && substr_count($estimatedPickupDateTime, 'At') == 1
            && substr_count($estimatedPickupDateTime, self::BR_TAG) == 0) {
                /* Tuesday,April 25th At 08:00 AM */
                $pickupDay = explode(',', $estimatedPickupDateTime);
                $pickupDateTime = explode(' ', $pickupDay[1]);
                preg_match_all(self::REMOVE_STRING, $pickupDateTime[1], $matches);
                $estimatePickupDateTime = $pickupDay[0].', '.$pickupDateTime[0].' '.$matches[0][0].', '
                .$pickupDateTime[3].strtolower($pickupDateTime[4]);
            } elseif (substr_count($estimatedPickupDateTime, ',') == 1
            && substr_count($estimatedPickupDateTime, 'At') == 0
            && substr_count($estimatedPickupDateTime, self::BR_TAG) == 0) {
                /* Monday,  April 17th 5:00 P.M. */
                $pickupDay = explode('  ', $estimatedPickupDateTime);
                $pickupDateTime = explode(' ', $pickupDay[1]);
                preg_match_all(self::REMOVE_STRING, $pickupDateTime[1], $matches);
                $estimatePickupDateTime = $this->manageOldPickupDateTimeFormat($pickupDay, $pickupDateTime, $matches);
            } elseif (substr_count($estimatedPickupDateTime, ',') == 1
            && substr_count($estimatedPickupDateTime, 'At') == 0
            && substr_count($estimatedPickupDateTime, self::BR_TAG) == 1) {
                /* Thursday,  March 9th</br>7:00 P.M. */
                $pickupDay = explode('  ', $estimatedPickupDateTime);
                $pickupDateTime = explode(' ', $pickupDay[1]);
                $pickupDateTimeArray = explode(self::BR_TAG, $pickupDateTime[1]);
                preg_match_all(self::REMOVE_STRING, $pickupDateTimeArray[0], $matches);
                $estimatePickupDateTime = $this->getOldPickupDateTimeFormat(
                    $pickupDay,
                    $pickupDateTime,
                    $matches,
                    $pickupDateTimeArray
                );
            }
        }

        return $estimatePickupDateTime;
    }

    /**
     * Manage the existing pickup date time format
     *
     * @param array $pickupDateArray
     * @param array $matches
     * @return string
     */
    public function getOldPickupDateFormat($pickupDateArray, $matches)
    {
        if (str_contains($pickupDateArray[6], 'p.m.')) {
            $estimatePickupDateTime = $pickupDateArray[0].' '.$pickupDateArray[1].' '.$matches[0][0].', '
            .$pickupDateArray[5].'pm';
        } else {
            $estimatePickupDateTime = $pickupDateArray[0].' '.$pickupDateArray[1].' '.$matches[0][0].', '
            .$pickupDateArray[5].'am';
        }

        return $estimatePickupDateTime;
    }

    /**
     * Manage the existing pickup date time format
     *
     * @param array $pickupDay
     * @param array $pickupDateTime
     * @param array $matches
     * @param array $pickupDateTimeArray
     * @return string
     */
    public function getOldPickupDateTimeFormat($pickupDay, $pickupDateTime, $matches, $pickupDateTimeArray)
    {
        if (str_contains($pickupDateTime[2], 'P.M.')) {
            $estimatePickupDateTime = $pickupDay[0].' '.$pickupDateTime[0].' '.$matches[0][0].', '
            .$pickupDateTimeArray[1].'pm';
        } else {
            $estimatePickupDateTime = $pickupDay[0].' '.$pickupDateTime[0].' '.$matches[0][0].', '
            .$pickupDateTimeArray[1].'am';
        }

        return $estimatePickupDateTime;
    }

    /**
     * Manage the existing pickup date time format
     *
     * @param array $pickupDay
     * @param array $pickupDateTime
     * @param array $matches
     * @return string
     */
    public function manageOldPickupDateTimeFormat($pickupDay, $pickupDateTime, $matches)
    {
        if (str_contains($pickupDateTime[3], 'P.M.')) {
            $estimatePickupDateTime = $pickupDay[0].' '.$pickupDateTime[0].' '.$matches[0][0].', '
            .$pickupDateTime[2].'pm';
        } else {
            $estimatePickupDateTime = $pickupDay[0].' '.$pickupDateTime[0].' '.$matches[0][0].', '
            .$pickupDateTime[2].'am';
        }

        return $estimatePickupDateTime;
    }

    /**
     * @inheritDoc
     *
     */
    public function getContactPhone()
    {
        $contactAddress = $this->helper->getContactAddress($this->getOrder()->getQuoteId());
        if (isset($contactAddress['telephone']) && $contactAddress['telephone'] != "") {
            $telephone = $contactAddress['telephone'];
            $telephone = substr_replace($telephone, '(', 0, 0);
            $telephone = substr_replace($telephone, ')', 4, 0);
            $telephone = substr_replace($telephone, ' ', 5, 0);
            $telephone = substr_replace($telephone, '-', 9, 0);

            return $telephone;
        }

        return '';
    }
}
