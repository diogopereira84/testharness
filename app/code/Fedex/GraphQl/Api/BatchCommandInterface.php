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

interface BatchCommandInterface
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
     * @return ?array
     */
    public function getResult(): ?array;

    /**
     * @return ?array
     */
    public function getRequests(): ?array;
}
