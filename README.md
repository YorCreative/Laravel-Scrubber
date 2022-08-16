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

