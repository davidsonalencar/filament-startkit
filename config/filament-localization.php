<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale to use when generating translation files.
    | If no locale is specified, this will be used.
    |
    */

    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | The locales that should be generated when running the localization command.
    | You can add or remove locales as needed.
    |
    */

    'locales' => [
        'en',
        'pt_BR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation File Structure
    |--------------------------------------------------------------------------
    |
    | The structure for organizing translation files.
    | Options: 'flat', 'nested', 'panel-based'
    |
    | - flat: All translations in lang/{locale}/filament.php
    | - nested: Organized by resource: lang/{locale}/filament/{resource}.php
    | - panel-based: Organized by panel and resource: lang/{locale}/filament/{panel}/{resource}.php
    |
    */

    'structure' => 'panel-based',

    /*
    |--------------------------------------------------------------------------
    | Backup Before Processing
    |--------------------------------------------------------------------------
    |
    | Whether to create a backup of files before modifying them.
    | Recommended to keep this enabled for safety.
    |
    */

    'backup' => false,

    /*
    |--------------------------------------------------------------------------
    | Git Integration
    |--------------------------------------------------------------------------
    |
    | Automatically create a git commit after localization.
    | This allows easy reverting if needed.
    |
    */

    'git' => [
        'enabled' => false,
        'commit_message' => 'chore: add Filament localization support',
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Formatting
    |--------------------------------------------------------------------------
    |
    | Run Laravel Pint to format code before creating git commit.
    | This ensures consistent code style across the project.
    |
    */

    'pint' => [
        'enabled' => true,
        'command' => 'vendor/bin/pint --dirty',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Panels
    |--------------------------------------------------------------------------
    |
    | Panels to exclude from localization scanning.
    |
    */

    'excluded_panels' => [
        // 'blogger',
    ],

    /*
    |--------------------------------------------------------------------------
    | Other Panel IDs (for translation key rewriting)
    |--------------------------------------------------------------------------
    |
    | When rewriting translation keys that reference another panel, these
    | panel IDs are matched so they can be replaced with the current panel.
    | Add your app's panel IDs here if you use multiple Filament panels.
    |
    */

    'other_panel_ids' => [
        'admin',
        'Admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Resources
    |--------------------------------------------------------------------------
    |
    | Resources to exclude from localization scanning.
    |
    */

    'excluded_resources' => [
        // 'UserResource',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Types to Scan
    |--------------------------------------------------------------------------
    |
    | The Filament component types to scan for localization.
    |
    */

    'scan_components' => [
        'forms' => true,
        'tables' => true,
        'infolists' => true,
        'actions' => true,
        'notifications' => true,
        'widgets' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Label Generation Strategy
    |--------------------------------------------------------------------------
    |
    | How to generate default labels from field names.
    | Options: 'title_case', 'sentence_case', 'keep_original'
    |
    */

    'label_generation' => 'title_case',

    /*
    |--------------------------------------------------------------------------
    | Preserve Existing Labels
    |--------------------------------------------------------------------------
    |
    | If a field already has a label, should we preserve it or replace it?
    |
    */

    'preserve_existing_labels' => false,

    /*
    |--------------------------------------------------------------------------
    | Translation Key Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix to use for translation keys.
    | Example: 'filament' will generate keys like __('filament/admin.user.name')
    |
    */

    'translation_key_prefix' => 'filament',

    /*
    |--------------------------------------------------------------------------
    | Verbose Output
    |--------------------------------------------------------------------------
    |
    | Show detailed progress information during processing.
    |
    */

    'verbose' => true,

    /*
    |--------------------------------------------------------------------------
    | Skip Identical Terms
    |--------------------------------------------------------------------------
    |
    | Terms that should remain identical across all language files.
    | These are typically proper nouns, brand names, technical terms,
    | and acronyms that should not be translated.
    |
    */

    'skip_identical_terms' => [
        'API', 'URL', 'HTTP', 'HTTPS', 'PDF', 'CSV', 'JSON', 'XML',
        'Laravel', 'Filament', 'Vue', 'React', 'Angular', 'Google', 'Microsoft',
        'GitHub', 'Docker', 'AWS', 'Azure', 'PayPal', 'Stripe', 'WordPress',
        'Bootstrap', 'Tailwind', 'jQuery', 'TypeScript', 'Webpack', 'Vite',
        'JWT', 'OAuth', 'REST', 'GraphQL', 'SEO', 'UX', 'UI', 'SaaS', 'PaaS',
        'IoT', 'AI', 'ML', 'DevOps', 'CI/CD', 'Agile', 'Scrum', 'TDD', 'BDD',
        'GDPR', 'ISO', 'IEEE', 'W3C', 'OWASP', 'NIST', 'ITIL', 'PMP',
    ],

    /*
    |--------------------------------------------------------------------------
    | DeepL Translation Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for DeepL API integration for automatic translation.
    | Set your DeepL API key in the .env file as DEEPL_API_KEY.
    |
    */

    'deepl' => [
        'api_key' => env('DEEPL_API_KEY'),
        'base_url' => env('DEEPL_BASE_URL', 'https://api-free.deepl.com/v2'),
        'timeout' => 60,
        'batch_size' => 50,
        'preserve_formatting' => true,
    ],

];
