<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Model;

use DateTime;
use PDO;
use ScavixWDF\WdfDbException;

/**
 * @internal SQL common query builder
 * @suppress PHP0413
 */
class Query
{
	public $_object = false;
	/**
	 * @var DataSource|bool
	 */
	public $_ds = false;
	public $_knownmodels = [];

	public $_initialSequence = false;
	public $_where = false;

	public $_values = [];

	/**
	 * @var ResultSet|bool
	 */
	public $_statement = false;

    function __construct(&$obj,&$datasource,$conditions_separator="WHERE")
	{
		if( !unserializer_active() )
		{
			$this->_object = $obj;
			$this->_ds = $datasource;
			$this->_where = new ConditionTree(-1,"AND",$conditions_separator);
			$this->_knownmodels = array($obj);
		}
	}

    public function __clone()
	{
		if( $this->_where )
            $this->_where = unserialize(serialize($this->_where));
	}

	function __toString()
	{
		return $this->_initialSequence . $this->__generateSql();
	}

    protected $argNamePrefix = 'a';
    protected $argNameLength = 2;
    protected $argNameCounter = 0;
    protected function argName()
    {
        if( $this->argNameCounter > 99 && $this->argNameLength == 2 )
        {
            $this->argNamePrefix = chr(ord($this->argNamePrefix) + 1);
            $this->argNameCounter = 0;
            if( $this->argNamePrefix == 'z' )
                $this->argNameLength = 9;
        }
        return ":{$this->argNamePrefix}" . str_pad("" . $this->argNameCounter++, $this->argNameLength, "0", STR_PAD_LEFT);
    }

	public function GetSql()
	{
		if( !$this->_statement )
			return "";
		return $this->_statement->GetSql();
	}

	public function GetArgs()
	{
		if( !$this->_statement )
			return [];
		return $this->_statement->GetArgs();
	}

	public function GetPagingInfo($key=false)
	{
		if( !$this->_statement )
			return "";
		return $this->_statement->GetPagingInfo($key);
	}

	function __execute($injected_sql=false, $injected_arguments=[], $ctor_args=null)
	{
		$sql = $injected_sql?$injected_sql:$this->__toString();
		if( $injected_arguments )
            $this->_values = is_array($injected_arguments)?$injected_arguments:[$injected_arguments];
        else
            $this->_values = $this->_where->__getArgs();

		$this->_statement = $this->_ds->Prepare($sql);
        $exec = $this->_statement->ExecuteWithArguments($this->_values);
		if( !$exec )
        {
            // log_debug("Query:",$this);
			WdfDbException::RaiseStatement($this->_statement);
        }
		$res = $this->_statement->fetchAll(PDO::FETCH_CLASS,get_class($this->_object),$ctor_args);
		return $res;
	}

	protected function &__conditionTree()
	{
		return $this->_where;
	}

	protected function __fqFields(&$property)
	{
		if( !$property )
			return;
		if( !is_array($property) )
			$property->__fqFields($this->_knownmodels);
		else
			foreach( $property as &$p )
				if( system_method_exists($p, '__fqFields') )
					$p->__fqFields($this->_knownmodels);
	}

	protected function __generateSql()
	{
		if( count($this->_knownmodels) > 0 )
			$this->__fqFields($this->_where);
		$sql = $this->_where->__generateSql();
		return $sql;
	}

	function where($defaultOperator = "AND")
	{
		$this->_where = new ConditionTree(-1,$defaultOperator);
	}

	function andAll()
	{
		$this->__conditionTree()->SetOperator("AND");
	}

	function orAll()
	{
		$this->__conditionTree()->SetOperator("OR");
	}

	function andX($count)
	{
		$this->__conditionTree()->Nest($count,"AND");
	}

	function orX($count)
	{
		$this->__conditionTree()->Nest($count,"OR");
	}

    function end()
    {
        $this->__conditionTree()->Close();
    }

    function if($condition)
	{
		$this->__conditionTree()->Nest(1,"IF",!!$condition);
	}

	function sql($sql,$arguments=[])
	{
        $args = [];
        if (strpos($sql, "?") !== false)
        {
            // Detect questionmarks inside text-literals and abstract the whole literal as argument.
            // This avoids the questionmark to be recognized as argument placeholder.
            $sql = preg_replace_callback('/([\'"])[^\1]*\?[^\1]*\1/', function ($m) use (&$args)
            {
                $n = $this->argName();
                $args[$n] = new ConditionArgument($n, trim($m[0], $m[1]));
                return $n;
            }, $sql);
            $sql = preg_replace_callback('/\?/', function ($m) use (&$args, &$arguments)
            {
                $n = $this->argName();
                $args[$n] = new ConditionArgument($n, array_shift($arguments));
                return $n;
            }, $sql);
        }
        else
        {
            foreach ($arguments as $n => $v)
            {
                if (!starts_with($n, ':'))
                    $n = ":$n";
                $args[$n] = new ConditionArgument($n, $v);
            }
        }
        $this->__conditionTree()->Add(new Condition("SQL", $sql, $args));
	}

    protected function stdOp($op, $property, $value, $value_is_sql=false, $prefix='', $suffix='')
    {
        if ($value instanceof ColumnAttribute || $value_is_sql)
            $this->__conditionTree()->Add(new Condition($op, $property, $value, $prefix, $suffix));
        else
        {
            $value = ($value instanceof ConditionArgument) ? $value : new ConditionArgument($this->argName(), $value);
            $this->__conditionTree()->Add(new Condition($op, $property, $value, $prefix, $suffix));
        }
    }

	function equal($property,$value,$value_is_sql=false)
	{
        $this->stdOp('=', $property, $value, $value_is_sql);
	}

	function notEqual($property,$value,$value_is_sql=false)
	{
        $this->stdOp('!=', $property, $value, $value_is_sql);
	}

	function greaterThan($property,$value,$value_is_sql=false)
	{
		$this->stdOp('>', $property, $value, $value_is_sql);
	}

	function greaterThanOrEqualTo($property,$value,$value_is_sql=false)
	{
		$this->stdOp('>=', $property, $value, $value_is_sql);
	}

	function lowerThan($property,$value,$value_is_sql=false)
	{
		$this->stdOp('<', $property, $value, $value_is_sql);
	}

	function lowerThanOrEqualTo($property,$value,$value_is_sql=false)
	{
		$this->stdOp('<=', $property, $value, $value_is_sql);
	}

	function binary($property,$value,$value_is_sql=false)
	{
        $this->stdOp('=', $property, $value, $value_is_sql,"BINARY ");
	}

	function notBinary($property,$value,$value_is_sql=false)
	{
		$this->stdOp('!=', $property, $value, $value_is_sql,"BINARY ");
	}

	function like($property,$value,$flipped=false)
	{
        if ($flipped)
            $this->stdOp('LIKE', new ConditionArgument($this->argName(), $property), $value);
        else
            $this->stdOp('LIKE', $property, $value);
	}

	function rlike($property,$value,$flipped=false)
	{
        if ($flipped)
            $this->stdOp('RLIKE', new ConditionArgument($this->argName(), $property), $value);
        else
            $this->stdOp('RLIKE', $property, $value);
	}

	public function in($property,$values)
	{
		if( count($values) == 0 )
			return;
        $args = [];
        foreach( is_array($values)?$values:[$values]  as $value )
            $args[] = new ConditionArgument($this->argName(), $value);
        $this->__conditionTree()->Add(new Condition('IN', $property, $args));
	}

	public function notIn($property,$values)
	{
		if( count($values) == 0 )
			return;
        $args = [];
        foreach( is_array($values)?$values:[$values]  as $value )
            $args[] = new ConditionArgument($this->argName(), $value);
        $this->__conditionTree()->Add(new Condition('NOT IN', $property, $args));
	}

	public function isNull($property)
	{
        $this->stdOp('IS', $property, null);
	}

	public function notNull($property)
	{
        $this->stdOp('IS NOT', $property, NULL);
	}

	public function newerThan($property,$value,$interval)
	{
        $this->stdOp('>', $property, new ConditionArgument($this->argName(), $value, "NOW() - INTERVAL ? $interval"));
	}

	public function olderThan($property,$value,$interval)
	{
        $this->stdOp('<', $property, new ConditionArgument($this->argName(), $value, "NOW() - INTERVAL ? $interval"));
	}

	public function noop()
	{
        $this->sql('1=1', []);
	}
}

/**
 * @internal Helper class for the SQL query builder <Query>
 */
class ConditionTree
{
	public $_firstToken = "WHERE";
	public $_operator = "AND";
	public $_conditions = [];
	public $_maxConditions = -1;
	public $_current = false;
	public $_parent = false;

	function __construct($conditionCount = -1,$operator = "AND", $firstToken = "WHERE")
	{
		$this->_operator = $operator;
		$this->_maxConditions = $conditionCount;
		$this->_current = $this;
		$this->_firstToken = $firstToken;
	}

	function __fqFields(&$knownModels)
	{
		foreach( $this->_conditions as &$c )
			if( $c instanceof Condition)
				$c->__fqFields($knownModels);
	}

	function __generateSql()
	{
		if( count($this->_conditions) < 1 )
			return "";

		$sql = [];
		foreach( $this->_conditions as $c )
		{
			if( is_string($c) )
				$s = $c;
			elseif( $c instanceof Condition )
				$s = $c->__toSql();
			else
				$s = $c->__generateSql();
			if( $s )
				$sql[] = $s;
		}
		if( count($sql) == 0 )
			return "";
        if( $this->_operator == "IF" )
        {
            if( count($sql) != 1 )
                \ScavixWDF\WdfException::Raise("Cannot handle more that 1 conditions in matched 'if' tree, use andX/orX/...");
            if (!$this->_firstToken)
                return "";
            return "({$sql[0]})";
        }

		if( $this->_parent )
			$sql = "(".implode(" {$this->_operator} ",$sql).")";
		else
			$sql = " {$this->_firstToken} ".implode(" {$this->_operator} ",$sql);
		return $sql;
	}

    function __getArgs($log = false)
    {
        $args = [];
        foreach ($this->_conditions as $c)
        {
            if( $c instanceof ConditionTree )
                $args += $c->__getArgs($log);
            if( $c instanceof Condition )
                $args += $c->__getArgs($log);
        }
        return $args;
    }

	function __ensureClose()
	{
		if( $this->_current->_parent && $this->_current->_maxConditions > -1 &&
			count($this->_current->_conditions) == $this->_current->_maxConditions )
		{
			$this->_current = $this->_current->_parent;
			$this->__ensureClose();
		}
	}

	function SetOperator($operator)
	{
		$this->Nest(-1,$operator);
	}

	function Add($condition)
	{
        if( $this->_current->_operator == "IF" && !$this->_current->_firstToken )
        {
            $this->_current->_conditions[] = "";
            $this->__ensureClose();
            return false;
        }
		$this->_current->_conditions[] = $condition;
		$this->__ensureClose();
        return true;
	}

	function Nest($conditionCount,$operator = "AND",$firstToken = "WHERE")
	{
		$mem = $this->_current;
		$this->_current->_conditions[] = new ConditionTree($conditionCount,$operator,$firstToken);
		$this->_current = $this->_current->_conditions[count($this->_current->_conditions)-1];
		$this->_current->_parent = $mem;
	}

    function Close()
    {
        $this->_current->_maxConditions = count($this->_current->_conditions);
        $this->__ensureClose();
    }
}

/**
 * @internal Helper class for the SQL query builder <Query>
 */
class Condition
{
	public $_operator;
	public $_op1;
	public $_op2;
	public $_pre;
	public $_suf;

	function __construct($operator="AND",$op1="",$op2 = "?",$prefix="",$suffix="")
	{
		$this->_operator = " $operator ";
		$this->_op1 = $op1;
		$this->_op2 = $op2;
		$this->_pre = $prefix;
		$this->_suf = $suffix;

        if ($op1 == "?" || $op2 == "?")
            WdfDbException::Raise("Stop calling with ?, use ConditionArgument class instead",$operator,$op1,$op2,$prefix,$suffix);
	}

	function __toSql()
	{
        if ($this->_operator == " SQL ")
            return "{$this->_op1}";

        if (is_array($this->_op2))
        {
            $op2 = [];
            foreach ($this->_op2 as $o)
                $op2[] = "$o";
            return "{$this->_op1}{$this->_operator}(" . implode(",", $op2) . ")";
        }
		return "{$this->_pre}{$this->_op1}{$this->_operator}{$this->_op2}{$this->_suf}";
	}

	function __fqFields(&$knownModels)
	{
		return;
		foreach( $knownModels as &$km )
		{
			if( !($this->_op1 instanceof ConditionArgument) )
			{
				$this->_op1 = $km->FullQualifiedFieldName($this->_op1);
				continue;
			}
			if( !($this->_op2 instanceof ConditionArgument) )
				$this->_op2 = $km->FullQualifiedFieldName($this->_op2);
		}
	}

    function __getArgs($log = false)
    {
        $args = [];
        $this->__fillArgs($args, $this->_op1, $log);
        $this->__fillArgs($args, $this->_op2, $log);
        if( $log )
            log_debug(__METHOD__, $args);
        return $args;
    }

    private function __fillArgs(&$args, $obj, $log = false)
    {
        if( $log )
            log_debug(__METHOD__, $obj, $args);
        if ($obj instanceof ConditionArgument)
        {
            $args[$obj->name] = $obj->value;
        }
        elseif (is_array($obj))
            foreach ($obj as $o)
                $this->__fillArgs($args, $o, $log);
    }
}

/**
 * @internal Encapsulates a condition argument
 */
class ConditionArgument
{
    public $name, $value, $pattern;

    public function __construct(string $name, $value, $pattern = '')
    {
        $this->name = $name;
        $this->value = $value;
        $this->pattern = $pattern;
    }

    function __toString()
    {
        if ($this->pattern)
            return str_replace("?", $this->name, $this->pattern);
        return $this->name;
    }
}