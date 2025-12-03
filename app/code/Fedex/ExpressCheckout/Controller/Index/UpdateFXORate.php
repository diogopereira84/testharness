<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpressCheckout\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\CartFactory;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\App\RequestInterface;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Customer\Model\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class UpdateFXORate extends Action
{
    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var CartFactory $cartFactory
     */
    protected $cartFactory;

    /**
     * @var FXORate $fxoRate
     */
    protected $fxoRate;

    /**
     * @var FXORateQuote
     */
    protected $fxoRateQuote;

    /**
     * @var RequestInterface $request
     */
    protected $request;

    /**
     * @var CartDataHelper $cartDataHelper
     */
    protected $cartDataHelper;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param CartFactory $cartFactory
     * @param FXORate $fxoRate
     * @param FXORateQuote $fxoRateQuote
     * @param RequestInterface $request
     * @param CartDataHelper $cartDataHelper
     * @param Session $customerSession
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        CartFactory $cartFactory,
        FXORate $fxoRate,
        FXORateQuote $fxoRateQuote,
        RequestInterface $request,
        CartDataHelper $cartDataHelper,
        Session $customerSession,
        ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->cartFactory = $cartFactory;
        $this->fxoRate = $fxoRate;
        $this->fxoRateQuote = $fxoRateQuote;
        $this->request = $request;
        $this->cartDataHelper = $cartDataHelper;
        $this->customerSession = $customerSession;
        $this->toggleConfig = $toggleConfig;
    }

    /**
     * Call FXO Rate and get response
     *
     * @return string
     */
    public function execute()
    {
        $locationId = $this->request->getPostValue('locationId');
        $account = $this->request->getPostValue('fedexAccount');
        $quote = $this->cartFactory->create()->getQuote();
        if (!$account && $quote->getData('fedex_account_number')) {
            $account = $this->cartDataHelper->decryptData($quote->getData('fedex_account_number'));
        }
        $arrRequestPickupData = [
            'locationId' => $locationId,
            'fedExAccountNumber' => $account
        ];
        $quote->setCustomerPickupLocationData($arrRequestPickupData);
        $quote->setIsFromPickup(true);

        // Set contact data in Quote to pass in RateQuote with Save Action
        if (!empty($this->customerSession->getProfileSession())) {
            $profileInfo = $this->customerSession->getProfileSession();
            $quote->setData('customer_firstname', $profileInfo->output->profile->contact->personName->firstName);
            $quote->setData('customer_lastname', $profileInfo->output->profile->contact->personName->lastName);
            $quote->setData('customer_email', $profileInfo->output->profile->contact->emailDetail->emailAddress);
            $quote->setData(
                'customer_telephone',
                $profileInfo->output->profile->contact->phoneNumberDetails[0]->phoneNumber->number
            );
        }

        try {
            $fxoRateResponse = $this->fxoRateQuote->getFXORateQuote($quote);
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            'Get response from FXO Rate : ' . $e->getMessage());
            $fxoRateResponse = ["success" => false];
        }
        $result = $this->jsonFactory->create();
        $result->setData($fxoRateResponse);
        
        return $result;
    }
}
