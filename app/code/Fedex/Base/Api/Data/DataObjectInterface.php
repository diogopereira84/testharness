<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Api\Data;

interface DataObjectInterface
{
    /**
     * Convert object and Children data to array
     * with requested keys
     *
     * @param array<string> $keys array of required keys
     * @param array<string> $visited array of required keys
     *
     * @return array object data as array
     */
    public function toArrayRecursive(array $keys = [], array &$visited = []): array;
}
