# Permission System Explanation

## How It Works

The application uses **Laravel's Gate system** with **Spatie Laravel Permission** package for role-based access control (RBAC).

## Super Admin Role

### Global Bypass

The `super-admin` role has a special privilege defined in `app/Providers/AppServiceProvider.php`:

```php
Gate::before(function ($user, $ability) {
    // If user has 'super-admin' role, allow everything
    return $user->hasRole('super-admin') ? true : null;
});
```

### What This Means

✅ **Super-admins can access EVERYTHING** without needing specific permissions
✅ **Bypasses all `@can` and `can()` checks** throughout the application
✅ **This is intentional and correct** for system administrators

### Examples

```php
// In views
@can('customers.view')
    <!-- Super-admin sees this even without the permission -->
@endcan

// In controllers
if ($request->user()->can('customers.edit')) {
    // Super-admin passes this check automatically
}

// In routes
Route::middleware(['permission:customers.create'])->group(function () {
    // Super-admin has access even without the permission
});
```

## Role Hierarchy

### 1. Super Admin
- **Access**: Everything (global bypass)
- **Use Case**: System administrators, developers
- **Permissions**: None needed (bypasses all checks)
- **Example**: IT department

### 2. Order Manager
- **Access**: Full order and customer management
- **Use Case**: Business managers, department heads
- **Permissions**: All `orders.*` and `customers.*` permissions
- **Example**: Sales manager, Operations manager

### 3. Packer
- **Access**: View and pack orders only
- **Use Case**: Warehouse staff, fulfillment team
- **Permissions**: `orders.view`, `orders.pack`
- **Example**: Warehouse worker

### 4. Custom Roles
- **Access**: Specific permission combinations
- **Use Case**: Specialized staff with limited access
- **Permissions**: As needed
- **Example**: View-only accountant, inventory manager

## Permission Naming Convention

Format: `resource.action`

### Customer Permissions
- `customers.view` - View customer list and details
- `customers.create` - Create new customers
- `customers.edit` - Edit existing customers
- `customers.delete` - Delete customers
- `customers.toggle-status` - Activate/deactivate customers

### Order Permissions
- `orders.view` - View all orders
- `orders.create` - Create new orders
- `orders.edit` - Edit orders
- `orders.delete` - Delete orders
- `orders.pack` - Pack orders (packer role)
- `orders.print` - Print delivery notes
- `orders.deliver` - Mark as delivered
- `orders.view-history` - View order history

### Analytics Permissions
- `analytics.view` - View analytics dashboard
- `analytics.export` - Export reports

## How to Assign Permissions

### Method 1: Assign Role (Recommended)

```php
// Via Tinker
php artisan tinker

// Assign super-admin (full access)
$user = User::find(1);
$user->assignRole('super-admin');

// Assign order-manager (order + customer management)
$user = User::find(2);
$user->assignRole('order-manager');

// Assign packer (packing only)
$user = User::find(3);
$user->assignRole('packer');
```

### Method 2: Assign Individual Permissions

```php
// Via Tinker
$user = User::find(1);

// Single permission
$user->givePermissionTo('customers.view');

// Multiple permissions
$user->givePermissionTo([
    'customers.view',
    'customers.create',
    'customers.edit'
]);
```

### Method 3: Via UI

1. Navigate to **Admin → Users**
2. Click **Edit** on the user
3. Select role(s) or permissions
4. Click **Save**

## Checking Permissions in Code

### In Blade Views

```php
{{-- Check permission --}}
@can('customers.view')
    <a href="{{ route('customers.index') }}">View Customers</a>
@endcan

{{-- Check role --}}
@role('super-admin')
    <div>Super Admin Panel</div>
@endrole

{{-- Check multiple permissions (OR) --}}
@canany(['orders.view', 'orders.create'])
    <div>Order Management</div>
@endcanany
```

### In Controllers

```php
// Check permission
if ($request->user()->can('customers.edit')) {
    // User can edit customers
}

// Check role
if ($request->user()->hasRole('super-admin')) {
    // User is super-admin
}

// Abort if no permission
abort_unless($request->user()->can('customers.delete'), 403);

// Authorize (throws exception if fails)
$this->authorize('customers.edit');
```

### In Routes

```php
// Single permission
Route::get('/customers', [CustomerController::class, 'index'])
    ->middleware('permission:customers.view');

// Multiple permissions (OR)
Route::middleware(['permission:customers.view|customers.edit'])->group(function () {
    // Routes
});
```

## Common Scenarios

### Scenario 1: New Employee Needs Customer Access
```bash
php artisan tinker
```
```php
$user = User::where('email', 'newemployee@company.com')->first();
$user->assignRole('order-manager');
// Now they have full customer + order access
```

### Scenario 2: Temporary View-Only Access
```php
$user = User::find(5);
$user->givePermissionTo('customers.view');
// Can view but not edit/delete
```

### Scenario 3: Remove Permission
```php
$user = User::find(5);
$user->revokePermissionTo('customers.delete');
// Can no longer delete customers
```

### Scenario 4: Check User's Permissions
```php
$user = User::find(1);

// Get all permissions
$permissions = $user->getAllPermissions();

// Get all roles
$roles = $user->getRoleNames();

// Check specific permission
$canEdit = $user->hasPermissionTo('customers.edit');
```

## Why Super-Admin Doesn't Need Permissions

### Design Philosophy
1. **Separation of Concerns**
   - Super-admins manage the system
   - Other roles manage business operations

2. **Flexibility**
   - New features automatically available to super-admins
   - No need to update permissions for each new feature

3. **Emergency Access**
   - Super-admins can always access everything
   - Useful for troubleshooting and emergency fixes

4. **Simplified Management**
   - Don't need to maintain super-admin permissions
   - Focus on business role permissions only

## Best Practices

### ✅ DO
- Assign `super-admin` to IT staff and developers
- Use `order-manager` for business users
- Create custom roles for specific access needs
- Use role assignment over individual permissions when possible
- Document custom roles and their purposes

### ❌ DON'T
- Don't assign `super-admin` to regular business users
- Don't manually assign permissions to super-admins (not needed)
- Don't create too many custom roles (keep it simple)
- Don't bypass permission checks in code without good reason

## Troubleshooting

### "I can't see the Customers menu"
**Check**: Do you have `customers.view` permission or `super-admin`/`order-manager` role?

```php
// In tinker
$user = User::find(YOUR_ID);
$user->hasPermissionTo('customers.view'); // Should return true
// OR
$user->hasRole('order-manager'); // Should return true
```

### "Super-admin can access customers without permissions - is this a bug?"
**Answer**: No, this is intentional! Super-admins bypass all permission checks.

### "How do I remove someone's access?"
```php
// Remove role
$user->removeRole('order-manager');

// Remove specific permission
$user->revokePermissionTo('customers.edit');

// Remove all permissions
$user->syncPermissions([]);
```

### "Can I have multiple roles?"
**Answer**: Yes! A user can have multiple roles, and they'll have the combined permissions of all roles.

```php
$user->assignRole(['order-manager', 'packer']);
// Has all permissions from both roles
```

## Security Notes

1. **Super-admin is powerful** - Only assign to trusted users
2. **Permissions are cached** - Clear cache after changes: `php artisan permission:cache-reset`
3. **Always use `@can` in views** - Don't rely on hiding links alone
4. **Validate in controllers** - Check permissions in backend code
5. **Audit regularly** - Review who has what access periodically

## Summary

| Role | Access Level | Use Case |
|------|-------------|----------|
| **super-admin** | Everything (bypass) | System administrators |
| **order-manager** | Orders + Customers | Business managers |
| **packer** | Pack orders only | Warehouse staff |
| **Custom** | Specific permissions | Specialized roles |

**Remember**: Super-admins don't need specific permissions - they have access to everything automatically!

---

For more information, see:
- [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Laravel Authorization Documentation](https://laravel.com/docs/authorization)
