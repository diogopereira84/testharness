<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Controller\Order;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Controller\Order\History as BaseHistory;
use Fedex\Ondemand\Model\Config;

class History
{
    /**
     * B-2107362 SGC Tab Name Updates Toggle Xpath
     */
    protected const SGC_TAB_NAME_UPDATES = 'sgc_b_2107362';

    /**
     * Initialize dependencies.
     *
     * @param ToggleConfig $toggleConfig
     * @param Config $config
     */
    public function __construct(
        public ToggleConfig $toggleConfig,
        public Config $config
    ) {
    }

    /**
     * Update Tab Name for Order History Page
     *
     * @param BaseHistory $subject
     * @param ResultInterface $result
     *
     * @return ResultInterface
     */
    public function afterExecute(BaseHistory $subject, ResultInterface $result)
    {
        $isUpdateTabNameToggleEnabled = (bool) $this->toggleConfig->getToggleConfigValue(self::SGC_TAB_NAME_UPDATES);

        if ($isUpdateTabNameToggleEnabled) {
            $tabNameTitle = $this->config->getMyAccountTabNameValue();
            $result->getConfig()->getTitle()->set(__($tabNameTitle));

            $pageMainTitle = $result->getLayout()->getBlock('page.main.title');
            if ($pageMainTitle) {
                $pageNameTitle = $this->config->getOrdersTabNameValue();
                $pageMainTitle->setPageTitle($pageNameTitle);
            }
        }

        return $result;
    }
}
