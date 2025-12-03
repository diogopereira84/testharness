<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface OrderRetationPeriodRepositoryInterface
{

    /**
     * Save OrderRetationPeriod
     * @param \Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface $orderRetationPeriod
     * @return \Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface $orderRetationPeriod
    );

    /**
     * Retrieve OrderRetationPeriod
     * @param string $orderretationperiodId
     * @return \Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($orderretationperiodId);


    /**
     * Delete OrderRetationPeriod
     * @param \Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface $orderRetationPeriod
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface $orderRetationPeriod
    );

    /**
     * Delete OrderRetationPeriod by ID
     * @param string $orderretationperiodId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($orderretationperiodId);
}

