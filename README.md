<br />
<br />

<div align="center">
  <a href="https://github.com/YorCreative">
    <img src="content/logo-2-color.png" alt="Logo" width="128" height="128">
  </a>
</div>

<h3 align="center">Laravel Scrubber</h3>

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

