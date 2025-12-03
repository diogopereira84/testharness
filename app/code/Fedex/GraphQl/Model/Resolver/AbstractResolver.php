<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model\Resolver;

use Fedex\GraphQl\Api\ResolverInterface;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Api\GraphQlBatchValidationInterface;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;

abstract class AbstractResolver implements ResolverInterface {
    /**
     * @param RequestCommandFactory $requestCommandFactory
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param ValidationComposite $validationComposite
     * @param NewRelicHeaders $newRelicHeaders
     * @param GraphQlBatchValidationInterface[] $validations
     */
    public function __construct(
        public RequestCommandFactory $requestCommandFactory,
        public BatchResponseFactory $batchResponseFactory,
        public LoggerHelper $loggerHelper,
        public ValidationComposite $validationComposite,
        public NewRelicHeaders $newRelicHeaders,
        private readonly array $validations = []
    ) {
    }

    /**
     * @param ContextInterface $context
     * @param Field $field
     * @param array $requests
     * @return BatchResponse
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse {
        $requestCommand = $this->requestCommandFactory->create([
            'context' => $context,
            'field' => $field,
            'requests' => $requests
        ]);

        foreach ($this->validations as $validation) {
            $this->validationComposite->add($validation);
        }

        $mutationName = $field->getName() ?? '';
        $this->validationComposite->validate($requestCommand);
        $headerArray = $this->newRelicHeaders->getHeadersForMutation($mutationName);

        return $this->proceed($context, $field, $requests, $headerArray);
    }

    /**
     * @param ContextInterface $context
     * @param Field $field
     * @param array $requests
     * @param array $headerArray
     * @return BatchResponse
     */
    abstract public function proceed(
        ContextInterface $context,
        Field $field,
        array $requests,
        array $headerArray
    ): BatchResponse;
}
