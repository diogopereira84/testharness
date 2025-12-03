<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Model\Service;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

/**
 * Validates FedEx account by contacting external API and parsing the response.
 */
class FedExAccountValidator
{
    public const ACCOUNT_SUMMARY = 'fedex/enhanced_profile_group/account_summary';
    private const FEDEX_API_VALIDATION = 'tigerteam_E469373_fedex_shipping_account_number_validation_api_call';

    public function __construct(
        private readonly Curl $curl,
        private readonly LoggerInterface $logger,
        private readonly ToggleConfig $toggleConfig,
        private readonly Session $customerSession,
        private readonly PunchoutHelper $punchoutHelper,
        private readonly HeaderData $headerData,
    ) {}

    /**
     * @return bool
     */
    public function isFedexAccountValidationCallEnabled(): bool
    {

        return (bool) $this->toggleConfig->getToggleConfigValue(self::FEDEX_API_VALIDATION);
    }

    /**
     * Fetches and validates FedEx account information via external API.
     *
     * @param string $accountNumber
     * @return bool
     */
    public function isShippingAccountValid(string $accountNumber): bool
    {
        $url = $this->buildEndpointUrl($accountNumber);
        $headers = $this->buildHeaders();

        $this->curl->setOptions([
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        try {
            $this->curl->post($url, '');
            $response = json_decode($this->curl->getBody());

            if ($this->curl->getStatus() === 500) {
                throw new \RuntimeException('FedEx API returned HTTP 500 Internal Server Error');
            }

            $accountUsage = $response->output->accounts[0]->accountUsage ?? null;
            if (!$accountUsage) {
                throw new \RuntimeException('FedEx API response missing accountUsage data');
            }

            $accountStatus = $accountUsage->print->status ?? '';
            $accountType = $this->resolveAccountType($accountUsage);

            if ($accountStatus === 'ACTIVE' && $accountType === 'SHIPPING') {
                return true;
            }

            $this->logAccountType($accountType);
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ' - API call failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }

        return false;
    }

    /**
     * Constructs API endpoint URL.
     *
     * @param string $accountNumber
     * @return string
     */
    private function buildEndpointUrl(string $accountNumber): string
    {
        $endpointTemplate = (string)$this->toggleConfig->getToggleConfig(self::ACCOUNT_SUMMARY);
        return str_replace('{accountNumber}', $accountNumber, $endpointTemplate);
    }

    /**
     * Prepares headers for the FedEx API request.
     *
     * @return array
     */
    private function buildHeaders(): array
    {
        $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
        $tazToken = $this->punchoutHelper->getTazToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();

        return [
            'Content-Type: application/json',
            'Accept: application/json',
            'Accept-Language: json',
            'X-clientid: ISHP',
            $authHeaderVal . $gatewayToken,
            'Cookie: Bearer=' . $tazToken,
            'Cookie: fxo_ecam_userid=user;',
        ];
    }

    /**
     * Determines the FedEx account type based on usage data.
     *
     * @param object $accountUsage
     * @return string
     */
    private function resolveAccountType(object $accountUsage): string
    {
        $originatingOpco = $accountUsage->originatingOpco ?? '';
        $paymentAllowed = $accountUsage->print->payment->allowed ?? null;
        $shippingStatus = $accountUsage->ship->status ?? '';

        return match (true) {
            $originatingOpco === 'FXK' && $paymentAllowed === 'Y' => 'PAYMENT',
            $originatingOpco === 'FXK' && $paymentAllowed === 'N' => 'DISCOUNT',
            $originatingOpco === 'FX' && $shippingStatus === 'true' => 'SHIPPING',
            default => 'INVALID',
        };
    }

    /**
     * Logs the resolved account type.
     *
     * @param string $accountType
     * @return void
     */
    private function logAccountType(string $accountType): void
    {
        $this->logger->info(__METHOD__ . ' - FedEx Account Type is: ' . $accountType);
    }
}
