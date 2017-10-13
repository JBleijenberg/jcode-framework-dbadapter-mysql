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
namespace Jcode\DBAdapter\Mysql\Table;

class Column
{

    protected $name;

    protected $type;

    protected $length;

    protected $default;

    protected $options = [];

    const TYPE_BINARY = 'BINARY';

    /**
     * A normal-sized integer that can be signed or unsigned.
     * If signed, the allowable range is from -2147483648 to 2147483647.
     * If unsigned, the allowable range is from 0 to 4294967295.
     * You can specify a width of up to 11 digits.
     */
    const TYPE_INT = 'INT';

    /**
     * A very small integer that can be signed or unsigned.
     * If signed, the allowable range is from -128 to 127.
     * If unsigned, the allowable range is from 0 to 255.
     * You can specify a width of up to 4 digits.
     */
    const TYPE_TINYINT = 'TINYINT';

    /**
     * A small integer that can be signed or unsigned.
     * If signed, the allowable range is from -32768 to 32767.
     * If unsigned, the allowable range is from 0 to 65535.
     * You can specify a width of up to 5 digits.
     */
    const TYPE_SMALLINT = 'SMALLINT';

    /**
     * A medium-sized integer that can be signed or unsigned.
     * If signed, the allowable range is from -8388608 to 8388607.
     * If unsigned, the allowable range is from 0 to 16777215.
     * You can specify a width of up to 9 digits.
     */
    const TYPE_MEDIUMINT = 'MEDIUMINT';

    /**
     * A large integer that can be signed or unsigned.
     * If signed, the allowable range is from -9223372036854775808 to 9223372036854775807.
     * If unsigned, the allowable range is from 0 to 18446744073709551615.
     * You can specify a width of up to 20 digits.
     */
    const TYPE_BIGINT = 'BIGINT';

    /**
     * A floating-point number that cannot be unsigned.
     * You can define the display length (M) and the number of decimals (D).
     * This is not required and will default to 10,2, where 2 is the number of decimals and 10
     * is the total number of digits (including decimals).
     * Decimal precision can go to 24 places for a FLOAT.
     */
    const TYPE_FLOAT = 'FLOAT';

    /**
     * A double precision floating-point number that cannot be unsigned.
     * You can define the display length (M) and the number of decimals (D).
     * This is not required and will default to 16,4, where 4 is the number of decimals.
     * Decimal precision can go to 53 places for a DOUBLE. REAL is a synonym for DOUBLE.
     */
    const TYPE_DOUBLE = 'DOUBLE';

    /**
     * Alias for TYPE_DOUBLE
     */
    const TYPE_REAL = 'REAL';

    /**
     * An unpacked floating-point number that cannot be unsigned.
     * In unpacked decimals, each decimal corresponds to one byte.
     * Defining the display length (M) and the number of decimals (D) is required.
     * NUMERIC is a synonym for DECIMAL.
     */
    const TYPE_DECIMAL = 'DECIMAL';

    /**
     * Alias for DECIMAL
     */
    const TYPE_NUMERIC = 'NUMERIC';

    /**
     * A date in YYYY-MM-DD format, between 1000-01-01 and 9999-12-31.
     * For example, December 30th, 1973 would be stored as 1973-12-30.
     */
    const TYPE_DATE = 'DATE';

    /**
     * A date and time combination in YYYY-MM-DD HH:MM:SS format, between 1000-01-01 00:00:00 and 9999-12-31 23:59:59.
     * For example, 3:30 in the afternoon on December 30th, 1973 would be stored as 1973-12-30 15:30:00.
     */
    const TYPE_DATETIME = 'DATETIME';

    /**
     * A timestamp between midnight, January 1, 1970 and sometime in 2037.
     * This looks like the previous DATETIME format, only without the hyphens between numbers;
     * 3:30 in the afternoon on December 30th, 1973 would be stored as 19731230153000 ( YYYYMMDDHHMMSS ).
     */
    const TYPE_TIMESTAMP = 'TIMESTAMP';

    /**
     * Stores the time in HH:MM:SS format.
     */
    const TYPE_TIME = 'TIME';

    /**
     * Stores a year in 2-digit or 4-digit format.
     * If the length is specified as 2 (for example YEAR(2)), YEAR can be 1970 to 2069 (70 to 69).
     * If the length is specified as 4, YEAR can be 1901 to 2155. The default length is 4.
     */
    const TYPE_YEAR = 'YEAR';

    /**
     * A fixed-length string between 1 and 255 characters in length (for example CHAR(5)),
     * right-padded with spaces to the specified length when stored.
     * Defining a length is not required, but the default is 1.
     */
    const TYPE_CHAR = 'CHAR';

    /**
     * A variable-length string between 1 and 255 characters in length; for example VARCHAR(25).
     * You must define a length when creating a VARCHAR field.
     */
    const TYPE_VARCHAR = 'VARCHAR';

    /**
     * A field with a maximum length of 65535 characters.
     * BLOBs are "Binary Large Objects" and are used to store large amounts of binary data, such as images or other types of files.
     * Fields defined as TEXT also hold large amounts of data;
     * the difference between the two is that sorts and comparisons on stored data are case sensitive on BLOBs and are not case sensitive in TEXT fields.
     * You do not specify a length with BLOB or TEXT.
     */
    const TYPE_BLOB = 'BLOB';
    const TYPE_TEXT = 'TEXT';

    /**
     * A BLOB or TEXT column with a maximum length of 255 characters.
     * You do not specify a length with TINYBLOB or TINYTEXT.
     */
    const TYPE_TINYBLOB = 'TINYBLOB';
    const TYPE_TINYTEXT = 'TINYTEXT';

    /**
     * A BLOB or TEXT column with a maximum length of 16777215 characters.
     * You do not specify a length with MEDIUMBLOB or MEDIUMTEXT.
     */
    const TYPE_MEDIUMBLOB = 'MEDIUMBLOB';
    const TYPE_MEDIUMTEXT = 'MEDIUMTEXT';

    /**
     * A BLOB or TEXT column with a maximum length of 4294967295 characters.
     * You do not specify a length with LONGBLOB or LONGTEXT.
     */
    const TYPE_LONGBLOB = 'LONGBLOB';
    const TYPE_LONGTEXT = 'LONGTEXT';

    /**
     * An enumeration, which is a fancy term for list.
     * When defining an ENUM, you are creating a list of items from which the value must be selected (or it can be NULL).
     * For example, if you wanted your field to contain "A" or "B" or "C",
     * you would define your ENUM as ENUM ('A', 'B', 'C') and only those values (or NULL) could ever populate that field.
     */
    const TYPE_ENUM = 'ENUM';

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($key)
    {
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        return null;
    }
}