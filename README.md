# 📦 GED API Client (PHP SDK)

Cliente oficial PHP da [GED API](https://ged.api.br) — Provedor de Assinatura Digital ICP-Brasil.

---

## 🚀 Instalação

```bash
composer require ged/api-client
```

---

## ⚙️ Exemplo de uso (Certificado A1) – fluxo legado

```php
use Ged\ApiClient\GedApiClient;

$client = new GedApiClient('https://ged.api.br/api/', 'seu_token_aqui');

// 1️⃣ Inicia assinatura
$start = $client->startSignature(
    base64_encode(file_get_contents('contrato.pdf')), 
    '2.16.76.1.7.1.11.1.1'
);

// 2️⃣ Assina localmente com o certificado A1 (PFX)
openssl_pkcs12_read(file_get_contents('certificado.pfx'), $certs, 'senha');
openssl_sign(base64_decode($start['signedAttrsDerBase64']), $sig, $certs['pkey'], OPENSSL_ALGO_SHA256);

// 3️⃣ Finaliza assinatura no GED API
$complete = $client->completeSignature(
    $start['pdfId'],
    base64_encode($sig),
    base64_encode($certs['cert'])
);

file_put_contents('assinado.pdf', base64_decode($complete['signedPdfBase64']));
```

**Veja o exemplo completo em**: [`examples/sign_a1.php`](./examples/sign_a1.php)

---

## 📋 Métodos Disponíveis

### `startSignature(string $pdfBase64, string $policyOid): array`
Inicia o processo de assinatura digital

**Parâmetros:**
- `$pdfBase64`: PDF em base64
- `$policyOid`: OID da política ICP-Brasil (ex: `2.16.76.1.7.1.11.1.1`)

**Retorna:**
```php
[
    'pdfId' => 'uuid',
    'hashToSign' => 'hash_sha256',
    'signedAttrsDerBase64' => 'base64_der'
]
```

### `completeSignature(string $pdfId, string $signatureBase64, string $certBase64): array`
Finaliza a assinatura e retorna PDF assinado

**Parâmetros:**
- `$pdfId`: ID retornado por `startSignature()`
- `$signatureBase64`: Assinatura digital em base64
- `$certBase64`: Certificado X.509 em base64

**Retorna:**
```php
[
    'signedPdfBase64' => 'base64_pdf',
    'downloadUrl' => 'url'
]
```

### `verifySignature(string $pdfBase64): array`
Verifica validade de PDF assinado

**Retorna:**
```php
[
    'valid' => true,
    'signatures' => [...]
]
```

---

## 🔐 Políticas ICP-Brasil

| Política | OID |
|----------|-----|
| AD-RB | 2.16.76.1.7.1.11.1.1 |
| AD-RT | 2.16.76.1.7.1.11.1.2 |
| AD-RC | 2.16.76.1.7.1.11.1.3 |

---

## 📄 Licença

MIT

---

## ✒️ PAdES (novo fluxo recomendado)

Autenticação: `Authorization: Bearer <API_KEY>` (compat `X-API-KEY` mantida).

### Exemplo rápido

```php
use Ged\ApiClient\GedApiClient;

$client = new GedApiClient('https://ged.api.br/api/', 'seu_token_aqui');

// 1) Prepare (com opção de anotações)
$prepare = $client->padesPrepareFromFile('contrato.pdf', visible: false, $anots ?? null);
$documentId = $prepare['document_id'];

// 2) Cms Params (dados para assinar localmente) — envie o certificado do signatário
$signerCertDerBase64 = base64_encode($certDer);
$params = $client->padesCmsParams($documentId, $signerCertDerBase64);
// Assine $params['to_be_signed_der_hex'] com seu A1/A3 e obtenha $cmsDerHex

// 3) Inject (duas opções)
// a) CMS DER pronto
$inject = $client->padesInject($documentId, $params['field_name'], $cmsDerHex);
// b) Assinatura crua PKCS#1 + cert (servidor monta CMS)
//$inject = $client->padesInjectPkcs1($documentId, $params['field_name'], $pkcs1DerHex, base64_encode($certDer));

// 4) Finalize
$final = $client->padesFinalize($documentId);
file_put_contents('assinado_pades.pdf', base64_decode($final['pdf_base64']));
```

### Novos métodos

- `padesPrepareFromBase64(string $pdfBase64, bool $visible = false, ?array $anots = null): array`
- `padesPrepareFromFile(string $filePath, bool $visible = false, ?array $anots = null): array`
- `padesCmsParams(string $documentId, ?string $fieldName = null): array`
- `padesInject(string $documentId, string $fieldName, string $signatureDerHex): array`
- `padesFinalize(string $documentId): array`
- `padesInjectPkcs1(string $documentId, string $fieldName, string $signaturePkcs1DerHex, string $signerCertDerBase64, ?array $signerChainDerBase64 = null): array`

Veja também: `examples/pades_flow.php`.
