<?php

return [
    'title' => 'Users',
    'user' => 'User',
    'users' => 'Users',
    'create' => 'Create User',
    'edit' => 'Edit User',
    'delete' => 'Delete User',
    
    'name' => 'Name',
    'email' => 'Email',
    'password' => 'Password',
    'password_confirmation' => 'Confirm Password',
    'roles' => 'Roles',
    'permissions' => 'Permissions',
    'status' => 'Status',
    'status_active' => 'Active',
    'status_inactive' => 'Deactivated',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',
    
    'messages' => [
        'created' => 'User successfully created',
        'updated' => 'User successfully updated',
        'deleted' => 'User successfully deleted',
        'confirm_delete' => 'Are you sure you want to delete this user?',
        'confirm_deactivate' => 'Really deactivate this user? They will no longer be able to log in, their orders are kept.',
        'activated' => 'User activated.',
        'deactivated' => 'User deactivated.',
        'cannot_deactivate_self' => 'You cannot deactivate your own account.',
        'cannot_deactivate_last_super_admin' => 'The last active super admin cannot be deactivated.',
    ],
];
