<br />
<br />
<div align="center">
  <a href="https://github.com/YorCreative">
    <img src="content/logo-2-color.png" alt="Logo" width="128" height="128">
  </a>
</div>
<h3 align="center">Laravel Scrubber</h3>

<div align="center">
<a href="https://github.com/YorCreative/Laravel-Scrubber/blob/main/LICENSE.md"><img alt="GitHub license" src="https://img.shields.io/github/license/YorCreative/Laravel-Scrubber"></a>
<a href="https://github.com/YorCreative/Laravel-Scrubber/stargazers"><img alt="GitHub stars" src="https://img.shields.io/github/stars/YorCreative/Laravel-Scrubber"></a>
<a href="https://github.com/YorCreative/Laravel-Scrubber/issues"><img alt="GitHub issues" src="https://img.shields.io/github/issues/YorCreative/Laravel-Scrubber"></a>
<a href="https://github.com/YorCreative/Laravel-Scrubber/network"><img alt="GitHub forks" src="https://img.shields.io/github/forks/YorCreative/Laravel-Scrubber"></a>
<img alt="Packagist Downloads" src="https://img.shields.io/packagist/dt/YorCreative/Laravel-Scrubber?color=green">
<a href="https://github.com/YorCreative/Laravel-Scrubber/actions/workflows/phpunit.yml"><img alt="PHPUnit" src="https://github.com/YorCreative/Laravel-Scrubber/actions/workflows/phpunit.yml/badge.svg"></a>
</div>

A Laravel package to scrub sensitive information that breaks operational security policies from being leaked on
accident ~~_or not_~~ by developers.

## Installation

install the package via composer:

```bash
composer require yorcreative/laravel-scrubber
```

Publish the packages assets.

```bash
php artisan vendor:publish --provider="YorCreative\Scrubber\ScrubberServiceProvider"
```

## Configuration

Adjust the configuration file to suite your application, located in `/config/scrubber.php`.

```php
return [
    /**
     * Specify the string to use to redact the data
     */
    'redaction' => '**redacted**',

    'secret_manager' => [
        'key' => env('APP_KEY'),
        'cipher' => 'AES-256-CBC',
        'enabled' => false,
        'providers' => [
            'gitlab' => [
                /**
                 * Enable the GitLab secret manager
                 */
                'enabled' => false,
                'project_id' => env('GITLAB_PROJECT_ID'),
                'token' => env('GITLAB_TOKEN'),
                'host' => 'https://gitlab.com',
                /**
                 * `*` will grab all the secrets, if you want specific variables
                 * define the keys in an array
                 */
                'keys' => ['*'],
            ],
        ],
    ],

    /**
     * Specify the regexes to load
     * You can use a wildcard (*) to load all regexes in all `custom_regex_namespaces` and the default core regexes.
     * Otherwise, specify the regexes you want to load either by qualified class name or by unqualified (base) class name,
     * which will then search the `custom_regex_namespaces` and the default core regexes for a match.
     */
    'regex_loader' => ['*'],
    
    /**
     * Specify regex patterns to exclude from loading when using the regex loader
     * This allows fine-grained control over which regex patterns are loaded, especially useful when using wildcard (*) in regex_loader
     * 
     * You can exclude patterns using any of these formats:
     * - Fully qualified class name (e.g., 'YorCreative\Scrubber\RegexCollection\GoogleApi')
     * - Base class name (e.g., 'GoogleApi', 'EmailAddress')
     * - Pattern constant from RegexCollection (e.g., RegexCollection::$GOOGLE_API)
     * - Custom namespace class (e.g., 'App\Scrubber\RegexCollection\HerokuApiKey')
     * 
     * Example:
     * [
     *     'GoogleApi',
     *     'YorCreative\Scrubber\RegexCollection\EmailAddress',
     *     RegexCollection::$HEROKU_API_KEY,
     *     'App\Scrubber\RegexCollection\HerokuApiKey'
     * ]
     */
    'exclude_regex' => [],


    /**
     * Specify namespaces from which regexes will be loaded when using the wildcard (*)
     * for the regex_loader or where you use unqualified class names.
     */
    'custom_regex_namespaces' => [
       'App\\Scrubber\\RegexCollection',
    ],

    /**
     * Specify config keys for which the values will be scrubbed
     * You should use the dot notation to specify the keys
     * You can use wildcards (*) to match multiple keys
     *
     *  - 'database.connections.*.password'
     *  - 'app.secrets.*'
     *  - 'app.some.nested.key'
     */
    'config_loader' => [
        '*token',
        '*key',
        '*secret',
        '*password',
    ],

    /**
     * Specify the channels to tap into
     * You can use wildcards (*) to match multiple channels
     */ 
    'tap_channels' => false,
];
```

## Usage

The scrubber can be utilized in two ways, the first one being a Log scrubber. A tap is added to detect and sanitize any
sensitive information from hitting a log file. The second way is to integrate into your application and utilize the
Scrubber directly. This way is particular useful if you, for example, would like to detect and sanitize any messages on
a messaging platform.

### Logging Detection & Sanitization

```php
Log::info('some message', [
    'context' => 'accidental',
    'leak_of' => [
        'jwt' => '<insert jwt token here>'
    ]
])

// testing.INFO: some message {"context":"accidental","leak_of":{"jwt": '**redacted**'}} 

Log::info('<insert jwt token here>')

// testing.INFO: **redacted**  
```

### Direct Usage for Detection & Sanitization

```php
Scrubber::processMessage([
    'context' => 'accidental',
    'leak_of' => [
        'jwt' => '<insert jwt token here>'
    ]
]);
// [
//     "context" => "accidental"
//     "leak_of" => [
//         "jwt" => "**redacted**"
//     ]
// ];

Scrubber::processMessage('<insert jwt token here>');
// **redacted**
```

## Log Channel Opt-in

This package provides you the ability to define through the configuration file what channels you want to scrub
specifically. By default, this package ships with a wildcard value and opts in to scrub all the log channels in your
application.

### Defining Log Channel Opt-in

To opt in to one or more channels, list the channel(s) name into the `tap_channels` array in the config.

```php
'tap_channels' => [
    'single',
    'papertrail'
]
```

To disable tap logging functionality and use the package independently and not tap your Laravel application logging, modify the config file by setting the tap_channels field as follows:
```php
'tap_channels' => false
```

## Regex Class Opt-in

You have the ability through the configuration file to define what regex classes you want loaded into the application
when it is bootstrapped. By default, this package ships with a wildcard value.

### Regex Collection & Defining Opt-in

To opt in, utilize the static properties on
the [RegexCollection](https://github.com/YorCreative/Laravel-Scrubber/blob/main/src/Repositories/RegexCollection.php)
class.

```php
 'regex_loader' => [
        RegexCollection::$GOOGLE_API,
        RegexCollection::$AUTHORIZATION_BEARER,
        RegexCollection::$CREDIT_CARD_AMERICAN_EXPRESS,
        RegexCollection::$CREDIT_CARD_DISCOVER,
        RegexCollection::$CREDIT_CARD_VISA,
        RegexCollection::$JSON_WEB_TOKEN
    ],
```

### Opting Into Custom Extended Classes

> To create custom scrubbers, see the [Extending the Scrubber](#extending-the-scrubber) section.

The `regex_loader` array takes strings, not objects. To opt in to specific custom extended regex classes, define the
class name as a string.

For example if I have a custom extended class as such:

```php
<?php

namespace App\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class TestRegex implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        /**
         * @note return a regex pattern to detect a specific piece of sensitive data.
         */
        return '(?<=basic) [a-zA-Z0-9=:\\+\/-]{5,100}';
    }

    public function getTestableString(): string
    {
        /**
         * @note return a string that can be used to verify the regex pattern provided.
         */
        return 'basic f9Iu+YwMiJEsQu/vBHlbUNZRkN/ihdB1sNTU';
    }
    
    public function getReplacementValue(): string
    {
        
        /**
         * @note return a string that replaces the regex pattern provided.
         */
        return config('scrubber.redaction');
    }

    public function isSecret(): bool
    {
        return false;
    }
}
```

The `regex_loader` array should be defined as such:

```php
 'regex_loader' => [
        RegexCollection::$GOOGLE_API,
        RegexCollection::$AUTHORIZATION_BEARER,
        RegexCollection::$CREDIT_CARD_AMERICAN_EXPRESS,
        RegexCollection::$CREDIT_CARD_DISCOVER,
        RegexCollection::$CREDIT_CARD_VISA,
        RegexCollection::$JSON_WEB_TOKEN,
        'TestRegex'
    ],
```
### RegexCollection & Defining Opt-out

When using wildcard loading (`'regex_loader' => ['*']`), you can exclude specific regex patterns using the `exclude_regex` configuration. This allows you to load all patterns except those explicitly excluded.

```php
'exclude_regex' => [
    // Exclude by base class name
    'GoogleApi',
    
    // Exclude by fully qualified class name
    'YorCreative\Scrubber\RegexCollection\EmailAddress',
    
    // Exclude using RegexCollection constant
    RegexCollection::$HEROKU_API_KEY,
    
    // Exclude from custom namespace
    'App\Scrubber\RegexCollection\HerokuApiKey'
],
```

The exclude_regex configuration supports multiple formats for excluding patterns:
- Base class names (e.g., 'GoogleApi')
- Fully qualified class names
- RegexCollection constants
- Custom namespace classes

This is particularly useful when you want to use most patterns but need to exclude a few specific ones from your scrubbing process.


## About the Scrubber

This package provides the ability to pull in secrets from external sources. Providing the ability to detect information
leakage, and sanitize secrets without needing an exact regex pattern to detect it.

### Encryption

For enhanced application security, all secrets pulled, from any provider, are encrypted and only decrypted to run the
detection. You can see this in
action [here](https://github.com/YorCreative/Laravel-Scrubber/blob/main/src/Services/ScrubberService.php#L45).

### Gitlab Integration

To utilize the Gitlab Integration, you will need to enable the `secret_manager` and the `gitlab` provider in the
Configuration file. If you are looking for information on how to add secrets in Gitlab. There is an article
on [adding project variables](https://docs.gitlab.com/ee/ci/variables/#add-a-cicd-variable-to-a-project).

## Extending the Scrubber

Creating new Scrubber Detection Classes

```bash
php artisan make:regex-class {name} 
```

This command will create a stubbed out class in `App\Scrubber\RegexCollection`. The Scrubber package will autoload
everything from the `App\Scrubber\RegexCollection` folder with the wildcard value on the `regex_loader` array in the
scrubber config file. You will need to provide a `Regex Pattern` and a `Testable String` for the class and you may also provide a `Replacement Value` if you want to replace the detected value with something other than the default value in the config file.

## Testing

```bash
composer test
```

## Credits

- [Yorda](https://github.com/yordadev)
- [Whizboy-Arnold](https://github.com/Whizboy-Arnold)
- [majchrosoft](https://github.com/majchrosoft)
- [Lucaxue](https://github.com/lucaxue)
- [AlexGodbehere](https://github.com/AlexGodbehere)
- [All Contributors](../../contributors)

