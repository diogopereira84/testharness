<?php
/**
 * @category    Fedex
 * @package     Fedex_CoreApi
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CoreApi\Gateway\Request;

interface BuilderInterface
{
    /**
     * Builds request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject = []): array;
}
