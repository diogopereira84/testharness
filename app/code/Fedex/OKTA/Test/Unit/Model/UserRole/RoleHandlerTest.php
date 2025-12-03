<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\UserRole;

use Exception;
use Fedex\OKTA\Model\UserRole\RoleHandler;
use Magento\Authorization\Model\ResourceModel\Role\Collection;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\Role;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config;
use Magento\Authorization\Model\RulesFactory;
use Magento\Authorization\Model\Rules;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;

class RoleHandlerTest extends TestCase
{
    /**
     * @var RoleFactory|MockObject
     */
    private RoleFactory $roleFactoryMock;

    /**
     * @var RulesFactory|MockObject
     */
    private RulesFactory $rulesFactoryMock;

    /**
     * @var RoleCollectionFactory|MockObject
     */
    private RoleCollectionFactory $roleCollectionFactoryMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface $scopeConfigMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private ConfigWriter $configWriterMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private SerializerInterface $serializerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $loggerMock;

    /**
     * @var array
     */
    private array $rolesMock = [
        'Super_Admin' =>
            [
                'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_superadmin_test_app3537131',
            ],
        'Marketing' =>
            [
                'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_marketing_test_app3537131',
            ],
        'DPM' =>
            [
                'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_dpm_test_app3537131',
            ],
        'CTC' =>
            [
                'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_ctc_test_app3537131',
            ],
        'Sales' =>
            [
                'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_sales_test_app3537131',
            ],
        'Product' =>
            [
                'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => '
                        fxo_ecommerce_product_test_app3537131
                    ',
            ],
        'Customer_Service' =>
            [
                'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_customerservice_test_app3537131',
            ],
    ];

    /**
     * @var array
     */
    private array $newMagentoRules =  [
        'Super_Admin' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_superadmin_test_app3537131',
            ],
        'Marketing_Read_Only' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_marketing_readonly_test_app3537131',
            ],
        'Marketing_Limited_Write' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_marketing_limitedwrite_test_app3537131',
            ],
        'Marketing_Super_User' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_marketing_superuser_test_app3537131',
            ],
        'DPM_Ready_Only' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_dpm_readonly_test_app3537131',
            ],
        'DPM_Limited_Write' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_dpm_limitedwrite_test_app3537131',
            ],
        'DPM_Super_User' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_dpm_superuser_test_app3537131',
            ],
        'CTC_Read_Only' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_ctc_readonly_test_app3537131',
            ],
        'CTC_Limited_Write' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_ctc_limitedwrite_test_app3537131',
            ],
        'CTC_Super_User' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_ctc_superuser_test_app3537131',
            ],
        'Sales_Read_Only' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_sales_readonly_test_app3537131',
            ],
        'Sales_Limited_Write' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_sales_limitedwrite_test_app3537131',
            ],
        'Sales_Super_User' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_sales_superuser_test_app3537131',
            ],
        'Product_Read_Only' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_product_readonly_test_app3537131',
            ],
        'Product_Limited_Write' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_product_limitedwrite_test_app3537131',
            ],
        'Product_Super_User' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_product_superuser_test_app3537131',
            ],
        'Customer_Service_Read_Only' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_customerservice_readonly_test_app3537131',
            ],
        'Customer_Service_Limited_Write' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_customerservice_limitedwrite_test_app3537131',
            ],
        'Customer_Service_Super_User' =>
             [
                 'resources' => 'Dotdigitalgroup_B2b::quotes,Dotdigitalgroup_B2b::reports',
                'role' => 'fxo_ecommerce_customerservice_superuser_test_app3537131',
            ],
    ];

    /**
     * @var array
     */
    private array $existingRoles =  [
        '_16509763181_181' =>
             [
                'internal_role' => '1',
                'external_group' => 'fxo_ecommerce_superadmin_app3537131',
            ],
        '_16509763185505_505' =>
             [
                'internal_role' => '5505',
                'external_group' => 'fxo_ecommerce_marketing_readonly_test_app3537131',
            ],
    ];

    /**
     * @var RoleHandler
     */
    private RoleHandler $roleHandler;

    /**
     * @var Collection|MockObject
     */
    private Collection $roleCollectionMock;

    private Select $selectMock;
    private Role $roleMock;
    private Rules $rulesMock;

    protected function setUp(): void
    {
        $this->roleFactoryMock = $this->createMock(RoleFactory::class);
        $this->roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'load',
                'setData',
                'setName',
                'setPid',
                'setRoleType',
                'setUserType',
                'getData',
                'getId',
                'getRoleType',
                'save'
            ])->getMock();
        $this->rulesFactoryMock = $this->createMock(RulesFactory::class);
        $this->rulesMock = $this->getMockBuilder(Rules::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRoleId', 'setResources', 'saveRel'])
            ->getMock();
        $this->roleCollectionFactoryMock = $this->createMock(RoleCollectionFactory::class);
        $this->roleCollectionMock = $this->createMock(Collection::class);
        $this->selectMock = $this->createMock(Select::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->roleMock->expects($this->any())->method('load')->willReturn($this->roleMock);
        $this->roleMock->expects($this->any())->method('setName')->willReturn($this->roleMock);
        $this->roleMock->expects($this->any())->method('setPid')->willReturn($this->roleMock);
        $this->roleMock->expects($this->any())->method('setRoleType')->willReturn($this->roleMock);
        $this->roleMock->expects($this->any())->method('getId')->willReturn(1);
        $this->roleMock->expects($this->any())->method('setUserType')->willReturn($this->roleMock);
        $this->roleMock->expects($this->any())->method('getData')->willReturn(true);
        $this->roleMock->expects($this->any())->method('save')->willReturn(true);
        $this->roleFactoryMock->expects($this->any())->method('create')->willReturn($this->roleMock);

        $this->rulesMock->expects($this->any())->method('setRoleId')->willReturn($this->rulesMock);
        $this->rulesMock->expects($this->any())->method('setResources')->willReturn($this->rulesMock);
        $this->rulesMock->expects($this->any())->method('saveRel')->willReturn('');
        $this->rulesFactoryMock->expects($this->any())->method('create')->willReturn($this->rulesMock);

        $this->roleHandler = new RoleHandler(
            $this->roleFactoryMock,
            $this->rulesFactoryMock,
            $this->roleCollectionFactoryMock,
            $this->scopeConfigMock,
            $this->configWriterMock,
            $this->serializerMock,
            $this->loggerMock
        );
    }

    public function testProcessRole(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('fedex_okta/magento_roles', ScopeInterface::SCOPE_STORE)
            ->willReturn($this->rolesMock);
        $this->roleCollectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->once())
            ->method('getSelect')
            ->willReturn($this->selectMock);
        $this->roleCollectionMock
            ->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $this->roleMock->setData(['role_id' => 1, 'role_type' => 'G', 'parent_id' => null]);

        $this->roleFactoryMock->expects($this->any())->method('create')->willReturn($this->roleMock);
        $this->roleCollectionMock
            ->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->roleMock);

        $this->assertTrue($this->roleHandler->processRole());
    }

    public function testProcessNewAfterDeleteRole(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('fedex_okta/magento_new_roles', ScopeInterface::SCOPE_STORE)
            ->willReturn($this->newMagentoRules);

        $this->assertTrue($this->roleHandler->processNewAfterDeleteRole());
    }

    public function testOptimizeNewRoles(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('fedex_okta/magento_new_roles', ScopeInterface::SCOPE_STORE)
            ->willReturn($this->newMagentoRules);
        $this->roleCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getSelect')
            ->willReturn($this->selectMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('count')
            ->willReturn(1);
        $this->roleMock->setData(['role_id' => 1, 'role_type' => 'G', 'parent_id' => null]);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->roleMock);

        $this->assertTrue($this->roleHandler->optimizeNewRoles());
    }

    public function testProcessNewRole(): void
    {
        $this->scopeConfigMock
            ->expects($this->any())
            ->method('getValue')
            ->withConsecutive(
                ['fedex_okta/magento_new_roles', ScopeInterface::SCOPE_STORE],
                ['fedex_okta/backend/roles', ScopeInterface::SCOPE_STORE]
            )->willReturnOnConsecutiveCalls($this->newMagentoRules, $this->existingRoles);
        $this->roleCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getSelect')
            ->willReturn($this->selectMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('count')
            ->willReturn(1);
        $this->roleMock->setData(['role_id' => 1, 'role_type' => 'G', 'parent_id' => null]);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->roleMock);
        $this->serializerMock->expects($this->once())->method('unserialize')->willReturn($this->existingRoles);

        $this->assertTrue($this->roleHandler->processNewRole());
    }

    public function testUpdateProductRolePermission(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('fedex_okta/magento_roles', ScopeInterface::SCOPE_STORE)
            ->willReturn($this->rolesMock);
        $this->roleCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getSelect')
            ->willReturn($this->selectMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('count')
            ->willReturn(1);
        $this->roleMock->setData(['role_id' => 1, 'role_type' => 'G', 'parent_id' => null]);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->roleMock);

        $this->assertTrue($this->roleHandler->updateProductRolePermission());
    }

    public function testUpdateProductRolePermissionFailure(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with('fedex_okta/magento_roles', ScopeInterface::SCOPE_STORE)
            ->willReturn($this->rolesMock);
        $this->roleCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getSelect')
            ->willReturn($this->selectMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('count')
            ->willReturn(1);
        $this->roleMock->setData(['role_id' => 1, 'role_type' => 'G', 'parent_id' => null]);
        $this->roleMock->expects($this->any())
            ->method('getData')
            ->with('role_id')
            ->willReturn(false);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->roleMock);

        $this->assertTrue($this->roleHandler->updateProductRolePermission());
    }

    public function testDeleteRoleFromNameInvalidName()
    {
        $this->assertFalse($this->roleHandler->deleteRoleFromName(null));
    }

    public function testDeleteRoleFromNameInvalidRole()
    {
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('count')
            ->willReturn(0);
        $this->roleCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getSelect')
            ->willReturn($this->selectMock);
        $this->assertFalse($this->roleHandler->deleteRoleFromName('Customer_Service_Read_Only'));
    }

    public function testDeleteRoleFromNameInvalidRoleLoad()
    {
        $this->roleCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->roleCollectionMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('getSelect')
            ->willReturn($this->selectMock);
        $this->roleCollectionMock
            ->expects($this->any())
            ->method('count')
            ->willReturn(1);
        $this->roleMock->expects($this->any())->method('getId')->willReturn(1);
        $this->assertFalse($this->roleHandler->deleteRoleFromName('Customer_Service_Read_Only'));
    }

    public function testSaveRoleException(): void
    {
        $class = new \ReflectionClass($this->roleHandler);
        $method = $class->getMethod('saveRole');
        $method->setAccessible(true);
        $this->rulesFactoryMock->expects($this->once())
            ->method('create')
            ->willThrowException(new Exception(''));
        $this->loggerMock->expects($this->once())->method('critical');
        $method->invokeArgs($this->roleHandler, [
            'rid' => false,
            'roleName' => 'test',
            'resource' => 'test',
        ]);
    }
}
