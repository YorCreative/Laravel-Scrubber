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

Adjust the configuration file to suite your application.

```php
return [
    'redaction' => '**redacted**', // Define what you want to overwrite detected information with??
    'secret_manager' => [
        'key' => '44mfXzhGl4IiILZ844mfXzhGl4IiILZ8', // key for cipher to use
        'cipher' => 'AES-256-CBC', 
        'enabled' => false, // Do you want this enabled??
        'providers' => [
            'gitlab' => [
                'enabled' => false,
                'project_id' => env('GITLAB_PROJECT_ID', 'change_me'),
                'token' => env('GITLAB_TOKEN', 'change_me'),
                'host' => 'https://gitlab.com',
                'keys' => ['*'], // * will grab all the secrets, if you want specific variables
                                 //  define the keys in an array
            ],
        ],
    ],
];
```

## Usage

Logging Detection & Sanitization

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

Direct Usage for Detection & Sanitization

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

## Secret Manager

This package provides the ability to pull in secrets from external sources. This provides the package the ability to detect leakage and sanitize secrets without needing an exact regex pattern to detect it. 

### Encryption

For enhanced application security, all secrets that are pulled from any provider are encrypted and only decrypted to run the detection. You can see this in action [here](https://github.com/YorCreative/Laravel-Scrubber/blob/main/src/Services/ScrubberService.php#L45).
### Gitlab Integration

To utilize the Gitlab Integration, you will need to enable the `secret_manager` and the `gitlab` provider in the Configuration file. If you are looking for information on how to add secrets in Gitlab. There is an article on [adding project variables](https://docs.gitlab.com/ee/ci/variables/#add-a-cicd-variable-to-a-project).

## Extending the Scrubber

Creating new Scrubber Detection Classes

```bash
php artisan make:regex-class {name} 
```

This command will create a stubbed out class in `App\Scrubber\RegexCollection`. The Scrubber package will autoload everything
from the `App\Scrubber\RegexCollection` folder. You will need to provide a `Regex Pattern` and a `Testable String` for
the class.

## Testing

```bash
composer test
```

## Credits

- [Yorda](https://github.com/yordadev)
- [All Contributors](../../contributors)

