<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\PageBuilderBanner\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    public const FOREGROUND_IMAGE_PATH='promobanner/foreground/';
    public const FOREGROUND_IMAGE_CONFIG_PATH='header_promo_banner/promobanner_group/foreground_image';
    public const FOREGROUND_LAPTOP_IMAGE_CONFIG_PATH='header_promo_banner/promobanner_group/foreground_laptop_image';
    public const FOREGROUND_TABLET_IMAGE_CONFIG_PATH='header_promo_banner/promobanner_group/foreground_tablet_image';

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context   $context,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }
    private function getForegroundImageConfigPath($configPath){
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $path
     * @return string
     * @throws NoSuchEntityException
     */
    private function getForeGroundImagePath($path): string
    {
        $logoConfigFilePath = $this->getForegroundImageConfigPath($path);
        if($logoConfigFilePath!=''){
            $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return ($mediaBaseUrl . self::FOREGROUND_IMAGE_PATH . $logoConfigFilePath);
        }
        return '';

    }

    /**
     * @param $name
     * @param $src
     * @param $node
     * @param $xpath
     * @return void
     *
     */
    private function getForegroundImages($name,$src,$node,$xpath){
        $foregroundImage = $xpath->document->createElement(
            'img',
            $name
        );
        $foregroundImage->setAttribute('src', $src);
        $foregroundImage->setAttribute('name',$name);
        $foregroundImage->setAttribute('class','d-none foreground-img ' . $name);
        $node->parentNode->appendChild($foregroundImage);
    }
    /**
     * @param $node
     * @param $xpath
     * @param $result
     * @return void
     * @throws NoSuchEntityException
     */
    public function createForegroundImages($node,$xpath,$name): void
    {
        $pathConfig='';
            if($name=='foreground_desktop_image'){
                $pathConfig = self::FOREGROUND_IMAGE_CONFIG_PATH;
            }
            if($name=='foreground_desktop_medium_image'){
                $pathConfig = self::FOREGROUND_LAPTOP_IMAGE_CONFIG_PATH;
            }
            if($name=='foreground_mobile_medium_image'){
                $pathConfig = self::FOREGROUND_TABLET_IMAGE_CONFIG_PATH;
            }
            $path = $this->getForeGroundImagePath($pathConfig);
            if($path!=''){
                $this->getForegroundImages($name,$path,$node,$xpath);
            }
    }

   }
