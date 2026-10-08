<?php

use craft\helpers\App;

return [
    'cacheKeySuffix' => '',
    // Fall back to the built assets in web/dist when `npm run serve` is not running.
    'checkDevServer' => true,
    'criticalPath' => '@webroot/dist/criticalcss',
    'criticalSuffix' => '_critical.min.css',
    'devServerInternal' => 'http://localhost:5173',
    'devServerPublic' => App::env('PRIMARY_SITE_URL') . ':5173',
    'errorEntry' => '',
    'includeModulePreloadShim' => true,
    'includeReactRefreshShim' => false,
    'manifestPath' => '@webroot/dist/.vite/manifest.json',
    'serverPublic' => App::env('PRIMARY_SITE_URL') . '/dist/',
    'useDevServer' => App::env('ENVIRONMENT') === 'dev' || App::env('CRAFT_ENVIRONMENT') === 'dev',
];
