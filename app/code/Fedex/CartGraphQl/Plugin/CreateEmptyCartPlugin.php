<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterfaceFactory;
use Fedex\CartGraphQl\Model\Validation\Validate\ValidateLocationId;
use Fedex\CartGraphQl\Model\Validation\Validate\ValidateStoreId;
use Fedex\GraphQl\Model\GraphQlRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\Validate\ValidateInput;
use Fedex\GraphQl\Model\Validation\ValidationComposite;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\QuoteGraphQl\Model\Resolver\CreateEmptyCart;
use Psr\Log\LoggerInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

class CreateEmptyCartPlugin
{
    /**
     * @param RequestCommandFactory $requestCommandFactory
     * @param ValidationComposite $validationComposite
     * @param ValidateInput $validateInput
     * @param ValidateLocationId $validateLocationId
     * @param ValidateStoreId $validateStoreId
     * @param CartIntegrationInterfaceFactory $cartIntegrationInterfaceFactory
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $cartRepository
     * @param PunchoutHelper $punchoutHelper
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     */
    public function __construct(
        private RequestCommandFactory $requestCommandFactory,
        private ValidationComposite $validationComposite,
        private ValidateInput $validateInput,
        private ValidateLocationId $validateLocationId,
        private ValidateStoreId $validateStoreId,
        private CartIntegrationInterfaceFactory $cartIntegrationInterfaceFactory,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        protected LoggerInterface $logger,
        private CartRepositoryInterface $cartRepository,
        private PunchoutHelper $punchoutHelper,
        private LoggerHelper $loggerHelper,
        private NewRelicHeaders $newRelicHeaders
    ) {}

    /**
     * @param CreateEmptyCart $subject
     * @param string $result
     * @param Field $field
     * @param Context $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve(
        CreateEmptyCart $subject,
        string $result,
        Field $field,
        Context $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $requestCommand = $this->requestCommandFactory->create([
            'field' => $field,
            'context' => $context,
            'info' => $info,
            'value' => $value,
            'args' => $args,
        ]);

        $this->validationComposite->add($this->validateInput);
        $this->validationComposite->add($this->validateLocationId);
        $this->validationComposite->add($this->validateStoreId);
        $this->validationComposite->validate($requestCommand);
        $mutationName = $field->getName() ?? '';
        $headerArray = $this->newRelicHeaders->getHeadersForMutation($mutationName);

        try {
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($result);
            $gtnNumber = $this->punchoutHelper->getGTNNumber();

            $quoteIntegration = $this->cartIntegrationInterfaceFactory->create();
            $quoteIntegration->setQuoteId($quoteId);
            $quoteIntegration->setLocationId($args['input']['location_id'] ?? null);
            $quoteIntegration->setStoreId($args['input']['store_id'] ?? null);

            $quote = $this->cartRepository->get($quoteId);
            $quote->setGtn($gtnNumber);
            $quote->setReservedOrderId($gtnNumber);
            $this->cartRepository->save($quote);

            $this->cartIntegrationRepository->save($quoteIntegration);
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);
        } catch (\Exception $e) {
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . $e->getTraceAsString(), $headerArray);
            throw new GraphQlInputException(__($e->getMessage()));
        }

        return [
            'cart_id' => $result,
            'store_id' => $quoteIntegration->getStoreId(),
            'location_id' => $quoteIntegration->getLocationId(),
            'gtn' => $gtnNumber
        ];
    }
}
