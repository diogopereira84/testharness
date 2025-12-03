<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai Solanki <yash.solanki.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory;
use Fedex\CartGraphQl\Model\Note\Command\SaveInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Exception;

class Notes extends AbstractResolver
{
    private $cart;
    private const INPUT = 'input';
    private const CART_ID = 'cart_id';
    private const NOTES = 'notes';

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param FXORateQuote $fxoRateQuote
     * @param ConfigInterface $instoreConfig
     * @param SaveInterface $commandOrderNoteSave
     * @param Json $jsonSerializer
     * @param Cart $cartModel
     * @param GraphQlBatchRequestCommandFactory $requestCommandFactory
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param ValidationBatchComposite $validationComposite
     * @param NewRelicHeaders $newRelicHeaders
     * @param array $validations
     */
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        private readonly FXORateQuote     $fxoRateQuote,
        private readonly ConfigInterface  $instoreConfig,
        private readonly SaveInterface    $commandOrderNoteSave,
        private readonly Json             $jsonSerializer,
        private readonly Cart             $cartModel,
        GraphQlBatchRequestCommandFactory $requestCommandFactory,
        BatchResponseFactory              $batchResponseFactory,
        LoggerHelper                      $loggerHelper,
        ValidationBatchComposite          $validationComposite,
        NewRelicHeaders                   $newRelicHeaders,
        array                             $validations = []
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
        if (!$this->instoreConfig->isEnabledAddNotes()) {
            throw new GraphQlInputException(__('Add notes is not enabled.'));
        }
        try {
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
            $response = $this->batchResponseFactory->create();

            foreach ($requests as $request) {
                $args = $request->getArgs();
                $this->cart =  $this->cartModel->getCart($args[self::INPUT][self::CART_ID], $context);
                $orderNotes = isset($args[self::INPUT][self::NOTES])
                    ? $this->jsonSerializer->serialize($args[self::INPUT][self::NOTES])
                    : null;

                $this->cart->setOrderNotes($orderNotes);
                try {
                    $this->fxoRateQuote->getFXORateQuote($this->cart, null, false, [], null, [], true);
                } catch (GraphQlFujitsuResponseException $e) {
                    $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on getting FXO Rate Quote. ' . $e->getMessage(), $headerArray);
                }
                $this->cartRepository->save($this->cart);
                $this->commandOrderNoteSave->execute($this->cart, $orderNotes);
                $this->cart = $this->cartRepository->get($this->cart->getId());
                $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);
                $response->addResponse(
                    $request,
                    ['cart' =>
                        [
                            'model' => $this->cart,
                        ]
                    ]
                );
            }
            return $response;
        } catch (Exception $e) {
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on saving information. ' . $e->getMessage(), $headerArray);
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . $e->getTraceAsString(), $headerArray);
            $this->loggerHelper->error('GTN: ' . $this->cart?->getGtn(), $headerArray);
            throw new GraphQlInputException(__('Error on saving information ' . $e->getMessage()));
        }
    }
}
