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

Adjust the configuration file to suite your application, located in `/config/scubber.php`.

```php
return [
    'redaction' => '**redacted**', // Define what you want to overwrite detected information with?
    'secret_manager' => [
        'key' => '44mfXzhGl4IiILZ844mfXzhGl4IiILZ8', // key for cipher to use
        'cipher' => 'AES-256-CBC', 
        'enabled' => false, // Do you want this enabled?
        'providers' => [
            'gitlab' => [
                'enabled' => false,
                'project_id' => env('GITLAB_PROJECT_ID', ''),
                'token' => env('GITLAB_TOKEN', ''),
                'host' => 'https://gitlab.com',
                'keys' => ['*'], // * will grab all the secrets, if you want specific variables
                                 //  define the keys in an array
            ],
        ],
    ],
    'regex_loader' => ['*'] // Opt-in to specific regex classes OR include all with * wildcard.
    'tap_channels' => ['*'] // Opt-in to tap specific log channels OR include all with * wildcard.
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
specifically. By default, this package ships with a wildcard value and opts in to scrub all the log channels
in your application. 

### Defining Log Channel Opt-in
To opt in to one or more channels, list the channel(s) name into the `tap_channels` array in the config.

```php
'tap_channels' => [
    'single',
    'papertrail'
]
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
         * @todo
         * @note return a regex pattern to detect a specific piece of sensitive data.
         */
        return '(?<=basic) [a-zA-Z0-9=:\\+\/-]{5,100}';
    }

    public function getTestableString(): string
    {
        /**
         * @todo
         * @note return a string that can be used to verify the regex pattern provided.
         */
        return 'basic f9Iu+YwMiJEsQu/vBHlbUNZRkN/ihdB1sNTU';
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
scrubber config file. You will need to provide a `Regex Pattern` and a `Testable String` for the class.

## Testing

```bash
composer test
```

## Credits

- [Yorda](https://github.com/yordadev)
- [Whizboy-Arnold](https://github.com/Whizboy-Arnold)
- [All Contributors](../../contributors)

