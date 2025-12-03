<?php
namespace Fedex\FXOCMConfigurator\Test\Unit\Model;

use Fedex\FXOCMConfigurator\Model\Userworkspace;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FXOCMConfigurator\Model\OptimizeProductInstanceMessage;
use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace as UserworkspaceResourceModel;
use Magento\Framework\Model\AbstractModel;

class UserworkspaceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
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


        $this->userworkspace = $this->getMockBuilder(Userworkspace::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userworkspace = $this->objectManager->getObject(
            Userworkspace::class,
            [ ]
        );
    }



    /**
     * @inheritDoc
     */
    public function testGetUserworkspaceId()
    {
         $this->assertNull($this->userworkspace->getUserworkspaceId());
    }

    /**
     * @inheritDoc
     */
    public function testSetUserworkspaceId()
    {
        $this->assertIsObject($this->userworkspace->setUserworkspaceId(2));
    }

    /**
     * @inheritDoc
     */
    public function testGetCustomerId()
    {
        $this->assertNull($this->userworkspace->getCustomerId());
    }

    /**
     * @inheritDoc
     */
    public function testSetCustomerId()
    {
        $this->assertIsObject($this->userworkspace->setCustomerId(2));
    }

    /**
     * @inheritDoc
     */
    public function testGetWorkspaceData()
    {
        $this->assertNull($this->userworkspace->getWorkspaceData());
    }

    /**
     * @inheritDoc
     */
    public function testSetWorkspaceData()
    {
        $this->assertIsObject($this->userworkspace->setWorkspaceData('test Data'));
    }

    /**
     * @inheritDoc
     */
    public function testGetApplicationType()
    {
        $this->assertNull($this->userworkspace->getApplicationType());
    }

    /**
     * @inheritDoc
     */
    public function testSetApplicationType()
    {
        $this->assertIsObject($this->userworkspace->setApplicationType('epro'));
    }

    /**
     * @inheritDoc
     */
    public function testGetOldUploadDate()
    {
        $this->assertNull($this->userworkspace->getOldUploadDate());
    }

    /**
     * @inheritDoc
     */
    public function testSetOldUploadDate()
    {
        $this->assertIsObject($this->userworkspace->setOldUploadDate('12/01/2023'));
    }

    /**
     * @inheritDoc
     */
    public function testGetCreatedAt()
    {
        $this->assertNull($this->userworkspace->getCreatedAt());
    }

    /**
     * @inheritDoc
     */
    public function testSetCreatedAt()
    {
         $this->assertIsObject($this->userworkspace->setCreatedAt('12/01/2023'));
    }

    /**
     * @inheritDoc
     */
    public function testGetUpdatedAt()
    {
         $this->assertNull($this->userworkspace->getUpdatedAt());
    }

    /**
     * @inheritDoc
     */
    public function testSetUpdatedAt()
    {
        $this->assertIsObject($this->userworkspace->setUpdatedAt('12/01/2023'));
    }
}
