<?php
// proxy.php - Updated to support Device ID & Serial Number
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$action = $_GET['action'] ?? '';
$portal = $_GET['portal'] ?? '';
$mac = $_GET['mac'] ?? '';
$sn = $_GET['sn'] ?? '';
$deviceId = $_GET['device_id'] ?? '';
$deviceId2 = $_GET['device_id2'] ?? '';
$token = $_GET['token'] ?? '';

if (!$portal || !$mac) {
    echo json_encode(["error" => "Missing Portal or MAC"]);
    exit;
}

// Format the API URL correctly
if (substr($portal, -1) != '/') $portal .= '/';
$apiUrl = $portal . "server/load.php";

// Headers to mimic a MAG Box
// We add the device_id to the cookie if available
$cookies = "mac=" . urlencode($mac) . "; stb_lang=en; timezone=Europe/London;";
if($deviceId) $cookies .= " stb_sn=" . urlencode($sn) . "; device_id=" . urlencode($deviceId) . ";";

$headers = [
    "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
    "Cookie: " . $cookies,
    "Referer: " . $portal . "c/",
    "Authorization: Bearer " . $token,
    "X-User-Agent: Model: MAG250; Link: Ethernet"
];

function makeRequest($url, $headers, $params = []) {
    $ch = curl_init();
    $queryString = http_build_query($params);
    curl_setopt($ch, CURLOPT_URL, $url . "?" . $queryString);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// 1. Handshake / Get Profile
if ($action == 'get_channels') {
    // Phase 1: Handshake (Send IDs here)
    $params = [
        'type' => 'stb', 
        'action' => 'handshake', 
        'token' => '', 
        'mac' => $mac,
        'sn' => $sn,
        'device_id' => $deviceId,
        'device_id2' => $deviceId2,
        'signature' => ''
    ];
    makeRequest($apiUrl, $headers, $params);

    // Phase 2: Get Profile
    $params = ['type' => 'stb', 'action' => 'get_profile'];
    makeRequest($apiUrl, $headers, $params);

    // Phase 3: Get Channels
    $params = ['type' => 'itv', 'action' => 'get_all_channels'];
    $resp = makeRequest($apiUrl, $headers, $params);
    echo json_encode($resp);
}

// 2. Create Stream Link
if ($action == 'create_link') {
    $cmd = $_GET['cmd'] ?? '';
    $params = ['type' => 'itv', 'action' => 'create_link', 'cmd' => $cmd, 'forced_storage' => 'false', 'disable_ad' => 'false'];
    $resp = makeRequest($apiUrl, $headers, $params);
    echo json_encode($resp);
}
?>
