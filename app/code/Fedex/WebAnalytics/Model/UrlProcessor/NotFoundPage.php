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

class NotFoundPage implements NotFoundPageInterface
{
    /**
     * CMS index default no route action name
     */
    private const CMS_INDEX_DEFAULT_NO_ROUTE = 'cms_index_defaultNoRoute';

    /**
     * CMS no route index action name
     */
    private const CMS_NOROUTE_INDEX = 'cms_noroute_index';

    /**
     * Page type value
     */
    private const PAGE_TYPE = 'application';

    /**
     * Page action names
     */
    private const PAGE_ACTION_NAMES = [
        self::CMS_INDEX_DEFAULT_NO_ROUTE,
        self::CMS_NOROUTE_INDEX,
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
    public function isCurrentPage(): bool
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
