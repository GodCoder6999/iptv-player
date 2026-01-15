<?php
// proxy.php - The Bridge for Stalker Portals
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$action = $_GET['action'] ?? '';
$portal = $_GET['portal'] ?? '';
$mac = $_GET['mac'] ?? '';
$sn = $_GET['sn'] ?? '';
$token = $_GET['token'] ?? ''; // Some portals need a token

if (!$portal || !$mac) {
    echo json_encode(["error" => "Missing Portal or MAC"]);
    exit;
}

// Format the API URL correctly
if (substr($portal, -1) != '/') $portal .= '/';
$apiUrl = $portal . "server/load.php";

// Headers to mimic a MAG Box
$headers = [
    "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
    "Cookie: mac=" . urlencode($mac) . "; stb_lang=en; timezone=Europe/London;",
    "Referer: " . $portal . "c/",
    "Authorization: Bearer " . $token // If user provides token
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
if ($action == 'handshake') {
    $params = ['type' => 'stb', 'action' => 'handshake', 'token' => '', 'mac' => $mac];
    $resp = makeRequest($apiUrl, $headers, $params);
    // Usually requires a second call to get_profile
    $params = ['type' => 'stb', 'action' => 'get_profile'];
    $resp = makeRequest($apiUrl, $headers, $params);
    echo json_encode($resp);
}

// 2. Get All Channels
if ($action == 'get_channels') {
    $params = ['type' => 'itv', 'action' => 'get_all_channels'];
    $resp = makeRequest($apiUrl, $headers, $params);
    echo json_encode($resp);
}

// 3. Get Stream Link (The most important part)
if ($action == 'create_link') {
    $cmd = $_GET['cmd'] ?? '';
    $params = ['type' => 'itv', 'action' => 'create_link', 'cmd' => $cmd, 'forced_storage' => 'false', 'disable_ad' => 'false'];
    $resp = makeRequest($apiUrl, $headers, $params);
    echo json_encode($resp);
}
?>
