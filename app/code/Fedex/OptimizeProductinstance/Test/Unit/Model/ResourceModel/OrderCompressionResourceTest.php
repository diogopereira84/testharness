<?php
namespace Fedex\OptimizeProductinstance\Test\Unit\Model;

use Fedex\OptimizeProductinstance\Model\ResourceModel\OrderCompression;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

class OrderCompressionResourceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
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

        $this->quoteCompression    = $this->objectManager->getObject(
            OrderCompression::class,
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
