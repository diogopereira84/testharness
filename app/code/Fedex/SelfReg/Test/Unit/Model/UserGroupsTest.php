<?php
namespace Fedex\SelfReg\Test\Unit\Model;

use Fedex\SelfReg\Api\Data\UserGroupsInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\Model\UserGroups;

class UserGroupsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $manageUserGroup;
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


        $this->manageUserGroup = $this->getMockBuilder(UserGroups::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manageUserGroup = $this->objectManager->getObject(
            UserGroups::class,
            [ ]
        );
    }



    /**
     * @inheritDoc
     */
    public function testGetUserGroupsId()
    {
         $this->assertNull($this->manageUserGroup->getUserGroupsId());
    }

    /**
     * @inheritDoc
     */
    public function testSetUserGroupsId()
    {
        $this->assertIsObject($this->manageUserGroup->setUserGroupsId(2));
    }
    
    /**
     * @inheritDoc
     */
    public function testGetCompanyId()
    {
        $this->assertNull($this->manageUserGroup->getCompanyId());
    }

    /**
     * @inheritDoc
     */
    public function testSetCompanyId()
    {
        $this->assertIsObject($this->manageUserGroup->setCompanyId(2));
    }

    /**
     * @inheritDoc
     */
    public function testGetGroupName()
    {
        $this->assertNull($this->manageUserGroup->getGroupName());
    }

    /**
     * @inheritDoc
     */
    public function testSetGroupName()
    {
        $this->assertIsObject($this->manageUserGroup->setGroupName('test Data'));
    }

    /**
     * @inheritDoc
     */
    public function testGetGroupType()
    {
        $this->assertNull($this->manageUserGroup->getGroupType());
    }

    /**
     * @inheritDoc
     */
    public function testSetGroupType()
    {
        $this->assertIsObject($this->manageUserGroup->setGroupType('folder_permission'));
    }

    /**
     * @inheritDoc
     */
    public function testGetUserList()
    {
        $this->assertNull($this->manageUserGroup->getUserList());
    }

    /**
     * @inheritDoc
     */
    public function testSetUserList()
    {
        $this->assertIsObject($this->manageUserGroup->setUserList('1,2,3'));
    }

    /**
     * @inheritDoc
     */
    public function testGetOrderApprover()
    {
        $this->assertNull($this->manageUserGroup->getOrderApprover());
    }

    /**
     * @inheritDoc
     */
    public function testSetOrderApprover()
    {
         $this->assertIsObject($this->manageUserGroup->setOrderApprover('1, 2, 3'));
    }

    /**
     * @inheritDoc
     */
    public function testGetCreatedAt()
    {
         $this->assertNull($this->manageUserGroup->getCreatedAt());
    }

    /**
     * @inheritDoc
     */
    public function getSetCreatedAt()
    {
        $this->assertIsObject($this->manageUserGroup->setCreatedAt('12/01/2023'));
    }

    /**
     * @inheritDoc
     */
    public function testSetCreatedAt()
    {
        $this->assertIsObject($this->manageUserGroup->setCreatedAt('12/01/2023'));
    }

    /**
     * @inheritDoc
     */
    public function testGetUpdatedAt()
    {
         $this->assertNull($this->manageUserGroup->getUpdatedAt());
    }

    /**
     * @inheritDoc
     */
    public function testSetUpdatedAt()
    {
        $this->assertIsObject($this->manageUserGroup->setUpdatedAt('12/01/2023'));
    }
}