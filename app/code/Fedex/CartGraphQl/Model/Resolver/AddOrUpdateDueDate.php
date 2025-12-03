<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Athira Indrakumar <aindrakumar@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\Stdlib\DateTime;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Exception;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class AddOrUpdateDueDate extends AbstractResolver
{
    /**
     * INPUT
     */
    private const INPUT = 'input';

    /**
     * CART_ID
     */
    private const CART_ID = 'cart_id';

    /**
     * DUE_DATE
     */
    private const DUE_DATE = 'due_date';

    /**
     * REQUESTED_PICKUP_LOCAL_TIME
     */
    private const REQUESTED_PICKUP_LOCAL_TIME = 'requested_pickup_local_time';

    /**
     * REQUESTED_DELIVERY_LOCAL_TIME
     */
    private const REQUESTED_DELIVERY_LOCAL_TIME = 'requested_delivery_local_time';

    /**
     * SHIPPING_ESTIMATED_DELIVERY_LOCAL_TIME
     */
    private const SHIPPING_ESTIMATED_DELIVERY_LOCAL_TIME = 'shipping_estimated_delivery_local_time';

    /**
     * @var string
     */
    private $cart;

    /**
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param FXORateQuote $fxoRateQuote
     * @param DateTime $dateTime
     * @param InstoreConfig $instoreConfig
     * @param Cart $cartModel
     * @param JsonSerializer $jsonSerializer
     * @param RequestCommandFactory $requestCommandFactory
     * @param ValidationComposite $validationComposite
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param array $validations
     */
    public function __construct(
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private readonly FXORateQuote $fxoRateQuote,
        private readonly DateTime $dateTime,
        private readonly InstoreConfig $instoreConfig,
        private readonly Cart $cartModel,
        private readonly JsonSerializer $jsonSerializer,
        RequestCommandFactory $requestCommandFactory,
        ValidationComposite $validationComposite,
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
            if ($this->instoreConfig->isAddOrUpdateDueDateEnabled()) {
                $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
                $response = $this->batchResponseFactory->create();
                foreach ($requests as $request) {
                    $args = $request->getArgs();
                    $cartId = $args[self::INPUT][self::CART_ID];
                    $this->cart = $this->cartModel->getCart($cartId, $context);
                    $dueDate = $args[self::INPUT][self::DUE_DATE] ?? null;
                    $shipByDate = $args[self::INPUT][self::SHIPPING_ESTIMATED_DELIVERY_LOCAL_TIME] ?? null;
                    $integration = $this->cartIntegrationRepository->getByQuoteId($this->cart->getId());
                    $this->cart->setData(
                        self::REQUESTED_PICKUP_LOCAL_TIME,
                        $dueDate ?? null
                    );
                    if ($this->instoreConfig->isAddShipByDateEnabled()) {
                        $this->cart->setData(
                            self::SHIPPING_ESTIMATED_DELIVERY_LOCAL_TIME,
                            $shipByDate ?? null
                        );
                    }
                    // Handle due_date for RequestedDeliveryLocalTime
                    if ($dueDate && $this->instoreConfig->isDeliveryDatesFieldsEnabled()) {
                        $formattedDueDate = date('Y-m-d\TH:i:s', strtotime($dueDate));
                        $this->cart->setData(self::REQUESTED_DELIVERY_LOCAL_TIME, $formattedDueDate);
                    }

                    try {
                        $this->fxoRateQuote->getFXORateQuote($this->cart);
                    } catch (GraphQlFujitsuResponseException $e) {
                        $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on getting FXO Rate Quote. ' . $e->getMessage(), $headerArray);
                        throw new GraphQlFujitsuResponseException(__($e->getMessage()));
                    }
                    $this->inStoreSavePickupLocationDate($integration, $dueDate, $shipByDate);
                    $data = [
                        'cart' => [
                            'model' => $this->cart,
                        ],
                        'gtn' => $this->cart->getGtn(),
                        'due_date' => $integration->getPickupLocationDate(),
                        'shipping_estimated_delivery_local_time' => $shipByDate
                    ];
                    $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);
                    $response->addResponse($request, $data);
                }
            } else {
                $response->addResponse(end($requests), $data ?? null);
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
     * Save pickup location date on quote integration
     *
     * @param CartIntegrationInterface $integration
     * @param $dueDate
     * @return void
     */
    private function inStoreSavePickupLocationDate(CartIntegrationInterface $integration, $dueDate, $shipByDate): void
    {
        if (!$dueDate) {
            return;
        }
        $formatPickupLocationDate = $this->dateTime->formatDate($dueDate, true);
        $integration->setPickupLocationDate($formatPickupLocationDate);

        if ($shipByDate && $this->instoreConfig->isAddShipByDateEnabled()) {
            $deliveryData = $this->getDeliveryDataFormatted($shipByDate);
            $integration->setDeliveryData($deliveryData);
        }

        $this->cartIntegrationRepository->save($integration);
    }

    /**
     * @param $data
     * @param $type
     * @return bool|string|null
     */
    protected function getDeliveryDataFormatted($shipByDate): bool|string|null
    {
        $data = [];
        if (!$shipByDate) {
            return false;
        }
        $data[self::SHIPPING_ESTIMATED_DELIVERY_LOCAL_TIME] = $shipByDate;
        return $this->jsonSerializer->serialize($data);
    }
}
