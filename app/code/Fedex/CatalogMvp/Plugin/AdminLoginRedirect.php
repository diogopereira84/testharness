<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Manish Chaubey
 * @email      manish.chaubey.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Backend\Model\Url;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Stdlib\CookieManagerInterface;

class AdminLoginRedirect
{
    private const REQUEST_URL = 'requestUrl';

    /**
     * Construct method
     *
     * @param RedirectInterface $redirect
     * @param ToggleConfig $toggleConfig
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        readonly private RedirectInterface $redirect,
        readonly private ToggleConfig $toggleConfig,
        private readonly CookieManagerInterface $cookieManager
    )
    {

    }

    /**
     * After get startup page admin dashboard redirect
     *
     * @param Url $subject
     * @param $result
     * @return string
     */
    public function afterGetStartupPageUrl(
        Url $subject,
        $result
    ) : string {
        $isNonStandardCatalogToggleEnable = $this->toggleConfig->getToggleConfigValue(
            'explorers_non_standard_catalog'
        );
        $refererUrl = $this->redirect->getRefererUrl();
        $requestUrl =  $this->cookieManager->getCookie(
            self::REQUEST_URL
        );
        $cookieEmail = $this->cookieManager->getCookie('email');
        if ($isNonStandardCatalogToggleEnable && isset(parse_url($refererUrl)['query'])) {
            parse_str(parse_url($refererUrl)['query'], $params);
            if (isset($params['email']) && $params['email'] == 1) {
                return $refererUrl;
            }
        }
        // Login from okta redirect
        if ($isNonStandardCatalogToggleEnable && (int)$cookieEmail == 1) {
            return (string)$requestUrl;
        }
        return $result;
    }
}
