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
    /**
     * @return LogRecord
     */
    public static function buildRecord(DateTimeImmutable $datetime, string $channel, int $level, string $message, array $context, array $extra)
    {
        if (interface_exists(LogRecord::class)) {
            return self::buildFromAnonymousLogRecord($datetime, $channel, $level, $message, $context, $extra);
        } elseif (class_exists(LogRecord::class)) {
            return self::buildFromLogRecordClass($datetime, $channel, $level, $message, $context, $extra);
        } else {
            throw new RuntimeException(' ¯\_(ツ)_/¯ ');
        }
    }

    private static function buildFromAnonymousLogRecord(DateTimeImmutable $datetime, string $channel, int $level, string $message, array $context, array $extra): LogRecord
    {
        return new class($datetime, $channel, $level, $message, $context, $extra) extends LogRecord
        {
            private const MODIFIABLE_FIELDS = [
                'extra' => true,
                'formatted' => true,
            ];

            public function __construct(
                DateTimeImmutable $datetime,
                string $channel,
                $level,
                string $message,
                array $context,
                array $extra
            ) {
                $level = Level::from($level);

                parent::__construct($datetime, $channel, $level, $message, $context, $extra);
            }

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

            public function offsetExists(mixed $offset): bool
            {
                if ($offset === 'level_name') {
                    return true;
                }

                return isset($this->{$offset});
            }

            public function offsetUnset(mixed $offset): void
            {
                if (! is_string($offset)) {
                    $offset = json_encode($offset);
                }

                throw new LogicException('Unsupported operation '.$offset);
            }

            public function &offsetGet(mixed $offset): mixed
            {
                if ($offset === 'level_name') {
                    $offset = 'levelName';
                }

                if (isset(self::MODIFIABLE_FIELDS[$offset])) {
                    return $this->{$offset};
                }

                // avoid returning readonly props by ref as this is illegal
                return $this->{$offset};
            }

            public function toArray(): array
            {
                return [
                    'message' => $this->message,
                    'context' => $this->context,
                    'level' => $this->level->value,
                    'level_name' => Logger::toMonologLevel($this->level)->getName(),
                    'channel' => $this->channel,
                    'datetime' => $this->datetime,
                    'extra' => $this->extra,
                ];
            }

            public function with(mixed ...$args): self
            {
                foreach (['message', 'context', 'level', 'channel', 'datetime', 'extra'] as $prop) {
                    $args[$prop] ??= $this->{$prop};
                }

                return new self(...$args);
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
