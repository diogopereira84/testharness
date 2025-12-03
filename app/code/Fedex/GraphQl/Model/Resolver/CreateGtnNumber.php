<?php
/**
 * @category     Fedex
 * @package      Fedex_ProductGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Brajmohan Rajput <brajmohan.rajput.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Fedex\Punchout\Helper\Data as PunchoutDataHelper;

class CreateGtnNumber implements BatchResolverInterface
{
    /**
     * @param PunchoutDataHelper $punchoutDataHelper
     */
    public function __construct(
        protected PunchoutDataHelper $punchoutDataHelper
    )
    {
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function resolve(
        $context,
        Field $field,
        array $requests
    ): BatchResponse {
        return $this->punchoutDataHelper->getGTNNumber();
    }
}
