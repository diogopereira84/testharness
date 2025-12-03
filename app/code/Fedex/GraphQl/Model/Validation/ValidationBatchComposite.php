<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model\Validation;

use Fedex\GraphQl\Api\GraphQlBatchValidationInterface;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;

class ValidationBatchComposite implements GraphQlBatchValidationInterface
{
    private array $validations = [];

    /**
     * @param GraphQlBatchRequestCommand $requestCommand
     */
    public function validate(GraphQlBatchRequestCommand $requestCommand): void
    {
        foreach ($this->validations as $child) {
            $child->validate($requestCommand);
        }
    }

    /**
     * @param GraphQlBatchValidationInterface $requestCommand
     */
    public function add(GraphQlBatchValidationInterface $requestCommand): void
    {
        $this->validations[] = $requestCommand;
    }
}
