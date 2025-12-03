<?php

declare(strict_types=1);

namespace Fedex\CSP\Model\Collector;

use Fedex\CSP\Model\CspManagement;
use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Store\Model\StoreManagerInterface;

class ConfigWhitelistCollector implements PolicyCollectorInterface
{
    /**
     * @param CspManagement $cspManagement
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected CspManagement $cspManagement,
        protected StoreManagerInterface $storeManager
    )
    {
    }

    /**
     * @param array $defaultPolicies
     * @return array|PolicyInterface[]
     */
    public function collect(array $defaultPolicies = []): array
    {
        $storeId = $this->currentStoreId();
        if ($this->cspManagement->isCspWhitelistEnabled()
            && $whitelistUrls = $this->cspManagement->getCurrentEntriesValueUnserialized($storeId)) {
            $finalPolicies = [];
            foreach ($whitelistUrls as $whitelistUrlData) {
                $entry = $whitelistUrlData['entry'];
                $policies = $whitelistUrlData['policies'] ?? [];
                foreach ($policies as $policy) {
                    $finalPolicies[$policy][] = trim($entry);
                }
            }
            foreach ($finalPolicies as $policy => $wlUrls) {
                $defaultPolicies[] = $this->buildPolicies($policy, $wlUrls);
            }
        }

        return $defaultPolicies;
    }

    /**
     * @param $policy
     * @param $wlUrlArray
     * @return FetchPolicy
     */
    protected function buildPolicies($policy, $wlUrlsArray): FetchPolicy
    {
        return new FetchPolicy(
            $policy,
            false,
            $wlUrlsArray,
            [],
            false,
            false,
            false,
            [],
            [],
            false,
            false
        );
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function currentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
