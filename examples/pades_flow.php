<?php

use Ged\ApiClient\GedApiClient;

require __DIR__ . '/../../vendor/autoload.php';

$baseUrl = getenv('GED_API_BASE_URL') ?: 'https://ged.api.br/api/';
$apiKey  = getenv('GED_API_KEY') ?: 'seu_token_aqui';

$client = new GedApiClient($baseUrl, $apiKey);

// 1) Prepare
$prepare = $client->padesPrepareFromFile(__DIR__ . '/../../tests/arquivo.pdf', false);
$documentId = $prepare['document_id'];
fwrite(STDERR, "Prepared: {$documentId}\n");

// 2) Cms Params
$params = $client->padesCmsParams($documentId);
// Aqui você assina localmente $params['to_be_signed_der_hex'] e obtém $cmsDerHex
// Exemplo fictício:
$cmsDerHex = 'DEADBEEF';

// 3) Inject (falhará com DER fictício; exemplo apenas estrutural)
$inject = $client->padesInject($documentId, $params['field_name'], $cmsDerHex);
fwrite(STDERR, "Injected.\n");

// 4) Finalize
$final = $client->padesFinalize($documentId);
file_put_contents(__DIR__ . '/signed_pades.pdf', base64_decode($final['pdf_base64']));
echo "OK\n";


