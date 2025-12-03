<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <aindrakumar@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\CartGraphQl\Model\PlaceOrder\PoliticalDisclosureService;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Exception;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Magento\Store\Model\StoreManagerInterface;

class AddOrUpdatePoliticalDisclosure extends AbstractResolver
{
    /**
     * @param PoliticalDisclosureService $politicalDisclosureService
     * @param InstoreConfig $instoreConfig
     * @param StoreManagerInterface $storeManager
     * @param RequestCommandFactory $requestCommandFactory
     * @param ValidationComposite $validationComposite
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param array $validations
     */
    public function __construct(
        private readonly PoliticalDisclosureService $politicalDisclosureService,
        private readonly InstoreConfig $instoreConfig,
        private readonly StoreManagerInterface $storeManager,
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
        $response = $this->batchResponseFactory->create();
        try {
            if (!$this->instoreConfig->isEnablePoliticalDisclosureInPlaceOrder()) {
                $store = $this->storeManager->getStore();
                throw new GraphQlInputException(__('Political disclosure is not enabled in the %1 scope', $store->getCode()));
            }
            foreach ($requests as $request) {
                $args = $request->getArgs();
                $input = $args['input'] ?? [];
                $orderId = $input['order_id'] ?? null;
                $disclosureInput = $input['political_disclosure'] ?? null;
                if (!$orderId || !$disclosureInput) {
                    throw new GraphQlInputException(__('order_id and political_disclosure are required.'));
                }
                $orderEntityId = $this->politicalDisclosureService->setDisclosureDetails($disclosureInput, $orderId);
                if (!$orderEntityId) {
                    throw new GraphQlInputException(__('Order not found.'));
                }
                // Return the full order object using Magento's default logic
                $output = [
                    'order' => [
                        'order_number' => $orderId,
                    ],
                    'political_disclosure' => $this->politicalDisclosureService->getDisclosureDetailsByOrderId($orderEntityId)
                ];
                $response->addResponse($request, $output);
            }
            return $response;
        } catch (Exception $e) {
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on saving political disclosure. ' . $e->getMessage(), $headerArray);
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . $e->getTraceAsString(), $headerArray);
            throw new GraphQlInputException(__('Error on saving political disclosure: ' . $e->getMessage()));
        }
    }
}
