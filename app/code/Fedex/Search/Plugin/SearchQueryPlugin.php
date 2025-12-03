<?php
declare(strict_types=1);

namespace Fedex\Search\Plugin;

use Magento\Search\Model\Query as QueryModel;
use Magento\Search\Model\ResourceModel\Query as QueryResource;
use Fedex\Search\Helper\Config as ConfigHelper;

/**
 * Plugin class to conditionally disable search query tracking
 *
 * Disables the execution of saveIncrementalPopularity() and saveNumResults()
 * in Magento when the corresponding toggle is enabled in admin config.
 */
class SearchQueryPlugin
{
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;

    /**
     * Constructor
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * Plugin for saveIncrementalPopularity()
     * Prevents writing popularity data to the search_query table if disabled via config.
     *
     * @param QueryResource $subject
     * @param \Closure $proceed
     * @param QueryModel $query
     * @return void
     */
    public function aroundSaveIncrementalPopularity(
        QueryResource $subject,
        \Closure $proceed,
        QueryModel $query
    ): void {
        if ($this->configHelper->isQueryTrackingDisabled()) {
            // Skip saving popularity if tracking is disabled
            return;
        }

        // Proceed with original method
        $proceed($query);
    }

    /**
     * Plugin for saveNumResults()
     * Prevents writing result count data to the search_query table if disabled via config.
     *
     * @param QueryResource $subject
     * @param \Closure $proceed
     * @param QueryModel $query
     * @return void|null
     */
    public function aroundSaveNumResults(
        QueryResource $subject,
        \Closure $proceed,
        QueryModel $query
    ): void {
        if ($this->configHelper->isQueryTrackingDisabled()) {
            // Skip saving number of results if tracking is disabled
            return;
        }

        // Proceed with original method
        $proceed($query);
    }
}
