<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Commission;

$pending = Commission::where('status', 'pending')->count();
$paid = Commission::where('status', 'paid')->count();
echo "Pending: $pending\nPaid: $paid\n";
