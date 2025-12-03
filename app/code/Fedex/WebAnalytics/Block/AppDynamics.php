<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Block;

use Fedex\Company\Helper\Data;
use Fedex\WebAnalytics\Api\Data\AppDynamicsConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class AppDynamics extends Template
{
    /**
     * @param Template\Context $context
     * @param Data $companyHelper
     * @param AppDynamicsConfigInterface $appDynamicsConfigInterface
     * @param SecureHtmlRenderer $secureHtmlRenderer
     * @param array $data
     */
    public function __construct(
        Template\Context           $context,
        protected Data                       $companyHelper,
        protected AppDynamicsConfigInterface $appDynamicsConfigInterface,
        private SecureHtmlRenderer         $secureHtmlRenderer,
        array                      $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function _toHtml()
    {
        $company = $this->companyHelper->getCustomerCompany();
        if($this->appDynamicsConfigInterface->isActive() || ($company && $company->getAppDynamics())) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * @return mixed
     */
    public function getScript()
    {
        $appDynamics = $this->appDynamicsConfigInterface->getScriptCode();

        if ($appDynamics) {

            $appDynamicsNonce = '';
            $appDynamicsNonce .= $this->getFirstScript($appDynamics);
            $appDynamicsNonce .= $this->getSecondScript($appDynamics);

            return $appDynamicsNonce;
        }

        return false;
    }

    /**
     * @param $appDynamicsScript
     * @return array|false
     */
    private function getFirstScript($appDynamicsScript)
    {
        $pattern = '/<script[^>]*>(.*?)<\/script>/s';
        preg_match($pattern, $appDynamicsScript, $matches);
        if (isset($matches[1])) {
            return $this->buildRenderTag(['type' => 'text/javascript'], $matches[1]);
        }

        return false;
    }

    /**
     * @param $appDynamicsScript
     * @return array|false
     */
    private function getSecondScript($appDynamicsScript)
    {
        $pattern = '/<script([^>]*)>(.*?)<\/script>/s';
        preg_match_all($pattern, $appDynamicsScript, $matches, PREG_SET_ORDER);
        if (isset($matches[1])) {
            $attributes_string = $matches[1][1];
            preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', $attributes_string, $attribute_matches, PREG_SET_ORDER);
            $attributes = ['type' => 'text/javascript'];
            foreach ($attribute_matches as $match) {
                $key = $match[1];
                $value = $match[2];
                if(strpos($value, 'http') === false && strpos($value, 'https') === false) {
                    $value = 'https:'.$value;
                }
                $attributes[$key] = $value;
            }

            return $this->buildRenderTag($attributes, ' ');
        }

        return false;
    }

    /**
     * @param $attributes
     * @param $content
     * @return string
     */
    private function buildRenderTag($attributes, $content)
    {
        return $this->secureHtmlRenderer->renderTag(
            'script',
            $attributes,
            $content,
            false
        );
    }
}
