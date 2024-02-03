<?php

namespace Ds\Foundations\Connection\Arch;
use Ds\Helper\Str;

use PDO;

class QueryCommon
{
    public const SELECT = 'SELECT';
    public const DISTINCT = 'DISTINCT';
    public const BULK_INSERT = 'BULK_INSERT';
    public const INSERT = 'INSERT';
    public const UPDATE = 'UPDATE';
    public const DELETE = 'DELETE';

    public const ASC = 'ASC';
    public const DESC = 'DESC';

    /**
     * orderAdditional
     *
     * @var string
     */
    protected $orderAdditional;
    /**
     * limitAdditional
     *
     * @var string
     */
    protected $limitAdditional;
    /**
     * groupAdditional
     *
     * @var string
     */
    protected $groupAdditional;
    /**
     * havingAdditional
     *
     * @var string
     */
    protected $havingAdditional;
    /**
     * quotSql
     *
     * @var string
     */
    protected $quotSql;
    /**
     * endQuotSql
     *
     * @var char
     */
    protected $endQuotSql;
    /**
     * bindSymbol
     *
     * @var char
     */
    protected $bindSymbol;

    /**
     * FixQuot
     *
     * @param  string $text
     * @return string
     */
    private function FixQuot($text)
    {
        $text = trim($text);
        if ($text == "*" || $text[0] == $this->quotSql) {
            return $text;
        }
        if (empty($text)) return $text;

        return $this->quotSql . $text . $this->endQuotSql;
    }
    /**
     * WrapQuot
     *
     * @param  string $name
     * @param  bool $reverseQuot
     * @return string
     */
    protected function WrapQuot($name, $reverseQuot = false)
    {
        $name = trim($name);
        if (empty($name)) return $name;
        $isRaw = $this->checkRaw($name);
        if ($isRaw) return $name;

        $isFunction = preg_match('/.*[(].*[)]/', $name);
        if ($isFunction) {
            $funcName = substr($name, 0, strpos($name, '('));
            $wrapStart = strpos($name, '(');
            $param = substr($name, $wrapStart + 1, strrpos($name, ')') - 4);
            return $funcName . '(' . $this->WrapQuot($param) . ')' . substr($name, strrpos($name, ')') + 1);
        }
        if (Str::contains($name, '.')) {
            $columnName = substr($name, strpos($name, '.') + 1);
            $columnAlias = array_map(function ($a) {
                return $this->FixQuot($a);
            }, explode(' ', $columnName));
            return substr($name, 0, strpos($name, '.') + 1) . implode(' ', $columnAlias);
        }

        if (Str::contains($name, ' ')) {
            $spaceIdx = strrpos($name, ' ');
            $selected = substr($name, 0, $spaceIdx);
            $alias = substr($name, $spaceIdx + 1);
            return $this->FixQuot($selected) . ' ' . $this->FixQuot($alias);
        }
        $isFunction = preg_match('/.*[(].*[)]/', $name);
        if (!$reverseQuot && $isFunction == 1)
            return $this->FixQuot($name);
        if ($name[0] == $this->quotSql) {
            return $name;
        } else
            return $this->FixQuot($name);
    }
    /**
     * GetType get parameter type of binding params
     *
     * @param  mixed $value
     * @return int
     */
    protected function GetType($value)
    {
        if (is_string($value)) {
            return PDO::PARAM_STR;
        } else if (is_numeric($value)) {
            return PDO::PARAM_INT;
        } else if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }
        return PDO::PARAM_STR;
    }
    protected function assignBindSymbol($paramName)
    {
        if (empty($paramName)) return $paramName;
        if ($paramName[0] == $this->bindSymbol) return $paramName;
        else return $this->bindSymbol . trim($paramName);
    }
    protected function setBehavior($startQuot, $endQuot, $bindSymbol = ':')
    {
        $this->quotSql = $startQuot;
        $this->endQuotSql = $endQuot;
        $this->bindSymbol = $bindSymbol;
    }
    /**
     * If value of query contains !! symbol on first word
     * it will remain as raw query
     *
     * @param  mixed $value
     * @return void
     */
    protected function checkRaw(&$value)
    {
        if (!is_string($value))
            return false;

        if (empty($value)) {
            return false;
        }
        if ($value[0] == '!' && $value[1] == '!') {
            $value = substr($value, 2);
            return true;
        }
        return false;
    }
}
