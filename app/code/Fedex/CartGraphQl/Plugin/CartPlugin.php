<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Olimjon Akhmedov <oakhmedov@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\CartGraphQl\Model\Validation\Validate\ValidateModel;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\GraphQlRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Resolver\Cart;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

class CartPlugin
{
    /**
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param RequestCommandFactory $requestCommandFactory
     * @param ValidationComposite $validationComposite
     * @param ValidateModel $validateModel
     * @param ToggleConfig $toggleConfig
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     */
    public function __construct(
        private FXORate  $fxoRateHelper,
        protected FXORateQuote $fxoRateQuote,
        private RequestCommandFactory $requestCommandFactory,
        private ValidationComposite $validationComposite,
        private ValidateModel $validateModel,
        protected ToggleConfig $toggleConfig,
        private LoggerHelper $loggerHelper,
        private NewRelicHeaders $newRelicHeaders
    ) {}

    /**
     * @param Cart $subject
     * @param $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve(
        Cart $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $requestCommand = $this->requestCommandFactory->create([
            'field' => $field,
            'context' => $context,
            'info' => $info,
            'value' => $value,
            'args' => $args,
            'result' => $result
        ]);
        $mutationName = $field->getName() ?? '';
        $headerArray = $this->newRelicHeaders->getHeadersForMutation($mutationName);

        $this->validationComposite->add($this->validateModel);
        $this->validationComposite->validate($requestCommand);
        try {
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
            $this->fxoRateQuote->getFXORateQuote($result['model']);
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);
        } catch (\Exception $exception) {
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . $exception->getTraceAsString(), $headerArray);
            throw new GraphQlInputException(__($exception->getMessage()));
        }

        return $result;
    }
}
