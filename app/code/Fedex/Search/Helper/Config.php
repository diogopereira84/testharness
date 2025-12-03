<?php
declare(strict_types=1);

namespace Fedex\Search\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Helper class for retrieving configuration values related to Fedex_Search module
 */
class Config extends AbstractHelper
{
    /**
     * XML path for the admin toggle that disables Magento's search query tracking
     *
     * Path: Stores > Configuration > FedEx Internal Configurations > Performance > Optimization
     * Field: B-2562150 Disable saveIncrementalPopularity() and saveNumResults() methods
     */
    private const XML_PATH_DISABLE_QUERY_TRACKING = 'performance/optimization/disable_query_tracking';

    /**
     * Config constructor
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Check whether Magento search query tracking (popularity & result count) should be disabled
     *
     * Returns true if the toggle is set to "Yes" in admin configuration.
     * This means the system should NOT execute saveIncrementalPopularity() or saveNumResults().
     *
     * @return bool
     */
    public function isQueryTrackingDisabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DISABLE_QUERY_TRACKING,
            ScopeInterface::SCOPE_STORE
        );
    }
}
