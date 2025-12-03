<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model\Validation;

use Fedex\GraphQl\Api\GraphQlValidationInterface;
use Fedex\GraphQl\Model\GraphQlRequestCommand;

class ValidationComposite implements GraphQlValidationInterface
{
    private array $validations = [];

    /**
     * @param GraphQlRequestCommand $requestCommand
     */
    public function validate(GraphQlRequestCommand $requestCommand): void
    {
        foreach ($this->validations as $child) {
            $child->validate($requestCommand);
        }
    }

    /**
     * @param GraphQlValidationInterface $requestCommand
     */
    public function add(GraphQlValidationInterface $requestCommand): void
    {
        $this->validations[] = $requestCommand;
    }
}
