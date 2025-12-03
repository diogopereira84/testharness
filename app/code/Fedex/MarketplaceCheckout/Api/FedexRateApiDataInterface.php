<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Rafael Vargas <rafael.vargas.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

interface FedexRateApiDataInterface
{
    public const SERVICE_DESCRIPTION = 'serviceDescription';

    public const SERVICE_TYPE = 'serviceType';

    public const RATED_SHIPMENT_DETAILS = 'ratedShipmentDetails';

    public const DESCRIPTION = 'description';

    public const OPERATIONAL_DETAIL = 'operationalDetail';

    public const DELIVERY_DATA = 'deliveryDate';

    public const SHIPMENT_RATE_DETAIL = 'shipmentRateDetail';

    public const SURCHARGES = 'surCharges';
}