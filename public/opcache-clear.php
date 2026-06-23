<?php
/**
 * Stand-alone opcache reset endpoint, hit by the deploy workflow after each
 * push. On shared hosting, opcache.validate_timestamps is often disabled or
 * has a long revalidate_freq for performance, so PHP-FPM keeps serving the
 * pre-deploy compiled bytecode for new requests until something explicitly
 * resets it (a worker restart does this too, but we usually can't trigger
 * one over SSH on shared hosting). This script is deliberately outside the
 * Laravel bootstrap so it has no framework dependency that could itself be
 * stale.
 */

$expectedToken = 'horntech-deploy-cache-bust-7f3a91';
$providedToken = $_GET['token'] ?? '';

if (!hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache reset.';
} else {
    echo 'OPcache not available.';
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo ' APCu cleared.';
}
