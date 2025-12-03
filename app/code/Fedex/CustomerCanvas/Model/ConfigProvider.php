<?php
declare(strict_types=1);
namespace Fedex\CustomerCanvas\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Session\SessionManagerInterface;

class ConfigProvider
{
   public const XML_PATH_TOGGLE = 'tiger_E_478196_dye_sub_pod_2_updates';
   public const XML_PATH_TOGGLE_E478196 = 'tiger_E478196';
   public const EXCLUDED_PRODUCTS = 'ondemand_setting/product_creation_restriction/excluded_product_list_for_customer_admin_catalog_creation';
   private const XML_PATH_RETENTION_PERIOD = 'fedex/customer_canvas_api/retention_period_for_dye_sub';
   private const XML_PATH_DYESUB_OWNER_UPDATE = 'tiger_dyesub_doc_owner_update_b2733283';


    /**
     * @param ToggleConfig $toggleConfig
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig,
        private readonly SessionManagerInterface $sessionManager
    ) {
    }

    /**
     * @return bool
     */
    public function isDyeSubEnabled(): bool
    {
      return (bool) $this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE);
    }
    /**
     * @return bool
     */
    public function isDyeSubEditEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE_E478196);
    }

    /**
     * @return bool
     */
    public function isDyeSubOwnerUpdate(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::XML_PATH_DYESUB_OWNER_UPDATE);
    }

    /**
     * @return string|null
     */
    public function getExcludedProductsForCustomerAdmin(): string|null
    {
        return $this->toggleConfig->getToggleConfig(self::EXCLUDED_PRODUCTS);
    }

    /**
     * @return string
     */
    public function getRetentionPeriod(): string
    {
        return $this->toggleConfig->getToggleConfig(self::XML_PATH_RETENTION_PERIOD);
    }

    /**
     * @param $productCollection
     * @return mixed
     */
    public function excludeConfigAndDyesubProductCollection($productCollection){
        $excludeProducts = $this->getExcludedProductsForCustomerAdmin();

        if (!empty($excludeProducts)) {
            $productsArray = array_filter(array_map('trim', explode(',', $excludeProducts)));
            if (!empty($productsArray)) {
                $productCollection->addFieldToFilter('sku', ['nin' => $productsArray]);
            }
        }

        //To exculde dyesub Product For All admin user
        $productCollection->addAttributeToFilter([
            ['attribute' => 'is_customer_canvas', 'neq' => 1],
            ['attribute' => 'is_customer_canvas', 'null' => true],
        ], null, 'left');

        return $productCollection;
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        try {
            return $this->sessionManager->getSessionId() ?? 'no-session';
        } catch (\Throwable $e) {
            return 'no-session';
        }
    }

}
