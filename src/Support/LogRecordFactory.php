<?php

namespace YorCreative\Scrubber\Support;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Monolog\Logger;
use Monolog\LogRecord;
use RuntimeException;

class LogRecordFactory
{
    /**
     * @param DateTimeImmutable $datetime
     * @param string $channel
     * @param int $level
     * @param string $message
     * @param array $context
     * @param array $extra
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

    /**
     * @param DateTimeImmutable $datetime
     * @param string $channel
     * @param int $level
     * @param string $message
     * @param array $context
     * @param array $extra
     * @return LogRecord
     */
    private static function buildFromAnonymousLogRecord(DateTimeImmutable $datetime, string $channel, int $level, string $message, array $context, array $extra): LogRecord
    {
        return new class($datetime, $channel, $level, $message, $context, $extra) implements LogRecord {
            private const MODIFIABLE_FIELDS = [
                'extra' => true,
                'formatted' => true,
            ];

            public function __construct(
                private DateTimeImmutable $datetime,
                private string            $channel,
                private int               $level,
                private string            $message,
                private array             $context,
                private array             $extra
            )
            {
                //
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                if ($offset === 'extra') {
                    if (!is_array($value)) {
                        throw new InvalidArgumentException('extra must be an array');
                    }

                    $this->extra = $value;
                    return;
                }

                if ($offset === 'formatted') {
                    $this->formatted = $value;
                    return;
                }

                throw new LogicException('Unsupported operation: setting ' . $offset);
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
                throw new LogicException('Unsupported operation');
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
                $copy = $this->{$offset};

                return $copy;
            }

            public function toArray(): array
            {
                return [
                    'message' => $this->message,
                    'context' => $this->context,
                    'level' => $this->level,
                    'level_name' => Logger::getLevelName($this->level),
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

    /**
     * @param DateTimeImmutable $datetime
     * @param string $channel
     * @param int $level
     * @param string $message
     * @param array $context
     * @param array $extra
     * @return LogRecord
     */
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
