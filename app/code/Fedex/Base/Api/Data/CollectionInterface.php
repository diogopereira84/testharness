<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Api\Data;

interface CollectionInterface
{
    /**
     * Convert collection to array
     * And return the items array only
     *
     * @param array $arrRequiredFields
     *
     * @return array
     */
    public function toArrayItems(array $arrRequiredFields = []): array;
}
