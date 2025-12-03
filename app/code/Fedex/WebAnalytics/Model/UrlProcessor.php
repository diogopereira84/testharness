<?php
/**
 * @category Fedex
 * @package  Fedex_WebAnalytics
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\WebAnalytics\Model\UrlProcessor\SearchPage;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\UrlInterface as Url;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Cms\Model\Page;

class UrlProcessor
{
    public const CATALOG_PRODUCT_VIEW = 'catalog_product_view';
    public const CATALOG_CATEGORY_VIEW = 'catalog_category_view';
    public const HOME_PAGE = 'cms_index_index';
    public const CONFIGURATOR_INDEX_INDEX = 'configurator_index_index';
    public const CART_PAGE = 'checkout_cart_index';
    public const CHECKOUT_PAGE = 'checkout_index_index';
    public const CUSTOM_FRAMES_PAGE = 'custom-frames';
    public const CUSTOM_BRANDED_BOXES_PAGE = 'custom-branded-boxes';
    public const APPAREL_PAGE = 'apparel';
    public const BAGS_PAGE = 'bags';
    public const DRINKWARE_PAGE = 'drinkware';
    public const OFFICE_ESSENTIALS_PAGE = 'office-essentials';
    public const WRITING_INSTRUMENTS_PAGE = 'writing-instruments';
    public const COUPONS_DEALS = 'coupons-deals';
    public const MARKETPLACE_INFORMATION = 'marketplace-information';
    public const SUCCESS_PAGE = 'submitorder_index_ordersuccess';
    public const DIRECT_MAIL_PAGE = 'direct-mail';
    public const ONLINE_NOTARY_PAGE = 'online-notary';
    public const CMS_PAGE_VIEW = 'cms_page_view';
    public const PAGE_TYPE_FOR_CATEGORY_PRODUCT = 'productpage';
    public const PAGE_TYPE_FOR_CONFIGURATOR_INDEX_INDEX = 'application';
    public const PAGE_TYPE_FOR_HOME_PAGE = 'homepage';
    public const PAGE_TYPE_FOR_COUPONS_DEALS = 'printingpage';
    public const PAGE_TYPE_FOR_MARKETPLACE_INFORMATION = 'content';
    public const PAGE_TYPE_FOR_CART_PAGE = 'cart';
    public const PAGE_TYPE_FOR_CHECKOUT_PAGE = 'checkout';
    public const PAGE_TYPE_FOR_FAQ_PAGE = 'supportpage';
    public const PAGE_TYPE_FOR_PROFILE = 'fxoprofile';
    public const PRODUCT_PAGE = [
        self::DIRECT_MAIL_PAGE,
        self::CUSTOM_FRAMES_PAGE,
        self::CUSTOM_BRANDED_BOXES_PAGE,
        self::APPAREL_PAGE,
        self::BAGS_PAGE,
        self::DRINKWARE_PAGE,
        self::OFFICE_ESSENTIALS_PAGE,
        self::WRITING_INSTRUMENTS_PAGE,
        self::ONLINE_NOTARY_PAGE
    ];


    /**
     * @param HttpRequest $request
     * @param Url $url
     * @param LocaleResolver $resolver
     * @param CookieManagerInterface $cookieManager
     * @param CacheInterface $cache
     * @param Page $cmsPage
     * @param SearchPage $searchPage
     * @param UrlProcessor\NotFoundPage $notFoundPage
     * @param UrlProcessor\IframePage $iframePage
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private HttpRequest $request,
        private Url $url,
        private LocaleResolver $resolver,
        private CookieManagerInterface $cookieManager,
        private CacheInterface $cache,
        private Page $cmsPage,
        private readonly UrlProcessor\SearchPage $searchPage,
        private readonly UrlProcessor\NotFoundPage $notFoundPage,
        private readonly UrlProcessor\IframePage $iframePage,
        private readonly ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @return bool
     */
    public function isHomePage()
    {
        $fullActionName = $this->request->getFullActionName();
        return in_array($fullActionName, ['cms_index_index', 'cms_index_defaultIndex']);
    }

    /**
     * @return false|string|null
     */
    public function getSubDomainName()
    {
        $baseUrl = $this->url->getBaseUrl();
        $url = parse_url((string)$baseUrl);
        if (isset($url['host'])) {
            $host = explode('.', $url['host']);
            return array_shift($host);
        }
        return false;
    }

    /**
     * @return false|string
     */
    public function generatePageId($domain = null)
    {
        $domainPrefix = $domain ?? $this->getSubDomainName();
        if ($domainPrefix) {
            $locale = $this->getLocale();
            if (empty($locale)) {
                return false;
            }
            //@codeCoverageIgnoreStart
            $localeFormat = explode("_", $locale);
            return $localeFormat[1] . '/' .$localeFormat[0] . '/' . $domainPrefix . $this->getPagePath();
            //@codeCoverageIgnoreEnd
        }

        return false;
    }

    /**
     * @return string
     */
    private function getLocale(): string
    {
        return $this->cookieManager->getCookie('fdx_locale') ?? $this->resolver->getLocale();
    }

    /**
     * @return array|false|int|string|null
     */
    private function getPagePath()
    {
        return $this->isHomePage() ? '/home' : (string)$this->getParsedUrl();
    }

    /**
     * @return string
     */
    private function getParsedUrl()
    {
        $parseUrl = parse_url((string)$this->url->getCurrentUrl(), PHP_URL_PATH);
        $withoutExt = preg_replace('/\\.[^.\\s]{0,6}$/', '', $parseUrl);
        if (strpos($withoutExt, 'sales/order/view/order_id') !== false) {
            $expUrl = explode('/', $withoutExt);
            if (isset($expUrl[1])) {
                $withoutExt = '/' . $expUrl[1] . '/sales/order/history';
            }
        }

        return rtrim($withoutExt, '/');
    }

    /**
     * @return string
     */
    private function getCacheKey()
    {
        return $this->request->getFullActionName() . '-' . $this->getPagePath();
    }

    /**
     * @return false|string
     */
    public function getPageId($domain = null)
    {
        $cacheKey = $this->getCacheKey();

        if ($pageId = $this->cache->load($cacheKey)) {
            return $pageId;
        }

        $pageId = $this->generatePageId($domain);
        if ($this->toggleConfig->getToggleConfigValue('hawks_d_195570_toggle')) {
            $ttl = (int) $this->toggleConfig->getToggleConfigValue('hawks_d_195570_toggle_value');
            $this->cache->save($pageId, $cacheKey, [], $ttl > 0 ? $ttl : 3600);
            return $pageId;
        }
        $this->cache->save($pageId, $cacheKey);
        return $pageId;
    }

    /**
     * Identify current page is profile page
     *
     * @return bool
     */
    public function isProfilePages()
    {
        $fullActionName = $this->request->getFullActionName();
        return in_array($fullActionName, ['customer_account_index', 'sales_order_history', 'customer_account_accountsandcreditcards', 'customer_account_preferences', 'sales_order_view']);
    }

    /**
     * Get page type
     *
     * @return string
     */
    public function getPageType()
    {
        $pageType = null;
        $currentPageIndentifier =  $this->getCurrentPageIdentifier();
        $actionName = $this->request->getFullActionName();
        $currentPageLayout =  $this->getCurrentPageLayout();

            if ($actionName == self::CATALOG_CATEGORY_VIEW ||
                $actionName == self::CATALOG_PRODUCT_VIEW ||
                in_array($currentPageIndentifier, self::PRODUCT_PAGE)
            ) {
                $pageType = self::PAGE_TYPE_FOR_CATEGORY_PRODUCT;
            } elseif ($actionName == self::CONFIGURATOR_INDEX_INDEX) {
                $pageType = self::PAGE_TYPE_FOR_CONFIGURATOR_INDEX_INDEX;
            } elseif ($actionName == self::HOME_PAGE) {
                $pageType = self::PAGE_TYPE_FOR_HOME_PAGE;
            } elseif ($actionName == self::CART_PAGE) {
                $pageType = self::PAGE_TYPE_FOR_CART_PAGE;
            } elseif ($actionName == self::CMS_PAGE_VIEW && ($currentPageIndentifier == self::COUPONS_DEALS ||
                $currentPageLayout == 'sitemap-template-full-width')) {
                $pageType = self::PAGE_TYPE_FOR_COUPONS_DEALS;
            } elseif ($actionName == self::CMS_PAGE_VIEW && $currentPageIndentifier == self::MARKETPLACE_INFORMATION) {
                $pageType = self::PAGE_TYPE_FOR_MARKETPLACE_INFORMATION;
            } elseif ($actionName == self::CMS_PAGE_VIEW && $currentPageLayout == 'faq-template-full-width') {
                $pageType = self::PAGE_TYPE_FOR_FAQ_PAGE;
            } elseif ($actionName == self::CHECKOUT_PAGE || $actionName == self::SUCCESS_PAGE) {
                $pageType = self::PAGE_TYPE_FOR_CHECKOUT_PAGE;
            } elseif ($this->isProfilePages()) {
                $pageType = self::PAGE_TYPE_FOR_PROFILE;
            }

            if ($this->searchPage->isSearchPage()) {
                $pageType = $this->searchPage->getType();
            } elseif ($this->notFoundPage->isCurrentPage()) {
                $pageType = $this->notFoundPage->getType();
            } elseif ($this->iframePage->isCurrentPage()) {
                $pageType = $this->iframePage->getType();
            }

        return $pageType;
    }

    /**
     * Get current page indentifier
     *
     * @return string
     */
    public function getCurrentPageIdentifier()
    {
        return $this->cmsPage->getIdentifier();
    }

    /**
     * Get current page layout
     *
     * @return string
     */
    public function getCurrentPageLayout()
    {
        return $this->cmsPage->getPageLayout();
    }
}
