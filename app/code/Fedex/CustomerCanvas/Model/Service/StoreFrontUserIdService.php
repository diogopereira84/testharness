<?php

declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\CustomerFactory;
use Fedex\CustomerCanvas\Model\Service\CustomerCanvasRegistrationService;

class StoreFrontUserIdService
{
    const USER_TOKEN_KEY = 'customer_canvas_user_token';
    const USER_ID = 'customer_canvas_user_id';

    public function __construct(
        private readonly Session                     $customerSession,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly Random                      $random,
        private readonly LoggerInterface $logger,
        private readonly CustomerFactory $customerFactory,
        private readonly CustomerCanvasRegistrationService $registrationService
    ) {}

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getStoreFrontUserId(): string
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->generateUuid();
        }

        try {
            $customer = $this->getCustomerFromSession();
            $uuid = $this->getOrCreateCanvasUuid($customer);
            return $uuid;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .': Error generating storefront user ID: ' . $e->getMessage());
            return $this->generateUuid();
        }
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerFromSession()
    {
        $customerId = (int) $this->customerSession->getCustomerId();
        return $this->customerRepository->getById($customerId);
    }

    /**
     * @param $customer
     * @return string
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function getOrCreateCanvasUuid($customer): string
    {
        $attribute = $customer->getCustomAttribute('customer_canvas_uuid');
        if ($attribute && $attribute->getValue()) {
            return (string) $attribute->getValue();
        }

        $uuid = $this->generateUuid();
        $userId = $this->registrationService->registerUser($uuid);
        if($userId != null){
            $customerModel = $this->customerFactory->create()->load($customer->getId());
            $customerModel->setData('customer_canvas_uuid', $uuid);
            $customerModel->save();
            return $uuid;
        }
        return '';
    }

    /**
     * Generate a UUID-like random string
     * @throws LocalizedException
     */
    private function generateUuid(): string
    {
        return $this->random->getRandomString(32);
    }

    /**
     * @param string $token
     * @param string $userId
     * @return void
     */
    public function saveTokenInSession(string $token, string $userId): void
    {
        $storage = $this->customerSession;
        $storage->setData(self::USER_TOKEN_KEY, $token);
        $storage->setData(self::USER_ID, $userId);
        $storage->setData(self::USER_TOKEN_KEY . '_timestamp',time());
    }

    /**
     * @return string[]
     */
    public function getUserTokenFromSession($merge=false): array
    {
            $storage = $this->customerSession;
            $timestamp = $storage->getData(self::USER_TOKEN_KEY . '_timestamp');
            $isExpired = ($timestamp === 0 || time() - $timestamp > 59 * 60);

        if($isExpired) {
            $token = '';
            $userId = (string) ($storage->getData(self::USER_ID) ?? '');
        }else{
            $token = (string) ($storage->getData(self::USER_TOKEN_KEY) ?? '');
            $userId = (string) ($storage->getData(self::USER_ID) ?? '');
         }


        return [
            'token'  => $token,
            'userId' => $userId
        ];
    }

    /**
     * @param string $loggedInUserId
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerById(string $loggedInUserId)
    {
        return $this->customerRepository->getById($loggedInUserId);
    }

}
