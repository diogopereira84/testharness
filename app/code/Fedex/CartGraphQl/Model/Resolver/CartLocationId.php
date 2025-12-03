<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface as ResolverContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

/**
 * @inheritdoc
 */
class CartLocationId extends AbstractResolver
{
    /**
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param RequestCommandFactory $requestCommandFactory
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param ValidationBatchComposite $validationComposite
     * @param NewRelicHeaders $newRelicHeaders
     * @param array $validations
     */
    public function __construct(
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository,
        RequestCommandFactory $requestCommandFactory,
        BatchResponseFactory $batchResponseFactory,
        LoggerHelper $loggerHelper,
        ValidationBatchComposite $validationComposite,
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
     * @param ResolverContextInterface $context
     * @param Field $field
     * @param array $requests
     * @param array $headerArray
     * @return BatchResponse
     */
    public function proceed(
        ContextInterface $context,
        Field $field,
        array $requests,
        array $headerArray
    ): BatchResponse {
        foreach ($requests as $key => $request) {
            /** @var ResolveRequest $request */
            $value = $request->getValue();
            if ($value['model']->getId()) {
                $cartIntegration = $this->cartIntegrationRepository->getByQuoteId($value['model']->getId());
                $results[$request->getValue()['model']->getId()] = $cartIntegration->getLocationId();
            }
        }
        $response = $this->batchResponseFactory->create();
        foreach ($requests as $request) {
            $modelId = $request->getValue()['model']->getId();
            $response->addResponse($request, $results[$modelId] ?? null);
        }
        return $response;
    }
}
