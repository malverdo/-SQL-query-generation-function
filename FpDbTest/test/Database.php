<?php

namespace FpDbTest\test;

use Exception;
use mysqli;
use stdClass;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    /**
     * @var stdClass
     * Специальное значение, которое будет использоваться для пропуска частей SQL запроса.
     */
    private stdClass $skipValue;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
        $this->skipValue = new stdClass();
    }

    public function buildQuery(string $query, array $args = []): string {
        $regex = '/\?(d|f|a|#)?/';

        /**
         * @param $matches
         * @return float|int|string
         * @throws Exception
         * Функция обратного вызова, которая заменяет спецификаторы в запросе.
         */
        $callback = function($matches) use (&$args) {
            // Определяем тип спецификатора.
            $specifier = $matches[1] ?? '';
            // Получаем текущее значение из массива параметров.
            $value = current($args);
            // Переходим к следующему параметру в массиве.
            next($args);

            switch ($specifier) {
                case 'd':
                    return intval($value);
                case 'f':
                    return floatval($value);
                case 'a':
                    if (!is_array($value)) {
                        throw new Exception("Expected array for ?a placeholder");
                    }
                    return $this->formatSet($value);
                case '#':
                    if (!is_array($value)) {
                        return $this->escapeIdentifier($value);
                    }
                    return implode(', ', array_map([$this, 'escapeIdentifier'], $value));
                default:
                    return $this->escapeValue($value);
            }
        };

        $query = preg_replace_callback($regex, $callback, $query);

        // Обработка условных блоков в запросе.
        // Это необходимо для того, чтобы включать или исключать части запроса на основе условий.
        return preg_replace_callback('/{([^{}]*)}/', function ($matches) use (&$args) {
            foreach ($args as $index => $arg) {
                // Проверка, является ли аргумент специальным значением skip.
                if ($arg === $this->skipValue) {
                    // Если да, удаляем его из списка и пропускаем соответствующий блок в запросе.
                    array_splice($args, $index, 1);
                    return '';
                }
            }
            // Если блок не пропущен, возвращаем его содержимое для включения в запрос.
            return $matches[1];
        }, $query);
    }

    private function formatSet($array) {
        $result = [];
        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                $result[] = $this->escapeIdentifier($key) . ' = ' . $this->escapeValue($value);
            } else {
                $result[] = $this->escapeValueIN($value);
            }
        }
        return implode(', ', $result);
    }

    private function escapeValue($value) {
        if (is_null($value)) {
            return "NULL";
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value)) {
            return ' ' . $this->mysqli->real_escape_string($value) ;
        }
        return ' \\\'' . $this->mysqli->real_escape_string($value) . ' \\\'';
    }

    private function escapeValueIN($value): string
    {

        if (is_int($value)) {
            return  $this->mysqli->real_escape_string($value) ;
        }
        return ' \\\'' . $this->mysqli->real_escape_string($value) . '\\\'';
    }

    public function skip(): stdClass
    {
        return $this->skipValue;
    }

    private function escapeIdentifier($value): string
    {
        if (is_array($value)) {
            return implode(',', array_map([$this, 'escapeSingleIdentifier'], $value));
        } else {
            return $this->escapeSingleIdentifier($value);
        }
    }

    private function escapeSingleIdentifier($value): string
    {
        $value = str_replace('`', '', $value);
        return " `$value`";
    }
}
