<?php

declare(strict_types=1);

// Единая точка входа: Apache отправляет сюда все запросы (FallbackResource)
require dirname(__DIR__) . '/vendor/autoload.php';

App\Kernel::fromEnvironment()
    ->handle(App\Http\Request::fromGlobals())
    ->send();
