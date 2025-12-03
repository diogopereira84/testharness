<?php

namespace Fedex\OrderGraphQl\Test\Unit;

class MockDataProvider
{
    const FULL_ARGUMENTS = [
        "filters" => [
            "Omni" => [
                "attributes" => [
                    "orderNumber"
                ],
                "text" => ""
            ],
            "contact" => [
                "emailDetail" => [
                    "emailAddress" => "test@tester.com"
                ],
                "personName" => [
                    "firstName" => "Test",
                    "lastName" => "Tester"
                ],
                "phoneNumberDetails" => [
                    [
                        "phoneNumber" => [
                            "number" => "8045645820"
                        ],
                        "usage" => "PRIMARY"
                    ]
                ]
            ],
            "location" => [
                "id" => "DNEK"
            ],
            "submissionTimeDateRange" => [
                "endDateTime" => "2024-01-25T09:50:35Z",
                "startDateTime" => "2018-01-25T09:50:35Z"
            ],
            "productionDueDate" => [
                "endDateTime" => "2024-01-25T09:50:35Z",
                "startDateTime" => "2018-01-25T09:50:35Z"
            ]
        ],
        "sorts" => [[
            "ascending" => true,
            "attribute" => "startDateTime"
        ]]
    ];

    const SHIPMENT_STATUS = [
        ["1" => "Pending"],
        ["2" => "Ready For Pickup"],
        ["3" => "Shipped",],
        ["4" => "Delivered"],
        ["5" => "Cancelled"],
        ["6" => "In Progress"],
        ["7" => "Confirmed"],
        ["8" => "Complete"]
    ];

}
