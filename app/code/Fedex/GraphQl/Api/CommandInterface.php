<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Api;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

interface CommandInterface
{
    /**
     * @return Field
     */
    public function getField(): Field;

    /**
     * @return object
     */
    public function getContext(): object;

    /**
     * @return ResolveInfo
     */
    public function getInfo(): ResolveInfo;

    /**
     * @return ?array
     */
    public function getValue(): ?array;

    /**
     * @return array
     */
    public function getArgs(): array;

    /**
     * @return ?array
     */
    public function getResult(): ?array;
}
