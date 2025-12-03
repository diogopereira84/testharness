<?php

namespace Fedex\Company\Test\Unit\Block;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\Company\Block\Adminhtml\AuthenticationRule;
use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\AuthDynamicRowsFactory;
use Fedex\Company\Model\AuthDynamicRows;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Request\Http;
use Fedex\Company\Model\ResourceModel\AuthDynamicRows\Collection;

class AuthenticationRuleTest extends TestCase
{


    protected $authDynamicRowsMock;
    protected $companyRepositoryMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $blockAuthenticationRule;
    /**
     * Sample ID
     * @var int
     */
    const ID = 2;

    /**
     * @var
     */

     protected $authDynamicRowsCollection;

    /**
     * @var
     */
    private $contextMock;

     /**
      * @var
      */
    private $authDynamicRowsFactoryMock;

    /**
     * @var
     */
    private $companyRepository;
    
    /**
     * @var
     */
    private $company;
     /**
      * @var
      */
    protected $AuthenticationRule;
    
     /**
      * @var Http|MockObject
      */
    private $requestMock;

    /**
     * @var
     */
    private $actioncontext;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {

        $this->authDynamicRowsCollection = $this
            ->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authDynamicRowsMock = $this->createMock(AuthDynamicRows::class);
        $this->authDynamicRowsFactoryMock = $this->createMock(AuthDynamicRowsFactory::class, ['create']);//B-1326233
        $this->authDynamicRowsFactoryMock->expects($this->any())->method('create')->will($this->returnValue($this->authDynamicRowsMock));

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();


        $this->actioncontext = $this->getMockBuilder(Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
       
        $this->contextMock = $this->objectManager->getObject(
            Context::class,
            [
                'request' => $this->requestMock,
            ]
        );

        $this->blockAuthenticationRule = $this->objectManager->getObject(
            AuthenticationRule::class,
            [
                'context' => $this->contextMock,
                'ruleFactory' => $this->authDynamicRowsFactoryMock,
                'companyRepository' => $this->companyRepositoryMock
            ]
        );
    }

    /**
     * Test for getRules method.
     * @return void
     */
    public function testGetRules()
    {

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['id', null, 2]
                ]
            );
        $filters = "extrinsic";

        $authDynamicRowsCollection =
            $this->createMock(\Fedex\Company\Model\ResourceModel\AuthDynamicRows\Collection::class);
        
        $this->authDynamicRowsMock->expects($this->once())->method('getCollection')->will($this->returnValue($authDynamicRowsCollection));
 
        $authDynamicRowsCollection->expects($this->once())->method('addFieldToSelect')->will($this->returnSelf());
        $authDynamicRowsCollection->expects($this->any())->method('addFieldToFilter')->will($this->returnSelf());
        
 
        $results = $this->blockAuthenticationRule->getRules($filters);
        $this->assertEquals($authDynamicRowsCollection, $results);
    }

    /**
     * Test for displayBlock method.
     * @return void
     */
    public function testDisplayBlock()
    {
            
        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn(8);
        $expected =  ['contact', 'commercial_store_sso'];
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAcceptanceOption', 'getIsStorefrontLoginMethod', 'getStorefrontLoginMethodOption'])
            ->getMockForAbstractClass();

        $this->companyRepositoryMock->expects($this->any())->method('get')->with(8)->willReturn($company);
        $company->expects($this->once())->method('getAcceptanceOption')->willReturn('contact');
        $company->expects($this->any())->method('getIsStorefrontLoginMethod')->willReturn(0);
        $company->expects($this->any())->method('getStorefrontLoginMethodOption')->willReturn('commercial_store_sso');
        
        $this->assertEquals($expected, $this->blockAuthenticationRule->displayBlock());
    }

    /**
     * B-1013340 | Anuj | RT-ECVS-Resolve PHPUnit Console Errors for module 'Company'
     * Test for displayBlock method.
     * @return void
     */
    public function testDisplayBlockwithBoth()
    {
            
        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn(8);
        $expected =  ['contact', 'extrinsic', 'commercial_store_sso'];
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAcceptanceOption', 'getIsStorefrontLoginMethod', 'getStorefrontLoginMethodOption'])
            ->getMockForAbstractClass();

        $this->companyRepositoryMock->expects($this->any())->method('get')->with(8)->willReturn($company);
        $company->expects($this->any())->method('getAcceptanceOption')->willReturn('both');
        $company->expects($this->any())->method('getIsStorefrontLoginMethod')->willReturn(1);
        $company->expects($this->any())->method('getStorefrontLoginMethodOption')->willReturn('commercial_store_sso');

        
        $this->assertEquals($expected, $this->blockAuthenticationRule->displayBlock());
    }
}
