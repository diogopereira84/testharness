<?php

namespace Fedex\SDE\Test\Unit\Plugin\Minicart;

use Fedex\SDE\Helper\SdeHelper;
use Fedex\SDE\Plugin\Minicart\AbstractItem;
use PHPUnit\Framework\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class AbstractItemTest
 *
 * This class is to do the phpunit for AbstractItem plugin class
 */
class AbstractItemTest extends TestCase
{
    protected $minicartItem;
    /**
     * @var SdeHelper|MockObject
     */
    private $sdeHelperMock;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->sdeHelperMock = $this->createMock(SdeHelper::class);
        $objectManagerHelper = new ObjectManager($this);

        $this->minicartItem = $objectManagerHelper->getObject(
            AbstractItem::class,
            [
                'sdeHelper' => $this->sdeHelperMock
            ]
        );
    }

    /**
     * After get item data test function
     */
    public function testAfterGetItemData()
    {
        $sdeMaskImage = 'https://staging3.office.fedex.com/pub/media/sde/default/sde_mask.png';
        $result = [
            'url' => 'https//:magento.com'
        ];
        $resultWithMaskImage = [
            'url' => 'https//:magento.com',
            'product_image' => [
                'src' => $sdeMaskImage
                ]
        ];
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);
        $this->sdeHelperMock->expects($this->any())
            ->method('getSdeMaskSecureImagePath')
            ->willReturn($sdeMaskImage);
        
        $this->assertEquals($resultWithMaskImage, $this->minicartItem->afterGetItemData($this->minicartItem, $result));
    }
}
