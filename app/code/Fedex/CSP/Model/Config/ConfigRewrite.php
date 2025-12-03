<?php

declare(strict_types=1);

namespace Fedex\CSP\Model\Config;

use Fedex\CSP\Model\CspManagement;
use Magento\Framework\App\RequestInterface;

class ConfigRewrite
{
    /**
     * @param RequestInterface $request
     * @param CspManagement $cspManagement
     */
    public function __construct(
        protected RequestInterface $request,
        protected CspManagement $cspManagement
    )
    {
    }
    /**
     * @param \Magento\Config\Model\Config $subject
     * @return void
     */
    public function beforeSave(\Magento\Config\Model\Config $subject): void
    {
        $store = $this->request->getParam('store');
        $data = $subject->getData();
        $groupCspWhitelist = $data['groups']['csp_whitelist'] ?? [];
        if ($store
            && !empty($groupCspWhitelist)
            && !empty($groupCspWhitelist['fields']['entries_list'])
            && !empty($groupCspWhitelist['fields']['entries_list']['inherit'])
        ) {
            $data['groups']['csp_whitelist']['fields']['entries']['inherit'] = '1';
            $subject->setData($data);
        }
    }
}
