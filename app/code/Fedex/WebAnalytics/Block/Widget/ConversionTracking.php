<?php
declare(strict_types=1);

namespace Fedex\WebAnalytics\Block\Widget;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Widget\Helper\Conditions;

/**
 * Class ConversionTracking
 * @package Fedex\WebAnalytics\Block\Widget
 */
class ConversionTracking extends Template implements BlockInterface
{
    public const FROM_REQUEST = 'from_request';
    public const TRACKING_PARAMETERS_FROM_URL = 'tracking_parameters_from_url';
    public const TRACKING_PARAMETERS_STATIC_VALUE = 'tracking_parameters_static_value';
    const ENABLED = 'enabled';
    const SELECTOR_CLASS = 'selector_class';
    const DISPLAY_TYPE = 'display_type';
    const STATIC_VALUE = 'static_value';

    /**
     * @var string
     */
    protected $_template = "Fedex_WebAnalytics::widget/conversion_tracking.phtml";

    /**
     * ConversionTracking constructor
     * @param Template\Context $context
     * @param Conditions $conditions
     * @param Json $serializer
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        protected Conditions $conditions,
        protected Json $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function toHtml()
    {
        if ($this->isEnabled()) {
            return parent::toHtml();
        }

        return false;
    }

    /**
     * Return widget config "Enabled" value
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getData(self::ENABLED);
    }

    /**
     * @return string|null
     */
    public function getSelectorClass()
    {
        return $this->getData(self::SELECTOR_CLASS);
    }

    /**
     * @return string|null
     */
    public function getDisplayType()
    {
        return $this->getData(self::DISPLAY_TYPE);
    }

    /**
     * @return string|null
     */
    public function getTrackingParams()
    {
        $requestParams = false;

        if ($this->getDisplayType() == self::FROM_REQUEST) {

            $requestParams = $this->getData(self::TRACKING_PARAMETERS_FROM_URL) ?? '';
        } elseif ($this->getDisplayType() == self::STATIC_VALUE) {

            $requestParams = $this->getData(self::TRACKING_PARAMETERS_STATIC_VALUE) ?? '';
        }

        if (str_contains($requestParams, '^[')) {
            $decodedParams = $this->conditions->decode($requestParams);
            foreach ($decodedParams as $key => $decodedParam) {
                $decodedParams[] = $decodedParam;
                unset($decodedParams[$key]);
            }
            return is_array($decodedParams) ? $this->serializer->serialize($decodedParams) : '[]';
        }

        return '[' . rtrim($requestParams ?? '', ',') . ']';
    }
}
