<?php
  /* INFO
    @product     : xbData
    @component   : xbDataModel
    @type        : сlass
    @description : Библиотека функций для работы с полями данными
    @revision    : 2015-12-20 16:21:00
  */

  if (!class_exists('xbDataModel')) require 'models.php';

  /* CLASS ~BEGIN */
  /**
   * Class xbDataQuery
   * @property-read xbDataModel $owner
   * @property-read bool        $ready
   * @property-read string      $type
   * @property-read array       $data
   * @property-read array       $adds
   * @property-read bool        $jmay
   * @property-read array       $fields
   * @property-read string      $query
   * @property-read string      $order
   * @property-read string      $where
   * @property-read string      $limit
   * @property-read string      $selectFields
   * @property-read string      $table
   */
  class xbDataQuery {
    protected $_owner = null;
    protected $_ready = false;
    protected $_type  = '';
    protected $_data  = null;
    protected $_adds   = array();
    protected $_jmay   = false;
    protected $_fields = array();
    protected $_query = '';
    protected $_order = '';
    protected $_where = '';
    protected $_limit = '';

    /* CLASS:CONSTRUCT */
    function __construct($owner,$type,$data=null,$fields=null) {
      if ($owner instanceof xbDataModel) {
        $this->_owner = $owner;
        $this->_ready = $this->owner->ready;
        if ($this->_ready) {
          $this->_jmay = is_array($this->owner->add);
          // Поля
          $_ = $this->owner->names($fields,$type);
          $this->_fields = array();
          foreach ($_ as $alias) {
            if ($this->owner->fields[$alias]['add'] && ($this->_type != 'table')) {
              if ($this->join_add($alias)) $this->_fields[$alias] = "t_$alias.`value`";
            } else { $this->_fields[$alias] = "tmain.`$alias`"; }
          }
          // Данные
          $this->_data = $data;
          $this->_type = $type;
          $t = '`[+prefix+]'.$this->owner->table.'`';
          switch ($this->_type) {
            case 'replace':
            case 'insert': $this->_query = "$type into $t values"; break;
            case 'select': $this->_query = "select [+selectfields+] from $t as tmain"; break;
            case 'update': $this->_query = "update $t as tmain"; break;
            case 'delete': $this->_query = "delete from $t as tmain"; break;
            case 'clear' : $this->_query = "delete from $t"; break;
            case 'table' : $this->_query = "create table if not exists $t"; break;
          }
        }
      }
    }

    /* CLASS:GET */
    function __get($n) {
      switch ($n) {
        case 'selectFields':
          $ret = array();
          foreach ($this->_fields as $alias => $name) $ret[] = "$name as `$alias`";
          return empty($ret) ? false : implode(',',$ret);
        case 'table': return "`[+prefix+]".$this->owner->table."`";
        case 'query': return $this->get();
      }
      $N = "_$n"; return property_exists($this,$N) ? $this->$N : false;
    }

    /* **************** ВНУТРЕННИЕ ФУНКЦИИ **************** */
    /* добавление дополнительного поля в запрос */
    protected function join_add($alias) {
      if (!$this->_jmay) return false;
      if (!isset($this->_adds[$alias]))
        $this->_adds[$alias] = $this->owner->fields[$alias]['id'];
      return true;
    }

    /* получение джойнов */
    protected function join_get() {
      if (!$this->_jmay) return '';
      $join = array();
      $at  = $this->owner->add['table'];
      $af  = $this->owner->add['field'];
      foreach ($this->_adds as $fn => $fid) {
        $q = "left join `[+prefix+]$at` as t_$fn on ";
        $q.= "((t_$fn.`$af` = tmain.`id`) and (t_$fn.`field` = $fid))";
        $join[] = $q;
      }
      return empty($join) ? '' : ' '.implode('',$join);
    }

    /* получение условий */
    protected function cond_get() {
      $_ = '';
      if (!empty($this->_where)) $_.= " where ".$this->_where;
      if (!empty($this->_order)) $_.= " order by ".$this->_order;
      if (!empty($this->_limit)) $_.= " limit ".$this->_limit;
      return $_;
    }

    /* получение строки условия выборки */
    protected function where_node($condition=null,$fname=null) {
      static $level = -1;
      if (is_null($condition))   return '';
      if (is_string($condition)) return $condition;
      $level++;
      if (is_array($condition)) {
        $ret = array();
        $fop = 'and'; $ffn = $fname; $fvl = null; $fqr = null;
        foreach ($condition as $nk => $nv) {
          switch ($nk) {
            case 'where.operation': $fop = self::operation($nv); break;
            case 'where.field':
              if ($this->owner->fields[$nv]['add']) {
                if ($this->join_add($nv)) $ffn = "t_$nv.`value`";
              } else { $ffn = "tmain.`$nv`"; }
              break;
            case 'where.value': $fvl = $nv; break;
            case 'where.query': $fqr = $nv; break;
            default:
              if (is_array($nv)) {
                $ret[] = $this->where_node($nv,null,$nk);
              } elseif (isset($this->owner->fields[$nk])) {
                $op = is_null($nv) ? 'is null' : '=';
                $vl = $this->value($nk,$nv);
                if ($this->owner->fields[$nk]['add']) {
                  if ($this->join_add($nk)) $ret[] = "(t_$nk.`value` $op $vl)";
                } else { $ret[] = "(tmain.`$nk` $op $vl)"; }
              }
          }
        }
        if (!empty($ret)) { $level--; return implode(" $fop ",$ret); }
        if (!empty($ffn) && !is_null($ffn)) {
          // Нельзя сочетать простые условия и установленное поле
          if (isset($this->owner->fields[$ffn])) {
            $level--;
            $vl = is_null($fqr) ? $this->value($ffn,$fvl) : "($fqr)";
            if ($this->owner->fields[$ffn]['add']) {
              if ($this->join_add($ffn)) return "(t_$ffn.`value` $fop $vl)";
            } else { return "(tmain.`$ffn` $fop $vl)"; }
          }
        }
      }
      $level--;
      return '';
    }

    /* **************** CHAIN-ФУНКЦИИ **************** */
    /* CLASS:CHAIN
      @name        : where
      @description : Условие выборки

      @param : $condition | array | value | @NULL | Условия
    */
    public function where($condition=null) {
      $this->_where = $this->where_node($condition);
      return $this;
    }

    /* CLASS:CHAIN
      @name        : order
      @description : Условие сортировки

      @param : $condition | array | value | @NULL | Условия
    */
    public function order($condition=null) {
      if (is_null($condition))   return $this;
      if (is_string($condition)) { $this->_order = $condition; return $this; }
      $ret = array();
      foreach ($condition as $nk => $nv) {
        $fn = is_int($nk) ? $nv : $nk;
        $fv = is_int($nk) ? 'asc' : (in_array($nv,array('asc','desc')) ? $nv : 'asc');
        if (isset($this->owner->fields[$fn])) {
          if ($this->owner->fields[$fn]['add']) {
            if ($this->join_add($fn)) $ret[] = "t_$fn.`value` $fv";
          } else { $ret[] = "tmain.`$fn` $fv"; }
        }
      }
      if (empty($ret)) return $this;
      $this->_order = implode(',',$ret);
      return $this;
    }

    /* CLASS:CHAIN
      @name        : limit
      @description : Условие ограничения

      @param : $cnt    | int | value | 0 | Количество записей
      @param : $offset | int | value | 0 | Смещение
    */
    public function limit($cnt=0,$offset=0) {
      $this->_limit = '';
      if ($cnt > 0) {
        if ($offset > 0) $this->_limit.= $offset.',';
        $this->_limit.= $cnt;
      }
      return $this;
    }

    /* **************** ОСНОВНЫЕ ФУНКЦИИ **************** */
    /* CLASS:METHOD
      @name        : value
      @description : SQL-представление значения

      @param : $alias | string | value | | Алиас поля
      @param : $value |        | value | | Значение

      @return : string
    */
    public function value($alias,$value) {
      if (!isset($this->owner->fields[$alias])) return false;
      $t = intval($this->owner->fields[$alias]['type']) & XBDATA_TYPE_VARIABLE;
      $a = $this->owner->fields[$alias]['add'];
      if (is_null($value)) return 'null';
      switch ($t) {
        case XBDATA_TYPE_BOOL    : return $value ? ($a ? "'1'" : "''") : ($a ? "'0'" : "null");
        case XBDATA_TYPE_INTEGER :
        case XBDATA_TYPE_FLOAT   : return $a ? "'$value'" : $value;
        case XBDATA_TYPE_DATETIME:
          $t = '/^(\d{4})\-(\d{2})\-(\d{2})\s(\d{2})\:(\d{2})\:(\d{2})$/';
          switch ($this->owner->fields[$alias]['type'] & 0x70) {
            case XBDATA_INPUT_DWEEK:
            case XBDATA_INPUT_MONTH: return ($a && is_int($value)) ? "'$value'" : $value;
            case XBDATA_INPUT_CLOCK:
            case XBDATA_INPUT_TIME : $t = '/^(\-?)(\d{2,3})\:(\d{2})\:(\d{2})$/'; break;
            case XBDATA_INPUT_DATE : $t = '/^(\d{4})\-(\d{2})\-(\d{2})$/'; break;
          }
          return preg_match($t,$value) ? "'$value'" : $value;
        default: $v = $value;
      }
      return "'".$this->owner->escape($v)."'";
    }

    /* CLASS:METHOD
      @name        : get
      @description : Получить все запросы

      @return : array | Массив запросов
    */
    public function get() {
      if (!$this->_ready) return false;
      if (in_array($this->_type,array('replace','insert','update'))) {
        if (!is_array($this->_data)) return false;
        if (empty($this->_data))     return false;
      }
      $ret = array();
      $at  = $this->_jmay ? '`[+prefix+]'.$this->owner->add['table'].'`' : '';
      $mt  = $this->table;
      switch ($this->_type) {
        case 'replace':
        case 'insert':
          foreach ($this->_data as $row) {
            if (!isset($row['main']['create']))    continue;
            if (!is_array($row['main']['create'])) continue;
            $q = array();
            foreach ($row['main']['create'] as $alias => $value) $q[] = $this->value($alias,$value);
            $ret[] = $this->_query." (".implode(',',$q).")";
            if (!empty($row['replace']['create']) && $this->_jmay) {
              $v = array();
              foreach ($row['replace']['create'] as $fn => $fv)
                $v[] = "([+last_insert_id+],".$this->_adds[$fn].",".$this->value($fn,$fv).")";
              if (!empty($v)) $ret[] = "insert into $at values ".implode(',',$v);
            }
          }
          break;
        case 'select':
          if ($_ = $this->selectFields) {
            $_ = str_replace('[+selectfields+]',$_,$this->_query);
            return array($_.$this->join_get().$this->cond_get());
          } else { return false; }
        case 'update':
          $join  = $this->join_get();
          $limit = (empty($this->_limit) ? '' : " limit ".$this->_limit);
          foreach ($this->_data as $row) {
            $q = array();
            foreach ($row['main']['update'] as $fn => $fv) $q[] = "tmain.`$fn` = ".$this->value($fn,$fv);
            if (empty($q)) continue;
            $_ = " set ".implode(',',$q);
            $w = array();
            if (!empty($this->_where))   $w[] = "(".$this->_where.")";
            if (!empty($row['primary'])) $w[] = "(tmain.`id` = ".$row['primary'].")";
            $_.= empty($w) ? '' : " where ".implode(' and ',$w);
            $ret[] = $this->_query."$join $_".$limit;
            $cfs = "select tmain.`id` from $mt as tmain".$join.$w.$limit;
            if (!empty($row['replace']['update']) && $this->_jmay) {
              $ret[] = $cfs;
              $v = array();
              foreach ($row['replace']['update'] as $fn => $fv)
                $v[] = "([+id+],".$this->_adds[$fn].",".$this->value($fn,$fv).")";
              if (!empty($v)) $ret[] = "replace into $at values ".implode(',',$v);
            }
            if (!empty($row['delete']['update']))
              $ret[] = "delete from $at"
                     . " where (`field` in (".implode(',',$row['delete']['update'])."))"
                     . " and (`".$this->owner->add['field']."` in ($cfs))";
          }
          break;
        case 'delete': return array($this->_query.$this->join_get().$this->cond_get());
        case 'clear' : return array($this->_query);
        case 'table' :
          $f = array();
          $p = '';
          foreach ($this->_fields as $alias => $v) {
            $f[] = "`$alias` ".xbDataFields::sqlType($this->owner->fields[$alias]);
            if ($this->owner->fields[$alias]['primary']) $p = $alias;
          }
          $_ = $this->_query." (".implode(',',$f);
          if (!empty($p)) $_.= "primary key (`$p`)";
          $_.= ") engine=InnoDB";
          $_.= " insert_method=first"; // TODO
          return array($_);
        default: return false;
      }
      return $ret;
    }

    /* **************** БИБЛИОТЕЧНЫЕ ФУНКЦИИ **************** */
    /* CLASS:STATIC
      @name        : operation
      @description : SQL-операция

      @param : $node | | value | | Value

      @return : string
    */
    public static function operation($node) {
      $ops = array(
        'eq' => '=' , 'neq' => '!=',
        'gt' => '>' , 'gte' => '>=',
        'lt' => '<' , 'lte' => '<=',
        'in' => 'in', 'nin' => 'not in',
        'like' => 'like',
        'and' => 'and', 'or' => 'or', 'not' => 'not',
        'null' => 'is null','nnull' => 'not is null'
      );
      if (is_string($node) || is_null($node)) {
        if (is_null($node)) return $ops['null'];
        $o = strval($node);
        if (in_array($o,array('not-eq','not eq','ne','neq','!='))) return $ops['neq'];
        if (in_array($o,array('greater','more','gt','>')))         return $ops['gt'];
        if (in_array($o,array('greater-eq','more-eq','gte','>='))) return $ops['gte'];
        if (in_array($o,array('less','lt','<')))                   return $ops['lt'];
        if (in_array($o,array('less-eq','lte','<=')))              return $ops['lte'];
        if (in_array($o,array('and','&&','&')))                    return $ops['and'];
        if (in_array($o,array('or','||','|')))                     return $ops['or'];
        if (in_array($o,array('nin','not-in','not in')))           return $ops['nin'];
        if (in_array($o,array('like','in','not','null')))          return $o;
        if (in_array($o,array('nnull','not-null','not null')))     return $ops['nnull'];
        return $ops['eq'];
      }
      return $ops['and'];
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>