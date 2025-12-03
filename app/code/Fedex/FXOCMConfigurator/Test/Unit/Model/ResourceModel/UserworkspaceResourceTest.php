<?php
namespace Fedex\FXOCMConfigurator\Test\Unit\Modely\ResourceModel;

use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

class UserworkspaceResourceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var object
     */
    protected $userworkspace;
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

        $this->userworkspace    = $this->objectManager->getObject(
            Userworkspace::class,
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
