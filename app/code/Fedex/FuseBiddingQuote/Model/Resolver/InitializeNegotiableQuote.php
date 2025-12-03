<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Fedex\Delivery\Helper\QuoteDataHelper;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Magento\NegotiableQuote\Model\PurgedContentFactory;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;

/**
 * InitializeNegotiableQuote class graphql API
 */
class InitializeNegotiableQuote implements ResolverInterface
{
    /**
     * @var mixed
     */
    public $moduleDataSetup;
    private const QUOTE_CREATION = 'quoteCreation';
    private const DEFAULT_CUSTOMER_NAME = 'Fuse Bidding';
    private const DEFAULT_EMAIL = 'v8JkL3r9zPqW7aX2yFnT@rtst.com';

    /**
     * InitializeNegotiableQuote constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param SerializerInterface $serializer
     * @param GraphqlApiHelper $graphqlApiHelper
     * @param LoggerInterface $logger
     * @param QuoteDataHelper $quoteDataHelper
     * @param FuseBidGraphqlHelper $fuseBidGraphqlHelper
     * @param PurgedContentFactory $purgedContentFactory
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerFactory $customer
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param FuseBidHelper $fuseBidHelper
     */
    public function __construct(
        protected CartRepositoryInterface $quoteRepository,
        protected SerializerInterface $serializer,
        protected GraphqlApiHelper $graphqlApiHelper,
        private LoggerInterface $logger,
        protected QuoteDataHelper $quoteDataHelper,
        protected FuseBidGraphqlHelper $fuseBidGraphqlHelper,
        private PurgedContentFactory $purgedContentFactory,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private CustomerRepositoryInterface $customerRepository,
        protected CustomerFactory $customer,
        protected CustomerInterfaceFactory $customerInterfaceFactory,
        protected FuseBidHelper $fuseBidHelper
    ) {
    }

    /**
     * Resolve method for GraphQL.
     *
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!$this->fuseBidGraphqlHelper->validateToggleConfig()) {
            throw new GraphQlInputException(__("Fuse Bidding toggle is not enabled"));
        }
        $this->fuseBidGraphqlHelper->validateCartUid($args);
        $quoteId = $this->fuseBidGraphqlHelper->getQuoteIdFromArgs($args['uid']);
        $quote = $this->quoteRepository->get($quoteId);

        return $this->processQuote($quote, $quoteId, $args);
    }

    /**
     * Process the negotiable quote.
     *
     * @param Obj $quote
     * @param int $quoteId
     * @param array|null $args
     * @return array|null
     * @throws GraphQlInputException
     */
    private function processQuote($quote, $quoteId, array $args = null)
    {
        try {
            if (empty($quote->getQuoteMgntLocationCode())) {
                $this->saveLocationCode($quote);
            }
            if (!$this->fuseBidGraphqlHelper->isNegotiableQuote($quote)) {
                $this->processCustomerForQuote($quoteId, $quote);
                $quote = $this->quoteRepository->get($quoteId);
                $this->createNegotiableQuote($quote, $quoteId);
            }

            if ($this->fuseBidHelper->isToggleTeamMemberInfoEnabled() && isset($args['comment'])) {
                $commentText = $args['comment'];
                $this->fuseBidGraphqlHelper->validateComment($commentText);
                $this->fuseBidGraphqlHelper->saveNegotiableQuoteComment($quoteId, $commentText);
            }

            return $this->prepareQuoteResponse($quote, $quoteId);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }

     /**
      * Process Customer for Quote
      *
      * @param int $quoteId
      * @param Quote $quote
      * @return void
      */
    public function processCustomerForQuote($quoteId, $quote)
    {
        $retailCustomerId = $this->getRetailCustomerId($quoteId);
        if ($retailCustomerId) {
             $customer = $this->doesRetailCustomerExistInCustomerTable($retailCustomerId);
            if ($customer) {
                $this->fuseBidGraphqlHelper->updateQuoteWithCustomerInfo($quoteId, $customer);
            } else {
                $this->createCustomerFromRetailCustomerId($retailCustomerId, $quote);
            }
        } else {
            $customer=$this->createDummyCustomer();
            $this->fuseBidGraphqlHelper->updateDummyCustomerInQuote($quoteId, $customer);

        }
    }

    /**
     * Step 1: Get Retail Customer ID from Quote Integration Table
     *
     * @param int $quoteId
     * @return int|null
     */
    private function getRetailCustomerId($quoteId)
    {
        try {
            $integration = $this->cartIntegrationRepository->getByQuoteId($quoteId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                'Error in Fetching Quote Integration: ' . $e->getMessage()
            );
            return null;
        }
        return $integration ? $integration->getRetailCustomerId() : null;
    }

    /**
     * Check if Retail Customer exists in the customer_entity table
     *
     * @param int $retailCustomerId
     * @return mixed
     */
    private function doesRetailCustomerExistInCustomerTable($retailCustomerId)
    {
        try {
            $customer = $this->fuseBidGraphqlHelper->getCustomerByRetailCustomerId($retailCustomerId);
            return $customer;
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * Step 3: Create a new customer using retail_customer_id
     *
     * @param int $retailCustomerId
     * @param Quote $quote
     * @return mixed
     */
    public function createCustomerFromRetailCustomerId($retailCustomerId, $quote)
    {
        $email = $quote->getCustomerEmail();
        try {
            $customer = $this->customerRepository->get($email);
            $customerWithRetailcustomerId = $this->doesRetailCustomerExistInCustomerTable($retailCustomerId);
            /* $customerWithRetailcustomerId this is false means
             new retail customer id is coming in request and not maching with email id
             associated email */
            if (!$customerWithRetailcustomerId && $customer) {
                if ($this->fuseBidGraphqlHelper->validateContactInfoFixConfig()) {
                        $customer = $this->customerInterfaceFactory->create();
                        $customer->setFirstname($quote->getCustomerFirstname());
                        $customer->setMiddlename($quote->getCustomerMiddlename());
                        $customer->setLastname($quote->getCustomerLastname());
                        $customer->setCustomAttribute('secondary_email', $email);
                        $customer->setCustomAttribute('retail_customer_id', $retailCustomerId);
                        $customer->setEmail($retailCustomerId.'@fedex.com');
                        $customer = $this->customerRepository->save($customer);
                } else {
                    throw new GraphQlInputException(
                        __('The provided Retail Customer ID does not match the existing one
                        associated with this email address')
                    );
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
            $customer = $this->customerInterfaceFactory->create();
            $customer->setFirstname($quote->getCustomerFirstname());
            $customer->setMiddlename($quote->getCustomerMiddlename());
            $customer->setLastname($quote->getCustomerLastname());
            $customer->setEmail($email);
            $customer = $this->customerRepository->save($customer);
        }
        $this->fuseBidGraphqlHelper->saveRetailCustomerId($customer->getId(), $retailCustomerId);
        $this->fuseBidGraphqlHelper->updateQuoteWithCustomerInfo($quote->getId(), $customer);

        return $customer;
    }

    /**
     * Save Retail Customer Id in customer table
     *
     * @param Int $customerId
     * @param String $retailCustomerId
     * @return void
     */
    public function saveRetailCustomerId($customerId, $retailCustomerId)
    {
        $customerEntityTable = $this->moduleDataSetup->getTable('customer_entity');
        $this->moduleDataSetup->getConnection()->update(
            $customerEntityTable,
            ['retail_customer_id' => $retailCustomerId],
            ['entity_id = ?' => $customerId]
        );
        $this->moduleDataSetup->endSetup();
    }

    /**
     * Create a dummy customer if no retail_customer_id exists
     *
     * @return CustomerInterface
     */
    private function createDummyCustomer()
    {
        $uniqueString = uniqid();
        $customerData = [
            'firstname' => 'Null',
            'lastname' => 'Null',
            'email' => 'null.null+' . $uniqueString . '@null.com'
        ];
        $customer = $this->customer->create();
        $customer->setFirstname($customerData['firstname']);
        $customer->setLastname($customerData['lastname']);
        $customer->setEmail($customerData['email']);
        $customer ->save($customer);
        return $customer;
    }

    /**
     * Create a negotiable quote.
     *
     * @param Obj $quote
     * @param int $quoteId
     * @return void
     */
    private function createNegotiableQuote($quote, $quoteId)
    {
        $requestData = $this->getNegotiableQuoteData($quoteId);
        $this->quoteDataHelper->createNegotiableQuote($quote, $this->quoteRepository, $requestData, [], false, true);
        $purgedData = [
            'customer_name' => self::DEFAULT_CUSTOMER_NAME,
            'entity_id' => 0,
            'company_name' => self::DEFAULT_CUSTOMER_NAME,
            'email' => self::DEFAULT_EMAIL
        ];
        $purgedEncodedData = $this->serializer->serialize($purgedData);
        $purgedModel = $this->purgedContentFactory->create();
        $purgedModel->setQuoteId($quoteId);
        $purgedModel->setPurgedData($purgedEncodedData);
        $purgedModel->save();
    }

    /**
     * Prepare the response for the negotiable quote.
     *
     * @param Obj $quote
     * @param int $quoteId
     * @return array
     */
    private function prepareQuoteResponse($quote, $quoteId)
    {
        $quoteInfo = $this->graphqlApiHelper->getQuoteInfo($quote);
        $rateResponse = $this->graphqlApiHelper->getRateResponse($quote);

        return [
            'quote_id' => $quoteInfo['quote_id'],
            'quote_status' => $quoteInfo['quote_status'],
            'is_bid' => $quote->getIsBid(),
            'hub_centre_id' => $quoteInfo['hub_centre_id'],
            'location_id' => $quoteInfo['location_id'],
            'quote_creation_date' => $quoteInfo['quote_creation_date'],
            'quote_updated_date' => $quoteInfo['quote_updated_date'],
            'quote_submitted_date' => $quoteInfo['quote_submitted_date'],
            'quote_expiration_date' => $quoteInfo['quote_expiration_date'],
            'contact_info' => $this->graphqlApiHelper->getQuoteContactInfo($quote),
            'rateSummary' => $this->graphqlApiHelper->getRateSummaryData($rateResponse),
            'line_items' => $this->graphqlApiHelper->getQuoteLineItems($quote, $rateResponse),
            'fxo_print_account_number' => $this->graphqlApiHelper->getFxoAccountNumberOfQuote($quote),
            'activities' => $this->graphqlApiHelper->getQuoteNotes($quoteId),
            'lte_identifier' => $quote->getLteIdentifier() ?? null
        ];
    }

    /**
     * Get the data required to create a negotiable quote.
     *
     * @param int $quoteId
     * @return array
     */
    public function getNegotiableQuoteData($quoteId)
    {
        return [
            self::QUOTE_CREATION => [
                'quoteId' => $quoteId,
                'quoteName' => 'FUSE bidding Quote Creation',
                'comment' => 'Review my quote',
            ]
        ];
    }

    /**
     * Save location_code in quote table
     *
     * @param obj $quote
     * @return void
     */
    public function saveLocationCode($quote)
    {
        $integration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());
        $quote->setQuoteMgntLocationCode($integration->getStoreId());
        $quote->setIsBid(1);
        $this->quoteRepository->save($quote);
    }
}
