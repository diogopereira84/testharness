<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Model\Company\Custom\Billing\CreditCard;

use Exception;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Company\Model\Company\Custom\Billing\CreditCard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Company\Api\Data\CustomBillingCreditCardInterfaceFactory;
use Fedex\Company\Api\Data\CustomBillingCreditCardInterface;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\Company\Model\Company\Custom\Billing\CreditCard\Mapper;
use Fedex\Company\Model\Company\Custom\Billing\CreditCard\CollectionFactory;
use Fedex\Company\Model\Company\Custom\Billing\CreditCard\Collection;

class MapperTest extends TestCase
{
    private const ARRAY_VALUE = [
        0 => [
            'visible' => '1',
            'editable' => '1',
            'required' => '1',
            'mask' => '',
            'record_id' => '0',
            'field_name' => 'AAAAAA',
            'field_label' => 'BBBBBB',
            'default' => 'DDDDDD',
            'custom_mask' => 'CCCCCCC',
            'error_message' => 'ddddddd'
        ]];

    /**
     * @var CustomBillingCreditCardInterfaceFactory|MockObject
     */
    private CustomBillingCreditCardInterfaceFactory|MockObject $customBillingCreditCardFactoryMock;

    /**
     * @var CustomBillingCreditCardInterface|MockObject
     */
    private CustomBillingCreditCardInterface|MockObject $customBillingCreditCardMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private CollectionFactory|MockObject $collectionFactoryMock;

    /**
     * @var Collection|MockObject
     */
    private Collection|MockObject $collectionMock;

    /**
     * @var Json|MockObject
     */
    private Json|MockObject $jsonMock;

    /**
     * @var JsonValidator|MockObject
     */
    private JsonValidator|MockObject $jsonValidatorMock;

    /**
     * @var Mapper
     */
    private Mapper $mapper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $collection = (new ObjectManager($this))->getObject(Collection::class);
        $invoice = (new ObjectManager($this))->getObject(CreditCard::class);
        $invoice->setData(self::ARRAY_VALUE[0]);
        $this->customBillingCreditCardMock = $this->getMockForAbstractClass(
            CustomBillingCreditCardInterface::class
        );
        $this->customBillingCreditCardFactoryMock = $this->getMockBuilder(
            CustomBillingCreditCardInterfaceFactory::class
        )->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customBillingCreditCardFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($invoice);
        $this->collectionFactoryMock = $this->getMockBuilder(
            CollectionFactory::class
        )->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($collection);
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonValidatorMock = $this->getMockBuilder(JsonValidator::class)
            ->setMethods(['isValid'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mapper = new Mapper(
            $this->customBillingCreditCardFactoryMock,
            $this->collectionFactoryMock,
            $this->jsonMock,
            $this->jsonValidatorMock
        );
    }

    /**
     * Test fromArray method
     *
     * @return void
     *
     * @throws Exception
     */
    public function testFromArray(): void
    {
        $collection = $this->mapper->fromArray(self::ARRAY_VALUE);
        $this->assertEquals(self::ARRAY_VALUE, $collection->toArray()['items']);
    }

    /**
     * Test fromJson method
     *
     * @return void
     *
     * @throws Exception
     */
    public function testFromJson(): void
    {
        $this->jsonValidatorMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->jsonMock->expects($this->once())
            ->method('unserialize')
            ->willReturn(self::ARRAY_VALUE);
        $collection = $this->mapper->fromJson(json_encode(self::ARRAY_VALUE));
        $this->assertEquals(self::ARRAY_VALUE, $collection->toArray()['items']);
    }

    /**
     * Test fromArrayToJson method
     *
     * @return void
     *
     * @throws Exception
     */
    public function testFromArrayToJson(): void
    {
        $json = json_encode(self::ARRAY_VALUE);
        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->willReturn($json);
        $this->assertEquals($json, $this->mapper->fromArrayToJson(self::ARRAY_VALUE));
    }
}
