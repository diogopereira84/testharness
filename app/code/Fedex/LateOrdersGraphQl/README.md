# Fedex_LateOrdersGraphQl Magento 2 Module

## Overview

Fedex_LateOrdersGraphQl is a Magento 2 module that exposes late order data and order details via GraphQL endpoints. It is designed for use in POD 2.0 environments, enabling clients to query for late orders and retrieve detailed information about specific orders.

## Features
- Query a paginated list of late orders with filters
- Retrieve detailed information for a specific order by ID

## GraphQL Schema

### Queries

#### 1. `lateOrders`
Fetches a paginated list of late orders, with optional filters.

| Argument      | Type                    | Required | Default | Description                       |
|--------------|-------------------------|----------|---------|-----------------------------------|
| filter       | LateOrderFilterInput    | No       | -       | Filter orders by date, status, etc|
| currentPage  | Int                     | No       | 1       | Page number                       |
| pageSize     | Int                     | No       | 100     | Number of results per page        |

**LateOrderFilterInput fields:**
| Field    | Type              | Required | Default | Description                |
|----------|-------------------|----------|---------|----------------------------|
| since    | String            | No       | -       | Start date (ISO 8601)      |
| until    | String            | No       | -       | End date (ISO 8601)        |
| status   | LateOrderStatus   | No       | new     | Order status               |
| is_1p    | Boolean           | No       | true    | 1P order flag              |

#### 2. `orderDetailsById`
Fetches detailed information for a specific order by its ID.

| Argument | Type   | Required | Default | Description      |
|----------|--------|----------|---------|------------------|
| orderId  | String | Yes      | -       | The order ID     |

### Types and Fields

#### LateOrderSummary
| Field     | Type            | Required |
|-----------|-----------------|----------|
| orderId   | String          | Yes      |
| createdAt | String          | Yes      |
| status    | LateOrderStatus | Yes      |
| is_1p     | Boolean         | Yes      |

#### OrderDetails
| Field        | Type                    | Required |
|--------------|-------------------------|----------|
| orderId      | String                  | Yes      |
| status       | LateOrderStatus         | Yes      |
| createdAt    | String                  | Yes      |
| customer     | Customer                | Yes      |
| fulfillment  | Fulfillment             | No       |
| store        | StoreRef                | Yes      |
| items        | [OrderDetailsItem]      | Yes      |
| orderNotes   | String                  | No       |
| is_1p        | Boolean                 | Yes      |

#### Customer
| Field | Type   | Required |
|-------|--------|----------|
| name  | String | Yes      |
| email | String | Yes      |
| phone | String | No       |

#### Fulfillment
| Field                 | Type           | Required |
|-----------------------|----------------|----------|
| type                  | FulfillmentType| No       |
| pickupTime            | String         | No       |
| deliveryTime          | String         | No       |
| shippingAccountNumber | String         | No       |
| shippingAddress       | Address        | No       |

#### Address
| Field      | Type   | Required |
|------------|--------|----------|
| line1      | String | No       |
| line2      | String | No       |
| city       | String | No       |
| region     | String | No       |
| postalCode | String | No       |
| country    | String | No       |

#### StoreRef
| Field       | Type   | Required |
|-------------|--------|----------|
| storeId     | String | Yes      |
| storeNumber | String | Yes      |
| storeEmail  | String | Yes      |

#### OrderDetailsItem
| Field                  | Type                    | Required |
|------------------------|-------------------------|----------|
| productId              | String                  | Yes      |
| documentId             | [String]                | Yes      |
| productConfiguration   | [ProductConfigEntry]    | Yes      |
| productionInstructions | [String]                | Yes      |
| downloadLinks          | [DownloadLink]          | Yes      |

#### ProductConfigEntry
| Field | Type   | Required |
|-------|--------|----------|
| key   | String | Yes      |
| value | String | No       |

#### DownloadLink
| Field | Type   | Required |
|-------|--------|----------|
| href  | String | Yes      |

### Enums
- `LateOrderStatus`: new, processing, complete, canceled, hold, closed, ordered
- `FulfillmentType`: PICKUP, DELIVERY

## Example Usage

### 1. Query Late Orders (all possible fields)
```graphql
query {
  lateOrders(
    filter: {
      since: "2025-09-01"
      until: "2025-09-30"
      status: complete
      is_1p: false
    }
    currentPage: 2
    pageSize: 5
  ) {
    items {
      orderId
      createdAt
      status
      is_1p
    }
    totalCount
    pageInfo {
      currentPage
      pageSize
      totalPages
      hasNextPage
    }
  }
}
```
- **All arguments are optional.**
- **Defaults:** `status: new`, `is_1p: true`, `currentPage: 1`, `pageSize: 100`

#### Example Return
```json
{
  "data": {
    "lateOrders": {
      "items": [
        {
          "orderId": "100000123",
          "createdAt": "2025-09-02T10:15:00Z",
          "status": "complete",
          "is_1p": false
        },
        {
          "orderId": "100000124",
          "createdAt": "2025-09-03T11:20:00Z",
          "status": "complete",
          "is_1p": false
        }
      ],
      "totalCount": 2,
      "pageInfo": {
        "currentPage": 2,
        "pageSize": 5,
        "totalPages": 1,
        "hasNextPage": false
      }
    }
  }
}
```

### 2. Query Order Details by ID (all possible fields)
```graphql
query {
  orderDetailsById(orderId: "100000123") {
    orderId
    status
    createdAt
    customer {
      name
      email
      phone
    }
    fulfillment {
      type
      pickupTime
      deliveryTime
      shippingAccountNumber
      shippingAddress {
        line1
        line2
        city
        region
        postalCode
        country
      }
    }
    store {
      storeId
      storeNumber
      storeEmail
    }
    items {
      productId
      documentId
      productConfiguration {
        key
        value
      }
      productionInstructions
      downloadLinks {
        href
      }
    }
    orderNotes
    is_1p
  }
}
```
- **Argument `orderId` is required.**
- **Fields marked as required in the tables above are always present. Optional fields may be null or omitted.**

#### Example Return
```json
{
  "data": {
    "orderDetailsById": {
      "orderId": "100000123",
      "status": "complete",
      "createdAt": "2025-09-02T10:15:00Z",
      "customer": {
        "name": "John Doe",
        "email": "john.doe@example.com",
        "phone": "+1-555-1234"
      },
      "fulfillment": {
        "type": "DELIVERY",
        "pickupTime": null,
        "deliveryTime": "2025-09-05T14:00:00Z",
        "shippingAccountNumber": "ACC123456",
        "shippingAddress": {
          "line1": "123 Main St",
          "line2": "Suite 100",
          "city": "New York",
          "region": "NY",
          "postalCode": "10001",
          "country": "US"
        }
      },
      "store": {
        "storeId": "ADSKK", 
        "storeNumber": "972.960.9449",
        "storeEmail": "usa3112@fedex.com"
      },
      "items": [
        {
          "productId": "P12345",
          "documentId": ["D98765"],
          "productConfiguration": [
            { "key": "color", "value": "blue" },
            { "key": "size", "value": "L" }
          ],
          "productionInstructions": ["Print double-sided"],
          "downloadLinks": [
            { "href": "https://example.com/download/12345" }
          ]
        }
      ],
      "orderNotes": "Urgent delivery required.",
      "is_1p": false
    }
  }
}
```

### Late Order Threshold: `createdBeforeMinutes` Config

The `lateOrders` query uses an admin configuration value called `createdBeforeMinutes` to determine which orders are considered "late." This value is set in the Magento Admin panel (system configuration) and represents the number of minutes before the current time. Only orders created before `(current time - createdBeforeMinutes)` are included in the results.

- **Purpose:** Automatically filters out orders that are too recent to be considered late, based on business rules.
- **Usage:** The backend applies this threshold in addition to any user-supplied filters. If the `until` field is not provided in the filter, the system uses the current time minus `createdBeforeMinutes` as the upper bound for order creation date. If `until` is provided, it overrides this threshold.
- **Configuration:** This value can be changed by an administrator in the Magento system configuration (path: `Stores > Configuration > Sales > Sales > GraphQl Response Options > Late Order GraphQl Query Max Window By Hours`).
- **Note:** This filter is only applied when the client does not specify an `until` date in the filter.

## Error Handling & Payloads

GraphQL errors are returned with structured payloads using the standard Magento pattern. Example:

```
{
  "errors": [
    {
      "message": "Not found",
      "extensions": {
        "code": "NOT_FOUND"
      }
    }
  ]
}
```

- Error codes used: `NOT_FOUND`, `BAD_REQUEST`, `INTERNAL_ERROR`.
- Empty results (e.g., no orders found) are not considered errors.

## Example Error Returns

```
{
    "errors": [
        {
            "message": "Order with increment ID '2010437469783265' not found.",
            "locations": [
                { "line": 2, "column": 3 }
            ],
            "path": [ "orderDetailsById" ],
            "extensions": { "category": "graphql-no-such-entity" }
        }
    ]
}

{
    "errors": [
        {
            "message": "Invalid 'since' date format: '%1'. Please use ISO-8601 format.",
            "locations": [
                { "line": 2, "column": 3 }
            ],
            "path": [ "orderDetailsById" ],
            "extensions": { "category": "graphql-input" }
        }
    ]
}

{
    "errors": [
        {
            "message": "Warning: Undefined variable $since in /var/www/html/app/code/Fedex/LateOrdersGraphQl/Model/Resolver/OrderDetailsById.php on line 37",
            "locations": [
                { "line": 2, "column": 3 }
            ],
            "path": [ "lateOrders" ],
            "extensions": { "category": "graphql-input" }
        }
    ]
}

{
    "errors": [
        {
            "message": "Invalid 'since' date format: '10/08/2025 06:42 PM'. Please use ISO-8601 format.",
            "locations": [
                { "line": 2, "column": 3 }
            ],
            "path": [ "lateOrders" ],
            "extensions": { "category": "graphql-input" }
        }
    ]
}
```

## Recommended Client Retry Logic
- Clients should retry only on `INTERNAL_ERROR`.
- Do not retry on `BAD_REQUEST` or `NOT_FOUND` errors.

## Server-Side Logging
- Each API call is logged with:
  - Caller (Bearer token hash)
  - Query type (resolver name)
  - Filter window or orderId
  - Result counts
  - Latency (ms)
  - Errors (if any)
- Downstream email delivery is not logged (client responsibility).

## Example Server-Side Logs

```
report.INFO: dffd776f8864d7117114c46713357b47 [LateOrdersGraphQl] API call {"caller":"3kx34skcuzjhgqeccb3tctr4ot1kwjhq","queryType":"LateOrders","filter":{"is_1p":true,"since":"2025-03-01T00:00:00Z","status":"new","until":"2025-09-10T00:00:00Z"},"resultCount":119,"latency_ms":8.0,"error":null}

report.INFO: dffd776f8864d7117114c46713357b47 [LateOrdersGraphQl] API call {"caller":"3kx34skcuzjhgqeccb3tctr4ot1kwjhq","queryType":"OrderDetailsById","orderId":"2010437469783265","resultCount":1,"latency_ms":38.0,"error":null}
```

## Dashboard Panel
- Dashboard metrics (latency, QPS) are not implemented in this module. Use external tools (e.g., ELK, Grafana) for visualization if needed.
