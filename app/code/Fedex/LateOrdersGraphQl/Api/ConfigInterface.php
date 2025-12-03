<?php
namespace Fedex\LateOrdersGraphQl\Api;

interface ConfigInterface
{
    /**
     * Get max window by hours for late order GraphQL query
     * @return int|null
     */
    public function getLateOrderQueryWindowHours(): ?int;

    /**
     * Get max pagination for late order GraphQL query response
     * @return int|null
     */
    public function getLateOrderQueryMaxPagination(): ?int;

    /**
     * Get default pagination for late order GraphQL query response
     * @return int|null
     */
    public function getLateOrderQueryDefaultPagination(): ?int;
}
