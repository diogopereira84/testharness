<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source;

use Fedex\SubmitOrderSidebar\Api\Data\DataSourceInterface;
use Fedex\SubmitOrderSidebar\Api\Data\UnifiedDataLayerInterface;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\Customer\CustomerType;
use Magento\Customer\Model\Session;
use Fedex\Base\Helper\Auth as AuthHelper;

class CustomerDataSource implements DataSourceInterface
{
    /**
     * @param Session $customerSession
     * @param AuthHelper $authHelper
     */
    public function __construct(
        private readonly Session $customerSession,
        protected AuthHelper $authHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function map(UnifiedDataLayerInterface $unifiedDataLayer, array $checkoutData = []): void
    {
        $customerName = '';
        $email = '';
        if (isset($checkoutData[0])) {
            $checkoutData = json_decode($checkoutData[0], true);
            $customerName = $checkoutData['output']["checkout"]["contact"]["personName"]["firstName"]
                . ' '. $checkoutData['output']["checkout"]["contact"]["personName"]["lastName"];
            $email = $checkoutData["output"]["checkout"]["contact"]["emailDetail"]["emailAddress"];
        }
        $customerType = $this->authHelper->isLoggedIn() ? CustomerType::LOGGED_IN : CustomerType::GUEST;
        $unifiedDataLayer->setCustomerName($customerName);
        $unifiedDataLayer->setCustomerType($customerType->value);
        $unifiedDataLayer->setCustomerEmail($email);
        $unifiedDataLayer->setCustomerSessionId($this->customerSession->getSessionId());
    }
}
