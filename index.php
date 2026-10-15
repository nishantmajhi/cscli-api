<?php

declare(strict_types=1);

/**
 * Front controller.
 *
 * Everything this file does is wiring: read the request out of the
 * superglobals, build the two objects the route table needs, hand the
 * resulting Response to the socket. The decisions are in utils/dispatch.php,
 * which knows nothing about $_SERVER or headers and is therefore testable.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'dispatch.php';

$router = new Router($_SERVER['REQUEST_URI'] ?? '/');
$catalog = new Catalog(__DIR__ . DIRECTORY_SEPARATOR . 'src');

dispatch(
    $router,
    $catalog,
    wantsJson($_SERVER['HTTP_ACCEPT'] ?? ''),
    $_SERVER['HTTP_IF_NONE_MATCH'] ?? null
)->send();
