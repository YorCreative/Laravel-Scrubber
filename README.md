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

## Requirements

- PHP 8.2, 8.3, 8.4, or 8.5
- Laravel 10.x, 11.x, or 12.x

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
            // See "Secret Manager Providers" section for full configuration options
            'gitlab' => ['enabled' => false, /* ... */],
            'aws' => ['enabled' => false, /* ... */],
            'vault' => ['enabled' => false, /* ... */],
            'azure' => ['enabled' => false, /* ... */],
            'google' => ['enabled' => false, /* ... */],
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

### Detection Statistics API

Track what patterns are matching and how often:

```php
// Get scrubbing statistics for the current request
$stats = Scrubber::getStats();
// ['total_scrubs' => 5, 'patterns_matched' => ['JsonWebToken' => 2, 'EmailAddress' => 3]]

// Test a string without modifying stats - useful for debugging
$result = Scrubber::test('Contact: john@example.com, SSN: 123-45-6789');
// [
//     'matched' => true,
//     'patterns' => ['EmailAddress' => 1, 'SocialSecurityNumber' => 1],
//     'scrubbed' => 'Contact: **redacted**, SSN: ***-**-****'
// ]

// Reset statistics between requests
Scrubber::resetStats();
```

### Events

The scrubber can dispatch a `SensitiveDataDetected` event each time a pattern matches during scrubbing. This is
useful for alerting, metrics, or audit logging.

Enable events in your config:

```php
'events' => [
    'enabled' => true,
],
```

The event carries three public properties:

| Property | Type | Description |
|----------|------|-------------|
| `patternName` | `string` | The class basename of the matched pattern (e.g. `JsonWebToken`) |
| `hitCount` | `int` | Number of matches found for that pattern in the content |
| `context` | `string` | Either `log` (triggered via log tap) or `manual` (triggered via `Scrubber::processMessage()`) |

Register a listener in your `EventServiceProvider` or with the `Event` facade:

```php
use YorCreative\Scrubber\Events\SensitiveDataDetected;

Event::listen(SensitiveDataDetected::class, function (SensitiveDataDetected $event) {
    // $event->patternName  — e.g. 'JsonWebToken'
    // $event->hitCount     — e.g. 2
    // $event->context      — 'log' or 'manual'
});
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

> **Note**: The package includes 31 built-in patterns. See all available patterns in [RegexCollection.php](https://github.com/YorCreative/Laravel-Scrubber/blob/main/src/Repositories/RegexCollection.php).

### PII Detection with Partial Masking

The following patterns use contextual replacement values for improved readability instead of the generic `**redacted**`:

| Pattern | Detects | Masked Output |
|---------|---------|---------------|
| `RegexCollection::$SOCIAL_SECURITY_NUMBER` | US Social Security Numbers | `***-**-****` |
| `RegexCollection::$PHONE_NUMBER` | Phone numbers (US/International) | `(***) ***-****` |
| `RegexCollection::$IP_ADDRESS_V4` | IPv4 addresses | `***.***.***.***` |
| `RegexCollection::$IP_ADDRESS_V6` | IPv6 addresses | `****:****:****:...` |
| `RegexCollection::$IBAN` | International Bank Account Numbers | `********************` |

```php
Scrubber::processMessage('SSN: 123-45-6789, Phone: (555) 123-4567');
// "SSN: ***-**-****, Phone: (***) ***-****"

Scrubber::processMessage('Server IP: 192.168.1.1');
// "Server IP: ***.***.***.***"
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

### Secret Manager Providers

Laravel Scrubber supports pulling secrets from multiple external secret management services. This allows you to detect and sanitize secrets without needing exact regex patterns - if a value matches a secret from your vault, it gets scrubbed.

To enable secret managers, set `secret_manager.enabled` to `true` in your config and enable one or more providers.

#### GitLab CI/CD Variables

Pull secrets from GitLab project variables.

```php
'gitlab' => [
    'enabled' => true,
    'project_id' => env('GITLAB_PROJECT_ID'),
    'token' => env('GITLAB_TOKEN'),
    'host' => 'https://gitlab.com', // Or your self-hosted GitLab URL
    'keys' => ['*'], // Or specific variable names: ['DB_PASSWORD', 'API_KEY']
],
```

See GitLab's documentation on [adding project variables](https://docs.gitlab.com/ee/ci/variables/#add-a-cicd-variable-to-a-project).

#### AWS Secrets Manager

Pull secrets from AWS Secrets Manager. Requires the AWS SDK.

```bash
composer require aws/aws-sdk-php
```

```php
'aws' => [
    'enabled' => true,
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'version' => 'latest',
    'credentials' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],
    'keys' => ['*'], // Or specific secret names/ARNs
],
```

The AWS SDK automatically uses the default credential chain (environment variables, IAM roles, ECS task roles, etc.) when credentials are not explicitly provided.

#### HashiCorp Vault

Pull secrets from HashiCorp Vault using REST API. No additional dependencies required.

```php
'vault' => [
    'enabled' => true,
    'host' => env('VAULT_ADDR', 'http://127.0.0.1:8200'),
    'token' => env('VAULT_TOKEN'),
    'namespace' => env('VAULT_NAMESPACE'), // Enterprise feature (optional)
    'engine' => env('VAULT_ENGINE', 'secret'), // KV engine mount path
    'path' => env('VAULT_PATH', ''), // Base path within the engine
    'version' => env('VAULT_KV_VERSION', 2), // KV engine version (1 or 2)
    'keys' => ['*'], // Or specific secret paths
],
```

#### Azure Key Vault

Pull secrets from Azure Key Vault using REST API. No additional dependencies required.

```php
'azure' => [
    'enabled' => true,
    'vault_url' => env('AZURE_VAULT_URL'), // https://my-vault.vault.azure.net
    // Authentication options (in order of precedence):
    // Option 1: Direct access token
    'access_token' => env('AZURE_VAULT_ACCESS_TOKEN'),
    // Option 2: Client credentials (service principal)
    'tenant_id' => env('AZURE_TENANT_ID'),
    'client_id' => env('AZURE_CLIENT_ID'),
    'client_secret' => env('AZURE_CLIENT_SECRET'),
    'keys' => ['*'], // Or specific secret names
],
```

**Authentication methods** (in order of precedence):
1. **Direct access token** - For testing or short-lived tokens
2. **Managed Identity** - Auto-detected when running in Azure (App Service, Functions, VMs)
3. **Client credentials** - Service principal with tenant_id, client_id, client_secret

#### Google Cloud Secret Manager

Pull secrets from Google Cloud Secret Manager using REST API. No additional dependencies required.

```php
'google' => [
    'enabled' => true,
    'project_id' => env('GOOGLE_CLOUD_PROJECT'),
    'access_token' => env('GOOGLE_SECRET_MANAGER_TOKEN'), // Optional
    'keys' => ['*'], // Or specific secret names
],
```

**Authentication methods** (in order of precedence):
1. **Direct access token** - For testing or when running outside GCP
2. **Application Default Credentials** - Auto-detected when running in GCP (Compute Engine, Cloud Run, GKE, Cloud Functions)

When running on GCP, leave `access_token` empty to use ADC automatically.

#### JSON Secret Values

All providers support JSON-formatted secret values. Nested values are automatically flattened:

```json
// Secret named "database-config" with value:
{
  "host": "db.example.com",
  "credentials": {
    "username": "admin",
    "password": "secret123"
  }
}
```

This creates three scrubber patterns:
- `database-config.host` → `db.example.com`
- `database-config.credentials.username` → `admin`
- `database-config.credentials.password` → `secret123`

## Extending the Scrubber

Creating new Scrubber Detection Classes

```bash
php artisan make:regex-class {name}
```

This command will create a stubbed out class in `App\Scrubber\RegexCollection`. The Scrubber package will autoload
everything from the `App\Scrubber\RegexCollection` folder with the wildcard value on the `regex_loader` array in the
scrubber config file. You will need to provide a `Regex Pattern` and a `Testable String` for the class and you may also provide a `Replacement Value` if you want to replace the detected value with something other than the default value in the config file.

### Validating Regex Patterns

After creating or modifying regex classes, you can verify that every loaded pattern correctly matches its own testable string:

```bash
php artisan scrubber:validate
```

The command tests each pattern against the string returned by `getTestableString()` and prints a results table:

```
+---------------------------+--------+---------+
| Pattern                   | Status | Message |
+---------------------------+--------+---------+
| JsonWebToken              | PASS   |         |
| EmailAddress              | PASS   |         |
| SocialSecurityNumber      | PASS   |         |
+---------------------------+--------+---------+
Results: 3 passed, 0 failed, 3 total.
```

The command exits with a non-zero status when any pattern fails, making it suitable for CI pipelines.

## Testing

```bash
composer test
```

## Credits

- [Yorda](https://github.com/yordadev)
- [Magentron](https://github.com/Magentron)
- [Whizboy-Arnold](https://github.com/Whizboy-Arnold)
- [majchrosoft](https://github.com/majchrosoft)
- [Lucaxue](https://github.com/lucaxue)
- [AlexGodbehere](https://github.com/AlexGodbehere)
- [JorgeAnzola](https://github.com/JorgeAnzola)
- [Haddowg](https://github.com/haddowg)
- [LorenzoSapora](https://github.com/LorenzoSapora)
- [All Contributors](../../contributors)

