<?php

/**
 * Exemplo: Assinatura com Certificado A1 (PFX)
 * 
 * Este exemplo demonstra como assinar um PDF usando certificado A1
 * armazenado no servidor (.pfx/.p12)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Ged\ApiClient\GedApiClient;

// === 1️⃣ Configuração ===
$client = new GedApiClient(
    'https://ged.api.br/api/',
    'pk_live_seu_token_aqui'
);

// === 2️⃣ Preparar dados ===
$pdfPath = 'contrato.pdf';
$pfxPath = 'certificado.pfx';
$pfxPassword = 'senha_do_certificado';

// Carrega PDF
$pdfBase64 = base64_encode(file_get_contents($pdfPath));

// === 3️⃣ Inicia assinatura no servidor ===
echo "🔄 Iniciando assinatura...\n";

$start = $client->startSignature($pdfBase64, '2.16.76.1.7.1.11.1.1');

echo "✅ PDF preparado\n";
echo "   PDF ID: {$start['pdfId']}\n";
echo "   Hash to Sign: {$start['hashToSign']}\n\n";

// === 4️⃣ Assina com certificado A1 local ===
echo "🔄 Assinando com certificado A1 local...\n";

// Extrai certificado e chave privada do PFX
openssl_pkcs12_read(file_get_contents($pfxPath), $certs, $pfxPassword);

// Assina o hash dos SignedAttributes
$signedAttrsDer = base64_decode($start['signedAttrsDerBase64']);
openssl_sign($signedAttrsDer, $signature, $certs['pkey'], OPENSSL_ALGO_SHA256);

echo "✅ Assinatura gerada: " . strlen($signature) . " bytes\n\n";

// === 5️⃣ Finaliza assinatura no servidor ===
echo "🔄 Finalizando assinatura...\n";

$complete = $client->completeSignature(
    $start['pdfId'],
    base64_encode($signature),
    base64_encode($certs['cert'])
);

echo "✅ PDF assinado com sucesso!\n";
echo "   Download: {$complete['downloadUrl']}\n\n";

// === 6️⃣ Salva PDF assinado ===
$signedPdf = base64_decode($complete['signedPdfBase64']);
file_put_contents('contrato_assinado.pdf', $signedPdf);

echo "🎉 Arquivo salvo: contrato_assinado.pdf\n";

