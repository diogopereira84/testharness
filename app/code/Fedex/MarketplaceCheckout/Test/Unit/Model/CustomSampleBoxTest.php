<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\CustomSampleBox;
use Fedex\MarketplaceCheckout\Model\Offers;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomSampleBoxTest extends TestCase
{
    /**
     * @var Offers|MockObject
     */
    private $offersMock;

    /**
     * @var CustomSampleBox
     */
    private $model;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    protected function setUp(): void
    {
        $this->offersMock = $this->getMockBuilder(Offers::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOfferItemsByOfferId'])
            ->getMock();

        $this->quoteMock = $this->createMock(Quote::class);

        $this->model = new CustomSampleBox(
            $this->offersMock
        );
    }

    private function prepareShopsData(array $shops): array
    {
        $result = [];
        foreach ($shops as $shopData) {
            $items = [];
            if (empty($shopData['items'])) {
                $result[] = ['items' => []];
                continue;
            }
            foreach ($shopData['items'] as $itemData) {
                $itemMock = $this->getMockBuilder(QuoteItem::class)
                    ->disableOriginalConstructor()
                    ->addMethods(['getAdditionalData'])
                    ->onlyMethods(['getData'])
                    ->getMock();
                $itemMock->method('getAdditionalData')
                    ->willReturn($itemData['additional_data'] ?? null);
                $itemMock->method('getData')
                    ->with('mirakl_offer_id')
                    ->willReturn($itemData['mirakl_offer_id'] ?? null);
                $items[] = $itemMock;
            }
            $result[] = ['items' => $items];
        }
        return $result;
    }

    private function configureOffersMock(array $offersConfig): void
    {
        if (empty($offersConfig)) {
            $this->offersMock->method('getOfferItemsByOfferId')->willReturn([]);
            return;
        }

        $map = [];
        if (isset($offersConfig['offer_id'])) {
            $offersConfig = [$offersConfig];
        }

        foreach ($offersConfig as $config) {
            $offerId = $config['offer_id'];
            $offerItems = [];
            foreach ($config['items'] as $itemData) {
                $offerItemMock = $this->getMockBuilder(DataObject::class)
                    ->disableOriginalConstructor()
                    ->addMethods(['getAdditionalInfo'])
                    ->getMock();
                $offerItemMock->method('getAdditionalInfo')->willReturn($itemData);
                $offerItems[] = $offerItemMock;
            }
            $map[] = [$offerId, $offerItems];
        }

        if (empty($map)) {
            $this->offersMock->method('getOfferItemsByOfferId')->willReturn([]);
        } else {
            $this->offersMock->method('getOfferItemsByOfferId')->willReturnMap($map);
        }
    }

    /**
     * @dataProvider isOnlySampleBoxProductInCartDataProvider
     */
    public function testIsOnlySampleBoxProductInCart(
        bool $expectedResult,
        array $shops,
        bool $isFreightShippingEnabled,
        bool $isMktCbbEnabled,
        bool $isMiraklQuote,
        array $offersConfig = []
    ): void {
        $this->configureOffersMock($offersConfig);

        $this->assertEquals(
            $expectedResult,
            $this->model->isOnlySampleBoxProductInCart(
                $this->quoteMock,
                $this->prepareShopsData($shops),
                $isFreightShippingEnabled,
                $isMktCbbEnabled,
                $isMiraklQuote
            )
        );
    }

    public function isOnlySampleBoxProductInCartDataProvider(): array
    {
        $punchoutItem = ['additional_data' => json_encode(['punchout_enabled' => true])];
        $nonPunchoutItem = ['additional_data' => json_encode(['punchout_enabled' => false])];
        $noPunchoutInfoItem = ['additional_data' => json_encode(['some_other_key' => 'value'])];
        $nullAdditionalDataItem = ['additional_data' => null];
        $invalidJsonItem = ['additional_data' => '{"a":'];
        $sampleBoxItem = [
            'additional_data' => json_encode(['punchout_enabled' => false]),
            'mirakl_offer_id' => 123
        ];
        $multiShopItems = [['items' => [$punchoutItem]], ['items' => [$sampleBoxItem]]];

        return [
            'Freight shipping disabled' => [false, [], false, true, true],
            'No shops, freight enabled' => [false, [], true, true, true],
            'Single shop with no items' => [false, [['items' => []]], true, true, true],
            'Single shop non-punchout item' => [true, [['items' => [$nonPunchoutItem]]], true, true, true],
            'Single shop item no punchout info' => [true, [['items' => [$noPunchoutInfoItem]]], true, true, true],
            'Single shop invalid json' => [true, [['items' => [$invalidJsonItem]]], true, true, true],
            'Single shop punchout item' => [false, [['items' => [$punchoutItem]]], true, true, true],
            'Single shop multiple items' => [false, [['items' => [$nonPunchoutItem, $punchoutItem]]], true, true, true],
            'Single shop null additional data' => [false, [['items' => [$nullAdditionalDataItem]]], true, true, true],
            'Multi shop, CBB enabled, has sample box' => [
                true,
                $multiShopItems,
                true, true, true,
                ['offer_id' => 123, 'items' => [['force_mirakl_shipping_options' => 'true']]]
            ],
            'Multi shop, CBB enabled, no sample box' => [
                false,
                $multiShopItems,
                true, true, true,
                ['offer_id' => 123, 'items' => [['force_mirakl_shipping_options' => 'false']]]
            ],
            'Multi shop, CBB disabled' => [false, $multiShopItems, true, false, true],
            'Multi shop, not Mirakl quote' => [false, $multiShopItems, true, true, false],
            'Single shop, not Mirakl quote' => [false, [['items' => [$nonPunchoutItem]]], true, true, false],
            'Final return false case' => [false, $multiShopItems, true, false, false]
        ];
    }

    /**
     * @dataProvider hasSampleBoxInAnyShopDataProvider
     */
    public function testHasSampleBoxInAnyShop(bool $expectedResult, array $shops, array $offersConfig = []): void
    {
        $this->configureOffersMock($offersConfig);
        $this->assertEquals($expectedResult, $this->model->hasSampleBoxInAnyShop($this->prepareShopsData($shops)));
    }

    public function hasSampleBoxInAnyShopDataProvider(): array
    {
        $nonSampleBoxItem = ['additional_data' => json_encode(['punchout_enabled' => true])];
        $sampleBoxItem = [
            'additional_data' => json_encode(['punchout_enabled' => false]),
            'mirakl_offer_id' => 123
        ];

        return [
            'No shops' => [false, []],
            'Shop with no items' => [false, [['items' => []]]],
            'One shop with sample box' => [
                true,
                [['items' => [$sampleBoxItem]]],
                ['offer_id' => 123, 'items' => [['force_mirakl_shipping_options' => 'true']]]
            ],
            'One shop without sample box (punchout)' => [false, [['items' => [$nonSampleBoxItem]]]],
            'Multiple shops, one with sample box' => [
                true,
                [['items' => [$nonSampleBoxItem]], ['items' => [$sampleBoxItem]]],
                ['offer_id' => 123, 'items' => [['force_mirakl_shipping_options' => 'true']]]
            ],
            'Multiple shops, none with sample box' => [false, [['items' => [$nonSampleBoxItem, $nonSampleBoxItem]]]]
        ];
    }

    /**
     * @dataProvider hasSampleBoxInShopDataProvider
     */
    public function testHasSampleBoxInShop(bool $expectedResult, array $shop, array $offersConfig = []): void
    {
        $this->configureOffersMock($offersConfig);
        $this->assertEquals($expectedResult, $this->model->hasSampleBoxInShop($this->prepareShopsData([$shop])[0]));
    }

    public function hasSampleBoxInShopDataProvider(): array
    {
        $sampleBoxItem = [
            'additional_data' => json_encode(['punchout_enabled' => false]),
            'mirakl_offer_id' => 123
        ];
        $punchoutItem = [
            'additional_data' => json_encode(['punchout_enabled' => true]),
            'mirakl_offer_id' => 456
        ];
        $noOfferIdItem = [
            'additional_data' => json_encode(['punchout_enabled' => false]),
            'mirakl_offer_id' => null
        ];
        $noPunchoutInfoItem = [
            'additional_data' => json_encode(['some_other_key' => 'value']),
            'mirakl_offer_id' => 123
        ];
        $invalidJsonItem = [
            'additional_data' => '{"a":',
            'mirakl_offer_id' => 123
        ];

        return [
            'Shop has more than one item' => [false, ['items' => [$sampleBoxItem, $punchoutItem]]],
            'Shop has punchout item' => [false, ['items' => [$punchoutItem]]],
            'Shop item has null additional data' => [false, ['items' => [['additional_data' => null]]]],
            'Item has invalid json' => [
                true,
                ['items' => [$invalidJsonItem]],
                ['offer_id' => 123, 'items' => [['force_mirakl_shipping_options' => 'true']]]
            ],
            'Item has no punchout info' => [
                true,
                ['items' => [$noPunchoutInfoItem]],
                ['offer_id' => 123, 'items' => [['force_mirakl_shipping_options' => 'true']]]
            ],
            'Item has no offer id' => [false, ['items' => [$noOfferIdItem]]],
            'Offer not found for offer id' => [
                false,
                ['items' => [$sampleBoxItem]],
                ['offer_id' => 123, 'items' => []]
            ],
            'Offer has no force shipping flag' => [
                false,
                ['items' => [$sampleBoxItem]],
                ['offer_id' => 123, 'items' => [['force_mirakl_shipping_options' => 'false']]]
            ],
            'Offer has empty additional info' => [
                false,
                ['items' => [$sampleBoxItem]],
                ['offer_id' => 123, 'items' => [[]]]
            ],
            'Valid sample box item' => [
                true,
                ['items' => [$sampleBoxItem]],
                ['offer_id' => 123, 'items' => [['force_mirakl_shipping_options' => 'true']]]
            ]
        ];
    }
}