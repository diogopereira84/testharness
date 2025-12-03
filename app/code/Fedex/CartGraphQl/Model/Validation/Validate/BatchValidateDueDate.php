<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai Solanki <yash.solanki.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Validation\Validate;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Fedex\GraphQl\Api\GraphQlBatchValidationInterface;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Exception;

class BatchValidateDueDate implements GraphQlBatchValidationInterface
{
    const RESOLVERS = ['addOrUpdateDueDate'];
    const TOGGLE_ORDER_DUE_DATES_FAILING = 'tiger_d_210735_order_due_dates_failing';

    /**
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param GraphQlBatchRequestCommand $requestCommand
     * @return void
     * @throws GraphQlInputException
     * @throws Exception
     */
    public function validate(GraphQlBatchRequestCommand $requestCommand): void
    {
        if (!$this->isToggleOrderDueDatesFailingEnabled()) {
            $requests = $requestCommand->getRequests();
            foreach ($requests as $key => $request) {
                if (in_array($request->getField()->getName(), self::RESOLVERS)) {
                    $args = $request->getArgs();
                    $dueDate = $args['input']['due_date'];
                    if ($dueDate && !$this->validateIsPastDate($dueDate)) {
                        $this->logger->error(__METHOD__ . ':' . __LINE__ .
                            'Due date should not be the past date.');
                        throw new GraphQlInputException(
                            __('Due date should not be the past date.')
                        );
                    }
                }
            }
        }
    }

    /**
     * @param $dueDate
     * @return bool
     * @throws Exception
     */
    function validateIsPastDate($dueDate): bool
    {
        $currentDate = new \DateTime();
        $dueDate = new \DateTime($dueDate);

        if ($dueDate < $currentDate) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    private function isToggleOrderDueDatesFailingEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TOGGLE_ORDER_DUE_DATES_FAILING);
    }
}
