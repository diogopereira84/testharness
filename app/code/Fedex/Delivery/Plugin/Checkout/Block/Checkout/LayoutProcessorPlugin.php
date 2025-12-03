<?php
namespace Fedex\Delivery\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\ObjectManager;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;

class LayoutProcessorPlugin
{
    /**
     * @var GetLoggedAsCustomerAdminIdInterface
     */
    protected $getLoggedAsCustomerAdminId;

    /**
     * ConfigProvider Constructor
     *
     * @param ToggleConfig $toggleConfig
     * @param PackagingCheckoutPricing $packagingCheckoutPricing
     * @param GetLoggedAsCustomerAdminIdInterface|null $getLoggedAsCustomerAdminId
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        private PackagingCheckoutPricing $packagingCheckoutPricing,
        ?GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId = null
    ){
        $this->getLoggedAsCustomerAdminId = $getLoggedAsCustomerAdminId
            ?? ObjectManager::getInstance()->get(GetLoggedAsCustomerAdminIdInterface::class);
    }

    /**
     * After plugin for process method in LayoutProcessor class.
     *
     * @param LayoutProcessor $subject
     * @param array $result
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(LayoutProcessor $subject, array $result, $jsLayout)
    {
        $adminId = $this->getLoggedAsCustomerAdminId->execute();
        $impersonatorIsEnabled =  $this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator');

        if($impersonatorIsEnabled && $adminId) {
            $result['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['firstname']['value'] = "";
            $result['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['lastname']['value'] = "";
        }

        // Show Loading Dock block in Checkout if any item is Freight Shipping
        $freightItems = $this->packagingCheckoutPricing->getPackagingItems(true);
        if ($freightItems) {
            foreach ($freightItems as $item) {
                if (isset($item[0]['packaging']['type']) && $item[0]['packaging']['type'] == 'pallet') {
                    $result['components']['checkout']['children']['steps']
                    ['children']['shipping-step']['children']['shippingAddress']['children']
                    ['before-shipping-form-submit'] = $this->getLayoutConfig();
                    break;
                }
            }
        }

        return $result;
    }
    private function getLayoutConfig(): array
    {
        return [
            'component' => 'uiComponent',
            'displayArea' => 'before-shipping-form-submit',
            'children' => [
                'shipping-freight' => [
                    'component' => 'Fedex_MarketplaceCheckout/js/view/checkout/shipping-freight',
                    'deps' => 'checkout.steps.shipping-step.shippingAddress',
                ]
            ]
        ];
    }
}
