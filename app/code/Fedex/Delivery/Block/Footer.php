<?php

namespace Fedex\Delivery\Block;

use Magento\Theme\Block\Html\Footer as ParentFooter;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Fedex\Delivery\ViewModel\CartPickup;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Footer extends ParentFooter
{
    /**
     * @param TemplateContext $templateContext
     * @param HttpContext $httpContext
     * @param CartPickup $cartPickupViewModel
     * @param ToggleConfig $toggleConfigViewModel
     * @param array $data
     */
    public function __construct(
        TemplateContext        $templateContext,
        HttpContext            $httpContext,
        protected CartPickup   $cartPickupViewModel,
        protected ToggleConfig $toggleConfigViewModel,
        array                  $data = []
    )
    {
        parent::__construct($templateContext, $httpContext, $data);
    }

    /**
     * @param string $path
     * @return string
     */
    public function getMediaUrl(string $path): string
    {
        return $this->cartPickupViewModel->getMediaUrl($path);
    }

    /**
     * @return bool|int
     */
    public function getProp19MazeGeeksToggle(): bool|int
    {
        return $this->toggleConfigViewModel->getToggleConfigValue('maze_geeks_add_data_analytics_and_prop_19_values');
    }

}
