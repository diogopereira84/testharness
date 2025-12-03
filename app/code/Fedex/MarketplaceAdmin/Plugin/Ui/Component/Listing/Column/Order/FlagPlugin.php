<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceAdmin
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Plugin\Ui\Component\Listing\Column\Order;

use Fedex\MarketplaceAdmin\Model\Config;
use Mirakl\Adminhtml\Ui\Component\Listing\Column\Order\Flag;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class FlagPlugin
{
    public function __construct(
        readonly private ToggleConfig $toggleConfig,
        readonly private Config       $config,
    )
    {
    }

    public function aroundPrepareDataSource(Flag $subject, callable $proceed, array $dataSource)
    {
        if ($this->toggleConfig->getToggleConfigValue('tigers_b2185176_remove_adobe_commerce_overrides')) {
            if (!$this->config->isMktSelfregEnabled()) {
                return $proceed($dataSource);
            }
            if (isset($dataSource['data']['items'])) {
                foreach ($dataSource['data']['items'] as &$item) {
                    $item[$subject->getData('name')] = $this->decorationFlag($item);
                }
            }
            return $dataSource;
        }
        return $proceed($dataSource);
    }

    public function decorationFlag(array $item): string
    {
        $class = Config::OPERATOR_CLASS;
        $label = __(Config::OPERATOR_LABEL);
        if (isset($item['flag']) && $item['flag'] == Config::ORIGIN_MARKETPLACE) {
            $class = Config::MARKETPLACE_CLASS;
            $label = __(Config::MARKETPLACE_LABEL);
        } elseif (isset($item['flag']) && $item['flag'] == Config::ORIGIN_MIXED) {
            $class = Config::MIXED_CLASS;
            $label = __(Config::MIXED_LABEL);
        }
        return sprintf('<span class="%s">%s</span>', $class, $label);
    }

}
