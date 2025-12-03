<?php

namespace Fedex\WebAnalytics\Plugin\Model\Widget\Instance;

use Fedex\WebAnalytics\Block\Widget\ConversionTracking;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Widget\Model\Widget\Instance;

/**
 * Class AfterGetWidgetParameters
 * @package Fedex\WebAnalytics\Plugin\Model\Widget\Instance
 */
class AfterGetWidgetParameters
{
    const CONVERSION_TRACKING_INSTANCE_CODE = 'conversion_tracking_widget';

    /**
     * AfterGetWidgetParameters constructor.
     * @param Json $serializer
     */
    public function __construct(
        private Json $serializer
    )
    {
    }

    /**
     * @param Instance $subject
     * @param $result
     * @return mixed
     */
    public function afterGetWidgetParameters(Instance $subject, $result)//NOSONAR
    {
        if ($subject->getInstanceCode() === self::CONVERSION_TRACKING_INSTANCE_CODE) {
            foreach ($result as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $innerKey => $innerValue) {
                        if (is_array($innerValue)) {
                            $value[$innerKey] = $this->serializer->serialize($innerValue);
                        }
                    }
                    $result[$key] = $value;
                }
            }
            if (isset($result[ConversionTracking::TRACKING_PARAMETERS_FROM_URL])
                && array_key_exists('__empty', $result[ConversionTracking::TRACKING_PARAMETERS_FROM_URL])) {
                unset($result[ConversionTracking::TRACKING_PARAMETERS_FROM_URL]['__empty']);
            }
            if (isset($result[ConversionTracking::TRACKING_PARAMETERS_STATIC_VALUE])
                && array_key_exists('__empty', $result[ConversionTracking::TRACKING_PARAMETERS_STATIC_VALUE])) {
                unset($result[ConversionTracking::TRACKING_PARAMETERS_STATIC_VALUE]['__empty']);
            }
        }
        return $result;
    }
}
