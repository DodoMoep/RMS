<?php

namespace App\Enums;

enum ContactType: string
{
    case Tenant   = 'tenant';
    case Customer = 'customer';
    case Both     = 'both';
}
