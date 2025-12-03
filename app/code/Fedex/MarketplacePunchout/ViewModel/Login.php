<?php

declare(strict_types=1);

namespace Fedex\MarketplacePunchout\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\MarketplacePunchout\Model\FclLogin;
use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Fedex\SSO\ViewModel\SsoConfiguration;

class Login implements ArgumentInterface
{
    const REQUEST_URL = 'marketplacepunchout/index/pkce';

    /**
     * @param FclLogin $fclLogin
     * @param Marketplace $marketplaceConfig
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        private FclLogin         $fclLogin,
        private Marketplace      $marketplaceConfig,
        private SsoConfiguration $ssoConfiguration
    )
    {
    }

    /**
     * Get custom data
     *
     * @return array
     */
    public function getCustomData(): array
    {
        return $this->fclLogin->getData();
    }

    public function getShopInfo(string $productSku): array
    {
        $shopCustomAttributes = $this->marketplaceConfig->getShopCustomAttributesByProductSku($productSku);
        $punchoutFlowEnhancement = false;
        if (isset($shopCustomAttributes['punchout-flow-enhancement'])) {
            $punchoutFlowEnhancement = $shopCustomAttributes['punchout-flow-enhancement'] === 'true';
        }
        return [
            'punchout_enable' => $punchoutFlowEnhancement,
            'punchout_url' => $this->ssoConfiguration->getHomeUrl() . SELF::REQUEST_URL
        ];
    }
}