<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api;

use Fedex\LateOrdersGraphQl\Api\Data\LateOrderSearchResultDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\OrderDetailsDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\LateOrderQueryParamsDTOInterface;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderQueryParamsDTO;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

interface LateOrderRepositoryInterface
{
    /**
     * Retrieve list of late orders based on filters
     *
     * @param LateOrderQueryParamsDTOInterface|LateOrderQueryParamsDTO $params
     * @return LateOrderSearchResultDTOInterface
     * @throws LocalizedException
     * @throws GraphQlInputException
     * @throws \DateInvalidOperationException
     */
    public function getList(
        LateOrderQueryParamsDTOInterface|LateOrderQueryParamsDTO $params
    ): LateOrderSearchResultDTOInterface;

    /**
     * Retrieve detailed information about a specific order by its ID
     *
     * @param string $orderIncrementId
     * @return OrderDetailsDTOInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     */
    public function getById(string $orderIncrementId): OrderDetailsDTOInterface;
}
