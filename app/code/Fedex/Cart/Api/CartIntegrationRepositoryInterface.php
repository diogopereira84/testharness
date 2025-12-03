<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Api;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface CartItemRepositoryInterface
 * @api
 * @since 100.0.2
 */
interface CartIntegrationRepositoryInterface
{
    /**
     * @param CartIntegrationInterface $integration
     * @return mixed
     */
    public function save(CartIntegrationInterface $integration);

    /**
     * @param $integrationId
     * @return mixed
     */
    public function getById($integrationId);

    /**
     * @param $integrationId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getByQuoteId($integrationId);

    /**
     * @param CartIntegrationInterface $integration
     * @return mixed
     */
    public function delete(CartIntegrationInterface $integration);

    /**
     * @param $integrationId
     * @return mixed
     */
    public function deleteById($integrationId);
}
