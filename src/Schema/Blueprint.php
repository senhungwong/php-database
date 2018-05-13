<?php

namespace Senhung\MySQL\Schema;

class Blueprint
{
    /**
     * IF NOT EXISTS
     *
     * @var bool $ifNotExists
     */
    private $ifNotExists = true;

    /**
     * Table Name
     *
     * @var string $tableName
     */
    private $tableName = '';

    /**
     * Column name
     *
     * @var string $columnName
     */
    private $mostRecentColumn;

    /**
     * Columns for building tables
     *
     * @var array $columns
     */
    private $columns = [];

    /**
     * Constraints for building tables
     *
     * @var array $constraints
     */
    private $constraints = [];

    /**
     * Table building constants
     */
    private const DEFAULT_STRING_LENGTH = 50;
    private const DEFAULT_INT_LENGTH = 11;
    private const DEFAULT_TEXT_LENGTH = 65535;

    /**
     * Base Types
     */
    private const STRING = 'VARCHAR';
    private const INT = 'INT';
    private const TEXT = 'TEXT';
    private const JSON = 'JSON';
    private const ENUM = 'ENUM';
    private const TIMESTAMP = 'TIMESTAMP';
    private const DECIMAL = 'DECIMAL';

    /**
     * Constraints
     */
    private const NOT_NULL = 'NOT NULL';
    private const NULL = 'NULL';
    private const PRIMARY_KEY = 'PRIMARY KEY';
    private const UNIQUE = 'UNIQUE';
    private const AUTO_INCREMENT = 'AUTO_INCREMENT';
    private const DEFAULT = 'DEFAULT';
    private const INDEX = 'INDEX';
    private const UNSIGNED = 'UNSIGNED';

    /**
     * Blueprint constructor.
     * @param string $tableName
     * @param bool $ifNotExists
     */
    public function __construct(string $tableName, bool $ifNotExists = true)
    {
        $this->tableName = $tableName;
        $this->ifNotExists = $ifNotExists;
    }

    /* ---------------------------------------------------------------------------------
     | Data Types
     | ---------------------------------------------------------------------------------
     */

    /**
     * Create a string type column
     *
     * @param string $columnName
     * @param int $length
     * @return Blueprint
     */
    public function string(string $columnName, int $length = Blueprint::DEFAULT_STRING_LENGTH): Blueprint
    {
        return $this->baseTypeDeclaration($columnName, Blueprint::STRING, $length);
    }

    /**
     * Create an int type column
     *
     * @param string $columnName
     * @param int $length
     * @return Blueprint
     */
    public function int(string $columnName, int $length = Blueprint::DEFAULT_INT_LENGTH): Blueprint
    {
        return $this->baseTypeDeclaration($columnName, Blueprint::INT, $length);
    }

    /**
     * Create a text type column
     *
     * @param string $columnName
     * @param int $length
     * @return Blueprint
     */
    public function text(string $columnName, int $length = Blueprint::DEFAULT_TEXT_LENGTH): Blueprint
    {
        return $this->baseTypeDeclaration($columnName, Blueprint::TEXT, $length);
    }

    /**
     * Create a Json type column
     *
     * @param string $columnName
     * @return Blueprint
     */
    public function json(string $columnName): Blueprint
    {
        return $this->baseTypeDeclaration($columnName, Blueprint::JSON);
    }

    /**
     * Create an enum type column
     *
     * @param string $columnName
     * @param array $values
     * @return Blueprint
     */
    public function enum(string $columnName, array $values): Blueprint
    {
        return $this->baseTypeDeclaration($columnName, Blueprint::ENUM . "('" . implode("', '", $values) . "')");
    }

    /**
     * Create a timestamp type column
     *
     * @param string $columnName
     * @param int|null $fsp
     * @return Blueprint
     */
    public function timestamp(string $columnName, int $fsp = null): Blueprint
    {
        return $this->baseTypeDeclaration($columnName, Blueprint::TIMESTAMP, $fsp);
    }

    /**
     * Create a decimal type column
     *
     * @param string $columnName
     * @param int $length
     * @param int|null $decimals
     * @return Blueprint
     */
    public function decimal(string $columnName, int $length, int $decimals = null): Blueprint
    {
        return $this->baseTypeDeclaration($columnName, Blueprint::DECIMAL);
    }

    /* ---------------------------------------------------------------------------------
     | Constraints
     | ---------------------------------------------------------------------------------
     */

    /**
     * Set column not null
     *
     * @param array|string|null $columnNames
     * @return Blueprint
     */
    public function notNull($columnNames = null): Blueprint
    {
        return $this->constraintDeclaration(Blueprint::NOT_NULL, $columnNames);
    }

    /**
     * Set column null
     *
     * @param array|string|null $columnNames
     * @return Blueprint
     */
    public function null($columnNames = null): Blueprint
    {
        return $this->constraintDeclaration(Blueprint::NULL, $columnNames);
    }

    /**
     * Set column as primary key
     *
     * @param array|string|null $columnNames
     * @return Blueprint
     */
    public function primary($columnNames = null): Blueprint
    {
        return $this->constraintDeclaration(Blueprint::PRIMARY_KEY, $columnNames);
    }

    /**
     * Set column as unique key
     *
     * @param array|string|null $columnNames
     * @return Blueprint
     */
    public function unique($columnNames = null): Blueprint
    {
        return $this->constraintDeclaration(Blueprint::UNIQUE, $columnNames);
    }

    /**
     * Set column as auto increment
     *
     * @param array|string|null $columnNames
     * @return Blueprint
     */
    public function autoIncrement($columnNames = null): Blueprint
    {
        return $this->constraintDeclaration(Blueprint::AUTO_INCREMENT, $columnNames);
    }

    /**
     * Set column default value
     *
     * @param string $value
     * @param array|string|null $columnNames
     * @return Blueprint
     */
    public function default(string $value, $columnNames = null): Blueprint
    {
        return $this->constraintDeclaration(Blueprint::DEFAULT . "(" . (string)$value . ")", $columnNames);
    }

    /**
     * Set column as auto increment
     *
     * @param array|string|null $columnNames
     * @return Blueprint
     */
    public function index($columnNames = null): Blueprint
    {
        return $this->constraintDeclaration(Blueprint::INDEX, $columnNames);
    }

    /**
     * Set column as unsigned
     *
     * @param array|string|null $columnNames
     * @return Blueprint
     */
    public function unsigned($columnNames = null): Blueprint
    {
        return $this->constraintDeclaration(Blueprint::UNSIGNED, $columnNames);
    }

    /* ---------------------------------------------------------------------------------
     | Helper Functions
     | ---------------------------------------------------------------------------------
     */

    /**
     * Create table MySQL query
     *
     * @return string
     */
    public function __toString(): string
    {
        /* Create table */
        $query = "CREATE TABLE ";

        if ($this->ifNotExists) {
            $query .= "IF NOT EXISTS ";
        }

        /* Table Name */
        $query .= "`" . $this->tableName . "` ";

        $query .= "(\n";

        /* Columns */
        $query .= $this->parseColumns();

        /* Constraints */
        $query .= $this->parseConstraints();

        $query .= "\n);";

        return $query;
    }

    /**
     * Create column
     *
     * @param string $columnName
     * @param string $baseType
     * @param int|null $length
     * @return Blueprint
     */
    private function baseTypeDeclaration(string $columnName, string $baseType, int $length = null): Blueprint
    {
        $this->mostRecentColumn = $columnName;

        $type = $baseType;

        if (isset($length)) {
            $type .= '(' . (string)$length . ')';
        }

        $this->columns[$columnName] = [$type];

        return $this;
    }

    /**
     * Create constraint
     *
     * @param string $constraintType
     * @param array|string|null $columnNames
     * @return Blueprint
     */
    private function constraintDeclaration(string $constraintType, $columnNames = null): Blueprint
    {
        if (isset($columnNames)) {
            $this->constraints[$constraintType] = $columnNames;
        } elseif (isset($this->mostRecentColumn)) {
            $this->columns[$this->mostRecentColumn][] = $constraintType;
        }

        return $this;
    }

    /**
     * Parse columns to string
     *
     * @return string
     */
    private function parseColumns(): string
    {
        $columns = [];

        foreach ($this->columns as $field => $declaration) {
            $columns[] = "\t`" . $field . "` " . implode(" ", $declaration);
        }

        return implode(", \n", $columns);
    }

    /**
     * Parse constraints to string
     *
     * @return string
     */
    private function parseConstraints(): string
    {
        $constraints = [];

        foreach ($this->constraints as $constraint => $field) {
            $constraints[] = "\t" . $constraint . " (`" .
                (is_array($field) ? implode("`, `", $field) : $field) . "`)";
        }

        return $constraints ? ", \n" . implode(", \n", $constraints) : '';
    }
}
