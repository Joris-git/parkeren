<?php
// Gedeelde "toeter"-teller voor Wat kost je zondag?
// GET  -> {"count": n}
// POST -> telt 1 op -> {"count": n, "counted": bool}
//
// Er wordt niets per bezoeker opgeslagen: geen IP-adressen, geen cookies.
// Tegen opdrijven is er alleen een rem voor de hele site: maximaal
// MAX_PER_MINUUT toeters per minuut, van alle bezoekers samen.
//
// Opslag: ../data/ (moet schrijfbaar zijn en niet via het web bereikbaar).

declare(strict_types=1);

const MAX_PER_MINUUT = 60;

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

function readJson(string $file): array {
    if (!is_file($file)) return [];
    $d = json_decode((string) file_get_contents($file), true);
    return is_array($d) ? $d : [];
}

if ($method === 'GET') {
    echo json_encode(['count' => max(0, (int) (readJson($countFile)['count'] ?? 0))]);
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

// Alles onder één lock, zodat gelijktijdige klikken niet verloren gaan.
$lock = fopen($dataDir . '/.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX)) {
    http_response_code(503);
    echo json_encode(['error' => 'even druk']);
    exit;
}

$minuteFile = $dataDir . '/minuut.json';
$minute = gmdate('YmdHi');
$m = readJson($minuteFile);
$inMinute = ($m['minuut'] ?? '') === $minute ? (int) ($m['aantal'] ?? 0) : 0;

$count = max(0, (int) (readJson($countFile)['count'] ?? 0));
$counted = false;

if ($inMinute < MAX_PER_MINUUT) {
    $count++;
    file_put_contents($countFile, json_encode(['count' => $count]), LOCK_EX);
    file_put_contents($minuteFile, json_encode(['minuut' => $minute, 'aantal' => $inMinute + 1]), LOCK_EX);
    $counted = true;
}

flock($lock, LOCK_UN);
fclose($lock);

echo json_encode(['count' => $count, 'counted' => $counted]);
