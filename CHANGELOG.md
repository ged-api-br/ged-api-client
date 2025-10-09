# Changelog

All notable changes to `ged-api-client` will be documented in this file.

## [1.1.0] - 2025-10-09

### Added
- `CmsBuilder` class - Constrói CMS/PKCS#7 completo com phpseclib3
- Suporte para montagem correta de SignedData
- Método `build()` - Monta CMS assinando SignedAttributes
- Método `buildWithOpenssl()` - Fallback usando comando openssl

### Changed
- `sign_a1.php` example atualizado para usar CmsBuilder
- Adicionada dependência `phpseclib/phpseclib ^3.0`

### Fixed
- CMS agora assina SignedAttributes ao invés de messageDigest
- Estrutura CMS compatível com Adobe Reader e validadores ICP-Brasil

## [1.0.0] - 2025-10-08

### Added
- Initial release
- `GedApiClient` main class
- `startSignature()` method - Inicia processo de assinatura
- `completeSignature()` method - Finaliza assinatura
- `verifySignature()` method - Valida PDF assinado
- `GedApiException` custom exception
- Example: `sign_a1.php` - Assinatura com certificado A1
- Complete documentation in README.md
- Support for ICP-Brasil policies (AD-RB, AD-RT, AD-RC)

### Features
- PAdES-BES digital signature
- A1 certificate support (PFX)
- PSR-4 autoloading
- Guzzle HTTP client integration
- Error handling with custom exceptions

### Compliance
- RFC 5652 (CMS)
- ETSI TS 101 733 (CAdES)
- DOC-ICP-15.03 (ICP-Brasil)

