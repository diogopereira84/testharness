<?php
/**
 * @category    Request
 * @package     Request_Canva
 * @copyright   Copyright (c) 2022 Request
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Gateway\Request;

use Fedex\Punchout\Helper\Data as PunchOutHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Builder implements BuilderInterface
{
    public function __construct(
        private Json            $json,
        private ToggleConfig    $toggleConfig,
        private PunchOutHelper  $punchOutHelper,
        private CustomerSession $customerSession
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject = []): array
    {
        $gateWayToken = $this->punchOutHelper->getAuthGatewayToken();
        $buildSubject = array_replace_recursive([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ], $buildSubject);


        if ($gateWayToken) {
            $buildSubject['headers']['Authorization'] = 'Bearer ' . $gateWayToken;
            if($this->toggleConfig->getToggleConfigValue('E352723_use_clientId_header')){
                $buildSubject['headers']['client_id'] =  $gateWayToken;
            }
        }

        $accessToken = $this->punchOutHelper->getTazToken();
        if ($accessToken) {
            $buildSubject['headers']['Cookie'] = "Bearer=" . $accessToken;
        }

        if ($this->customerSession->getId() && $this->customerSession->getCustomerCanvaId()) {
            $buildSubject['body'] = $this->json->serialize([
                'userTokensRequest' => [
                    'canvaUserId' => $this->customerSession->getCustomerCanvaId()
                ]
            ]);
        }

        return $buildSubject;
    }
}
