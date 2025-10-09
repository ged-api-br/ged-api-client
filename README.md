# 📦 GED API Client (PHP SDK)

Cliente oficial PHP da [GED API](https://ged.api.br) — Provedor de Assinatura Digital ICP-Brasil.

---

## 🚀 Instalação

```bash
composer require ged/api-client
```

---

## ⚙️ Exemplo de uso (Certificado A1)

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
