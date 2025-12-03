<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Model\Data;

use Fedex\Base\Api\Data\CollectionInterface;

class Collection extends \Magento\Framework\Data\Collection implements CollectionInterface
{
    /**
     * @inheritDoc
     */
    public function toArrayItems(array $arrRequiredFields = []): array
    {
        return parent::toArray($arrRequiredFields)['items'];
    }
}
