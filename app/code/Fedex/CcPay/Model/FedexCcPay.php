<?php


namespace Fedex\CcPay\Model;

use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Psr\Log\LoggerInterface;

class FedexCcPay extends \Magento\Payment\Model\Method\Adapter
{
    public function __construct(ManagerInterface $eventManager, ValueHandlerPoolInterface $valueHandlerPool, PaymentDataObjectFactory $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, CommandPoolInterface $commandPool = null, ValidatorPoolInterface $validatorPool = null, CommandManagerInterface $commandExecutor = null, LoggerInterface $logger = null)
    {
        parent::__construct($eventManager, $valueHandlerPool, $paymentDataObjectFactory, $code, $formBlockType, $infoBlockType, $commandPool, $validatorPool, $commandExecutor, $logger);
    }
}
