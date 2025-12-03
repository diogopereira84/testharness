<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Registry;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Psr\Log\LoggerInterface;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\NegotiableQuote\Model\CommentFactory;

/**
 * FuseBidGraphqlHelper for graphql API's
 */
class FuseBidGraphqlHelper extends AbstractHelper
{
    /**
     * FuseBidGraphqlHelper Constructor
     *
     * @param Context $context
     * @param CustomerFactory $customer
     * @param CustomerRepositoryInterface $customerRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMask $quoteIdMaskResource
     * @param QuoteFactory $quoteFactory
     * @param Registry $registry
     * @param GetCartForUser $getCartForUser
     * @param LoggerInterface $logger
     * @param FuseBidViewModel $fuseBidViewModel
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CommentFactory $negotiableQuoteCommentFactory
     */
    public function __construct(
        Context $context,
        protected CustomerFactory $customer,
        private CustomerRepositoryInterface $customerRepository,
        protected CartRepositoryInterface $quoteRepository,
        private QuoteIdMask $quoteIdMaskResource,
        protected QuoteFactory $quoteFactory,
        protected Registry $registry,
        protected GetCartForUser $getCartForUser,
        private LoggerInterface $logger,
        private FuseBidViewModel $fuseBidViewModel,
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected CommentFactory $negotiableQuoteCommentFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Validate if the Fuse Bidding toggle is enabled.
     *
     * @throws GraphQlInputException
     */
    public function validateToggleConfig()
    {
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled()) {
            return true;
        }

        return false;
    }

    /**
     * Validate if the Contact information update fix.
     *
     * @throws GraphQlInputException
     */
    public function validateContactInfoFixConfig()
    {
        if ($this->fuseBidViewModel->isContactInfoFix()) {
            return true;
        }

        return false;
    }

    /**
     * Validate that the uid argument is provided.
     *
     * @param array $args
     * @throws GraphQlInputException
     */
    public function validateCartUid($args)
    {
        if (empty($args['uid'])) {
            throw new GraphQlInputException(__("uid value must be specified."));
        }
    }

    /**
     * Validate that the template argument is provided.
     *
     * @param array $args
     * @throws GraphQlInputException
     */
    public function validateTemplate($args)
    {
        if (empty($args['template'])) {
            throw new GraphQlInputException(__("template value must be specified."));
        }
    }

    /**
     * Update dummy CustomerInfo With Retail CustomerId
     *
     * @param array $args
     * @throws GraphQlInputException
     */
    public function updateCartAndCustomerForFuseBid($args)
    {
        $quoteId = $this->getQuoteIdFromArgs($args['cart_id']);
        $quote = $this->quoteRepository->get($quoteId);
        if ($quote->getIsBid() && $this->isNegotiableQuote($quote)) {
            $retailCustomerId = $args['contact_information']['retail_customer_id'];
            $existingCustomer = $this->getCustomerByRetailCustomerId($retailCustomerId);
            if ($existingCustomer) {
                $this->updateQuoteWithCustomerInfo($quoteId, $existingCustomer);
            } else {
                $customer = $this->updateDummyCustomerInfo($quoteId, $args);
                $this->updateQuoteWithCustomerInfo($quoteId, $customer);
            }
            return $quote;
        }
    }

    /**
     * Get the unmasked quote ID from the cart_uid argument.
     *
     * @param sting $quoteUid
     * @return int
     */
    public function getQuoteIdFromArgs($quoteUid)
    {
        return $this->quoteIdMaskResource->getUnmaskedQuoteId($quoteUid);
    }

    /**
     * Fetch customer by retail_customer_id custom field.
     *
     * @param int $retailCustomerId
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     * @throws NoSuchEntityException
     */
    public function getCustomerByRetailCustomerId($retailCustomerId)
    {
        $customerCollection = $this->customer->create()->getCollection();
        $customerCollection->getSelect()->where("retail_customer_id = ?", $retailCustomerId);
        $customer = $customerCollection->getFirstItem();
        if ($customer && $customer->getId()) {
            return $customer;
        } else {
            return false;
        }
    }

    /**
     * Update quote with customer information
     *
     * @param int $quoteId
     * @param obj $customer
     * @return void
     */
    public function updateQuoteWithCustomerInfo($quoteId, $customer)
    {
        $quote = $this->quoteFactory->create()->load($quoteId);
        $dummyCustomerId = $quote->getCustomerId();
        $customerObj = $this->customerRepository->getById($customer->getId());
        $secondaryEmailAttribute = $customerObj->getCustomAttribute('secondary_email');
        $customerEmail = $secondaryEmailAttribute ? $secondaryEmailAttribute->getValue() : $customer->getEmail();
        try {
            $id = $customerObj->getId();
            if ($id != $dummyCustomerId) {
                $quote->setCustomerFirstname($customer->getFirstname());
                $quote->setCustomerLastname($customer->getLastname());
                $quote->setCustomerEmail($customerEmail);
                $quote->setCustomerIsGuest(0);
                $quote->setCustomerId($id);
                $quote->setCustomerGroupId($customer->getGroupId());
                $this->quoteRepository->save($quote);
                $quote = $this->quoteFactory->create()->load($quoteId);
                $this->registry->register('isSecureArea', true);
                $customer = $this->customer->create()->load($dummyCustomerId);
                $customer->delete();
            }
        } catch (GraphQlInputException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Update dummy customer information
     *
     * @param int $quoteId
     * @param array $args
     * @return void
     */
    public function updateDummyCustomerInfo($quoteId, $args)
    {
        $quote = $this->quoteFactory->create()->load($quoteId);
        $dummyCustomerId = $quote->getCustomerId();
        $customer = $this->customerRepository->getById($dummyCustomerId);
        $customerData = [
            'retail_customer_id' => $args['contact_information']['retail_customer_id'],
            'firstname' => $args['contact_information']['firstname'],
            'lastname' => $args['contact_information']['lastname'],
            'secondary_email' => $args['contact_information']['email']
        ];
        if ($this->validateContactInfoFixConfig() && !empty($args['contact_information']['retail_customer_id'])) {
            $customerData['email'] = $args['contact_information']['retail_customer_id'].'@fedex.com';
        } else {
            $customerData['email'] = $args['contact_information']['email'];
        }
        $customer->setFirstname($customerData['firstname']);
        $customer->setLastname($customerData['lastname']);
        $customer->setEmail($customerData['email']);
        $customer->setCustomAttribute('secondary_email', $customerData['secondary_email']);
        $customer = $this->customerRepository->save($customer);
        $this->saveRetailCustomerId($customer->getId(), $args['contact_information']['retail_customer_id']);
        return $customer;
    }

    /**
     * Check if the quote is negotiable.
     *
     * @param Obj $quote
     * @return bool
     */
    public function isNegotiableQuote($quote)
    {
        $extensionAttributes = $quote->getExtensionAttributes();
        return $extensionAttributes && !empty($extensionAttributes->getNegotiableQuote()->getData());
    }

    /**
     * Update quote with dummy customer information
     *
     * @param int $quoteId
     * @param obj $customer
     * @return void
     */
    public function updateDummyCustomerInQuote($quoteId, $customer)
    {
        $quote = $this->quoteFactory->create()->load($quoteId);
        $customerObj = $this->customerRepository->getById($customer->getId());
        try {
            $id = $customerObj->getId();
            $quote->setCustomerFirstname($customer->getFirstname());
            $quote->setCustomerLastname($customer->getLastname());
            $quote->setCustomerEmail($customerObj->getEmail());
            $quote->setCustomerIsGuest(0);
            $quote->setCustomerId($id);
            $quote->setCustomerGroupId($customer->getGroupId());
            $this->quoteRepository->save($quote);
        } catch (GraphQlInputException $e) {
            $this->logger->error($e->getMessage());
        }
    }

     /**
      * Get Cart for Fuse Bid quote
      *
      * @param string $cart_id
      * @param int $storeId
      * @return void
      */
    public function getCartForBidQuote(string $cart_id, $storeId)
    {
        $quoteId = $this->getQuoteIdFromArgs($cart_id);
        $quote = $this->quoteFactory->create()->load($quoteId);
        if ($quote->getIsBid()) {
            $customerId = $quote->getCustomerId();
            return $this->getCartForUser->execute($cart_id, $customerId, $storeId);
        } else {
            return $this->getCartForUser->execute($cart_id, null, $storeId);
        }
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
     * Save comment in negotiable_quote_comment table.
     *
     * @param int $quoteId
     * @param string $commentText
     * @return void
     */
    public function saveNegotiableQuoteComment($quoteId, $commentText, $quoteType = 'quote_created')
    {
        $negotiableQuoteComment = $this->negotiableQuoteCommentFactory->create();
        $negotiableQuoteComment->setParentId($quoteId);
        $negotiableQuoteComment->setComment($commentText);
        $negotiableQuoteComment->setCreatorType(2);
        $negotiableQuoteComment->setCreatorId('2');
        $negotiableQuoteComment->setType($quoteType);
        $negotiableQuoteComment->save();
    }

    /**
     * Validate quote comment input
     *
     * @param string $comment
     * @return void
     */
    public function validateComment($comment)
    {
        if (empty($comment)) {
            throw new GraphQlInputException(__('comment cannot be empty'));
        }
    }
}
