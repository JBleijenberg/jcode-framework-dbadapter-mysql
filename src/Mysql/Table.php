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
namespace Jcode\DBAdapter\Mysql;

use Jcode\Application;
use Jcode\Db\TableInterface;
use Jcode\DBAdapter\Mysql\Table\Column;

class Table implements TableInterface
{

    protected $tableName;

    protected $columns = [];

    protected $dropColumns = [];

    protected $alterColumns = [];

    protected $engine;

    protected $primaryKey;

    protected $charset = 'utf8';

    const ENGINE_INNODB = 'InnoDB';

    const ENGINE_MEMORY = 'memory';

    const ENGINE_MYISAM = 'MyISAM';

    /**
     * Set table name
     *
     * @param $name
     *
     * @return $this
     */
    public function setTableName($name)
    {
        $this->tableName = $name;

        return $this;
    }

    /**
     * Return tablename
     *
     * @return mixed
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Set table engine
     *
     * @param $engine
     *
     * @return $this
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * Return table engine
     *
     * @return mixed
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Set charset
     *
     * @param $charset
     *
     * @return $this
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Return charset
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Add column to this table
     *
     * @param $name
     * @param $type
     * @param null $length
     * @param array $options
     * @return $this
     * @throws \Exception
     */
    public function addColumn($name, $type, $length = null, array $options = [])
    {
        /* @var Column $column */
        $column = Application::getClass('Jcode\DBAdapter\Mysql\Table\Column');

        $column->setName($name);
        $column->setType($type);
        $column->setLength($length);
        $column->setOptions($options);

        $this->columns[$name] = $column;

        return $this;
    }

    /**
     * Alter column from this table
     *
     * @param $name
     * @param array $options
     * @return $this
     */
    public function alterColumn($name, array $options)
    {
        /* @var Column $column */
        $column = Application::getClass('Jcode\Db\Model\Adapter\Mysql\Table\Column');

        $column->setName($name);
        $column->setOptions($options);

        $this->alterColumns[$name] = $column;

        return $this;
    }

    public function getAlteredColumns()
    {
        return $this->alterColumns;
    }

    /**
     * Drop column from table
     *
     * @param $name
     * @return $this
     */
    public function dropColumn($name)
    {
        $this->dropColumns[$name] = $name;

        return $this;
    }

    public function getDroppedColumns()
    {
        return $this->dropColumns;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getColumn($name)
    {
        return $this->columns[$name];
    }

    public function setPrimaryKey($key)
    {
        $this->primaryKey = $key;

        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
}