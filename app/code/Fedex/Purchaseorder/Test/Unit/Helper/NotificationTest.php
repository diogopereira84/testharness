<?php

namespace Fedex\Purchaseorder\Test\Unit\Helper;


use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Fedex\Purchaseorder\Helper\Notification;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
   

    /**
     * Test for sendXmlNotification method.
     *
     * @return string|int
     */
    public function testSendXmlNotification()
    {   
        $cxml='<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE cXML SYSTEM "http://xml.cXML.org/schemas/cXML/1.2.021/cXML.dtd"><cXML timestamp="2021-02-17 21:16:34-08:00" xml:lang="en-US" payloadID="602df83257f71.d90b2c07d4aa1b00@c0021757.prod.cloud.fedex.com"><Header><From><Credential domain="privateid"><Identity>999032669</Identity></Credential></From><To><Credential domain="MAGENTO1"><Identity>NetworkId1</Identity></Credential></To><Sender><Credential domain="AribaNetworkUserId"><Identity>sysadmin@ariba.com</Identity><SharedSecret>f3d3xs3rv1c3s</SharedSecret></Credential><UserAgent>Hubspan Translation Services</UserAgent></Sender></Header><Message deploymentMode="production"><PunchOutOrderMessage><BuyerCookie>24941604898076815</BuyerCookie><PunchOutOrderMessageHeader operationAllowed="create" quoteStatus="final"><Total><Money currency="USD">0.49</Money></Total><SupplierOrderInfo orderID="267" />
        </PunchOutOrderMessageHeader><ItemIn quantity="1" lineNumber="1"><ItemID><SupplierPartID>1</SupplierPartID><SupplierPartAuxiliaryID>434</SupplierPartAuxiliaryID></ItemID><ItemDetail><UnitPrice><Money currency="USD">0.49</Money></UnitPrice><Description xml:lang="en_US">Catalog Item at Level 2</Description><UnitOfMeasure>EA</UnitOfMeasure><Classification domain="UNSPSC">82121503</Classification><Extrinsic name="ItemExtendedPrice"><Money currency="USD">0.49</Money></Extrinsic></ItemDetail></ItemIn></PunchOutOrderMessage></Message></cXML>';
        $api_url='https://shop-staging2.fedex.com ';

        $response=1;
        $data = $this->createMock(Notification::class);
        $data->method('sendXmlNotification')->with($cxml,$api_url)
        ->willreturn($response);
        $this->assertEquals($response, $data->sendXmlNotification($cxml,$api_url));

        $response='failed';
        $data = $this->createMock(Notification::class);
        $data->method('sendXmlNotification')->with($cxml,$api_url)
        ->willreturn($response);
        $this->assertEquals($response, $data->sendXmlNotification($cxml,$api_url));
    }

    
   

   
}
