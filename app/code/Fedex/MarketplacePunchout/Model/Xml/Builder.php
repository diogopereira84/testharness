<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\Xml;

use Fedex\MarketplacePunchout\Api\BuilderInterface;
use Fedex\MarketplacePunchout\Model\Xml\Builder\Header;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Simplexml\ElementFactory;
use Magento\Framework\Simplexml\Element;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Builder
{
    /**
     * @param ElementFactory $xmlFactory
     * @param DateTime $dateTime
     * @param Header $header
     * @param BuilderInterface $request
     */
    public function __construct(
        private ElementFactory $xmlFactory,
        private DateTime $dateTime,
        private Header $header,
        private BuilderInterface $request,
    ) {
    }

    /**
     * Build xml request payload
     *
     * @param $productSku
     * @param mixed|null $productConfigData
     * @return Element
     * @throws NoSuchEntityException
     */
    public function build($productSku = null, mixed $productConfigData = null): Element
    {
        $cxml = $this->xmlFactory->create(
            [
                'data' => "<?xml version='1.0' encoding='UTF-8'?>
                <!DOCTYPE cXML SYSTEM 'http://xml.cXML.org/schemas/cXML/1.2.026/cXML.dtd'>
                <cXML>
                </cXML>"
            ]
        );

        $cxml->addAttribute('payloadID', uniqid());
        $cxml->addAttribute('xml:lang', 'en-US');
        $cxml->addAttribute(
            'timestamp',
            date('m/d/Y h:i:s A', strtotime($this->dateTime->gmtDate()))
        );

        $cxml->appendChild($this->header->build($productSku));
        $cxml->appendChild($this->request->build($productSku, $productConfigData));

        return $cxml;
    }
}
