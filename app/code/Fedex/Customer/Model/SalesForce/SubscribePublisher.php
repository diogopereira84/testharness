<?php
declare(strict_types=1);

namespace Fedex\Customer\Model\SalesForce;

use Fedex\Customer\Api\Data\SalesForceCustomerSubscriberInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class SubscribePublisher
{
    const SALESFORCE_CUSTOMER_SUBSCRIBE_TOPIC = 'salesforce.customer.subscribe';

    /**
     * @param PublisherInterface $publisher
     * @param SalesForceCustomerSubscriberInterface $salesForceCustomerSubscriberInterface
     * @param LoggerInterface $logger
     * @param Json $json
     */
    public function __construct(
        private PublisherInterface $publisher,
        private SalesForceCustomerSubscriberInterface $salesForceCustomerSubscriberInterface,
        private LoggerInterface $logger,
        private Json $json
    )
    {
    }

    /**
     * @param array $subscriberData
     * @return void
     */
    public function execute(array $subscriberData)
    {
        $this->salesForceCustomerSubscriberInterface->setFirstName($subscriberData['firstName'] ?? '');
        $this->salesForceCustomerSubscriberInterface->setLastName($subscriberData['lastName'] ?? '');
        $this->salesForceCustomerSubscriberInterface->setEmailAddress($subscriberData['emailAddress'] ?? '');
        $this->salesForceCustomerSubscriberInterface->setCompanyName($subscriberData['companyName'] ?? '');
        $this->salesForceCustomerSubscriberInterface->setLanguageCode($subscriberData['languageCode'] ?? '');
        $this->salesForceCustomerSubscriberInterface->setPostalCode($subscriberData['postalCode'] ?? '');
        $this->salesForceCustomerSubscriberInterface->setCityName($subscriberData['cityName'] ?? '');
        $this->salesForceCustomerSubscriberInterface->setStateProvince($subscriberData['stateProvince'] ?? '');
        $this->salesForceCustomerSubscriberInterface->setStreetAddress($subscriberData['streetAddress'] ?? '');
        $this->salesForceCustomerSubscriberInterface->setCountryCode($subscriberData['countryCode'] ?? '');

        $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' SalesForce Subscribe Publish => ' . $this->json->serialize($subscriberData));

        $this->publisher->publish(
            self::SALESFORCE_CUSTOMER_SUBSCRIBE_TOPIC,
            $this->salesForceCustomerSubscriberInterface
        );
    }
}
