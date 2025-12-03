<?php
declare(strict_types=1);

namespace Fedex\CSP\Model\Collector\CspWhitelistXml;

use Fedex\CSP\Api\CspManagementInterface;

class FileResolver
{
    /**
     * @param CspManagementInterface $cspManagement
     */
    public function __construct(
        private CspManagementInterface $cspManagement
    )
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param \Magento\Csp\Model\Collector\CspWhitelistXml\FileResolver $subject
     * @param $result
     * @return array
     */
    public function afterGet(\Magento\Csp\Model\Collector\CspWhitelistXml\FileResolver $subject, $result)//NOSONAR
    {
        if($this->cspManagement->isCspWhitelistEnabled()) {
            $configs = [];
            foreach ($result as $key => $config) {
                if (!str_contains($key, 'FedexCsp/etc/csp_whitelist.xml')) {
                    $configs[$key] = $config;
                }
            }
            return $configs;
        }

        return $result;
    }
}
