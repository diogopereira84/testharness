<?php
/**
 * @category    Fedex
 * @package     Fedex_ShippingAddressValidation
 * @copyright   Copyright (c) 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\ShippingAddressValidation\Controller\Index;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Fedex\ShippingAddressValidation\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * AddressValidate Controller
 *
 */
class AddressValidate implements ActionInterface
{
    private const ERRORS = 'errors';
    private const OUTPUT = 'output';
    private const EXPLORERS_SHIPPING_ADDRESS_ERROR_CODE = 'explorers_shipping_address_errorCode';
    private const GENERIC_ERROR_BYPASS = 'fedex/general/shipping_address_api_generic_error_bypass';

    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     * @param JsonFactory $resultJsonFactory
     * @param Data $addressValidationHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private LoggerInterface               $logger,
        private RequestInterface              $request,
        private ToggleConfig                  $toggleConfig,
        private JsonFactory                   $resultJsonFactory,
        private Data                          $addressValidationHelper,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Execute method to validate address
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $responseData = [];
        $responseError = [];
        try {
            $data = $this->request->getPostValue();
            if (!empty($data)) {
                $responseData = $this->addressValidationHelper->callAddressValidationApi($data);
            }

            $shippingAddressErrorCodeToggle = (bool)$this->toggleConfig->getToggleConfigValue(self::EXPLORERS_SHIPPING_ADDRESS_ERROR_CODE);
            if ($shippingAddressErrorCodeToggle) {
                if (isset($responseData[self::ERRORS]) || !isset($responseData[self::OUTPUT])) {
                    if (isset($responseData[self::ERRORS]) && is_array($responseData[self::ERRORS])) {
                        foreach ($responseData[self::ERRORS] as $error) {
                            if (isset($error['message'])) {
                                $errorMessage = $error['message'];
                                break; // Use the first error message found.
                            }
                        }
                    } elseif (isset($responseData[self::ERRORS]) && is_string($responseData[self::ERRORS])) {
                        $errorMessage = $responseData['errors'];
                    } elseif (!empty($responseData['output']['resolvedAddresses'][0]['customerMessages'][0])) {
                        $errorMessage = $responseData['output']['resolvedAddresses'][0]['customerMessages'][0]['message'];
                    }
                    $errorCodes = $this->addressValidationHelper->getShippingAddressErrorCode();
                    $errorCodesArray = array_map('trim', explode(';', $errorCodes));

                    if (is_array($errorCodesArray) && isset($errorMessage)) {
                        foreach ($errorCodesArray as $errorCode) {
                            if (strcasecmp($errorCode, $errorMessage) === 0) {
                                $responseError[self::ERRORS] = $errorMessage;
                                return $resultJson->setData($responseError);
                            }
                        }
                    } else {
                        return $resultJson->setData([]);
                    }
                } else {
                    return $resultJson->setData($responseData);
                }
            } else {
                if ($this->isGenericErrorInsideResponse($responseData ?? [])) {
                    return $resultJson->setData([]);
                }
                if (!empty($responseData['output']['resolvedAddresses'][0]['customerMessages'][0])) {
                    $responseError['error_msg'] = $responseData['output']['resolvedAddresses'][0]['customerMessages'][0]['message'];
                    return $resultJson->setData($responseError);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                'Error found no data from AddressValidationAPI. ' . $e->getMessage());
            $responseData = ['error_msg' => "Error found no data from AddressValidationAPI." . $e->getMessage()];
        }
        return $resultJson->setData($responseData);
    }

    /**
     * Check if the response contains a generic error
     *
     * @param array $response
     * @return bool
     */
    private function isGenericErrorInsideResponse(array $response): bool
    {
        if ($this->isToggleForGenericErrorBypassEnabled()
            && isset($response[self::ERRORS]) && is_array($response[self::ERRORS]))
        {
            foreach ($response[self::ERRORS] as $error) {
                if (isset($error['message']) && strcasecmp('GENERIC.ERROR', $error['message']) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if the toggle for generic error bypass is enabled
     *
     * @return bool
     */
    private function isToggleForGenericErrorBypassEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::GENERIC_ERROR_BYPASS);
    }
}
