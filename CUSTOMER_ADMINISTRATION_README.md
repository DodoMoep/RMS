# Customer Administration Implementation

## Overview
Complete customer management system (CRUD) integrated with the order management module. Includes customer list, create, edit, view, delete, and status toggle functionality.

## Features Implemented

### ✅ Customer List (Index)
- Searchable table (by name, number, email)
- Filter by status (active/inactive)
- Shows customer number, name, email, phone, order count, status
- Pagination support
- Quick actions: view, edit, delete
- Delete disabled for customers with orders

### ✅ Create Customer
- Form with all customer fields
- Auto-generated customer number (CUS-YYYY-NNNN)
- Active/Inactive status toggle
- Required fields validation
- Redirects to customer details after creation

### ✅ Edit Customer
- Pre-filled form with current data
- Can change status
- Can update all customer information
- Checkbox for active/inactive status

### ✅ View Customer
- Complete customer information display
- Statistics cards (total orders, delivered orders)
- Recent orders table (last 10)
- Quick actions: edit, activate/deactivate
- Link to create new order for this customer
- Link to view all customer orders

### ✅ Delete Customer
- Only possible if customer has no orders
- Confirmation dialog
- Soft prevention (button disabled if orders exist)

### ✅ Toggle Status
- Quick activate/deactivate action
- Visible on customer detail page
- Inactive customers not shown in order form dropdown

## File Structure

```
app/
├── Http/Controllers/
│   └── CustomerController.php       (All CRUD operations + toggle status)
├── Models/
│   └── Customer.php                 (Already existed)

resources/views/
└── customers/
    ├── index.blade.php              (List view with filters)
    ├── create.blade.php             (Create form)
    ├── edit.blade.php               (Edit form)
    ├── show.blade.php               (Detail view with orders)
    └── _form.blade.php              (Shared form partial)

routes/
└── customers.php                    (Resource routes + toggle status)

database/seeders/
├── CustomerPermissionsSeeder.php    (Permissions setup)
└── TestCustomerSeeder.php           (Sample data)

lang/
├── de/
│   └── customers.php                (German translations)
└── en/
    └── customers.php                (English translations)
```

## Routes

All routes protected by `auth` and `verified` middleware:

```
GET     /customers                      Index (list)
GET     /customers/create               Create form
POST    /customers                      Store new customer
GET     /customers/{customer}           Show details
GET     /customers/{customer}/edit      Edit form
PUT     /customers/{customer}           Update customer
DELETE  /customers/{customer}           Delete customer
PATCH   /customers/{customer}/toggle-status  Activate/Deactivate
```

## Permissions

Created and assigned to `order-manager` role:
- `customers.view` - View customer list and details
- `customers.create` - Create new customers
- `customers.edit` - Edit existing customers
- `customers.delete` - Delete customers (only if no orders)
- `customers.toggle-status` - Activate/Deactivate customers

### Super Admin Access

**Important**: Users with the `super-admin` role have automatic access to ALL features without needing specific permissions. This is configured in `app/Providers/AppServiceProvider.php`:

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```

This means:
- ✅ Super-admins can access customers without `customers.*` permissions
- ✅ Super-admins bypass all permission checks system-wide
- ✅ This is intentional and correct behavior for administrative accounts

**For other users**: Assign either:
- Individual permissions: `customers.view`, `customers.create`, etc.
- OR the `order-manager` role (which includes all customer permissions)

## Controller Methods

### CustomerController

**index(Request $request)**
- Lists customers with search and filter
- Search: name, customer_number, email, phone
- Filter: active/inactive status
- Pagination: 15 per page

**create()**
- Shows create form

**store(Request $request)**
- Validates input
- Creates customer with auto-generated customer_number
- Handles is_active checkbox
- Redirects to customer details

**show(Customer $customer)**
- Loads customer with last 10 orders
- Shows complete customer info
- Displays statistics and recent orders

**edit(Customer $customer)**
- Shows edit form with current data

**update(Request $request, Customer $customer)**
- Validates input
- Updates customer data
- Handles is_active checkbox
- Redirects to customer details

**destroy(Customer $customer)**
- Checks if customer has orders
- Prevents deletion if orders exist
- Deletes customer if allowed

**toggleStatus(Customer $customer)**
- Toggles is_active status
- Returns to previous page with success message

## Validation Rules

```php
'name' => 'required|string|max:255',
'email' => 'nullable|email|max:255',
'phone' => 'nullable|string|max:50',
'address' => 'nullable|string',
'notes' => 'nullable|string',
'is_active' => 'boolean',
```

## View Components

### Index View
- Bootstrap card with table
- Search input and status filter
- Action buttons (view, edit, delete)
- Delete button disabled if customer has orders
- Empty state message

### Form Partial
- Shared between create and edit
- Two-column layout
- Textarea for address and notes
- Checkbox for active status
- Help text for notes and status
- Save and cancel buttons

### Show View
- Three-column layout:
  - **Left**: Customer information card
  - **Right**: Statistics card (order counts)
  - **Bottom**: Recent orders table
- Action buttons: edit, toggle status, back to list
- Create order button (links to order form with customer pre-selected)

## Navigation

Added to sidebar under "Order Management":
- Position: After "Articles", before "Orders"
- Icon: fas fa-users
- Only visible with `customers.view` permission
- Highlights active when on customer routes

## Integration with Orders

### Pre-fill Customer in Order Form
When creating order from customer detail page:
- URL: `/orders/create?customer_id={uuid}`
- Order form auto-selects customer
- Customer type radio set to "Existing Customer"

### Customer View Shows Orders
- Last 10 orders displayed in table
- Shows order number, status, item count, date
- Link to view all orders (searches by customer number)
- Empty state if no orders

### Order Protection
- Customers with orders cannot be deleted
- Foreign key on orders table: `onDelete('set null')`
- If customer deleted (manually via DB), orders remain with null customer_id

## Translations

### German (de/customers.php)
```
customers => 'Kunden'
create => 'Neuen Kunden anlegen'
edit => 'Kunde bearbeiten'
active => 'Aktiv'
inactive => 'Inaktiv'
...and more
```

### English (en/customers.php)
```
customers => 'Customers'
create => 'Create Customer'
edit => 'Edit Customer'
active => 'Active'
inactive => 'Inactive'
...and more
```

## Usage Examples

### Create a Customer
1. Navigate to "Kunden" in sidebar
2. Click "+ Neuen Kunden anlegen"
3. Fill in name (required)
4. Optional: email, phone, address, notes
5. Check "Aktiv" checkbox (checked by default)
6. Click "Speichern"

### Search Customers
1. Go to customer list
2. Enter search term in search box
3. Optionally select status filter
4. Click "Suchen"

### View Customer Details
1. Click on customer number or view button
2. See customer info, statistics, recent orders
3. Click "Neue Bestellung" to create order for this customer

### Edit Customer
1. From customer detail page, click "Bearbeiten"
2. Update fields as needed
3. Click "Speichern"

### Deactivate Customer
1. From customer detail page, click "Deaktivieren"
2. Customer status changes to inactive
3. Customer won't appear in order form dropdown

### Delete Customer
1. From customer list, click delete button
2. Only works if customer has no orders
3. Confirm deletion in dialog

## Business Rules

1. **Customer Number**: Auto-generated on creation (CUS-YYYY-NNNN format)
2. **Active Status**: Only active customers shown in order form dropdown
3. **Deletion**: Not allowed if customer has any orders
4. **Required Fields**: Only name is required
5. **Search**: Searches across name, customer number, email, phone
6. **Orders Relationship**: One customer can have many orders
7. **Data Retention**: Customer data stored on orders for historical accuracy

## Statistics & Metrics

Customer detail page shows:
- **Total Orders**: Count of all orders for this customer
- **Delivered Orders**: Count of orders with "delivered" status
- **Recent Activity**: Last 10 orders with quick view links

## Future Enhancements

- [ ] Customer import/export (CSV, Excel)
- [ ] Customer groups/categories
- [ ] Credit limit tracking
- [ ] Payment terms
- [ ] Multiple addresses per customer (shipping/billing)
- [ ] Customer portal access
- [ ] Order history analytics per customer
- [ ] Customer lifetime value calculation
- [ ] Email integration (send statements, invoices)
- [ ] Customer notes/activity log

## Testing

### Manual Testing Checklist
- [x] Create customer with all fields
- [x] Create customer with only name
- [x] Edit customer information
- [x] Search customers by name
- [x] Search customers by email
- [x] Filter active customers
- [x] Filter inactive customers
- [x] View customer details
- [x] Toggle customer status
- [x] Attempt to delete customer with orders (should fail)
- [x] Delete customer without orders
- [x] Create order from customer detail page
- [x] Verify customer number auto-generation

### Database Integrity
- Customer number unique constraint
- Foreign key to orders table
- Active status boolean
- UUID primary key

## Files Modified

1. ✅ `app/Http/Controllers/CustomerController.php` - Updated
2. ✅ `resources/views/customers/index.blade.php` - Created
3. ✅ `resources/views/customers/create.blade.php` - Created
4. ✅ `resources/views/customers/edit.blade.php` - Created
5. ✅ `resources/views/customers/show.blade.php` - Created
6. ✅ `resources/views/customers/_form.blade.php` - Created
7. ✅ `routes/customers.php` - Created
8. ✅ `routes/web.php` - Updated (added customers.php require)
9. ✅ `database/seeders/CustomerPermissionsSeeder.php` - Created
10. ✅ `lang/de/customers.php` - Created
11. ✅ `lang/en/customers.php` - Created
12. ✅ `lang/de/common.php` - Updated (navigation)
13. ✅ `lang/en/common.php` - Updated (navigation)
14. ✅ `resources/views/layouts/navigation.blade.php` - Updated (added customers menu)

## Setup Commands

```bash
# Run permissions seeder
php artisan db:seed --class=CustomerPermissionsSeeder

# Optional: Add test customers
php artisan db:seed --class=TestCustomerSeeder

# Clear caches
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## Summary

✅ **Complete customer administration system**
✅ **Fully integrated with order management**
✅ **Permissions and role-based access control**
✅ **Multi-language support (German/English)**
✅ **Responsive UI with Bootstrap**
✅ **Search and filter capabilities**
✅ **Data integrity protection**
✅ **User-friendly interface**

---
**Implementation Date**: 2025-11-19
**Status**: ✅ Complete and Ready for Production
