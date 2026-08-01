<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supplier Details
    |--------------------------------------------------------------------------
    |
    | Static details of the supplying legal entity, printed on the order
    | delivery note PDF.
    |
    */

    'supplier' => [
        'name' => env('COMPANY_SUPPLIER_NAME', 'TOO Food Alem'),
        'bin' => env('COMPANY_SUPPLIER_BIN', '250640027310'),
    ],

];
