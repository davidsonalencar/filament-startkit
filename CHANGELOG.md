# Changelog

## [2026.3.12-pr.1](https://github.com/davidsonalencar/filament-startkit/compare/v2026.3.12-pr.0...v2026.3.12-pr.1) (2026-03-21)

## [2026.3.12-pr.0](https://github.com/davidsonalencar/filament-startkit/compare/v2026.3.11...v2026.3.12-pr.0) (2026-03-21)

### Outras Melhorias

* refactor Envoy scripts and release-it workflow for streamlined versioning and build processes ([cbbe4fb](https://github.com/davidsonalencar/filament-startkit/commit/cbbe4fba114301cbe75df77d38508f7fa605b826))

## [2026.3.11](https://github.com/davidsonalencar/filament-startkit/compare/v2026.3.10...v2026.3.11) (2026-03-21)

### Outras Melhorias

* update Dockerfile and .dockerignore for optimized builds and cleanup ([3437a1c](https://github.com/davidsonalencar/filament-startkit/commit/3437a1cf885eff451cf074a65cd2ac115f6880ae))
* update release-it script to use CI mode ([ccbb6c7](https://github.com/davidsonalencar/filament-startkit/commit/ccbb6c793f468a38d05221a438eeb39b7f4f0805))

## [2026.3.10](https://github.com/davidsonalencar/filament-startkit/compare/v2026.3.8...v2026.3.10) (2026-03-21)

### Outras Melhorias

* add `--no-dev` flag to composer install in Dockerfile ([40fd03c](https://github.com/davidsonalencar/filament-startkit/commit/40fd03c9628cf03a058471a9cb20bda6406ef805))
* add `release:image` task to post-bump hooks in release-it config ([c64fe5e](https://github.com/davidsonalencar/filament-startkit/commit/c64fe5e0d67e9ee01121e89f4e84a58aaf72dea9))
* add `release:images` task to post-bump hooks in release-it config ([c4d8734](https://github.com/davidsonalencar/filament-startkit/commit/c4d873447ea4ea5e91a5885e34a4eb7f3a0e4212))
* clear PHP cache files in Dockerfile during build ([acc33be](https://github.com/davidsonalencar/filament-startkit/commit/acc33be31b6d401965bc10087bf34512b0f339c4))

## [2026.3.8](https://github.com/davidsonalencar/filament-startkit/compare/v2026.3.7...v2026.3.8) (2026-03-20)

### Outras Melhorias

* remove `release:app` task from Envoy script ([bb5a1b8](https://github.com/davidsonalencar/filament-startkit/commit/bb5a1b829c948a93e4bfe579c940f4e9bcb5b8eb))

## [2026.3.7](https://github.com/davidsonalencar/filament-startkit/compare/v2026.3.6...v2026.3.7) (2026-03-20)

### Outras Melhorias

* refine release-it bumper plugin config to include `in` and `out`
  properties ([25dcf81](https://github.com/davidsonalencar/filament-startkit/commit/25dcf81c255dcd1fff9ce11a2e1796064f89017e))
* release
  v2026.3.6 ([563629e](https://github.com/davidsonalencar/filament-startkit/commit/563629e34dba7ac385835e84b935cdef2f703765))
* update release-it config to refine bumper plugin and changelog
  sections ([ea089d3](https://github.com/davidsonalencar/filament-startkit/commit/ea089d3909049c702ce40bbdab8d3ff7c2aa692c))

## [2026.3.6](https://github.com/davidsonalencar/filament-startkit/compare/2026.3.5...v2026.3.6) (2026-03-20)

## 2026.3.5 (2026-03-20)

## 2026.3.4 (2026-03-20)

## 2026.3.3 (2026-03-19)

## 2026.3.1 - 2026-03-18

- Refactor Docker Compose configurations and automate Envoy tasks
- Streamline server setup and deployment process
- Update environment configuration and streamline setup process
- Remove unused Filament CSS to optimize asset loading.
- Refactor and modularize Docker Compose setup with service-specific configurations
- Add fallback timezone configuration and Filament timezone handling
- Refactor export actions to support dynamic column formatting and table context
- Add localized date and time formats for Filament components based on app locale
- Add translations and localization strings for Filament Panels and Notifications in English, Spanish, and Brazilian
  Portuguese
- Add translations for Filament Shield permissions in English and Brazilian Portuguese
- Add localized strings and translations for Filament resources
- Implement Role management resource with Create, Edit, View, and List functionality.
- Add localization support and configurations
- Add PDF export view template for reports
- Implement task export functionality
- first commit
