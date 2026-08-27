<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | هنا يتم تحديد الحارس (guard) الافتراضي للتطبيق.
    | 'web' هو الحارس الافتراضي لجلسات الويب.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | هنا نُعرف الحراس المختلفين للتطبيق. كل حارس مسؤول عن طريقة
    | مصادقة معينة. لقد قمنا بتعريف حارس لكل دور باستخدام Sanctum.
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // حارس خاص بالمستخدمين العاديين عبر الـ API
        'user-api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],

        // حارس خاص بالمديرين عبر الـ API
        'manager-api' => [
            'driver' => 'sanctum',
            'provider' => 'managers',
        ],

        // حارس خاص بأمناء المستودعات عبر الـ API
        'warehouse-keeper-api' => [
            'driver' => 'sanctum',
            'provider' => 'warehouse_keepers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | هنا نُحدد كيف يتم جلب بيانات المستخدمين من قاعدة البيانات.
    | كل provider مرتبط بموديل (Eloquent model) معين.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'managers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Manager::class,
        ],

        'warehouse_keepers' => [
            'driver' => 'eloquent',
            'model' => App\Models\WarehouseKeeper::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => 10800,

];
