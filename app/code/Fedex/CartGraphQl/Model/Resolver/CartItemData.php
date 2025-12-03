<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem\Collection as IntegrationItemCollection;
use Fedex\CartGraphQl\Helper\LoggerHelper;

/**
 * @inheritdoc
 */
class CartItemData extends AbstractResolver
{
    /**
     * @param IntegrationItemCollection $integrationItemCollection
     * @param RequestCommandFactory $requestCommandFactory
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param ValidationComposite $validationComposite
     * @param NewRelicHeaders $newRelicHeaders
     * @param array $validations
     */
    public function __construct(
        private readonly IntegrationItemCollection $integrationItemCollection,
        RequestCommandFactory $requestCommandFactory,
        BatchResponseFactory $batchResponseFactory,
        LoggerHelper $loggerHelper,
        ValidationComposite $validationComposite,
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
     * @return BatchResponse
     */
    public function proceed(
        ContextInterface $context,
        Field $field,
        array $requests,
        array $headerArray
    ): BatchResponse {
        $results = [];
        $itemIds = [];
        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
        foreach ($requests as $key => $request) {
            /** @var ResolveRequest $request */
            $value = $request->getValue();
            $itemData = $value['model']->getExtensionAttributes()->getIntegrationItemData() ?
                $value['model']->getExtensionAttributes()->getIntegrationItemData()->getItemData() : null;

            if ($itemData) {
                $results[$request->getValue()['model']->getId()] = $itemData;
            } else {
                $itemIds[] = $value['model']->getId();
            }
        }

        if (!empty($itemIds)) {
            try {
                $integrationItems = $this->integrationItemCollection->addFieldToFilter('item_id',array('in' => $itemIds));
                foreach ($integrationItems as $integrationItem) {
                    $results[$integrationItem->getItemId()] = $integrationItem->getItemData();
                }
            } catch (\Exception $e) {
                $this->loggerHelper->error(
                    __METHOD__ . ':' . __LINE__ . ' Requested quote item integration data does not exist.', $headerArray
                );
            }
        }

        $response = new BatchResponse();

        foreach ($requests as $request) {
            $modelId = $request->getValue()['model']->getId();
            $response->addResponse($request, $results[$modelId] ?? null);
        }

        return $response;
    }
}
