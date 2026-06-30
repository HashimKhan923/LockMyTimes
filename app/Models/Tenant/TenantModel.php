<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for every tenant model.
 * Forces the "tenant" connection so queries hit the dynamically-switched DB.
 */
abstract class TenantModel extends Model
{
    protected $connection = 'tenant';
}