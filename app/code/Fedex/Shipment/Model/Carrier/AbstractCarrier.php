<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;

class AbstractCarrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier
{
    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    public $_rateResultFactory;
    public $_trackStatusFactory;
    /**
     * @var \Magento\Shipping\Model\Tracking\ResultFactory
     */
    public $_trackFactory;
    public $_result;
    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\Result\StatusFactory
     */
    protected $trackStatusFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\ResultFactory
     */
    protected $trackFactory;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param array $data
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->_rateResultFactory = $rateResultFactory;
        $this->_trackStatusFactory = $trackStatusFactory;
        $this->_trackFactory = $trackFactory;
    }

    /**
     * Determins if tracking is set in the admin panel
     **/
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * @param RateRequest $request
     * @return Result|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collectRates(RateRequest $request)
    {
        return false;
    }

    public function getTrackingInfo($tracking, $postcode = null)
    {
        $result = $this->getTracking($tracking, $postcode);
        if ($result instanceof \Magento\Shipping\Model\Tracking\Result) {
            if ($trackings = $result->getAllTrackings()) {
                return $trackings[0];
            }
        } elseif (is_string($result) && !empty($result)) {
            return $result;
        }
        return false;
    }

    public function getTracking($trackings, $postcode = null)
    {
        if (!is_array($trackings)) {
            $trackings = [$trackings];
        }
        $this->getCgiTracking($trackings, $postcode);
        return $this->_result;
    }

    /** Popup window to tracker **/
    protected function getCgiTracking($trackings, $postcode = null)
    {
        $this->_result = $this->_trackFactory->create();
        //try
        $defaults = $this->getDefaults();
        foreach ($trackings as $tracking) {
            $status = $this->_trackStatusFactory->create();
            $status->setCarrier('Tracker');
            $status->setCarrierTitle($this->getConfigData('title'));
            $status->setTracking($tracking);
            $status->setPopup(1);
            $manualUrl = "http://www.fedex.com/Tracking?language=english&cntry_code=us&tracknumbers=#TRACKNUM#";
            $taggedUrl = $manualUrl;
            $fullUrl = str_replace("#TRACKNUM#", $tracking, $taggedUrl);
            $taggedUrlPostcode = strpos($taggedUrl, '#POSTCODE#');
            if ($postcode && $taggedUrlPostcode) {
                $fullUrl = str_replace("#POSTCODE#", $postcode, $fullUrl);
            }
            $status->setUrl($fullUrl);
            $this->_result->append($status);
        }
    }
}
