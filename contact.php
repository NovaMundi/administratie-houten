<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://administratiehouten.nl');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$to      = 'info@administratiehouten.nl';
$subject = 'Nieuw contactverzoek via administratiehouten.nl';

$naam    = htmlspecialchars(trim($data['Voornaam'] ?? $data['Naam'] ?? ''));
$achter  = htmlspecialchars(trim($data['Achternaam'] ?? ''));
$email   = filter_var(trim($data['E-mailadres'] ?? ''), FILTER_SANITIZE_EMAIL);
$tel     = htmlspecialchars(trim($data['Telefoonnummer'] ?? ''));
$dienst  = htmlspecialchars(trim($data['Wat zoekt u?'] ?? $data['Wat kan ik voor u betekenen?'] ?? ''));
$bericht = htmlspecialchars(trim($data['Bericht'] ?? ''));

if (!$naam || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Naam en geldig e-mailadres zijn verplicht']);
    exit;
}

$body  = "Nieuw contactverzoek van administratiehouten.nl\n";
$body .= "================================================\n\n";
$body .= "Naam:        $naam $achter\n";
$body .= "E-mail:      $email\n";
if ($tel)    $body .= "Telefoon:    $tel\n";
if ($dienst) $body .= "Onderwerp:   $dienst\n";
if ($bericht) {
    $body .= "\nBericht:\n$bericht\n";
}
$body .= "\n================================================\n";
$body .= "Verzonden op: " . date('d-m-Y H:i') . "\n";

$headers  = "From: noreply@administratiehouten.nl\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$ok = mail($to, $subject, $body, $headers);

if ($ok) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Kon e-mail niet verzenden']);
}
