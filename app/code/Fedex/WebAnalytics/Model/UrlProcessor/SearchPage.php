<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model\UrlProcessor;

use Magento\Framework\App\Request\Http;

class SearchPage implements SearchPageInterface
{
    /**
     * Catalog result index action name
     */
    private const CATALOG_RESULT_INDEX = 'catalogsearch_result_index';

    /**
     * Catalog search advanced result action name
     */
    private const CATALOGSEARCH_ADVANCED_RESULT = 'catalogsearch_advanced_result';

    /**
     * Catalog search advanced index action name
     */
    private const CATALOGSEARCH_ADVANCED_INDEX = 'catalogsearch_advanced_index';

    /**
     * Search term popular action name
     */
    private const SEARCH_TERM_POPULAR = 'search_term_popular';

    /**
     * Page type value
     */
    private const PAGE_TYPE = 'FXOSearchpage';

    /**
     * Page action names
     */
    private const PAGE_ACTION_NAMES = [
        self::CATALOG_RESULT_INDEX,
        self::CATALOGSEARCH_ADVANCED_RESULT,
        self::CATALOGSEARCH_ADVANCED_INDEX,
        self::SEARCH_TERM_POPULAR,
    ];

    /**
     * Initializes SearchPage
     *
     * @param Http $http
     */
    public function __construct(
        private readonly Http $http
    ){
    }

    /**
     * @inheritDoc
     */
    public function isSearchPage(): bool
    {
        if (in_array(
                $this->http->getFullActionName(),
                self::PAGE_ACTION_NAMES
            )) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return self::PAGE_TYPE;
    }
}
