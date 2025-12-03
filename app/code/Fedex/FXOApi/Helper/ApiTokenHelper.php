<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOApi
 * @copyright   Copyright (c) 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\FXOApi\Helper;

use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Psr\Log\LoggerInterface;

class ApiTokenHelper
{
    /**
     * @param PunchoutHelper $punchoutHelper
     * @param LoggerInterface $logger
     * @param HeaderData $headerData
     */
    public function __construct(
        protected PunchoutHelper $punchoutHelper,
        protected LoggerInterface $logger,
        protected HeaderData $headerData
    )
    {
    }

    /**
     * Get Taz token for api call
     *
     * @return string
     */
    public function getTazToken(): string
    {
        $tazToken = $this->punchoutHelper->getTazToken();

        return 'Cookie: Bearer=' . $tazToken;
    }

    /**
     * Get Gateway token for api call
     *
     * @param string $gateway
     * @return string
     */
    public function getGatewayToken($gateway): string
    {
        $gatewayHeader = '';
        $gatewayType = strtoupper($gateway);

        switch ($gatewayType) {
            case 'FXO-RETAIL-GATEWAY':
                $gatewayHeader = $this->getRetailGatewayToken();
                break;
            default:
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . 'Invalid Gateway');
        }

        return $gatewayHeader;
    }

    /**
     * Get FXO retail gateway token for api call
     *
     * @return string
     */
    public function getRetailGatewayToken(): string
    {
        $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();

        return $authHeaderVal . $gatewayToken;
    }
}
