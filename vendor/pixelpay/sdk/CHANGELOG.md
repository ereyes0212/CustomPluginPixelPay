# Changelog - PixelPay Java Standalone SDK
Todos los cambios notables de este proyecto se documentarán en este archivo. Los registros de cambios son *para humanos*, no para máquinas, y la última versión es lo primero a mostrar.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Tipos de cambios: `Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security`.

## [v2.2.1] - 2024-02-27
### Fixed
- Se arreglan códigos de estados en Nicaragua, Reino Unido, Estados Unidos, Guatemala, Panamá, Perú y Portugal

## [v2.2.0] - 2023-09-22
### Removed
- Se elimina lógica de encriptación de datos de tarjeta.

## [v2.1.2] - 2023-08-08
### Fixed
- Se arregló conflicto de encriptación al reutilizar instancias de `RequestBehaviour`
- Se arregló servicios de tokenización enviando fecha de expiración incorrecta si no se llenan los campos

## [v2.1.1] - 2023-08-08
### Added
- Se omiten los métodos de encriptación si la petición no lo requiere

+### Changed
- En caso de fallos, se hacen reintentos para adquirir la llave pública del comercio
- En caso de fallos, se hacen reintentos para encriptar los datos de tarjeta

### Fixed
- Se arregló acceso a propiedad protegida
- Se corrigió caso donde reintentos de transacciones con una misma instancia de `RequestBehaviour` encripta datos múltiples veces

## [v2.1.0] - 2023-07-27
### Added
- Se agregó encriptación de datos de tarjeta.

## [v2.0.2] - 2023-06-07
### Added
- Se agregan campos de cuotas y puntos.
- Se agrega firma de transacciones de anulación.
- Se agrega función para obtener listado de formatos de teléfono y zip.

## [v2.0.1] - 2023-01-26
### Added
- Agregar función para obtener listado de formatos de teléfono y zip

## [v2.0.0] - 2022-05-09
### Added
- Se publica primer version
