<?php

namespace FpDbTest;

class Database implements DatabaseInterface
{
    private const string IGNORED_BLOCK_MARKER = '###';

    public function buildQuery(string $query, array $args = []): string
    {
        return $this->removeIgnoredBlocks($this->getRawQuery($args, $query));
    }

    public function skip(): string
    {
        return $this::IGNORED_BLOCK_MARKER;
    }

    private function getValue(mixed $arg): string
    {
        return is_null($arg) ? 'NULL' : (is_string($arg) ? "'{$arg}'" : $arg);
    }

    private function getNumber(mixed $arg): string
    {
        if (!is_numeric($arg) && !is_bool($arg)) {
            throw new \Exception('Not a number|bool');
        }

        return $this->getValue($arg);
    }

    private function getArray(mixed $arg): string
    {
        if (!is_array($arg)) {
            throw new \Exception('Not an array');
        }

        if (array_is_list($arg)) {
            return join(', ', array_map(fn($value) => $this->getValue($value), $arg));
        }

        return join(', ', array_map(fn($key, $value) => "`$key` = {$this->getValue($value)}", array_keys($arg), array_values($arg)));
    }

    private function getIds(string|array $arg): string
    {
        $arg = (array)$arg;
        return join(', ', array_map(fn($value) => "`$value`", $arg));
    }

    protected function getRawQuery(array $args, string $query): string
    {
        return preg_replace_callback('/\?[dfa#]?/m', function ($matches) use (&$args) {
            $arg = array_shift($args);
            if ($arg !== $this::IGNORED_BLOCK_MARKER) {
                $operand = $matches[0];

                return match ($operand) {
                    '?' => $this->getValue($arg),
                    '?d' => (int)$this->getNumber($arg),
                    '?f' => (float)$this->getNumber($arg),
                    '?a' => $this->getArray($arg),
                    '?#' => $this->getIds($arg),
                    default => throw new \Exception("Unexpected template '$operand'"),
                };
            }

            return $this::IGNORED_BLOCK_MARKER;
        }, $query);
    }

    protected function removeIgnoredBlocks(string $query): ?string
    {
        return preg_replace_callback('/{(.*)}/m', function ($matches){
            $content = $matches[1];
            if (str_contains($content, $this::IGNORED_BLOCK_MARKER)) {
                return '';
            }

            return $content;
        }, $query);
    }
}
