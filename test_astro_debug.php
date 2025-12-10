#!/usr/bin/env php
<?php

/**
 * AstronomyAPI Debug Script
 * Проверяет различные методы аутентификации
 */

$appId = '9ec70b76-acfc-4f96-bed0-81509abf8d84';
$appSecret = 'f123cff6a029b264b2805dd8b720812376e0584c15309605ea6bfe58a8f21718cadd23006be721c84add1b26c04d6f3539a170b7bd28e3f68e074f260618b47d4a8d477acc78a1d2183188eae97020c54ddf57f38c38f377438af8768292456505ce5491cec11781c84e8a8a0c8cfaf4';

echo "🔍 AstronomyAPI Debug Test\n";
echo "==========================\n\n";

echo "App ID: {$appId}\n";
echo "Secret length: " . strlen($appSecret) . " chars\n\n";

// Test 1: Basic Auth с withBasicAuth()
echo "📌 Test 1: Laravel Http::withBasicAuth()\n";
echo "------------------------------------------\n";

$ch = curl_init();
$url = "https://api.astronomyapi.com/api/v2/bodies/events/sun?latitude=55.7558&longitude=37.6176&from_date=2025-12-09&to_date=2025-12-12&time=12:00:00&elevation=0";

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "{$appId}:{$appSecret}");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response: " . substr($response, 0, 500) . "\n\n";

// Test 2: Manual Base64 Authorization header
echo "📌 Test 2: Manual Authorization: Basic header\n";
echo "----------------------------------------------\n";

$authString = base64_encode("{$appId}:{$appSecret}");
echo "Auth String length: " . strlen($authString) . " chars\n";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    "Authorization: Basic {$authString}",
    'Accept: application/json'
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Code: {$httpCode2}\n";
echo "Response: " . substr($response2, 0, 500) . "\n\n";

// Test 3: Test /bodies endpoint (simpler)
echo "📌 Test 3: Simple /bodies endpoint\n";
echo "-----------------------------------\n";

$ch3 = curl_init();
$simpleUrl = "https://api.astronomyapi.com/api/v2/bodies";

curl_setopt($ch3, CURLOPT_URL, $simpleUrl);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_HTTPHEADER, [
    "Authorization: Basic {$authString}",
    'Accept: application/json'
]);

$response3 = curl_exec($ch3);
$httpCode3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

echo "HTTP Code: {$httpCode3}\n";
echo "Response: " . substr($response3, 0, 500) . "\n\n";

// Test 4: Check account status endpoint
echo "📌 Test 4: Account status (if exists)\n";
echo "--------------------------------------\n";

$ch4 = curl_init();
$statusUrl = "https://api.astronomyapi.com/api/v2/status";

curl_setopt($ch4, CURLOPT_URL, $statusUrl);
curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch4, CURLOPT_HTTPHEADER, [
    "Authorization: Basic {$authString}",
    'Accept: application/json'
]);

$response4 = curl_exec($ch4);
$httpCode4 = curl_getinfo($ch4, CURLINFO_HTTP_CODE);
curl_close($ch4);

echo "HTTP Code: {$httpCode4}\n";
echo "Response: " . substr($response4, 0, 500) . "\n\n";

echo "==========================\n";
echo "✅ Test completed!\n\n";

echo "🔍 Recommendations:\n";
echo "-------------------\n";
echo "1. Check your AstronomyAPI dashboard: https://astronomyapi.com/dashboard\n";
echo "2. Verify account is activated and subscription is active\n";
echo "3. Contact support if 403 errors persist: support@astronomyapi.com\n";
echo "4. Check API status: https://astronomyapi.statuspage.io/\n";
