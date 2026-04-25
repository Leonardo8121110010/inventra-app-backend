<?php

return [
    /*
     * The role name that bypasses all permission checks (super admin).
     * Change via env: PERMISSIONS_SUPER_ADMIN_ROLE=owner
     */
    'super_admin_role' => env('PERMISSIONS_SUPER_ADMIN_ROLE', 'admin'),
];
