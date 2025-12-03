<?php
/**
 * DTO Alerts
 *
 * Class for mapping alert parameters.
 *
 * @category     Fedex
 * @package      Fedex_UploadToQuote
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <athiraindrakumar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\UploadToQuote\Model;

use Fedex\UploadToQuote\Model\AlertsDtoFactory;

class AlertsMapper
{
    /**
     * @param AlertsDtoFactory $alertsDtoFactory
     */
    public function __construct(
        private readonly AlertsDtoFactory $alertsDtoFactory
    ) { }
    /**
     * @param array $raqResponse
     * @return array
     */
    public function map(array $raqResponse): array
    {
        $alerts = [];

        if (!empty($raqResponse)) {
            foreach ($raqResponse as $alertData) {
                $alert = $this->alertsDtoFactory->create();
                $alert->setCode($alertData['code'] ?? '');
                $alert->setMessage($alertData['message'] ?? '');
                $alert->setAlertType($alertData['alertType'] ?? '');
                $alerts[] = $alert->toArray();
            }
        }
        return $alerts;
    }
}
