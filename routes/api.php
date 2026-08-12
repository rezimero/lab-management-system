<?php

require __DIR__.'/api/auth.php';
require __DIR__.'/api/portal.php';
require __DIR__.'/api/landlord.php';
require __DIR__.'/api/tenant.php';

if (app()->environment('local')) {
    require __DIR__.'/api/debug.php';
}
