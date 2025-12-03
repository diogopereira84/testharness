<?php
namespace Fedex\UpdateTitle\UnitTest\Test\Unit\Plguin;

use Fedex\Customer\Api\Data\ConfigInterface;
use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Fedex\Customer\Api\SalesForceInterface;
use Fedex\Customer\Model\SalesForce\SubscribePublisher;
use Fedex\Customer\Plugin\MarketingOptIn;
use Fedex\SubmitOrderSidebar\Controller\Quote\SubmitOrderOptimized;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarketingOptInTest extends TestCase {

    /**
     * @var MarketingOptIn|object
     */
    protected $marketingOptInPluginMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected RequestInterface $requestMock;

    /**
     * @var ConfigInterface|MockObject
     */
    protected ConfigInterface $configMock;

    /**
     * @var SubscribePublisher|MockObject
     */
    protected SubscribePublisher $salesForceSubscribePublisherMock;

    /**
     * @var SubmitOrderOptimized|MockObject
     */
    protected SubmitOrderOptimized $submitOrderOptimizedMock;

    /**
     * @var JsonFactory|MockObject
     */
    protected JsonFactory $resultJsonFactory;

    /**
     * @var JsonValidator|MockObject
     */
    protected JsonValidator $jsonValidatorMock;

    /**
     * @var Json|MockObject
     */
    protected Json $jsonMock;

    /**
     * @var SalesForceResponseInterface|MockObject
     */
    protected SalesForceResponseInterface $salesForceResponseMock;

    /**
     * Set up
     *
     * @return void
     */

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->getMockForAbstractClass();

        $this->configMock = $this->getMockBuilder(ConfigInterface::class)
            ->setMethods(['isMarketingOptInEnabled'])
            ->getMockForAbstractClass();

        $this->salesForceSubscribePublisherMock = $this->createMock(SubscribePublisher::class);

        $this->submitOrderOptimizedMock = $this->createMock(SubmitOrderOptimized::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->jsonValidatorMock = $this->createMock(JsonValidator::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->salesForceResponseMock = $this->createMock(SalesForceResponseInterface::class);

        $objectManger = new ObjectManager($this);
        $this->marketingOptInPluginMock = $objectManger->getObject(
            MarketingOptIn::class,
            [
                'request' => $this->requestMock,
                'config' => $this->configMock,
                'salesForceSubscribePublisher' => $this->salesForceSubscribePublisherMock,
                'jsonValidator' => $this->jsonValidatorMock,
                'json' => $this->jsonMock
            ]
        );
    }

    /**
     * Test unitTest function
     */

    public function testAfterExecute()
    {
        $postData = '{"marketingOptIn":"{\\"emailAddress\\":\\"avo@salesforce.com.br\\",\\"countryCode\\":\\"US\\",\\"languageCode\\":\\"EN\\",\\"firstName\\":\\"Albert\\",\\"lastName\\":\\"Vo\\",\\"companyName\\":\\"Salesforce\\",\\"streetAddress\\":\\"1234 Salesforce Rd.\\",\\"cityName\\":\\"Plano\\",\\"stateProvince\\":\\"TX\\",\\"postalCode\\":\\"75074\\",\\"industry\\":\\"Technology\\",\\"companySize\\":\\"1000+\\"}"}';
        $requestBody = [
            "marketingOptIn" => '{"emailAddress":"avo@salesforce.com.br","countryCode":"US","languageCode":"EN","firstName":"Albert","lastName":"Vo","companyName":"Salesforce","streetAddress":"1234 Salesforce Rd.","cityName":"Plano","stateProvince":"TX","postalCode":"75074","industry":"Technology","companySize":"1000+"}'
        ];

        $marketingOptInString = '{"emailAddress":"avo@salesforce.com.br","countryCode":"US","languageCode":"EN","firstName":"Albert","lastName":"Vo","companyName":"Salesforce","streetAddress":"1234 Salesforce Rd.","cityName":"Plano","stateProvince":"TX","postalCode":"75074","industry":"Technology","companySize":"1000+"}';
        $marketingOptInArray = [
            "emailAddress" => "avo@salesforce.com.br",
            "countryCode" => "US",
            "languageCode" => "EN",
            "firstName" => "Albert",
            "lastName" => "Vo",
            "companyName" => "Salesforce",
            "streetAddress" => "1234 Salesforce Rd.",
            "cityName" => "Plano",
            "stateProvince" => "TX",
            "postalCode" => "75074",
            "industry" => "Technology",
            "companySize" => "1000+"
        ];

        $this->jsonValidatorMock->expects($this->atMost(2))->method('isValid')
            ->withConsecutive([$postData], [$marketingOptInString])->willReturnOnConsecutiveCalls(true, true);
        $this->jsonMock->expects($this->atMost(2))->method('unserialize')
            ->withConsecutive([$postData], [$marketingOptInString])->willReturnOnConsecutiveCalls($requestBody, $marketingOptInArray);

        $this->configMock->expects($this->once())->method('isMarketingOptInEnabled')->willReturn(true);
        $this->requestMock->expects($this->once())->method('getPost')->with('data')
            ->willReturn($postData);
        $this->salesForceSubscribePublisherMock->expects($this->once())->method('execute')
            ->with($marketingOptInArray)->willReturnSelf();

        $result = $this->marketingOptInPluginMock->afterExecute(
            $this->submitOrderOptimizedMock,
            ['request' => true]
        );
        $this->assertIsArray($result);
        $this->assertEquals(['request' => true], $result);
    }
}
