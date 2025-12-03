<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder;

use Fedex\MarketplacePunchout\Api\BuilderInterface;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request\Create;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request\Edit;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Simplexml\Element;

class Request implements BuilderInterface
{

    /**
     * @param RequestInterface $httpRequest
     * @param Create $create
     * @param Edit $edit
     */
    public function __construct(
        private RequestInterface $httpRequest,
        private Create $create,
        private Edit $edit
    ) {
    }

    /**
     * Build the request xml body
     *
     * @param null $productSku
     * @param mixed|null $productConfigData
     * @return Element
     */
    public function build($productSku = null, mixed $productConfigData = null): Element
    {
        if ($this->httpRequest->getParam('supplier_part_auxiliary_id')) {
            return $this->edit->build($productSku, $productConfigData);
        }

        return $this->create->build($productSku, $productConfigData);
    }
}
