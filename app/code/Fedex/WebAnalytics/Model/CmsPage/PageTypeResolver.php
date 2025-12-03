<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model\CmsPage;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Cms\Model\Page;

class PageTypeResolver implements PageTypeResolverInterface
{
    /**
     * CMS Page identifiers
     */
    private const CMS_PAGE_IDENTIFIERS = [
        'cms_index_index',
        'cms_index_defaultIndex',
        'cms_page_view'
    ];

    /**
     * @param Http $http
     * @param LoggerInterface $logger
     * @param Page $page
     */
    public function __construct(
        private readonly Http $http,
        private readonly LoggerInterface $logger,
        private readonly Page $page,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(): ?string
    {
        try {
            if (in_array(
                $this->http->getFullActionName(),
                self::CMS_PAGE_IDENTIFIERS
            ) && $this->page->getId()) {
                return $this->page->getPageType();
            }
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }

        return null;
    }
}
