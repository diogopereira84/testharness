<?php
declare(strict_types=1);

namespace Fedex\UploadToQuote\Test\Unit\Model\Validator;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Model\Validator\NegotiableStatusChange;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote as NegotiableQuoteResource;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class NegotiableStatusChangeTest extends TestCase
{
    /**
     * @var NegotiableQuoteInterfaceFactory|MockObject
     */
    private $negotiableQuoteFactory;

    /**
     * @var NegotiableQuote|MockObject
     */
    private $negotiableQuote;

    /**
     * @var NegotiableQuoteResource|MockObject
     */
    private $negotiableQuoteResource;

    /**
     * @var NegotiableQuoteInterface|MockObject
     */
    private $negotiableQuoteInterface;

    /**
     * @var ValidatorResultFactory|MockObject
     */
    private $validatorResultFactory;

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfig;

    /**
     * @var NegotiableStatusChange
     */
    private $negotiableStatusChange;

    protected function setUp(): void
    {
        $this->negotiableQuoteFactory = $this->createMock(NegotiableQuoteInterfaceFactory::class);
        $this->negotiableQuote = $this->createMock(NegotiableQuote::class);
        $this->negotiableQuoteResource = $this->createMock(NegotiableQuoteResource::class);
        $this->negotiableQuoteInterface = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'hasData', 'getQuoteId'])
            ->getMockForAbstractClass();

        $this->validatorResultFactory = $this->createMock(ValidatorResultFactory::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->negotiableStatusChange = new NegotiableStatusChange(
            $this->negotiableQuoteFactory,
            $this->negotiableQuoteResource,
            $this->validatorResultFactory,
            $this->toggleConfig
        );
    }

    public function testValidateWithEmptyNegotiableQuote()
    {
        $data = [];
        $result = $this->createMock(ValidatorResult::class);
        $this->validatorResultFactory->method('create')->willReturn($result);

        $this->assertSame($result, $this->negotiableStatusChange->validate($data));
    }

    public function testValidateWithInvalidStatusChange()
    {
        $result = $this->createMock(ValidatorResult::class);
        $this->validatorResultFactory->method('create')->willReturn($result);

        $this->negotiableQuoteInterface->method('getQuoteId')->willReturn(1);
        $this->negotiableQuoteInterface
            ->method('getData')
            ->with(NegotiableQuoteInterface::QUOTE_STATUS)
            ->willReturnOnConsecutiveCalls(['invalid_status'], ['created']);
        $this->negotiableQuoteInterface->method('hasData')->with(NegotiableQuoteInterface::QUOTE_STATUS)->willReturn(true);

        $this->negotiableQuoteFactory->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuoteResource->method('load')->with($this->negotiableQuote, 1);

        $this->toggleConfig->method('getToggleConfigValue')->with(NegotiableStatusChange::D208156_TOGGLE)->willReturn(true);

        $result->expects($this->once())->method('addMessage')->with(__('You cannot update the quote status.'));

        $this->negotiableStatusChange->validate(['negotiableQuote' => $this->negotiableQuoteInterface]);
    }

    public function testValidateWithValidStatusChange()
    {
        $result = $this->createMock(ValidatorResult::class);
        $this->validatorResultFactory->method('create')->willReturn($result);

        $this->negotiableQuoteInterface->method('getQuoteId')->willReturn(1);
        $this->negotiableQuoteInterface
            ->method('getData')
            ->with(NegotiableQuoteInterface::QUOTE_STATUS)
            ->willReturnOnConsecutiveCalls(['submitted_by_customer'], ['created']);
        $this->negotiableQuoteInterface->method('hasData')->with(NegotiableQuoteInterface::QUOTE_STATUS)->willReturn(true);

        $this->negotiableQuoteFactory->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuoteResource->method('load')->with($this->negotiableQuote, 1);

        $this->toggleConfig->method('getToggleConfigValue')->with(NegotiableStatusChange::D208156_TOGGLE)->willReturn(true);

        $this->assertSame($result, $this->negotiableStatusChange->validate(['negotiableQuote' => $this->negotiableQuoteInterface]));
    }

    public function testRetrieveNegotiableQuote()
    {
        $negotiableQuote = $this->createMock(NegotiableQuoteInterface::class);

        $negotiableStatusChange = new \ReflectionMethod(
            NegotiableStatusChange::class,
            'retrieveNegotiableQuote',
        );
        $negotiableStatusChange->setAccessible(true);
        $result = $negotiableStatusChange->invoke($this->negotiableStatusChange, ['negotiableQuote' => $negotiableQuote]);

        $this->assertSame($negotiableQuote, $result);
    }

    public function testRetrieveNegotiableQuoteFromQuote()
    {

        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->setMethods(['getIsRegularQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $quote->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->method('getIsRegularQuote')->willReturn(true);

        $negotiableStatusChange = new \ReflectionMethod(
            NegotiableStatusChange::class,
            'retrieveNegotiableQuote',
        );
        $negotiableStatusChange->setAccessible(true);
        $result = $negotiableStatusChange->invoke($this->negotiableStatusChange, ['negotiableQuote' => null, 'quote' => $quote]);

        $this->assertSame($negotiableQuote, $result);
    }

    public function testValidateWithCoreFileAllowChangesValidStatus()
    {
        $result = $this->createMock(ValidatorResult::class);
        $this->validatorResultFactory->method('create')->willReturn($result);

        $this->negotiableQuoteInterface->method('getQuoteId')->willReturn(3);
        $this->negotiableQuoteInterface
            ->method('getData')
            ->with(NegotiableQuoteInterface::QUOTE_STATUS)
            ->willReturnOnConsecutiveCalls([NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER], [NegotiableQuoteInterface::STATUS_CREATED]);
        $this->negotiableQuoteInterface->method('hasData')->with(NegotiableQuoteInterface::QUOTE_STATUS)->willReturn(true);

        $this->negotiableQuoteFactory->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuoteResource->method('load')->with($this->negotiableQuote, 3);

        // Toggle is OFF, so allowChangesFromCoreFile is used
        $this->toggleConfig->method('getToggleConfigValue')->with(NegotiableStatusChange::D208156_TOGGLE)->willReturn(false);
        $this->assertSame($result, $this->negotiableStatusChange->validate(['negotiableQuote' => $this->negotiableQuoteInterface]));
    }
}


