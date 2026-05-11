<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Datamarket (FastAPI -> SQL Server). Replicacion best-effort de retenciones.
    */
    'datamarket' => [
        'enabled'         => env('DATAMARKET_ENABLED', true),
        'base_url'        => env('DATAMARKET_BASE_URL', 'http://127.0.0.1:8000'),
        'timeout'         => (int) env('DATAMARKET_TIMEOUT', 5),
        'connect_timeout' => (int) env('DATAMARKET_CONNECT_TIMEOUT', 2),
        'token'           => env('DATAMARKET_TOKEN', null),
    ],

    /*
    | DB Peru e-dbfact: servicio remoto que firma el XML, envia a SUNAT y
    | devuelve CodigoHash / CodigoQr / pdf417. Header `credencial` (literal).
    */
    'dbperu' => [
        'enabled'    => env('DBPERU_ENABLED', true),
        'base_url'   => env('DBPERU_BASE_URL', 'http://e-dbfact.dbperu.com:8180'),
        'credencial' => env('DBPERU_CREDENCIAL', ''),
        'timeout'    => (int) env('DBPERU_TIMEOUT', 60),
    ],

    /*
    | Datos extra del Emisor que SUNAT exige en el JSON de Resumen de Reversion
    | (baja del CRE). El RUC, razon social y direccion siguen viniendo de la
    | tabla parametros (IDs 2, 3, 4). Estos otros 7 los leemos del .env porque
    | son configuracion estatica de la empresa (no cambian por documento).
    */
    'emisor' => [
        'nombre_comercial' => env('EMISOR_NOMBRE_COMERCIAL', ''),
        'ubigeo'           => env('EMISOR_UBIGEO', ''),
        'urbanizacion'     => env('EMISOR_URBANIZACION', ''),
        'departamento'     => env('EMISOR_DEPARTAMENTO', ''),
        'provincia'        => env('EMISOR_PROVINCIA', ''),
        'distrito'         => env('EMISOR_DISTRITO', ''),
        'email'            => env('EMISOR_EMAIL', ''),
    ],

];
