<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and
creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in
many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache)
  storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all
modern web application frameworks, making it a breeze to get started with the framework. You can also check
out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video
tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging
into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in
becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in
the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by
the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell
via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

https://filamentphp.com/insights/modrictin-multiple-vite-and-tailwind-configs#:~:text=%23The%20solution,json

```shell
composer global require laravel/installer
laravel new filament
phpstorm .env
composer require laravel/sail --dev
php artisan sail:install
sail migrate
sail composer require filament/filament:"^5.0"
sail php artisan filament:install --panels
sail artisan make:filament-user
sail artisan vendor:publish --tag=filament-config
sail php -r "echo 'opcache.enable => ' . ini_get('opcache.enable') . PHP_EOL;"

sail composer require cwsps154/users-roles-permissions

sail composer require bezhansalleh/filament-shield                                                                                                               
sail php artisan vendor:publish --tag="filament-shield-config"
sail artisan shield:setup

sail artisan make:model Task -m
sail artisan make:filament-resourse Task --generate
sail artisan make:filament-resource User --generate
sail artisan make:filament-resource Task --generate

# associar usuario ao rule
sail artisan shield:seeder
sail artisan shield:generate --all

sail artisan vendor:publish --tag=filament-panels-views                                                                                                                                                                          

sail composer require spatie/laravel-pdf
sail composer require maatwebsite/excel:^3.1
sail php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
sail php artisan vendor:publish --tag=pdf-config

sail artisan make:queue-batches-table
sail artisan make:notifications-table

mkdir -p app/Exports app/Jobs app/Filament/Actions/Export app/Filament/Actions/BulkExport resources/views/exports
mkdir -p app/Filament/Actions/Concerns

sail composer require laravel/horizon
sail php artisan horizon:install

php artisan make:model Export -m
php artisan make:policy ExportPolicy --model=Export

sail artisan make:filament-theme

sail artisan install:broadcasting

# Gerar traduções
sail artisan shield:translation pt_BR --panel=admin

# Atualizar permissoes
sail artisan shield:generate --all
```
