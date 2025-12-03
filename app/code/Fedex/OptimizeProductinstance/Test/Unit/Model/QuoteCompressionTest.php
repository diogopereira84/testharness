<?php
namespace Fedex\OptimizeProductinstance\Test\Unit\Model;

use Fedex\OptimizeProductinstance\Model\QuoteCompression;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\OptimizeProductinstance\Model\OptimizeProductInstanceMessage;
use Fedex\OptimizeProductinstance\Model\ResourceModel\QuoteCompression as QuoteCompressionResourceModel;
use Magento\Framework\Model\AbstractModel;

class QuoteCompressionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Fedex\OptimizeProductinstance\Model\ResourceModel\QuoteCompression & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteCompressionResourceModel;
    /**
     * @var object
     */
    protected $quoteCompression;
    /**
     * @var string
     */
    protected $message;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->quoteCompressionResourceModel  = $this->getMockBuilder(QuoteCompressionResourceModel::class)
        ->setMethods(['getMessage'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->quoteCompression    = $this->objectManager->getObject(
            QuoteCompression::class,
            []
        );
    }
    /**
     * Test nill.
     *
     * @return null
     */
    public function testnull()
    {}
}
