#!/usr/bin/env php
<?php

// Test script to check AstronomyAPI credentials format

$appId = '4e238a51-a5db-42ba-b850-1909dcb74ce5';
$appSecret = 'f123cff6a029b264b2805dd8b720812376e0584c15309605ea6bfe58a8f21718cadd23006be721c84add1b26c04d6f3539a170b7bd28e3f68e074f260618b47db88e05c0cd7a8e534b08d608c3dec9e38a718cd777344d9108c3b4085a21c3f9857cc15707648878292ed78527057f48';

echo "=== AstronomyAPI Credentials Analysis ===\n\n";

echo "Application ID:\n";
echo "  Value: {$appId}\n";
echo "  Length: " . strlen($appId) . " chars\n";
echo "  Format: " . (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $appId) ? 'Valid UUID' : 'INVALID UUID') . "\n\n";

echo "Application Secret:\n";
echo "  Length: " . strlen($appSecret) . " chars\n";
echo "  First 40 chars: " . substr($appSecret, 0, 40) . "...\n";
echo "  Last 20 chars: ..." . substr($appSecret, -20) . "\n";
echo "  Contains only HEX (0-9,a-f): " . (ctype_xdigit($appSecret) ? 'YES' : 'NO') . "\n";
echo "  Expected length: 64 chars (SHA-256) or 128 chars (SHA-512)\n";
echo "  Actual length: " . strlen($appSecret) . " chars - ";
if (strlen($appSecret) == 64) {
    echo "✓ CORRECT (SHA-256)\n";
} elseif (strlen($appSecret) == 128) {
    echo "✓ CORRECT (SHA-512)\n";
} else {
    echo "✗ WRONG LENGTH!\n";
    echo "  Your secret appears to be CONCATENATED or MALFORMED\n";
}

echo "\nBasic Auth String:\n";
$authString = $appId . ':' . $appSecret;
echo "  Combined length: " . strlen($authString) . " chars\n";
$encoded = base64_encode($authString);
echo "  Base64 encoded length: " . strlen($encoded) . " chars\n";
echo "  Ends with '=': " . (substr($encoded, -1) === '=' ? 'YES (problematic for AstronomyAPI)' : 'NO') . "\n";
echo "  Base64 hash: " . substr($encoded, -50) . "\n";

echo "\n=== RECOMMENDATION ===\n";
if (strlen($appSecret) !== 64 && strlen($appSecret) !== 128) {
    echo "⚠️  Your Application Secret has WRONG length!\n";
    echo "   Expected: 64 or 128 hex characters\n";
    echo "   Actual: " . strlen($appSecret) . " characters\n\n";
    echo "   ACTION REQUIRED:\n";
    echo "   1. Go to https://astronomyapi.com/applications\n";
    echo "   2. Find the 'Application Secret' field (NOT Application Hash!)\n";
    echo "   3. Copy ONLY the Application Secret (64-128 hex chars)\n";
    echo "   4. Update ASTRO_APP_SECRET in .env file\n";
    echo "   5. Run: docker-compose restart php nginx\n";
} else {
    echo "✓ Credentials format looks correct!\n";
    echo "  If still getting 403 errors, the API key might be invalid or expired.\n";
}

echo "\n";
