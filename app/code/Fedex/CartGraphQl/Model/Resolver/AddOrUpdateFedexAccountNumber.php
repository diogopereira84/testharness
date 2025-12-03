<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Athira Indrakumar <aindrakumar@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Exception;
use Fedex\CartGraphQl\Model\FedexAccountNumber\SetFedexAccountNumber;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\Cart\Helper\Data as CartDataHelper;

class AddOrUpdateFedexAccountNumber extends AbstractResolver
{
    /**
     * CART_ID
     */
    const CART_ID = 'cart_id';

    /**
     * ALTERNATE_CONTACT
     */
    const ALTERNATE_CONTACT = 'alternate_contact';

    /**
     * FIRST_NAME
     */
    const FIRST_NAME = 'firstname';

    /**
     * LAST_NAME
     */
    const LAST_NAME = 'lastname';

    /**
     * EMAIL
     */
    const EMAIL = 'email';

    /**
     * TELEPHONE
     */
    const TELEPHONE = 'telephone';

    /**
     * STREET
     */
    public const STREET = 'street';

    /**
     * REGION_ID
     */
    public const REGION_ID = 'region_id';

    /**
     * COMPANY
     */
    public const COMPANY = 'company';

    /**
     * @var string
     */
    private $cart;

    /**
     * @param FXORateQuote $fxoRateQuote
     * @param InstoreConfig $instoreConfig
     * @param SetFedexAccountNumber $setFedexAccountNumber
     * @param Cart $cartModel
     * @param CartDataHelper $cartHelper
     * @param RequestCommandFactory $requestCommandFactory
     * @param ValidationBatchComposite $validationComposite
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param array $validations
     */
    public function __construct(
        private readonly FXORateQuote $fxoRateQuote,
        private readonly InstoreConfig $instoreConfig,
        private readonly SetFedexAccountNumber $setFedexAccountNumber,
        private readonly Cart $cartModel,
        private readonly CartDataHelper $cartHelper,
        RequestCommandFactory $requestCommandFactory,
        ValidationBatchComposite $validationComposite,
        BatchResponseFactory $batchResponseFactory,
        LoggerHelper $loggerHelper,
        NewRelicHeaders $newRelicHeaders,
        array $validations = []
    ) {
        parent::__construct(
            $requestCommandFactory,
            $batchResponseFactory,
            $loggerHelper,
            $validationComposite,
            $newRelicHeaders,
            $validations
        );
    }

    /**
     * @param ContextInterface $context
     * @param Field $field
     * @param array $requests
     * @param array $headerArray
     * @return BatchResponse
     * @throws GraphQlInputException
     */
    public function proceed(
        ContextInterface $context,
        Field $field,
        array $requests,
        array $headerArray
    ): BatchResponse {
        try {
            $response = $this->batchResponseFactory->create();
            $data = [];
            if ($this->instoreConfig->isAddOrUpdateFedexAccountNumberEnabled()) {
                $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
                foreach ($requests as $request) {
                    $args = $request->getArgs();
                    $this->cart = $this->cartModel->getCart($args['input']['cart_id'], $context);
                    $hasFedexAccountNumber = isset($args['input']['fedex_account_number']);
                    $hasFedexShipAccountNumber = isset($args['input']['fedex_ship_account_number']);
                    $hasLteIdentifier = isset($args['input']['lte_identifier']);
                    if ($hasFedexAccountNumber || $hasFedexShipAccountNumber) {
                        $fedexAccountNumber = $args['input']['fedex_account_number'] ?? null;
                        $fedexShipAccountNumber =  $args['input']['fedex_ship_account_number'] ?? null;
                        $this->setFedexAccountNumber->setFedexAccountNumber($fedexAccountNumber, $fedexShipAccountNumber, $this->cart);
                        $lteIdentifier = null;
                        if ($this->instoreConfig->isSupportLteIdentifierEnabled() && $hasLteIdentifier) {
                            $lteIdentifier =  $args['input']['lte_identifier'];
                        }
                        $this->cart->setLteIdentifier($lteIdentifier);
                        if ($this->instoreConfig->canSaveLteIdentifier()) {
                            $this->cart->save();
                        }
                        try {
                            $this->fxoRateQuote->getFXORateQuote($this->cart);
                        } catch (GraphQlFujitsuResponseException $e) {
                            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on getting FXO Rate Quote. ' . $e->getMessage(), $headerArray);
                            throw new GraphQlFujitsuResponseException(__($e->getMessage()));
                        }
                        $data = [
                            'cart' => [
                                'model' => $this->cart,
                            ],
                            'gtn' => $this->cart->getGtn(),
                            'fedex_account_number' => $this->getDecryptedFedexAccountNumber($this->cart),
                            'fedex_ship_account_number' => $this->cart->getFedexShipAccountNumber(),
                            'lte_identifier' => $this->cart->getLteIdentifier()
                        ];
                        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);
                        $response->addResponse($request, $data);
                    }
                }
            } else {
                $response->addResponse(end($requests), $data);
            }
            return $response;

        } catch (Exception $e) {
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on saving information into cart. ' . $e->getMessage(), $headerArray);
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . $e->getTraceAsString(), $headerArray);
            $this->loggerHelper->error('GTN: ' . $this->cart?->getGtn(), $headerArray);
            throw new GraphQlInputException(__('Error on saving information into cart ' . $e->getMessage()));
        }
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return string|null
     */
    private function getDecryptedFedexAccountNumber($quote)
    {
        return $this->cartHelper->decryptData($quote->getData("fedex_account_number"));
    }
}
