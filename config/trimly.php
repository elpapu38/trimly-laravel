<?php

// ============================================================
//  config/trimly.php — Configuración propia de Trimly
//  (equivalente al config/app.php del sistema original;
//   la config nativa de Laravel vive en config/app.php ahora)
// ============================================================

return [
    'per_page' => 12,

    'mercadopago' => [
        'public_key' => env('MP_PUBLIC_KEY', ''),
        'access_token' => env('MP_ACCESS_TOKEN', ''),
        'sandbox' => true,
    ],

    'roles' => [
        'superadmin' => 'Super Administrador',
        'shop_owner' => 'Dueño de Local',
        'employee' => 'Empleado',
        'client' => 'Cliente',
    ],

    'appointment_statuses' => [
        'pending' => ['label' => 'Pendiente', 'color' => 'yellow'],
        'confirmed' => ['label' => 'Confirmado', 'color' => 'green'],
        'cancelled_client' => ['label' => 'Cancelado por cliente', 'color' => 'red'],
        'cancelled_shop' => ['label' => 'Cancelado por el local', 'color' => 'red'],
        'completed' => ['label' => 'Completado', 'color' => 'blue'],
        'no_show' => ['label' => 'No se presentó', 'color' => 'gray'],
    ],
];
