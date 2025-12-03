<?php
 
namespace Fedex\CcPay\Test\Unit\Model;
 
use Fedex\CcPay\Model\FedexCcPay;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
 
class FedexCcPayTest extends TestCase
{
    public function testInstanceCreation()
    {
        // Create mocks for all the constructor dependencies
        $eventManager = $this->createMock(ManagerInterface::class);
        $valueHandlerPool = $this->createMock(ValueHandlerPoolInterface::class);
        $paymentDataObjectFactory = $this->createMock(PaymentDataObjectFactory::class);
        $commandPool = $this->createMock(CommandPoolInterface::class);
        $validatorPool = $this->createMock(ValidatorPoolInterface::class);
        $commandManager = $this->createMock(CommandManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
 
        // Set constructor parameters
        $code = 'fedexccpay';
        $formBlockType = 'Magento\Payment\Block\Form';
        $infoBlockType = 'Magento\Payment\Block\Info';
 
        // Instantiate the object
        $instance = new FedexCcPay(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandManager,
            $logger
        );
 
        // Assert that the object is an instance of FedexCcPay
        $this->assertInstanceOf(FedexCcPay::class, $instance);
    }
}