<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\Login\App\Request;

use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Magento\Framework\App\Request\Http as HttpCore;

class Http extends HttpCore
{
    /**
     * Remove Url Extension Value from URL
     *
     * @return string
     */
    public function getPathInfo()
    {
        if (empty($this->pathInfo)) {
            $isCommercial = str_contains($_SERVER['REQUEST_URI']?? '', '/ondemand');
            if ($isCommercial && $urlExtension = $this->checkIfCustomerLogin()) {
                $this->pathInfo = str_replace($urlExtension."/", "", $this->getOriginalPathInfoCache());

                $this->pathInfo = str_replace("/".$urlExtension."/", "", $this->getOriginalPathInfoCache());

            } else {
                $this->pathInfo = $this->getOriginalPathInfoCache();
            }
        }
        return (string) $this->pathInfo;
    }

    private function getOriginalPathInfoCache(): string
    {
        static $return = null;
        if ($return !== null
            && $this->isPerformanceImprovementPhaseTwoConfigActive()
        ) {
            return $return;
        }
        return $return = $this->getOriginalPathInfo();
    }

    /**
     * Check if commercial customer and url extension if exists
     *
     * @return string
     */
    public function checkIfCustomerLogin()
    {
        static $return = null;
        if ($return !== null
            && $this->isPerformanceImprovementPhaseTwoConfigActive()
        ) {
            return $return;
        }
        $urlExtension = false;
        $cookieManager = $this->objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        if ($cookieManager->getCookie('url_extension')) {
            $urlExtension = $cookieManager->getCookie('url_extension');
        }
        $return = $urlExtension;
        return $return;
    }

    /**
     * You have to use ObjectManager inside this Class
     * If you use regular Design Patterns it will get a loop
     *
     * @return bool
     */
    private function isPerformanceImprovementPhaseTwoConfigActive(): bool
    {
        $obj = $this->objectManager->get(
            PerformanceImprovementPhaseTwoConfig::class
        );
        return  $obj && method_exists($obj, 'isActive') ? $obj->isActive() : true;
    }
}
