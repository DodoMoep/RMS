# Customer Administration - Quick Start Guide

## ⚡ Quick Setup (5 minutes)

### 1. Verify Migrations
Ensure customers table exists (already done):
```bash
php artisan migrate:status | grep customer
```

Should show:
- `create_customers_table` - Ran
- `add_customer_id_to_orders_table` - Ran

### 2. Seed Permissions
```bash
php artisan db:seed --class=CustomerPermissionsSeeder
```

### 3. (Optional) Add Test Data
```bash
php artisan db:seed --class=TestCustomerSeeder
```
Creates 3 sample customers.

### 4. Clear Caches
```bash
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

### 5. Assign Permissions to User

**Note**: If you're a `super-admin`, you already have access to everything! Skip this step.

Option A - Via Tinker (for non-super-admin users):
```bash
php artisan tinker
```
```php
$user = User::find(1); // Your user ID
$user->givePermissionTo(['customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.toggle-status']);
// Or assign order-manager role
$user->assignRole('order-manager');
exit
```

Option B - Assign role in UI:
- Go to Admin → Users
- Edit your user
- Assign "order-manager" role

### Super Admin Explanation

The `super-admin` role has automatic access to all features without needing specific permissions. This is configured in the system's `AppServiceProvider` with a global gate bypass:

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```

This means super-admins can access customers, orders, and all other features automatically.

## 🎯 First Steps

### Access Customer Management
1. Login to the application
2. Navigate to sidebar: **Order Management → Kunden**
3. You should see the customer list

### Create Your First Customer
1. Click **"+ Neuen Kunden anlegen"**
2. Enter required information:
   - **Name**: ABC Company (required)
   - **Email**: info@abc-company.com (optional)
   - **Phone**: +49 123 456789 (optional)
   - **Address**: Main Street 123, 12345 City (optional)
   - **Notes**: Regular customer, VIP (optional)
   - **Active**: ✓ Checked
3. Click **"Speichern"**
4. Customer is created with auto-generated number: **CUS-2025-0001**

### Use Customer in Order
1. Navigate to **Orders → Create**
2. Select **"Existing Customer"** radio button
3. Choose customer from dropdown: **CUS-2025-0001 - ABC Company**
4. Customer data auto-fills
5. Add order items and save

## 📊 Main Features

### Customer List
- **Search**: By name, number, or email
- **Filter**: Active/Inactive status
- **Actions**: View, Edit, Delete
- **Info**: Shows order count per customer

### Customer Details
- **View**: Complete customer information
- **Statistics**: Total orders, delivered orders
- **Recent Orders**: Last 10 orders with links
- **Actions**: 
  - Edit customer
  - Activate/Deactivate
  - Create new order for this customer

### Customer Lifecycle

```
CREATE → ACTIVE → (can be used in orders)
           ↓
       INACTIVE (cannot be selected for new orders, but existing orders remain)
           ↓
       DELETE (only if NO orders exist)
```

## 🔒 Permissions

Required permissions for different actions:

| Action | Permission |
|--------|-----------|
| View list & details | `customers.view` |
| Create new customer | `customers.create` |
| Edit customer | `customers.edit` |
| Delete customer | `customers.delete` |
| Activate/Deactivate | `customers.toggle-status` |

### Super Admin Bypass

**Important**: The `super-admin` role automatically has access to ALL features without needing specific permissions. This is by design.

**How it works:**
- Super-admins bypass all permission checks via `Gate::before()` in `AppServiceProvider`
- This allows full system access for administrative accounts
- Other users need explicit permissions or the `order-manager` role

**Recommendation**: 
- Use `super-admin` for system administrators
- Use `order-manager` for business users managing orders/customers
- Assign individual permissions for specific access control

## 💡 Common Tasks

### Find a Customer
1. Go to customer list
2. Enter name or email in search box
3. Click "Suchen"

### Deactivate Customer (Don't Delete)
1. Open customer details
2. Click **"Deaktivieren"** button
3. Customer status changes to "Inactive"
4. Customer won't appear in order form dropdown
5. Existing orders remain unchanged

### View Customer's Orders
1. Open customer details
2. Scroll to "Letzte Bestellungen" section
3. Click **"Alle Bestellungen anzeigen"** to see full list
4. Or click individual order number to view details

### Create Order for Specific Customer
**Method 1** (From customer page):
1. Open customer details
2. Click **"+ Neue Bestellung"** button in orders section
3. Order form opens with customer pre-selected

**Method 2** (From order page):
1. Navigate to Orders → Create
2. Select "Existing Customer"
3. Choose customer from dropdown

## ⚠️ Important Notes

### Customer Number
- **Format**: CUS-YYYY-NNNN (e.g., CUS-2025-0001)
- **Auto-generated**: Cannot be changed
- **Unique**: One per customer
- **Sequential**: Increments per year

### Deletion Rules
- ❌ Cannot delete customer with orders
- ✅ Can delete customer without orders
- 💡 Better to deactivate instead of delete

### Order Integration
- Customer data **copied to order** when order created
- If customer info changes, **old orders keep original data**
- If customer deleted (via DB), orders remain with NULL customer_id
- Customer link preserved for reporting/history

## 🐛 Troubleshooting

### "Customers" menu not visible
**Solution**: Check permissions
```php
// In tinker
$user = User::find(YOUR_ID);
$user->givePermissionTo('customers.view');
```

### Cannot create customer
**Solution**: Check `customers.create` permission

### Delete button disabled
**Reason**: Customer has orders
**Solution**: Either:
- Deactivate customer instead
- Delete all customer orders first (not recommended)

### Customer not appearing in order form
**Reason**: Customer is inactive
**Solution**: Activate customer via toggle button

## 📈 Best Practices

1. **Use Deactivate Instead of Delete**
   - Preserves data integrity
   - Keeps order history intact
   - Can be reactivated if needed

2. **Fill Complete Information**
   - Email for notifications
   - Phone for quick contact
   - Address for shipping/billing

3. **Use Notes Field**
   - Special requirements
   - VIP status
   - Payment terms
   - Internal remarks

4. **Regular Cleanup**
   - Review inactive customers quarterly
   - Merge duplicate entries
   - Update outdated information

5. **Search Before Create**
   - Check if customer already exists
   - Avoid duplicates
   - Use search function

## 🔗 Related Documentation

- **CUSTOMER_INTEGRATION_SUMMARY.md** - Technical implementation details
- **CUSTOMER_SELECTION_GUIDE.md** - Visual workflow diagrams
- **CUSTOMER_ADMINISTRATION_README.md** - Complete feature documentation
- **ORDER_MANAGEMENT_README.md** - Order system overview

## 🆘 Support

If you encounter issues:
1. Check permissions first
2. Clear caches: `php artisan cache:clear`
3. Check error logs: `storage/logs/laravel.log`
4. Verify migrations: `php artisan migrate:status`

---

**Ready to use!** 🎉

Access via: **Sidebar → Order Management → Kunden**
