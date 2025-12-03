<?php
/**
 * @category    Fedex
 * @package     Fedex_CloudDriveIntegration
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CloudDriveIntegration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
       const XML_CONFIG_PATH_GENERAL_ENABLED = 'fedex/cloud_drive_integration/enabled';
       const XML_CONFIG_PATH_BOX_ENABLED = 'fedex/cloud_drive_integration/box_enabled';
       const XML_CONFIG_PATH_DROPBOX_ENABLED = 'fedex/cloud_drive_integration/dropbox_enabled';
       const XML_CONFIG_PATH_GOOGLE_ENABLED = 'fedex/cloud_drive_integration/google_enabled';
       const XML_CONFIG_PATH_MICROSOFT_ENABLE = 'fedex/cloud_drive_integration/microsoft_enabled';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return mixed
     */
   public function isEnabled()
   {
     return $this->scopeConfig->getValue(self::XML_CONFIG_PATH_GENERAL_ENABLED,ScopeInterface::SCOPE_STORE);
   }

    /**
     * @return mixed
     */
   public function isBoxEnabled()
   {
     return $this->scopeConfig->getValue(self::XML_CONFIG_PATH_BOX_ENABLED,ScopeInterface::SCOPE_STORE);
   }

    /**
     * @return mixed
     */
   public function isDropboxEnabled()
   {
     return $this->scopeConfig->getValue(self::XML_CONFIG_PATH_DROPBOX_ENABLED,ScopeInterface::SCOPE_STORE);
   }

    /**
     * @return mixed
     */
   public function isGoogleEnabled()
   {
     return $this->scopeConfig->getValue(self::XML_CONFIG_PATH_GOOGLE_ENABLED,ScopeInterface::SCOPE_STORE);
   }

    /**
     * @return mixed
     */
   public function isMicrosoftEnabled()
   {
     return $this->scopeConfig->getValue(self::XML_CONFIG_PATH_MICROSOFT_ENABLE,ScopeInterface::SCOPE_STORE);
   }
}
