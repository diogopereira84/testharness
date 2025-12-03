<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Model;

use Fedex\AccountValidation\Api\AccountValidationInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;

class AccountValidation implements AccountValidationInterface
{
    public const XML_PATH_TOGGLE_E456656 = 'tigers_e456656_account_validation_in_magento_admin';
    private const ACCOUNT_TYPE_PAYMENT = 'PAYMENT';
    private const ACCOUNT_TYPE_DISCOUNT = 'DISCOUNT';
    private const ACCOUNT_TYPE_SHIPPING = 'SHIPPING';
    private const ORIGINATING_OPCO_FXK = 'FXK';
    private const ORIGINATING_OPCO_FX = 'FX';

    public function __construct(
        protected ToggleConfig $toggleConfig,
        protected UrlInterface $urlBuilder,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected PunchoutHelper $punchoutHelper,
        protected HeaderData $headerData,
        protected Curl $curl
    ) {
    }

    public function isToggleE456656Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE_E456656);
    }

    public function getAccountValidationUrl(): string
    {
        return $this->urlBuilder->getUrl('accountvalidation/account/validateaccount');
    }

    public function validateAccount(string $printAccountNumber, string $discountAccountNumber, string $shippingAccountNumber): array
    {
        $response = [
            'status' => false,
            'accountType' => '',
            'holdCode' => 0,
            'holdCodeDescription' => ''
        ];

        $accountNumber = $printAccountNumber ?: ($discountAccountNumber ?: $shippingAccountNumber);

        if (!$accountNumber || !is_numeric($accountNumber)) {
            return $response;
        }

        try {
            $accountSummary = $this->getAccountSummary($accountNumber);
            if (empty($accountSummary['account_type'])) {
                return $response;
            }

            $response['status'] = $accountSummary['account_status'] ?? false;
            $response['accountType'] = $accountSummary['account_type'];
            $response['holdCode'] = (int)($accountSummary['holdCode'] ?? 0);
            $response['holdCodeDescription'] = $accountSummary['holdCodeDescription'] ?? '';

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during account validation: ' . $e->getMessage(), ['exception' => $e]);
            throw new LocalizedException(__($e->getMessage()));
        }

        return $response;
    }

    /**
     * @param string $accountNumber
     * @return array|string[]
     */
    public function getAccountSummary(string $accountNumber): array
    {
        $endPointUrl = str_replace('{accountNumber}', $accountNumber, (string)$this->enhancedProfile->getConfigValue(EnhancedProfile::ACCOUNT_SUMMARY));
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "X-clientid: ISHP",
            $this->headerData->getAuthHeaderValue() . $this->punchoutHelper->getAuthGatewayToken(),
            "Cookie: Bearer=" . $this->punchoutHelper->getTazToken(),
            "Cookie: fxo_ecam_userid=user;",
        ];

        $this->curl->setOptions([
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $accountInfo = [];
        try {
            $this->curl->post($endPointUrl, '');
            $response = json_decode($this->curl->getBody(), true);

            if ($response && (isset($response['status']) && $response['status'] === 500 || isset($response['message']) && $response['message'] === 'Account Not Found')) {
                return [
                    'account_status' => 'inactive',
                    'account_type' => '',
                ];
            }

            $accountUsage = $response['output']['accounts'][0]['accountUsage'] ?? [];
            $accountInfo['account_status'] = $accountUsage['print']['status'] ?? '';
            $accountInfo['account_type'] = $this->determineAccountType($accountUsage);
            $accountInfo['holdCode'] = $accountUsage['print']['invoicing']['holdCode'] ?? 0;
            $accountInfo['holdCodeDescription'] = $accountUsage['print']['invoicing']['holdCodeDescription'] ?? '';

        } catch (\Exception $e) {
            $this->logger->critical("Payment Account API is not working: " . $e->getMessage());
        }

        return $accountInfo;
    }

    private function determineAccountType(array $accountUsage): string
    {
        if (isset($accountUsage['originatingOpco'])) {
            if ($accountUsage['originatingOpco'] === self::ORIGINATING_OPCO_FXK) {
                return $accountUsage['print']['payment']['allowed'] === 'Y' ? self::ACCOUNT_TYPE_PAYMENT : self::ACCOUNT_TYPE_DISCOUNT;
            } elseif ($accountUsage['originatingOpco'] === self::ORIGINATING_OPCO_FX) {
                return $accountUsage['ship']['status'] === 'true' ? self::ACCOUNT_TYPE_SHIPPING : '';
            }
        }
        return '';
    }
}
