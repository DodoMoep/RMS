# Order Management - Customer Selection Feature

## Feature Flow

```
┌─────────────────────────────────────────────────────┐
│          CREATE/EDIT ORDER FORM                     │
└─────────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│     CHOOSE CUSTOMER TYPE (Radio Buttons)            │
│                                                     │
│  ○ Existing Customer    ○ Individual Customer      │
└─────────────────────────────────────────────────────┘
         │                           │
         │                           │
    ┌────▼────┐                 ┌────▼────┐
    │ Option 1│                 │ Option 2│
    └────┬────┘                 └────┬────┘
         │                           │
         ▼                           ▼
┌─────────────────────┐    ┌─────────────────────┐
│ SELECT CUSTOMER     │    │ ENTER MANUALLY      │
│                     │    │                     │
│ Dropdown showing:   │    │ - Customer Name*    │
│ CUS-2025-0001 -     │    │ - Email             │
│ Test Customer GmbH  │    │ - Phone             │
│                     │    │ - Address           │
│ When selected:      │    │                     │
│ → Auto-fills fields │    │                     │
│ → Links order to    │    │                     │
│   customer record   │    │                     │
└─────────┬───────────┘    └─────────┬───────────┘
          │                          │
          └──────────┬───────────────┘
                     │
                     ▼
         ┌───────────────────────┐
         │   ADD ORDER ITEMS     │
         │   (Articles, Qty)     │
         └───────────┬───────────┘
                     │
                     ▼
         ┌───────────────────────┐
         │    SAVE ORDER         │
         └───────────┬───────────┘
                     │
                     ▼
         ┌───────────────────────┐
         │  ORDER CREATED WITH:  │
         │                       │
         │  If Existing:         │
         │  ✓ customer_id set    │
         │  ✓ data copied        │
         │                       │
         │  If Individual:       │
         │  ✓ customer_id null   │
         │  ✓ data stored inline │
         └───────────────────────┘
```

## Database Relationship

```
┌──────────────────────┐         ┌──────────────────────┐
│     CUSTOMERS        │         │       ORDERS         │
├──────────────────────┤         ├──────────────────────┤
│ id (UUID) PK        │◄────────┤ customer_id (UUID)FK │
│ customer_number     │         │ order_number         │
│ name                │         │ customer_name        │
│ email               │         │ customer_email       │
│ phone               │         │ customer_phone       │
│ address             │         │ customer_address     │
│ notes               │         │ status               │
│ is_active           │         │ created_by           │
│ created_at          │         │ packed_by            │
│ updated_at          │         │ ...                  │
└──────────────────────┘         └──────────────────────┘
       1                                  *
       │                                  │
       └──────── has many orders ─────────┘

Note: customer_id is NULLABLE
- If set: Order is linked to a customer
- If null: Order has individual customer data
```

## User Interface Examples

### Option 1: Existing Customer Selected
```
┌──────────────────────────────────────────────────────┐
│ Customer Information                                 │
├──────────────────────────────────────────────────────┤
│                                                      │
│ ● Existing Customer    ○ Individual Customer        │
│                                                      │
│ Select Customer: *                                   │
│ ┌──────────────────────────────────────────────┐    │
│ │ CUS-2025-0001 - Test Customer GmbH        ▼│    │
│ └──────────────────────────────────────────────┘    │
│                                                      │
│ Customer Name:                                       │
│ Test Customer GmbH (auto-filled, read-only)         │
│                                                      │
│ Email:                                               │
│ test@customer.com (auto-filled)                      │
│                                                      │
│ Phone:                                               │
│ +49 123 456789 (auto-filled)                         │
│                                                      │
│ Address:                                             │
│ Teststraße 123, 12345 Teststadt (auto-filled)       │
│                                                      │
└──────────────────────────────────────────────────────┘
```

### Option 2: Individual Customer Selected
```
┌──────────────────────────────────────────────────────┐
│ Customer Information                                 │
├──────────────────────────────────────────────────────┤
│                                                      │
│ ○ Existing Customer    ● Individual Customer        │
│                                                      │
│ Customer Name: *                                     │
│ ┌──────────────────────────────────────────────┐    │
│ │ [Enter customer name...]                     │    │
│ └──────────────────────────────────────────────┘    │
│                                                      │
│ Email:                                               │
│ ┌──────────────────────────────────────────────┐    │
│ │ [Enter email...]                             │    │
│ └──────────────────────────────────────────────┘    │
│                                                      │
│ Phone:                                               │
│ ┌──────────────────────────────────────────────┐    │
│ │ [Enter phone...]                             │    │
│ └──────────────────────────────────────────────┘    │
│                                                      │
│ Address:                                             │
│ ┌──────────────────────────────────────────────┐    │
│ │ [Enter address...]                           │    │
│ └──────────────────────────────────────────────┘    │
│                                                      │
└──────────────────────────────────────────────────────┘
```

## Data Storage Examples

### Existing Customer Order
```json
{
  "order_number": "ORD-2025-0042",
  "customer_id": "9c8f7e6d-5b4a-3c2d-1e0f-1a2b3c4d5e6f",
  "customer_name": "Test Customer GmbH",
  "customer_email": "test@customer.com",
  "customer_phone": "+49 123 456789",
  "customer_address": "Teststraße 123, 12345 Teststadt",
  "status": "new"
}
```

### Individual Customer Order
```json
{
  "order_number": "ORD-2025-0043",
  "customer_id": null,
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+49 555 111222",
  "customer_address": "One-Time Street 99, 88888 Singletown",
  "status": "new"
}
```

## Business Logic

### When to Use Existing Customer
- Regular/repeat customers
- B2B customers with accounts
- Customers with credit terms
- Want to track order history
- Need consistent data

### When to Use Individual Customer
- One-time buyers
- Walk-in customers
- Guest orders
- Trade show/event orders
- Quick orders without setup

### Data Integrity Rules
1. Customer data is ALWAYS copied to order (snapshot)
2. If customer is linked (customer_id set):
   - Changes to customer record don't affect existing orders
   - Order shows data at time of creation
3. If customer is deleted:
   - Foreign key set to null (onDelete: set null)
   - Order data remains intact
4. Orders can be searched by customer_id for history

## Implementation Benefits
✓ Flexibility: Support both scenarios
✓ Data integrity: Historical accuracy preserved
✓ Performance: No joins needed for display
✓ User-friendly: Simple UI with clear options
✓ Future-proof: Foundation for customer features
