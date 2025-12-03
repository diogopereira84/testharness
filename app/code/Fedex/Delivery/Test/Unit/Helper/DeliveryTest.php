<?php
namespace Fedex\Delivery\Test\Unit\Helper;

use Fedex\Delivery\Helper\Delivery;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\Delivery\Helper\ShippingDataHelper;
use Magento\Framework\App\RequestInterface;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\OrderApprovalB2b\ViewModel\ReviewOrderViewModel;

class DeliveryTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $regionFactory;
    protected $companyHelper;
    protected $companyMock;
    protected $cartFactory;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    protected $retailHelper;
    protected $curl;
    protected $cartMock;
    protected $quoteMock;
    protected $itemMock;
    protected $_optionInterface;
    protected $region;
    protected $abstractHelper;
    protected $Address;
    protected $sdeHelper;
    protected $toggleConfig;
    protected $cartDataHelperMock;
    protected $requestObj;
    protected $quoteHelper;
    protected $companyRepository;
    protected $inBranchValidation;
    protected $configInterfaceMock;
    protected $reviewOrderViewModel;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $data;
    public const DELIVERY_API = 'https://apitest.fedex.com/order/fedexoffice/v2/deliveryoptions';
    public const DISPLAY_TEXT = 'Direct Signature Required';
    public const SERVICE_DESCRIPTION = 'FedEx Express Saver';
    public const ESTIMATED_SHIPMENT_RATE = '21.82';
    public const ESTIMATED_SHIPMENT_DATE = '2022-09-16';
    public const ESTIMATED_DELIVERY_LOCAL_TIME = 'Thursday, September 22 4:30 am';
    public const ESTIMATED_TIME = "2021-06-06 04:00:00";

    /**
     * @var ShippingDataHelper $shippingDataHelper
     */
    protected $shippingDataHelper;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionFactory = $this->getMockBuilder(\Magento\Directory\Model\RegionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyHelper = $this->getMockBuilder(\Fedex\Company\Helper\Data::class)
            ->setMethods(['getPaymentMethod', 'getCompanyPaymentMethod','getCustomerCompany'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getShippingAccountNumber', 'getRecipientAddressFromPo', 'getStorefrontLoginMethodOption', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartFactory = $this->getMockBuilder(\Magento\Checkout\Model\CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->retailHelper = $this->getMockBuilder(\Fedex\Delivery\Helper\Data::class)
            ->setMethods(['isCommercialCustomer', 'getAllowedDeliveryOptions', 'getRateRequestShipmentSpecialServices'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->curl = $this->getMockBuilder(\Magento\Framework\HTTP\Client\Curl::class)
            ->setMethods(['post', 'getBody'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartMock = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOptionByCode', 'getQty'])
            ->getMockForAbstractClass();

        $this->_optionInterface = $this
            ->getMockBuilder(\Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->region = $this->getMockBuilder(\Magento\Directory\Model\Region::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractHelper = $this->getMockBuilder(\Magento\Framework\Model\AbstractModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getId'])
            ->getMock();
        $this->Address = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartDataHelperMock = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['decryptData','encryptData', 'isCommercialCustomer', 'checkQuotePriceableDisable', 'setAddressClassification'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestObj = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()
            ->setMethods(['getContent', 'getPost'])
            ->getMockForAbstractClass();
        $this->quoteHelper = $this->createMock(QuoteHelper::class);
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->shippingDataHelper = $this->getMockBuilder(ShippingDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRetailOnePShippingMethods'])
            ->getMock();

        $this->inBranchValidation = $this->getMockBuilder(InBranchValidation::class)
            ->disableOriginalConstructor()
            ->setMethods(['isInBranchUser','getAllowedInBranchLocation','isInBranchProductWithContentAssociationsEmpty'])
            ->getMock();

        $this->configInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue','isSetFlag'])
            ->getMock();

        $this->reviewOrderViewModel = $this->getMockBuilder(ReviewOrderViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isOrderApprovalB2bEnabled',
                'getPendingOrderQuoteId',
                'getQuoteObj'
            ])->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->data = $this->objectManager->getObject(
            Delivery::class,
            [
                'context' => $this->contextMock,
                'cartFactory' => $this->cartFactory,
                'companyHelper' => $this->companyHelper,
                'logger' => $this->logger,
                'cartFactory' => $this->cartFactory,
                'regionFactory' => $this->regionFactory,
                'retailHelper' => $this->retailHelper,
                'curl' => $this->curl,
                'sdeHelper' => $this->sdeHelper,
                'toggleConfig' => $this->toggleConfig,
                'cartDataHelper' => $this->cartDataHelperMock,
                'quoteHelper' => $this->quoteHelper,
                'shippingDataHelper' => $this->shippingDataHelper,
                'requestObj' => $this->requestObj,
                'inBranchValidation' =>$this->inBranchValidation,
                'configInterface' => $this->configInterfaceMock,
                'reviewOrderViewModel' => $this->reviewOrderViewModel
            ]
        );
    }
    /**
     * Test getDeliveryOptions.
     */
    public function testGetDeliveryOptions()
    {
        $config =
            [
                'token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2',
                'delivery_api' => self::DELIVERY_API,
                'street' => 'hn 34',
                'country_id' => 'US',
                'region_id' => 57,
                'postcode' => '5024',
                'city' => 'plano',
                'site' => 'testeprosite',
                'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xp
            ZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2MTQxMTI4NTcsImF1dGhvcml0aWVzIjpbIm1h
            Z2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6IjcxMzA3ZjY3
            LTE1NDktNDdiMS04Mjg0LWQwYzZkYTk1NDEzMSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19Q
            T0RfU0VSVklDRSJ9.jQ1ozMb-TfEFPFCZmAbETAAsxPtqNuHCtIZNQ2uc8fgRz4NjAZZ7DxpQyfOTrlo
            7eoRlykXK8IkeKkePnc0y3pX2KIVE1AnRhlqrAheYkKmROed2HQmajmAIEI-34xR_aof1SMZ38VJNCPE
            S1IDKOcu2Zxw7GBvgt36dMlVrwQeRIvDyoILbYVr-z4DlcqHwWFGyLXZzbpEBkVrM7cjxVDEl_wSWutC
            d73Kxi9Qq8vrKiGqYIJQKFb7ZwcmT0hOTMpf4panPoFYv_bfdRoOSZHbGX2CS5tB0egFbSXCZo47ydKP
            SW8giCcrGsmnAECWSeEndgCssEPKXWIh0FHxtjPklsN3IU-Cm2JNrMEtfNh0A14zf7yJa3Hp3rlOZf8S
            6LdQdgmPe5bSr_0YnwefFnQwEooiUlFJtboVHatRnrYJZrTHIT-vBZwvbq1PEy1RO2O29qaYpGCP_WDH
            -nqOyJD-IoCV2wrQ449SsISsKujRRK6cYCKoDp4TFmwKTQ3iakZKimLQ71Zsb1ClqxjE6THawQC42EsI
            LKgL3ay7cWj9Qj5-W7eheF7eUR3LOmhstV5cEMEoVs5Vh1E0LM86k32Vmipe4HvFycZiR_AeEKil5W7J
            LrwIMdCrwBJaSi-UuMape1raijKgQc8ZHEgDdBqydT2JDadK1fZR-MG9TdoU',
                'token_type' => 'Bearer',
            ];
        $output = '{
            "transactionId" : "696c0a47-ee79-4e7b-95e6-4d4697c41254",
            "output" : {
              "deliveryOptions" : [ {
                "deliveryReference" : "default",
                "shipmentOptions" : [ {
                  "serviceType" : "LOCAL_DELIVERY_AM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T12:00:00",
                  "priceable" : true
                }, {
                  "serviceType" : "LOCAL_DELIVERY_PM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T17:00:00",
                  "priceable" : true
                } ]
              } ]
            }
          }';
          $addressData = [
            'address' => [
                'custom_attributes' => [
                    ['attribute_code' => 'residence_shipping', 'value' => true]
                ]
            ]
        ];
        $allowedDeliveryOptions = ['LOCAL_DELIVERY_AM' => 0, 'LOCAL_DELIVERY_PM' => 1];
        $this->requestObj->expects($this->any())
                       ->method('getContent')
                       ->willReturn(json_encode($addressData));
        $this->companyHelper->expects($this->any())->method('getCustomerCompany')
            ->willReturn($this->companyMock);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');
        $this->companyMock->expects($this->any())->method('getRecipientAddressFromPo')->willReturn(1);

        $this->companyHelper->expects($this->any())->method('getCustomerCompany')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getId')->willReturn(2);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);

        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->Address);
        $this->Address->expects($this->any())->method('getData')->with('company')->willReturn('walmart');
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->itemMock]);
        $this->inBranchValidation->expects($this->any())->method('isInBranchUser')->willReturn(true);
        $this->inBranchValidation->expects($this->any())->method('getAllowedInBranchLocation')->willReturn('38017');
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);

        $this->reviewOrderViewModel->expects($this->once())
            ->method('isOrderApprovalB2bEnabled')
            ->willReturn(true);
        $this->reviewOrderViewModel->expects($this->any())
            ->method('getPendingOrderQuoteId')
            ->willReturn('1234');
        $this->reviewOrderViewModel->expects($this->once())
            ->method('getQuoteObj')
            ->willReturn($this->quoteMock);
        $this->region->expects($this->any())
            ->method('load')
            ->willReturn($this->abstractHelper);
        $this->abstractHelper->expects($this->any())
            ->method('getCode')
            ->willReturn("TX");
        $this->abstractHelper->expects($this->any())
            ->method('getId')
            ->willReturn(4);
        $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->_optionInterface);
        $prodarray = [];
        $prodarray['external_prod'][0] = [
            'instanceId' => 0,
            'catalogReference' => 1,
            'preview_url' => 'url',
            'fxo_product' => 'product',
        ];

        $this->_optionInterface
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));
        $this->itemMock->expects($this->any())->method('getQty')->willReturn(2);
        $this->retailHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        //For non sde signature options should not be included
        $this->retailHelper->expects($this->any())->method('getRateRequestShipmentSpecialServices')->willReturn([]);

        $this->companyHelper->expects($this->any())->method('getCompanyPaymentMethod')
            ->willReturn('fedexaccountnumber');
        $this->curl->expects($this->any())->method('getBody')->willreturn($output);
        $this->retailHelper->expects($this->any())->method('getAllowedDeliveryOptions')
            ->willReturn($allowedDeliveryOptions);
        $this->quoteHelper->expects($this->any())->method('isFullMiraklQuote')->willReturn(false);
        $this->data->GetDeliveryOptions($config);
    }

    /**
     * Test getDeliveryOptions.
     *
     * @return array
     */
    public function testGetDeliveryOptions2()
    {
        $config =
            [
                'token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2',
                'delivery_api' => self::DELIVERY_API,
                'street' => 'h0 34',
                'country_id' => 'US',
                'region_id' => 57,
                'postcode' => '7502',
                'city' => 'plano',
                'site' => 'testeprosite',
                'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0
            YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2MTQxMTI4NTcsImF1dGhv
            cml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0
            Il0sImp0aSI6IjcxMzA3ZjY3LTE1NDktNDdiMS04Mjg0LWQwYzZkYTk1NDEzMSIsImNsaWVu
            dF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.jQ1ozMb-TfEFPFCZmAbETAA
            sxPtqNuHCtIZNQ2uc8fgRz4NjAZZ7DxpQyfOTrlo7eoRlykXK8IkeKkePnc0y3pX2KIVE1An
            RhlqrAheYkKmROed2HQmajmAIEI-34xR_aof1SMZ38VJNCPES1IDKOcu2Zxw7GBvgt36dMlV
            rwQeRIvDyoILbYVr-z4DlcqHwWFGyLXZzbpEBkVrM7cjxVDEl_wSWutCd73Kxi9Qq8vrKiGq
            YIJQKFb7ZwcmT0hOTMpf4panPoFYv_bfdRoOSZHbGX2CS5tB0egFbSXCZo47ydKPSW8giCcr
            GsmnAECWSeEndgCssEPKXWIh0FHxtjPklsN3IU-Cm2JNrMEtfNh0A14zf7yJa3Hp3rlOZf8S
            6LdQdgmPe5bSr_0YnwefFnQwEooiUlFJtboVHatRnrYJZrTHIT-vBZwvbq1PEy1RO2O29qaYp
            GCP_WDH-nqOyJD-IoCV2wrQ449SsISsKujRRK6cYCKoDp4TFmwKTQ3iakZKimLQ71Zsb1Clq
            xjE6THawQC42EsILKgL3ay7cWj9Qj5-W7eheF7eUR3LOmhstV5cEMEoVs5Vh1E0LM86k32Vmi
            pe4HvFycZiR_AeEKil5W7JLrwIMdCrwBJaSi-UuMape1raijKgQc8ZHEgDdBqydT2JDadK1fZ
            R-MG9TdoU',
                'token_type' => 'Bearer',
            ];
        $output = '{
            "transactionId" : "696c0a47-ee79-4e7b-95e6-4d4697c41254",
            "errors" : "6d6ydgd6f6f6udu",
            "outputs" : {
              "deliveryOptions" : [ {
                "deliveryReference" : "default",
                "shipmentOptions" : [ {
                  "serviceType" : "LOCAL_DELIVERY_AM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T12:00:00",
                  "priceable" : true
                }, {
                  "serviceType" : "LOCAL_DELIVERY_PM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T17:00:00",
                  "priceable" : true
                } ]
              } ]
            }
          }';
        $this->companyHelper->expects($this->any())->method('getCustomerCompany')
          ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getId')->willReturn(2);
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->Address);
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->itemMock]);
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())
            ->method('load')
            ->willReturn($this->abstractHelper);
        $this->abstractHelper->expects($this->any())
            ->method('getCode')
            ->willReturn("TX");
        $this->abstractHelper->expects($this->any())
            ->method('getId')
            ->willReturn(4);
        $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->_optionInterface);
        $prodarray = [];
        $prodarray['external_prod'][0] = [
            'instanceId' => 0,
            'catalogReference' => 1,
            'preview_url' => 'url',
            'fxo_product' => 'product',
        ];

        $this->_optionInterface
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));
        $this->itemMock->expects($this->any())->method('getQty')->willReturn(2);
        $this->retailHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);

        $this->quoteMock->expects($this->any())->method('getData')
            ->with('fedex_account_number')
            ->willReturn('0:3:7D96hHguKjU5P5mx3jYOJmrD1+1Pw0AKypX5QYs0JgrR0NIjZg==');
        $this->cartDataHelperMock->expects($this->any())->method('decryptData')->willReturn(12345678);
        $this->companyHelper->expects($this->any())->method('getCompanyPaymentMethod')
            ->willReturn('fedexaccountnumber');
        $this->curl->expects($this->any())->method('getBody')->willreturn($output);
        $this->quoteHelper->expects($this->once())->method('isFullMiraklQuote')->willReturn(true);
        $this->data->GetDeliveryOptions($config);
    }

    public function testAllowedDeliveryOptionsStore() {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->cartDataHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->data->allowedDeliveryOptionsStore();
    }

    /**
     * Test getDeliveryOptions.
     *
     * @return array
     */
    public function testGetDeliveryOptions3()
    {
        $config =
            [
                'token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2',
                'delivery_api' => self::DELIVERY_API,
                'street' => 'hn0 3',
                'country_id' => 'US',
                'region_id' => 57,
                'postcode' => '7524',
                'city' => 'plano',
                'site' => 'testeprosite',
                'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xp
            ZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2MTQxMTI4NTcsImF1dGhvcml0aWVzIjpbIm1h
            Z2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0Il0sImp0aSI6IjcxMzA3ZjY3
            LTE1NDktNDdiMS04Mjg0LWQwYzZkYTk1NDEzMSIsImNsaWVudF9pZCI6IjM1MzcxMzFfTUFHRU5UT19Q
            T0RfU0VSVklDRSJ9.jQ1ozMb-TfEFPFCZmAbETAAsxPtqNuHCtIZNQ2uc8fgRz4NjAZZ7DxpQyfOTrlo
            7eoRlykXK8IkeKkePnc0y3pX2KIVE1AnRhlqrAheYkKmROed2HQmajmAIEI-34xR_aof1SMZ38VJNCPE
            S1IDKOcu2Zxw7GBvgt36dMlVrwQeRIvDyoILbYVr-z4DlcqHwWFGyLXZzbpEBkVrM7cjxVDEl_wSWutC
            d73Kxi9Qq8vrKiGqYIJQKFb7ZwcmT0hOTMpf4panPoFYv_bfdRoOSZHbGX2CS5tB0egFbSXCZo47ydKPS
            W8giCcrGsmnAECWSeEndgCssEPKXWIh0FHxtjPklsN3IU-Cm2JNrMEtfNh0A14zf7yJa3Hp3rlOZf8S6
            LdQdgmPe5bSr_0YnwefFnQwEooiUlFJtboVHatRnrYJZrTHIT-vBZwvbq1PEy1RO2O29qaYpGCP_WDH-
            nqOyJD-IoCV2wrQ449SsISsKujRRK6cYCKoDp4TFmwKTQ3iakZKimLQ71Zsb1ClqxjE6THawQC42EsIL
            KgL3ay7cWj9Qj5-W7eheF7eUR3LOmhstV5cEMEoVs5Vh1E0LM86k32Vmipe4HvFycZiR_AeEKil5W7JL
            rwIMdCrwBJaSi-UuMape1raijKgQc8ZHEgDdBqydT2JDadK1fZR-MG9TdoU',
                'token_type' => 'Bearer',
            ];
        $this->companyHelper->expects($this->any())->method('getCustomerCompany')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getId')->willReturn(2);
        $this->cartFactory->expects($this->exactly(2))->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->Address);
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->itemMock]);
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())
            ->method('load')
            ->willReturn($this->abstractHelper);
        $this->abstractHelper->expects($this->any())
            ->method('getCode')
            ->willReturn("TX");
        $this->abstractHelper->expects($this->any())
            ->method('getId')
            ->willReturn(4);
        $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->_optionInterface);
        $prodarray = [];
        $prodarray['external_prod'][0] = [
            'instanceId' => 0,
            'catalogReference' => 1,
            'preview_url' => 'url',
            'fxo_product' => 'product',
        ];

        $this->_optionInterface->expects($this->any())->method('getValue')->willReturn(json_encode($prodarray));
        $this->testIsDeliveryApiMockEnabled();
        $this->itemMock->expects($this->any())->method('getQty')->willReturn(2);
        $this->retailHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_account_number')
            ->willReturn('0:3:7D96hHguKjU5P5mx3jYOJmrD1+1Pw0AKypX5QYs0JgrR0NIjZg==');
        $this->cartDataHelperMock->expects($this->any())->method('decryptData')->willReturn(12345678);
        $this->companyHelper->expects($this->any())->method('getPaymentMethod')->willReturn(['fedexaccountnumber']);
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception($phrase);
        $this->curl->expects($this->any())->method('getBody')->with('product', 'url')->willThrowException($exception);
        $this->quoteHelper->expects($this->exactly(2))->method('isFullMiraklQuote')->willReturn(false);
        $this->data->GetDeliveryOptions($config);
        $this->data->GetDeliveryOptions($config);
    }

    /**
     * TEST Get Delivery Options with Signature options in request
     */
    public function testGetDeliveryOptionsIncludingSignatureOptions()
    {
        $expectedDeliveryOptions = [
            [
                'serviceType' => 'EXPRESS_SAVER',
                'serviceDescription' => self::SERVICE_DESCRIPTION,
                'currency' => 'USD',
                'estimatedShipmentRate' => self::ESTIMATED_SHIPMENT_RATE,
                'estimatedShipDate' => self::ESTIMATED_SHIPMENT_DATE,
                'estimatedDeliveryDuration' => '',
                'priceable' => 1,
                'estimatedDeliveryLocalTime' => self::ESTIMATED_DELIVERY_LOCAL_TIME
            ]
        ];
        $config =
            [
                'token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2',
                'delivery_api' => self::DELIVERY_API,
                'street' => 'hn0 34',
                'country_id' => 'US',
                'region_id' => 57,
                'postcode' => '75024',
                'city' => 'plano',
                'site' => '',
                'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xp
            ZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2MTQxMTI4NTcsImF1dGhvcml0aWVzIjpbIm1h',
                'token_type' => 'Bearer',
            ];
        $output = '{
            "transactionId": "5ef7619f-a755-4956-80ef-41da96d2c683",
            "output": {
               "deliveryOptions": [
                  {
                     "deliveryReference": "default",
                     "shipmentOptions": [
                        {
                           "serviceType": "LOCAL_DELIVERY_PM",
                           "serviceDescription": "FedEx Local Delivery",
                           "currency": "USD",
                           "priceable": true,
                           "estimatedShipmentRate": "19.99",
                           "estimatedShipDate": "2022-09-16",
                           "estimatedDeliveryLocalTime": "2022-09-19T17:00:00"
                        },
                        {
                           "serviceType": "EXPRESS_SAVER",
                           "serviceDescription": "Express Saver",
                           "currency": "USD",
                           "priceable": true,
                           "estimatedShipmentRate": "21.82",
                           "estimatedShipDate": "2022-09-16",
                           "estimatedDeliveryLocalTime": "2022-09-22T04:30:00"
                        }
                     ]
                  }
               ]
            }
         }';
        $allowedDeliveryOptions = ['EXPRESS_SAVER' => 1];

        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->Address);
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->itemMock]);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);

        $this->region->expects($this->any())
            ->method('load')
            ->willReturn($this->abstractHelper);
        $this->abstractHelper->expects($this->any())
            ->method('getCode')
            ->willReturn("TX");
        $this->abstractHelper->expects($this->any())
            ->method('getId')
            ->willReturn(4);
        $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->_optionInterface);
        $prodarray = [];
        $prodarray['external_prod'][0] = [
            'instanceId' => 0,
            'catalogReference' => 1,
            'preview_url' => 'url',
            'fxo_product' => 'product',
        ];

        $this->_optionInterface
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));
        $this->itemMock->expects($this->any())->method('getQty')->willReturn(2);
        //For non sde signature options should not be included
        $signatureOptions = [
            'specialServiceType' => 'SIGNATURE_OPTION',
            'specialServiceSubType' => 'DIRECT',
            'displayText' => self::DISPLAY_TEXT,
            'description' => self::DISPLAY_TEXT,
        ];
        $specialServices = [$signatureOptions];
        $this->retailHelper->expects($this->any())->method('getRateRequestShipmentSpecialServices')
            ->willReturn($specialServices);
        $this->curl->expects($this->any())->method('getBody')->willreturn($output);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willreturn(true);
        $this->companyHelper->expects($this->any())->method('getCustomerCompany')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getShippingAccountNumber')
            ->willReturn('123456');
        $this->retailHelper->expects($this->any())->method('getAllowedDeliveryOptions')
            ->willReturn($allowedDeliveryOptions);
        $output = json_decode($output, true);
        $this->quoteHelper->expects($this->once())->method('isFullMiraklQuote')->willReturn(false);
        $this->assertNotNull($this->data->GetDeliveryOptions($config));
    }

    /**
     * Test getDeliveryOptions.
     *
     * @return array
     */
    public function testGetDeliveryOptionsAddressClassificationFix()
    {
        $config =
            [
                'token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2',
                'delivery_api' => self::DELIVERY_API,
                'street' => 'h0 34',
                'country_id' => 'US',
                'region_id' => 57,
                'postcode' => '7502',
                'city' => 'plano',
                'site' => 'testeprosite',
                'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0
            YXouY2xpZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2MTQxMTI4NTcsImF1dGhv
            cml0aWVzIjpbIm1hZ2VudG8ub3JkZXIiLCJ0YXouc3lzdGVtLnVzZXIiLCJlbWFpbC5wb3N0
            Il0sImp0aSI6IjcxMzA3ZjY3LTE1NDktNDdiMS04Mjg0LWQwYzZkYTk1NDEzMSIsImNsaWVu
            dF9pZCI6IjM1MzcxMzFfTUFHRU5UT19QT0RfU0VSVklDRSJ9.jQ1ozMb-TfEFPFCZmAbETAA
            sxPtqNuHCtIZNQ2uc8fgRz4NjAZZ7DxpQyfOTrlo7eoRlykXK8IkeKkePnc0y3pX2KIVE1An
            RhlqrAheYkKmROed2HQmajmAIEI-34xR_aof1SMZ38VJNCPES1IDKOcu2Zxw7GBvgt36dMlV
            rwQeRIvDyoILbYVr-z4DlcqHwWFGyLXZzbpEBkVrM7cjxVDEl_wSWutCd73Kxi9Qq8vrKiGq
            YIJQKFb7ZwcmT0hOTMpf4panPoFYv_bfdRoOSZHbGX2CS5tB0egFbSXCZo47ydKPSW8giCcr
            GsmnAECWSeEndgCssEPKXWIh0FHxtjPklsN3IU-Cm2JNrMEtfNh0A14zf7yJa3Hp3rlOZf8S
            6LdQdgmPe5bSr_0YnwefFnQwEooiUlFJtboVHatRnrYJZrTHIT-vBZwvbq1PEy1RO2O29qaYp
            GCP_WDH-nqOyJD-IoCV2wrQ449SsISsKujRRK6cYCKoDp4TFmwKTQ3iakZKimLQ71Zsb1Clq
            xjE6THawQC42EsILKgL3ay7cWj9Qj5-W7eheF7eUR3LOmhstV5cEMEoVs5Vh1E0LM86k32Vmi
            pe4HvFycZiR_AeEKil5W7JLrwIMdCrwBJaSi-UuMape1raijKgQc8ZHEgDdBqydT2JDadK1fZ
            R-MG9TdoU',
                'token_type' => 'Bearer',
            ];
        $output = '{
            "transactionId" : "696c0a47-ee79-4e7b-95e6-4d4697c41254",
            "errors" : "6d6ydgd6f6f6udu",
            "outputs" : {
              "deliveryOptions" : [ {
                "deliveryReference" : "default",
                "shipmentOptions" : [ {
                  "serviceType" : "LOCAL_DELIVERY_AM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T12:00:00",
                  "priceable" : true
                }, {
                  "serviceType" : "LOCAL_DELIVERY_PM",
                  "serviceDescription" : "FedEx Local Delivery",
                  "currency" : "USD",
                  "estimatedShipmentRate" : "19.99",
                  "estimatedShipDate" : "2021-09-22",
                  "estimatedDeliveryLocalTime" : "2021-09-23T17:00:00",
                  "priceable" : true
                } ]
              } ]
            }
          }';
        $postData = '{
            "address": {
                "street": [
                    "8229 Boone Boulevard",
                    ""
                ],
                "city": "Vienna",
                "region_id": "181",
                "region": "VA",
                "country_id": "US",
                "postcode": "22182",
                "firstname": "Iago",
                "lastname": "Lima",
                "company": "",
                "telephone": "(555) 555-5555",
                "custom_attributes": [
                    {
                        "attribute_code": "email_id",
                        "value": "ilima@mcfadyen.com"
                    },
                    {
                        "attribute_code": "ext",
                        "value": ""
                    },
                    {
                        "attribute_code": "residence_shipping",
                        "value": 1
                    }
                ]
            },
            "productionLocation": null,
            "isPickup": false,
            "reRate": true
        }';
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->requestObj->expects($this->atMost(2))->method('getContent')->willReturn($postData);
        $this->companyHelper->expects($this->any())->method('getCustomerCompany')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getId')->willReturn(2);
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->Address);
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->itemMock]);
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())
            ->method('load')
            ->willReturn($this->abstractHelper);
        $this->abstractHelper->expects($this->any())
            ->method('getCode')
            ->willReturn("TX");
        $this->abstractHelper->expects($this->any())
            ->method('getId')
            ->willReturn(4);
        $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->_optionInterface);
        $prodarray = [];
        $prodarray['external_prod'][0] = [
            'instanceId' => 0,
            'catalogReference' => 1,
            'preview_url' => 'url',
            'fxo_product' => 'product',
        ];

        $this->_optionInterface
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));
        $this->itemMock->expects($this->any())->method('getQty')->willReturn(2);
        $this->retailHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);

        $this->quoteMock->expects($this->any())->method('getData')
            ->with('fedex_account_number')
            ->willReturn('0:3:7D96hHguKjU5P5mx3jYOJmrD1+1Pw0AKypX5QYs0JgrR0NIjZg==');
        $this->cartDataHelperMock->expects($this->any())->method('decryptData')->willReturn(12345678);
        $this->companyHelper->expects($this->any())->method('getCompanyPaymentMethod')
            ->willReturn('fedexaccountnumber');
        $this->curl->expects($this->any())->method('getBody')->willreturn($output);
        $this->quoteHelper->expects($this->once())->method('isFullMiraklQuote')->willReturn(true);
        $this->data->GetDeliveryOptions($config);
    }

    /**
     * Test to get available shipping services
     */
    public function testGetAvailableShippingServices()
    {
        $availableServices = [
            'GROUND_US' => 'Ground US',
            'LOCAL_DELIVERY_AM'=> 'FedEx Local Delivery',
            'LOCAL_DELIVERY_PM' => 'FedEx Local Delivery',
            'EXPRESS_SAVER' => 'Express Saver',
            'TWO_DAY' => '2 Day',
            'STANDARD_OVERNIGHT' => 'Standard Overnight',
            'PRIORITY_OVERNIGHT' => 'Priority Overnight',
            'FIRST_OVERNIGHT' => 'First Overnight',
        ];
        $this->assertEquals($availableServices, $this->data->getAvailableShippingServices());
    }

    /**
     * Test to Get shipping service title for FedEx services
     */
    public function testGetShippingServiceTitle()
    {
        $serviceType = 'GROUND_US';
        $serviceTitle = 'Ground US';
        $this->assertEquals($serviceTitle, $this->data->getShippingServiceTitle($serviceType));
    }

    /**
     * Test to Get shipping service title for not available service
     */
    public function testGetShippingServiceTitleWithNotAvailableService()
    {
        $serviceType = '';
        $serviceTitle = '';
        $this->assertEquals($serviceTitle, $this->data->getShippingServiceTitle($serviceType));
    }

    /**
     * TEST Get Delivery Options with Signature options and SDE workflow
     */
    public function testGetDeliveryOptionsWithSensitiveWorkflow()
    {
        $expectedDeliveryOptions = [
            [
                'serviceType' => 'EXPRESS_SAVER',
                'serviceDescription' => self::SERVICE_DESCRIPTION,
                'currency' => 'USD',
                'estimatedShipmentRate' => self::ESTIMATED_SHIPMENT_RATE,
                'estimatedShipDate' => self::ESTIMATED_SHIPMENT_DATE,
                'estimatedDeliveryDuration' => '',
                'priceable' => 1,
                'estimatedDeliveryLocalTime' => self::ESTIMATED_DELIVERY_LOCAL_TIME
            ]
        ];
        $config =
            [
                'token' => 'l7xx1cb26690fadd4b2789e0888a96b80ee2',
                'delivery_api' => self::DELIVERY_API,
                'street' => 'hn0 34',
                'country_id' => 'US',
                'region_id' => 57,
                'postcode' => '75024',
                'city' => 'plano',
                'site' => '',
                'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJ0YXouY2xp
            ZW50czp3cml0ZSJdLCJpc3MiOiJ0YXoiLCJleHAiOjE2MTQxMTI4NTcsImF1dGhvcml0aWVzIjpbIm1h',
                'token_type' => 'Bearer',
            ];
        $output = '{
            "transactionId": "5ef7619f-a755-4956-80ef-41da96d2c683",
            "output": {
               "deliveryOptions": [
                  {
                     "deliveryReference": "default",
                     "shipmentOptions": [
                        {
                           "serviceType": "EXPRESS_SAVER",
                           "priceable": true,
                           "estimatedShipmentRate": "21.82",
                           "estimatedShipDate": "2022-09-16",
                           "estimatedDeliveryLocalTime": "2022-09-22T04:30:00"
                        }
                     ]
                  }
               ]
            }
         }';

        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->Address);
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->itemMock]);

        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);

        $this->region->expects($this->any())
            ->method('load')
            ->willReturn($this->abstractHelper);
        $this->abstractHelper->expects($this->any())
            ->method('getCode')
            ->willReturn("TX");
        $this->abstractHelper->expects($this->any())
            ->method('getId')
            ->willReturn(4);
        $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->_optionInterface);
        $prodarray = [];
        $prodarray['external_prod'][0] = [
            'instanceId' => 0,
            'catalogReference' => 1,
            'preview_url' => 'url',
            'fxo_product' => 'product',
        ];

        $this->_optionInterface
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));
        $this->itemMock->expects($this->any())->method('getQty')->willReturn(2);
        //For non sde signature options should not be included
        $signatureOptions = [
            'specialServiceType' => 'SIGNATURE_OPTION',
            'specialServiceSubType' => 'DIRECT',
            'displayText' => self::DISPLAY_TEXT,
            'description' => self::DISPLAY_TEXT,
        ];
        $specialServices = [$signatureOptions];
        $this->retailHelper->expects($this->any())
            ->method('getRateRequestShipmentSpecialServices')->willReturn($specialServices);
        $this->curl->expects($this->any())->method('getBody')->willreturn($output);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willreturn(true);
        $this->companyHelper->expects($this->any())->method('getCustomerCompany')
            ->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getShippingAccountNumber')
            ->willReturn('123456');
        $output = json_decode($output, true);
        $this->quoteHelper->expects($this->once())->method('isFullMiraklQuote')->willReturn(false);
        $this->assertNotNull($this->data->GetDeliveryOptions($config));
    }

    public function testGetDeliveryOptionsData()
    {
        $expectedDeliveryOptions = [
            [
                'serviceType' => 'EXPRESS_SAVER',
                'serviceDescription' => self::SERVICE_DESCRIPTION,
                'currency' => 'USD',
                'estimatedShipmentRate' => '21.82',
                'estimatedShipDate' => '2022-09-16',
                'estimatedDeliveryDuration' => '',
                'priceable' => true,
                'estimatedDeliveryLocalTime' => 'Thursday, September 22 4:30 am',
                'productionLocationId' => null
            ]
        ];
        $output = '{
            "transactionId": "5ef7619f-a755-4956-80ef-41da96d2c683",
            "output": {
               "deliveryOptions": [
                  {
                     "deliveryReference": "default",
                     "shipmentOptions": [
                        {
                           "serviceType": "EXPRESS_SAVER",
                           "priceable": true,
                           "estimatedShipmentRate": "21.82",
                           "estimatedShipDate": "2022-09-16",
                           "estimatedDeliveryLocalTime": "2022-09-22T04:30:00",
                           "productionLocationId": "null"
                        }
                     ]
                  }
               ]
            }
        }';
        $this->assertEquals($expectedDeliveryOptions, $this->data->getDeliveryOptionsData($output));
    }

    public function testGetDeliveryOptionsDataWithToggleOn()
    {
        $expectedDeliveryOptions = [
            [
                'serviceType' => 'EXPRESS_SAVER',
                'serviceDescription' => self::SERVICE_DESCRIPTION,
                'currency' => 'USD',
                'estimatedShipmentRate' => '21.82',
                'estimatedShipDate' => '2022-09-16',
                'estimatedDeliveryDuration' => '',
                'priceable' => true,
                'estimatedDeliveryLocalTime' => 'Thursday, September 22 4:30 am',
                'productionLocationId' => null
            ]
        ];

        $output = '{
            "transactionId": "5ef7619f-a755-4956-80ef-41da96d2c683",
            "output": {
               "deliveryOptions": [
                  {
                     "deliveryReference": "default",
                     "shipmentOptions": [
                        {
                           "serviceType": "EXPRESS_SAVER",
                           "priceable": true,
                           "estimatedShipmentRate": "21.82",
                           "estimatedShipDate": "2022-09-16",
                           "estimatedDeliveryLocalTime": "2022-09-22T04:30:00",
                           "productionLocationId": "null"
                        }
                     ]
                  }
               ]
            }
        }';
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->shippingDataHelper->expects($this->once())->method('getRetailOnePShippingMethods')
        ->willReturn(['allowStore'=> 1, 'allowedDeliveryOptions' => []]);

        $this->assertEquals($expectedDeliveryOptions, $this->data->getDeliveryOptionsData($output));
    }

    /**
     * Test case for testIsItPickup
     */
    public function testIsItPickup()
    {
        $pickupData = [];
        $pickupData['addressInformation']['shipping_method_code'] = "PICKUP";
        $this->assertNotNull($this->data->isItPickup($pickupData));
    }

    /**
     * Test case for getExpectedDate
     */
    public function testGetExpectedDate()
    {
        $deliveryOption['serviceType'] = "GROUND_US";
        $deliveryOption['serviceDescription'] = "Ground US";
        $deliveryOption['currency'] = "USD";
        $deliveryOption['estimatedShipDate'] = "2023-03-01";
        $deliveryOption['estimatedDeliveryLocalTime'] = static::ESTIMATED_TIME;
        $deliveryOption['estimatedShipmentRate'] = "9.99";
        $deliveryOption['priceable'] = true;

        $this->assertNotNull($this->data->getExpectedDate($deliveryOption));
    }

    /**
     * Test case for testGetExpectedDateWithExpressSaver
     */
    public function testGetExpectedDateWithExpressSaver()
    {
        $deliveryOption['serviceType'] = "EXPRESS_SAVER";
        $deliveryOption['serviceDescription'] = "Express Saver";
        $deliveryOption['currency'] = "USD";
        $deliveryOption['estimatedShipDate'] = "2023-03-01";
        $deliveryOption['estimatedDeliveryLocalTime'] = static::ESTIMATED_TIME;
        $deliveryOption['estimatedShipmentRate'] = "24.92";
        $deliveryOption['priceable'] = true;
        $this->assertNotNull($this->data->getExpectedDate($deliveryOption));
    }

    /**
     * Test case for testGetExpectedDateWithGroundUsDuration
     */
    public function testGetExpectedDateWithGroundUsDuration()
    {
        $deliveryOption['serviceType'] = "GROUND_US";
        $deliveryOption['serviceDescription'] = "Ground US";
        $deliveryOption['currency'] = "USD";
        $deliveryOption['estimatedShipDate'] = "2023-03-01";
        $deliveryOption['estimatedDeliveryLocalTime'] = '';
        $deliveryOption['estimatedDeliveryDuration']['unit'] = 'Business Days';
        $deliveryOption['estimatedShipmentRate'] = "9.99";
        $deliveryOption['priceable'] = true;

        $this->assertNotNull($this->data->getExpectedDate($deliveryOption));
    }

    /**
     * Test case for getExpectedDateFormat
     */
    public function testGetExpectedDateFormat()
    {
        $deliveryOption['serviceType'] = "GROUND_US";
        $deliveryOption['serviceDescription'] = "Ground US";
        $deliveryOption['currency'] = "USD";
        $deliveryOption['estimatedShipDate'] = "2023-03-01";
        $deliveryOption['estimatedDeliveryLocalTime'] = static::ESTIMATED_TIME;
        $deliveryOption['estimatedShipmentRate'] = "9.99";
        $deliveryOption['priceable'] = true;
        $this->assertNotNull($this->data->getExpectedDateFormat($deliveryOption));
    }

    /**
     * Test case for testGetExpectedDateFormat
     */
    public function testGetExpectedDateFormatWithExpressSaver()
    {
        $deliveryOption['serviceType'] = "EXPRESS_SAVER";
        $deliveryOption['serviceDescription'] = "Express Saver";
        $deliveryOption['currency'] = "USD";
        $deliveryOption['estimatedShipDate'] = "2023-03-01";
        $deliveryOption['estimatedDeliveryLocalTime'] = static::ESTIMATED_TIME;
        $deliveryOption['estimatedShipmentRate'] = "24.92";
        $deliveryOption['priceable'] = true;
        $this->assertNotNull($this->data->getExpectedDateFormat($deliveryOption));
    }

    /**
     * Test case for getExpectedDateFormatWithGroundUs
     */
    public function testGetExpectedDateFormatWithGroundUs()
    {
        $deliveryOption['serviceType'] = "GROUND_US";
        $deliveryOption['serviceDescription'] = "Ground US";
        $deliveryOption['currency'] = "USD";
        $deliveryOption['estimatedShipDate'] = "2023-03-01";
        $deliveryOption['estimatedDeliveryLocalTime'] = static::ESTIMATED_TIME;
        $deliveryOption['estimatedShipmentRate'] = "9.99";
        $deliveryOption['priceable'] = true;
        $this->assertNotNull($this->data->getExpectedDateFormat($deliveryOption));
    }

    /**
     * Test case for getExpectedDateFormatWithGroundUs
     */
    public function testGetExpectedDateFormatWithGroundUsDuration()
    {
        $deliveryOption['serviceType'] = "GROUND_US";
        $deliveryOption['serviceDescription'] = "Ground US";
        $deliveryOption['currency'] = "USD";
        $deliveryOption['estimatedShipDate'] = "2023-03-01";
        $deliveryOption['estimatedDeliveryLocalTime'] = '';
        $deliveryOption['estimatedDeliveryDuration']['unit'] = 'Business Days';
        $deliveryOption['estimatedShipmentRate'] = "9.99";
        $deliveryOption['priceable'] = true;
        $this->assertNotNull($this->data->getExpectedDateFormat($deliveryOption));
    }
    /**
     * Test case for isDeliveryApiMockEnabled
     */
    public function testIsDeliveryApiMockEnabled()
    {
        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn(true);
        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn(true);
        $this->assertEquals(true,$this->data->isDeliveryApiMockEnabled());
    }

    /**
     * Test case for getDeliveryMockApiUrl
     */
    public function testGetDeliveryMockApiUrl()
    {
        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn(true);
        $this->assertEquals(true,$this->data->getDeliveryMockApiUrl());
    }
}
