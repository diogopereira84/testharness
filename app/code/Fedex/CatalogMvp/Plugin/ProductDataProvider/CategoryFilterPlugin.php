<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin\ProductDataProvider;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider as CoreProvider;
use Magento\Framework\Api\Filter;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

/**
 * Plugin to customize category filter behavior in product data provider.
 */
class CategoryFilterPlugin
{
    /**
     * Feature flag constant
     */
    public const TECH_TITANS_E_475721 = 'tech_titans_E_475721';

    /**
     * CategoryFilterPlugin constructor.
     *
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig,
        private readonly LoggerInterface $logger
    ) {
    }
    
    /**
     * Around plugin for addFilter method.
     *
     * Intercepts the category_id_filter and applies custom filtering logic
     * when the feature toggle is enabled.
     *
     * @param CoreProvider $subject Product data provider
     * @param callable $proceed Original addFilter method
     * @param Filter $filter Filter object
     * @return void
     */
    public function aroundAddFilter(
        CoreProvider $subject,
        callable $proceed,
        Filter $filter
    ): void {
        try {
            if ($filter->getField() === 'category_id_filter'
                && $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_E_475721)
            ) {
                $subject->getCollection()->addCategoriesFilter(
                    ['in' => (array) $filter->getValue()]
                );
                return;
            }
            $proceed($filter);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in CategoryFilterPlugin::aroundAddFilter ' . $e->getMessage());
        }
    }
}
