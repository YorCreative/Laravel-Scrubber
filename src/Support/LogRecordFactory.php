<?php

namespace YorCreative\Scrubber\Support;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use RuntimeException;

class LogRecordFactory
{
    public static bool $useAnonymous = false;

    public static function buildRecord(
        DateTimeImmutable $datetime,
        string $channel,
        int $level,
        string $message,
        array $context,
        array $extra
    ): LogRecord {
        if (self::$useAnonymous) {
            return self::buildFromAnonymousLogRecord($datetime, $channel, $level, $message, $context, $extra);
        }

        if (class_exists(LogRecord::class)) {
            return self::buildFromLogRecordClass($datetime, $channel, $level, $message, $context, $extra);
        }

        // @codeCoverageIgnore
        throw new RuntimeException(' ¯\_(ツ)_/¯ ');
    }

    private static function buildFromAnonymousLogRecord(DateTimeImmutable $datetime, string $channel, int $level, string $message, array $context, array $extra): LogRecord
    {
        $levelEnum = Level::from($level);

        return new class($datetime, $channel, $levelEnum, $message, $context, $extra) extends LogRecord
        {
            private const MODIFIABLE_FIELDS = [
                'extra' => true,
                'formatted' => true,
            ];

            public function __construct(
                DateTimeImmutable $datetime,
                string $channel,
                Level $level,
                string $message,
                array $context,
                array $extra
            ) {
                parent::__construct($datetime, $channel, $level, $message, $context, $extra);
            }

            /**
             * @codeCoverageIgnore
             */
            public function offsetSet(mixed $offset, mixed $value): void
            {
                if ($offset === 'extra') {
                    if (! is_array($value)) {
                        throw new InvalidArgumentException('extra must be an array');
                    }
                    $this->extra = $value;

                    return;
                }
                if ($offset === 'formatted') {
                    $this->formatted = $value;

                    return;
                }
                throw new LogicException('Unsupported operation: setting '.$offset);
            }

            /**
             * @codeCoverageIgnore
             */
            public function offsetExists(mixed $offset): bool
            {
                if ($offset === 'level_name') {
                    return true;
                }

                return isset($this->{$offset});
            }

            /**
             * @codeCoverageIgnore
             */
            public function offsetUnset(mixed $offset): void
            {
                if (! is_string($offset)) {
                    $offset = json_encode($offset);
                }

                throw new LogicException('Unsupported operation '.$offset);
            }

            /**
             * @codeCoverageIgnore
             */
            public function &offsetGet(mixed $offset): mixed
            {
                if ($offset === 'level_name') {
                    $offset = 'levelName';
                }

                if (isset(self::MODIFIABLE_FIELDS[$offset])) {
                    return $this->{$offset};
                }

                return $this->{$offset};
            }

            public function toArray(): array
            {
                return [
                    'message' => $this->message,
                    'context' => $this->context,
                    'level' => $this->level->value,
                    'level_name' => $this->level->getName(),
                    'channel' => $this->channel,
                    'datetime' => $this->datetime,
                    'extra' => $this->extra,
                ];
            }

            public function with(mixed ...$args): LogRecord
            {
                foreach (['datetime', 'channel', 'level', 'message', 'context', 'extra'] as $prop) {
                    $args[$prop] ??= $this->$prop;
                }

                if (is_int($args['level'])) {
                    // @codeCoverageIgnore
                    $args['level'] = Level::from($args['level']);
                }

                return new self(
                    $args['datetime'],
                    $args['channel'],
                    $args['level'],
                    $args['message'],
                    $args['context'],
                    $args['extra']
                );
            }
        };
    }

    private static function buildFromLogRecordClass(DateTimeImmutable $datetime, string $channel, int $level, string $message, array $context, array $extra): LogRecord
    {
        return new LogRecord(
            $datetime,
            $channel,
            Logger::toMonologLevel($level),
            $message,
            $context,
            $extra
        );
    }
}
