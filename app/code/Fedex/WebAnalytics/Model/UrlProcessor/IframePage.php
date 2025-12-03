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

class IframePage implements PageResolverInterface
{
    /**
     * Canva index action name
     */
    private const CANVA_INDEX_INDEX = 'canva_index_index';

    /**
     * Configurator index action name
     */
    private const CONFIGURATOR_INDEX_INDEX = 'configurator_index_index';

    /**
     * Page action names
     */
    private const PAGE_ACTION_NAMES = [
        self::CONFIGURATOR_INDEX_INDEX,
        self::CANVA_INDEX_INDEX,
    ];


    /**
     * Page type value
     */
    private const PAGE_TYPE = 'application';

    /**
     * Initializes IframePage
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
