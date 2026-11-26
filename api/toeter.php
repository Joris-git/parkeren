<?php
// Gedeelde "toeter"-teller voor Wat kost je zondag?
// GET  -> {"count": n}
// POST -> telt 1 op (max TOETS_PER_DAG per IP per dag) -> {"count": n, "counted": bool}
//
// Opslag: ../data/ (moet schrijfbaar zijn en niet via het web bereikbaar).
// IP-adressen worden niet bewaard: alleen een gezouten hash, en alleen voor vandaag.

declare(strict_types=1);

const TOETS_PER_DAG = 5;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$dataDir = dirname(__DIR__) . '/data';
if (!is_dir($dataDir) && !@mkdir($dataDir, 0750, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'opslag niet beschikbaar']);
    exit;
}

$countFile = $dataDir . '/teller.json';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function readCount(string $file): int {
    if (!is_file($file)) return 0;
    $d = json_decode((string) file_get_contents($file), true);
    return is_array($d) && isset($d['count']) ? max(0, (int) $d['count']) : 0;
}

if ($method === 'GET') {
    echo json_encode(['count' => readCount($countFile)]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['error' => 'alleen GET of POST']);
    exit;
}

// Alleen verzoeken vanaf de eigen site accepteren.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('http://' . $host, PHP_URL_HOST)) {
    http_response_code(403);
    echo json_encode(['error' => 'andere herkomst']);
    exit;
}

// Geheim zout, eenmalig aangemaakt.
$saltFile = $dataDir . '/zout.txt';
if (!is_file($saltFile)) {
    file_put_contents($saltFile, bin2hex(random_bytes(32)), LOCK_EX);
}
$salt = trim((string) file_get_contents($saltFile));

$today = gmdate('Ymd');
$ipHash = hash('sha256', $salt . '|' . $today . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
$rateFile = $dataDir . '/vandaag-' . $today . '.json';

// Oude dagbestanden opruimen.
foreach (glob($dataDir . '/vandaag-*.json') ?: [] as $f) {
    if ($f !== $rateFile) @unlink($f);
}

// Alles onder één lock, zodat gelijktijdige klikken niet verloren gaan.
$lock = fopen($dataDir . '/.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX)) {
    http_response_code(503);
    echo json_encode(['error' => 'even druk']);
    exit;
}

$rates = is_file($rateFile) ? (json_decode((string) file_get_contents($rateFile), true) ?: []) : [];
$used = (int) ($rates[$ipHash] ?? 0);
$count = readCount($countFile);
$counted = false;

if ($used < TOETS_PER_DAG) {
    $count++;
    $rates[$ipHash] = $used + 1;
    file_put_contents($countFile, json_encode(['count' => $count]), LOCK_EX);
    file_put_contents($rateFile, json_encode($rates), LOCK_EX);
    $counted = true;
}

flock($lock, LOCK_UN);
fclose($lock);

echo json_encode(['count' => $count, 'counted' => $counted]);
