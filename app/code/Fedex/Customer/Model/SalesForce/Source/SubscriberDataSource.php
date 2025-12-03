<?php
/**
 * @category  Fedex
 * @package   Fedex_Customer
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Customer\Model\SalesForce\Source;

use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Fedex\Customer\Api\Data\DataSourceInterface;

class SubscriberDataSource implements DataSourceInterface
{

    /**
     * @inheritDoc
     */
    public function map(SalesForceResponseInterface $salesForceResponseInterface, array $subscriberData = []): void
    {
        $salesForceResponseInterface->setStatus(
            $subscriberData['status'] ?? ''
        );
        $salesForceResponseInterface->setErrorMessage(
            $subscriberData['errorMessage'] ?? ''
        );
        $salesForceResponseInterface->setSubscriberResponse(
            (bool)$subscriberData['subscriberResponse'] ?? false
        );
        $salesForceResponseInterface->setEmailSendResponse(
            $subscriberData['emailSendResponse'] ?? ''
        );
        $salesForceResponseInterface->setFxoSubscriberResponse(
            (bool)$subscriberData['fxoSubscriberResponse'] ?? false
        );
    }
}
