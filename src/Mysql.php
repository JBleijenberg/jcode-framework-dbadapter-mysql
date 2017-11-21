<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the General Public License (GPL 3.0)
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/GPL-3.0
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category    J!Code: Framework
 * @package     J!Code: Framework
 * @author      Jeroen Bleijenberg <jeroen@jcode.nl>
 *
 * @copyright   Copyright (c) 2017 J!Code (http://www.jcode.nl)
 * @license     http://opensource.org/licenses/GPL-3.0 General Public License (GPL 3.0)
 */
namespace Jcode\DBAdapter;

use Jcode\Application;
use Jcode\Db\AdapterInterface;
use Jcode\Db\Resource;
use Jcode\Db\TableInterface;
use Jcode\DBAdapter\Mysql\Table\Column;
use Jcode\DBAdapter\Mysql\Table;
use Jcode\DataObject;
use Jcode\Log;
use \PDOException;
use \Exception;

class Mysql extends \PDO implements AdapterInterface
{
    protected $bindVars = [];

    protected $bindIncrement = 1;

    protected $query;

    /**
     * Do not use PDO's construct yet
     */
    public function __construct()
    {

    }

    /**
     * Connect to mysql with PDO
     *
     * @param \Jcode\DataObject $config
     */
    public function connect(DataObject $config)
    {
        $dsn = "mysql:dbname={$config->getName()};host={$config->getHost()}";

        $options = [
            parent::ATTR_TIMEOUT => 5,
            parent::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            parent::ATTR_ERRMODE => parent::ERRMODE_EXCEPTION,
            parent::ATTR_AUTOCOMMIT => false
        ];

        try {
            parent::__construct($dsn, $config->getUser(), $config->getPassword(), $options);
        } catch (PDOException $e) {
            Application::logException($e);
        }
    }

    /**
     * Get table object
     *
     * @param $tableName
     * @param string $engine
     *
     * @return \Jcode\DBAdapter\Mysql\Table
     * @throws \Exception
     */
    public function getTable($tableName, $engine = 'innoDB')
    {
        /* @var \Jcode\DBAdapter\Mysql\Table $table */
        $table = Application::getClass('Jcode\DBAdapter\Mysql\Table');

        $table->setTableName($tableName);
        $table->setEngine($engine);

        return $table;
    }

    /**
     * @param \Jcode\DBAdapter\Mysql\Table|\Jcode\Db\TableInterface $table
     *
     * @return array
     * @throws \Exception
     */
    public function alterTable(TableInterface $table)
    {
        $this->query = sprintf('SHOW FULL COLUMNS FROM %s', $table->getTableName());

        $tableInfo = $this->execute();

        if (empty($tableInfo)) {
            throw new Exception('Selected table is empty. Nothing to alter');
        }

        $orderedTableInfo = [];

        array_map(function ($arr) use (&$orderedTableInfo) {
            $orderedTableInfo[$arr['Field']] = $arr;
        }, $tableInfo);

        $query = sprintf('ALTER TABLE %s', $table->getTableName());
        $tables = '';

        foreach ($table->getDroppedColumns() as $column) {
            if (!array_key_exists($column, $orderedTableInfo)) {
                throw new Exception('Trying to drop a non existing column.');
            }

            $tables .= sprintf('DROP COLUMN %s, ', $column);
        }

        /* @var \Jcode\DBAdapter\Mysql\Table\Column $column */
        foreach ($table->getAlteredColumns() as $column) {
            if (!array_key_exists($column->getName(), $orderedTableInfo)) {
                throw new Exception('Trying to alter a non existing column.');
            }

            if ($column->getOption('name')) {
                $tables .= sprintf('CHANGE COLUMN %s %s', $column->getName(), $column->getOption('name'));
            } else {
                $tables .= sprintf('CHANGE COLUMN %s', $column->getName());
            }

            $columnInfo = $orderedTableInfo[$column->getName()];

            if ($column->getOption('type')) {
                $tables .= sprintf(' %s', strtoupper($column->getOption('type')));

                switch ($column->getOption('type')) {
                    case Column::TYPE_BINARY:
                    case Column::TYPE_LONGBLOB:
                    case Column::TYPE_LONGTEXT:
                    case Column::TYPE_MEDIUMBLOB:
                    case Column::TYPE_MEDIUMTEXT:
                    case Column::TYPE_TINYBLOB:
                    case Column::TYPE_TINYTEXT:
                    case Column::TYPE_TEXT:
                    case Column::TYPE_BLOB:
                    case Column::TYPE_TIME:
                    case Column::TYPE_TIMESTAMP:
                    case Column::TYPE_DATE:
                    case Column::TYPE_DATETIME:
                        $length = null;

                        break;
                    default:
                        if (!$column->getOption('length')) {
                            throw new Exception('Length value required for this column type.');
                        }

                        $length = $column->getOption('length');
                }

                $tables .= sprintf('(%s)', $length);
            }

            if ($column->getOption('unsigned') == true) {
                $tables .= ' unsigned';
            }

            if ($column->getOption('not_null') == true) {
                $tables .= ' NOT NULL';
            } else {
                if ($column->getOption('not_null') === false && $columnInfo['Null'] == 'YES') {
                    $tables .= ' NULL';
                }
            }

            if ($column->getOption('auto_increment') == true) {
                $tables .= ' AUTO_INCREMENT';
            }

            if ($column->getOption('zerofill') == true) {
                $tables .= ' ZEROFILL';
            } else {
                if ($column->getOption('zerofill') === false) {
                    $tables .= ' DROP ZEROFILL';
                }
            }

            if ($column->getOption('default')) {
                $tables .= sprintf(' DEFAULT "%s"', $column->getOption('default'));
            } else {
                if ($column->getOption('default') === false) {
                    $tables .= ' DROP DEFAULT';
                }
            }

            if ($column->getOption('comment')) {
                $tables .= sprintf(' COMMENT "%s"', $column->getOption('comment'));
            } else {
                if ($column->getOption('comment') === false) {
                    $tables .= ' COMMENT ""';
                }
            }

            $tables .= ', ';
        }

        if ($table->getColumns()) {
            foreach ($table->getColumns() as $column) {
                $tables .= 'ADD ';

                $this->getAddColumnQuery($table, $column, $tables);
            }
        }

        $tables = trim($tables, ', ');

        $this->query = sprintf('%s %s;', $query, $tables);

        return $this->execute();
    }

    public function getAddColumnQuery(Table $table, Column $column, &$tables)
    {
        if (!$column->getName() || !$column->getType()) {
            throw new Exception('Cannot add column to table. Name or type missing');
        }

        if ($column->getOption('primary_key') == true) {
            $table->setPrimaryKey($column->getName());
        }

        $tables .= sprintf('%s %s', $column->getName(), $column->getType());

        if ($column->getLength() !== null) {
            $tables .= sprintf('(%s)', $column->getLength());
        }

        if ($column->getOption('unsigned') == true) {
            $tables .= ' unsigned';
        }

        if ($column->getOption('not_null') == true) {
            $tables .= ' NOT NULL';
        }


        if ((!$table->getPrimaryKey() && $column->getOption('auto_increment') == true)
            || ($table->getPrimaryKey() == $column->getName())
        ) {
            $tables .= ' AUTO_INCREMENT';
        }

        if ($column->getOption('zerofill') == true) {
            $tables .= ' ZEROFILL';
        }

        if (($default = $column->getOption('default'))) {
            $tables .= ($default == 'current_timestamp()')
                ? sprintf(' DEFAULT %s', $default)
                : sprintf(' DEFAULT "%s"', $default);
        }

        if ($column->getOption('on_update')) {
            $tables .= sprintf(' ON UPDATE %s', $column->getOption('on_update'));
        }

        if ($column->getOption('comment') != false) {
            $tables .= sprintf(' COMMENT "%s"', $column->getOption('comment'));
        }

        $tables .= ', ';
    }

    /**
     * Add index to an existing table
     *
     * @param TableInterface $table Table object
     * @param string $name Name of the index
     * @param string $column Comma seperated list of columns
     * @param null $type
     * @return $this
     * @throws Exception
     */
    public function addIndex(TableInterface $table, $name, $column, $type = null)
    {
        $columns = explode(',', $column);
        $type    = ($type !== null)
            ? ' UNIQUE '
            : ' ';

        foreach ($columns as $c) {
            preg_match("/^([A-z0-9\-_]+)\(/", $c, $matches);

            $columnName = (empty($matches))
                ? $c
                : $matches[1];

            if ($table->getColumn($columnName)) {
                $this->query = "CREATE{$type}INDEX {$name} ON {$table->getTableName()} ({$column});";

                $this->execute();
            } else {
                throw new \Exception("Column '{$columnName}' not found in table {$table->getTableName()}'");
            }
        }

        return $this;
    }

    /**
     * Create new table
     *
     * @param \Jcode\DBAdapter\Mysql\Table|\Jcode\Db\TableInterface $table
     *
     * @return array
     * @throws \Exception
     */
    public function createTable(TableInterface $table)
    {
        return $this->executeCreateTable($table, sprintf('CREATE TABLE %s', $table->getTableName()));
    }

    /**
     * Create new table if it doesn't exists yet
     *
     * @param \Jcode\DBAdapter\Mysql\Table $table
     *
     * @return array
     * @throws \Exception
     */
    public function createTableIfNotExists(Table $table)
    {
        return $this->executeCreateTable($table, sprintf('CREATE TABLE IF NOT EXISTS %s', $table->getTableName()));
    }

    /**
     * Create new table and drop it first if it already exists
     *
     * @param \Jcode\DBAdapter\Mysql\Table $table
     *
     * @return array
     * @throws \Exception
     */
    public function createTableDropIfExists(Table $table)
    {
        return $this->executeCreateTable(
            $table,
            sprintf('DROP TABLE IF EXISTS %s; CREATE TABLE %s', $table->getTableName(), $table->getTableName())
        );
    }

    /**
     * Create new table
     *
     * @param \Jcode\DBAdapter\Mysql\Table $table
     * @param $query
     *
     * @return array
     * @throws \Exception
     */
    protected function executeCreateTable(Table $table, $query)
    {
        if (!$table->getTableName() || !$table->getEngine() || !$table->getColumns()) {
            throw new Exception('Not enough data to create table');
        }

        $tables = '';

        foreach ($table->getColumns() as $column) {
            $this->getAddColumnQuery($table, $column, $tables);
        }

        if ($table->getPrimaryKey()) {
            $tables .= sprintf(' PRIMARY KEY(%s)', $table->getPrimaryKey());
        }

        $query .= sprintf(' (%s) ENGINE=%s DEFAULT CHARSET=%s;', preg_replace("/,\s{0,}$/", '', $tables), $table->getEngine(), $table->getCharset());

        $this->query = $query;

        return $this->execute();
    }

    public function build(Resource $resource, $delete = false)
    {
        $select = '';

        if ($resource->getDistinct() && !in_array($resource->getDistinct(), $resource->getSelect())) {
            $select .= sprintf('DISTINCT %s, ', $resource->getDistinct());
        }

        foreach ($resource->getSelect() as $column) {
            $select .= sprintf('%s, ', $column);
        }

        $select = trim($select, ', ');

        $query = ($delete === false)
            ? sprintf('SELECT %s FROM %s AS main_table', $select, $resource->getTable())
            : sprintf('DELETE %s FROM %s AS main_table', $select, $resource->getTable());

        if (count($resource->getJoin())) {
            foreach ($resource->getJoin() as $join) {
                reset($join['tables']);

                $query .= sprintf(
                    ' %s JOIN %s AS %s ON %s',
                    strtoupper($join['type']),
                    key($join['tables']),
                    current($join['tables']),
                    $join['clause']
                );
            }
        }

        $where = '';

        if (count($resource->getFilter())) {
            $filters = $resource->getFilter();

            foreach ($filters as $column => $filter) {
                reset($filters);

                foreach ($filter as $condition) {
                    $where .= ($column === key($filters)) ? ' WHERE ' : 'AND ';

                    $this->formatStatement(key($condition), $column, current($condition), $where);
                }
            }

            if (!count($resource->getExpression()) && $where != '') {
                $query .= $where;
            }
        }

        if (count($resource->getExpression())) {
            $where .= (empty($where)) ? ' WHERE ' : ' AND ';

            foreach ($resource->getExpression() as $column => $expression) {
                foreach ($expression as $expr) {
                    $where .= sprintf('%s %s', $column, key($expr));

                    if (is_array(current($expr))) {
                        foreach (current($expr) as $value) {
                            $this->bindVars[$this->bindIncrement++] = $value;
                        }
                    } else {
                        $this->bindVars[$this->bindIncrement++] = current($expr);
                    }

                    $query .= $where;
                }
            }
        }

        if ($resource->getGroupBy()) {
            $query .= sprintf(' GROUP BY %s', $resource->getGroupBy());
        }

        if (count($resource->getOrder())) {
            foreach ($resource->getOrder() as $i => $order) {
                $query .= ($i == 0)
                    ? sprintf(' ORDER BY %s %s', key($order), current($order))
                    : sprintf(', %s %s', key($order), current($order));
            }
        }

        if (count($resource->getLimit())) {
            $query .= sprintf(' LIMIT %s, %s', $resource->getLimit('offset'), $resource->getLimit('limit'));
        }


        $this->query = "{$query};";

        return $this;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function execute()
    {
        if (!$this->query) {
            throw new Exception('No query specified. Run build() first');
        }

        try {
            if (!$this->inTransaction()) {
                $this->beginTransaction();
            }

            $stmt = parent::prepare($this->query);

            foreach ($this->bindVars as $id => $value) {
                $stmt->bindValue($id, $value);
            }

            $stmt->execute();
            $this->commit();

            if (Application::logMysqlQueries()) {
                $logger = new Log;

                $logger->setLogfile(BP . '/var/log/mysql.log');
                $logger->setLevel(3);
                $logger->setMessage($this->getQuery());

                $logger->write();
            }
        } catch (Exception $e) {
            $this->rollBack();

            Application::logException($e);

            throw new \Exception($e->getMessage());
        }

        if (preg_match("/^(DROP|CREATE|ALTER|DELETE).*/", $this->query)) {
            return [];
        }

        $this->cleanup();

        return $stmt->fetchAll(parent::FETCH_ASSOC);
    }

    /**
     * Build query and parse it into a readable format.
     * This is for debugging purposes.
     *
     * @return bool|mixed
     */
    public function getQuery()
    {
        if ($this->query) {
            $tmpVars = $this->bindVars;

            $query = preg_replace_callback('/([\?])/', function () use (&$tmpVars) {
                return sprintf("'%s'", array_shift($tmpVars));
            }, $this->query);

            return $query;
        }

        return false;
    }

    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    public function setBindVars(array $vars)
    {
        $this->bindVars = $vars;
    }

    /**
     * @param $condition
     * @param $column
     * @param $value
     * @param $where
     * @throws \Exception
     */
    public function formatStatement($condition, $column, $value, &$where)
    {
        switch ($condition) {
            case 'eq':
                $this->defaultFormatStatement('=', $column, $value, $where);
                break;
            case 'neq':
                $this->defaultFormatStatement('!=', $column, $value, $where);
                break;
            case 'gt':
                $this->defaultFormatStatement('>', $column, $value, $where);
                break;
            case 'lt':
                $this->defaultFormatStatement('<', $column, $value, $where);
                break;
            case 'gteq':
                $this->defaultFormatStatement('>=', $column, $value, $where);
                break;
            case 'lteq':
                $this->defaultFormatStatement('<=', $column, $value, $where);
                break;
            case 'like':
                $this->defaultFormatStatement('LIKE', $column, $value, $where);
                break;
            case 'nlike':
                $this->defaultFormatStatement('NOT LIKE', $column, $value, $where);
                break;
            case 'in':
                $this->formatInStatement('IN', $column, $value, $where);
                break;
            case 'nin':
                $this->formatInStatement('NOT IN', $column, $value, $where);
                break;
            case 'null':
                $this->formatNullStatement('IS NULL', $column, $where);
                break;
            case 'not-null':
                $this->formatNullStatement('NOT NULL', $column, $where);
                break;
            default:
                throw new \Exception('Invalied condition supplied');
        }
    }

    /**
     * @param $condition
     * @param $column
     * @param $value
     * @param $where
     */
    protected function defaultFormatStatement($condition, $column, $value, &$where)
    {
        if (is_array($value)) {
            $valArr = [];

            foreach ($value as $val) {
                $valArr[] = sprintf('%s %s ?', $column, $condition);

                $this->bindVars[$this->bindIncrement++] = $val;
            }

            $where .= sprintf('(%s) ', implode(' OR ', $valArr));
        } else {
            $where .= sprintf('%s %s ?', $column, $condition);
            $this->bindVars[$this->bindIncrement++] = $value;
        }
    }

    /**
     * @param $column
     * @param $value
     * @param $where
     * @param string $condition
     */
    protected function formatInStatement($condition, $column, $value, &$where)
    {
        if (is_array($value)) {
            $replace = '';

            foreach ($value as $v) {
                $this->bindVars[$this->bindIncrement++] = $v;
                $replace .= '?,';
            }

            $replace = preg_replace('/,$/', '', $replace);
        } else {
            $replace = $value;
        }

        $where .= sprintf('%s %s (%s) ', $column, $condition, $replace);
    }

    /**
     * @param string $condition
     * @param $column
     * @param $where
     * @internal param $value
     */
    protected function formatNullStatement($condition, $column, &$where)
    {
        $where .= sprintf('%s %s ', $column, $condition);
    }

    public function cleanup()
    {
        $this->query         = null;
        $this->bindIncrement = 1;
        $this->bindVars      = [];

        return $this;
    }
}