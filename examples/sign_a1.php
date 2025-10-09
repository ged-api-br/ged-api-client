<?php

/**
 * ============================================================================
 * Exemplo: Assinatura com Certificado A1 (PFX) - VERSÃO ATUALIZADA
 * ============================================================================
 * 
 * Este exemplo demonstra o fluxo completo de assinatura digital usando:
 * - Certificado A1 armazenado localmente (.pfx/.p12)
 * - CmsBuilder para montar CMS/PKCS#7 correto
 * - API ged.api.br
 * 
 * Fluxo:
 *   1. Cliente envia PDF → Servidor prepara e retorna SignedAttributes
 *   2. Cliente assina SignedAttributes com chave privada local
 *   3. Cliente monta CMS completo com CmsBuilder
 *   4. Cliente envia CMS → Servidor injeta no PDF
 *   5. Cliente baixa PDF assinado
 * ============================================================================
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Ged\ApiClient\GedApiClient;
use Ged\ApiClient\CmsBuilder;

// === 1️⃣ CONFIGURAÇÃO ===
$apiBaseUrl = 'https://ged.api.br/api/';
$apiKey = 'pk_live_seu_token_aqui';

$client = new GedApiClient($apiBaseUrl, $apiKey);
$cmsBuilder = new CmsBuilder();

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   🔐 ASSINATURA DIGITAL COM CERTIFICADO A1\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// === 2️⃣ PREPARAR DADOS ===
$pdfPath = 'contrato.pdf';
$pfxPath = 'certificado.pfx';
$pfxPassword = 'senha_do_certificado';

if (!file_exists($pdfPath)) {
    die("❌ Arquivo não encontrado: $pdfPath\n");
}

if (!file_exists($pfxPath)) {
    die("❌ Certificado não encontrado: $pfxPath\n");
}

echo "📄 PDF: $pdfPath (" . number_format(filesize($pdfPath)) . " bytes)\n";
echo "🔐 Certificado: $pfxPath\n\n";

// === 3️⃣ EXTRAIR CERTIFICADO E CHAVE DO PFX ===
echo "━━━ EXTRAINDO CERTIFICADO DO PFX ━━━\n";

$pfxContent = file_get_contents($pfxPath);
if (!openssl_pkcs12_read($pfxContent, $certs, $pfxPassword)) {
    die("❌ Falha ao ler PFX. Verifique a senha.\n");
}

$certPem = $certs['cert'];
$privateKey = $certs['pkey'];

// Converter certificado para DER
$certDer = base64_decode(preg_replace(
    '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
    '',
    $certPem
));

echo "✅ Certificado extraído\n";
echo "✅ Chave privada carregada\n\n";

// === 4️⃣ INICIAR ASSINATURA NO SERVIDOR ===
echo "━━━ FASE 1-3: SERVIDOR (Preparar PDF) ━━━\n";

try {
    $response = $client->post('/sign/start', [
        'multipart' => [
            [
                'name' => 'file',
                'contents' => fopen($pdfPath, 'r'),
                'filename' => basename($pdfPath)
            ],
            [
                'name' => 'cert_der_base64',
                'contents' => base64_encode($certDer)
            ],
            [
                'name' => 'policy_oid',
                'contents' => '2.16.76.1.7.1.11.1.1' // PAdES AD-RB v1.1
            ],
            [
                'name' => 'policy_uri',
                'contents' => 'https://iti.gov.br/politica/pa.pdf'
            ],
            [
                'name' => 'policy_hash_hex',
                'contents' => 'ddb84f2009dae5cb11c2c5d274ba8c79ac6f621a5b389363a85f784a4bd7db64'
            ]
        ]
    ]);

    $uuid = $response['uuid'];
    $signedAttributesHex = $response['signedAttributesHex'];
    $hashHex = $response['hashHex'];

    echo "✅ PDF preparado no servidor\n";
    echo "   UUID: $uuid\n";
    echo "   Hash: " . substr($hashHex, 0, 32) . "...\n\n";

} catch (\Exception $e) {
    die("❌ Erro ao iniciar assinatura: " . $e->getMessage() . "\n");
}

// === 5️⃣ ASSINAR LOCALMENTE (FASE 4) ===
echo "━━━ FASE 4: CLIENTE (Assinar com A1) ━━━\n";

$signedAttributesDer = hex2bin($signedAttributesHex);

// Assinar SignedAttributes com chave privada local
$signSuccess = openssl_sign(
    $signedAttributesDer,
    $signature,
    $privateKey,
    OPENSSL_ALGO_SHA256
);

if (!$signSuccess) {
    die("❌ Falha ao gerar assinatura RSA\n");
}

echo "✅ Assinatura RSA: " . strlen($signature) . " bytes\n";

// Verificar assinatura (opcional)
$publicKey = openssl_pkey_get_public($certPem);
$verify = openssl_verify($signedAttributesDer, $signature, $publicKey, OPENSSL_ALGO_SHA256);

if ($verify === 1) {
    echo "✅ Assinatura verificada\n\n";
} else {
    echo "⚠️  Assinatura não verificada\n\n";
}

// Montar CMS completo
echo "⚙️  Montando CMS com CmsBuilder...\n";

try {
    $cmsDer = $cmsBuilder->build(
        $signedAttributesDer,
        $signature,
        $certDer,
        $certPem
    );
    
    echo "✅ CMS montado: " . strlen($cmsDer) . " bytes\n";
    echo "   Hex: " . strlen(bin2hex($cmsDer)) . " chars\n\n";
    
} catch (\Exception $e) {
    echo "⚠️  CmsBuilder falhou, usando fallback openssl...\n";
    
    // Fallback: usar método openssl
    $cmsDer = $cmsBuilder->buildWithOpenssl(
        $signedAttributesDer,
        $certPem,
        openssl_pkey_export($privateKey, $keyPem) ? $keyPem : ''
    );
    
    echo "✅ CMS gerado (fallback): " . strlen($cmsDer) . " bytes\n\n";
}

// === 6️⃣ COMPLETAR ASSINATURA NO SERVIDOR ===
echo "━━━ FASE 5: SERVIDOR (Injetar CMS no PDF) ━━━\n";

try {
    $result = $client->post('/sign/complete', [
        'json' => [
            'uuid' => $uuid,
            'cmsHex' => bin2hex($cmsDer)
        ]
    ]);

    echo "✅ CMS injetado no PDF\n";
    echo "   Download: {$result['downloadUrl']}\n\n";

} catch (\Exception $e) {
    die("❌ Erro ao completar assinatura: " . $e->getMessage() . "\n");
}

// === 7️⃣ BAIXAR PDF ASSINADO ===
echo "━━━ DOWNLOAD DO PDF ASSINADO ━━━\n";

try {
    $signedPdf = $client->get($result['downloadUrl']);
    $outputPath = 'contrato_assinado.pdf';
    
    file_put_contents($outputPath, $signedPdf);
    
    echo "✅ PDF assinado salvo: $outputPath\n";
    echo "📊 Tamanho: " . number_format(filesize($outputPath)) . " bytes\n\n";

} catch (\Exception $e) {
    die("❌ Erro ao baixar PDF: " . $e->getMessage() . "\n");
}

// === 8️⃣ RESUMO ===
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "   🎉 ASSINATURA CONCLUÍDA COM SUCESSO!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📄 Arquivo assinado: $outputPath\n";
echo "🔐 Certificado: " . (openssl_x509_parse($certPem)['subject']['CN'] ?? 'N/A') . "\n";
echo "📊 Tamanho: " . number_format(filesize($outputPath)) . " bytes\n\n";

echo "🔍 Próximos passos:\n";
echo "   1. Abra o PDF no Adobe Reader\n";
echo "   2. Verifique a assinatura digital (Ctrl+D)\n";
echo "   3. Valide com ITI Verificador (se disponível)\n\n";

echo "═══════════════════════════════════════════════════════════════\n\n";
