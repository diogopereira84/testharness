<?php

namespace Fedex\WebAnalytics\Plugin\Model\Widget;

use Magento\Widget\Helper\Conditions;
use Magento\Widget\Model\Widget;

/**
 * Class AfterGetWidgetParameters
 * @package Fedex\WebAnalytics\Plugin\Model\Widget
 */
class BeforeGetWidgetDeclaration
{
    /**
     * AfterGetWidgetParameters constructor.
     * @param Conditions $conditions
     */
    public function __construct(
        private Conditions $conditions
    )
    {
    }

    /**
     * @param Widget $subject
     * @param string $type Widget Type
     * @param array $params Pre-configured Widget Params
     * @param bool $asIs Return result as widget directive(true) or as placeholder image(false)
     * @return string Widget directive ready to parse
     */
    public function beforeGetWidgetDeclaration(Widget $subject, $type, $params = [], $asIs = true)//NOSONAR
    {
        $conversionTrackingWidgetClass = \Fedex\WebAnalytics\Block\Widget\ConversionTracking::class;
        if ($type === $conversionTrackingWidgetClass) {
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $innerKey => $innerValue) {
                        if ($innerKey === '__empty') {
                            unset($value[$innerKey]);
                        }
                    }
                    $params[$key] = $this->conditions->encode($value);
                }
            }
        }
        return [$type, $params, $asIs];
    }
}
