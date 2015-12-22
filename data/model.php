<?php
  /* INFO
    @product     : xbData
    @component   : xbDataModel
    @type        : сlass
    @description : Класс модели данных
    @revision    : 2015-12-20 16:36:00
  */

  if (!class_exists('xbDataQuery')) require 'query.php';

  /* CLASS ~BEGIN */
  /**
   * Class xbDataModel
   * @property-read object $owner
   * @property-read string $table
   * @property-read array  $fields
   * @property-read array  $add
   * @property-read bool   $ready
   * @property-read string $error
   */
  class xbDataModel {
    protected $_owner  = null; // Владелец
    protected $_table  = null; // Название основной таблицы
    protected $_fields = null; // Поля
    protected $_add    = null; // Информация по дополнительным полям
    protected $_ready  = null; // Готовность
    protected $_error  = null; // Сообщение ошибки

    /* CLASS:CONSTRUCT */
    function __construct($data,$owner=null) {
      $this->_owner = $owner;
      $this->_ready = true;
      $cached = false;
      if (isset($data['cached'])) if ($data['cached']) $cached = true;
      if (!$cached) {
        if ($ret = xbDataModels::correct($data)) {
          if (is_object($this->_owner)) {
            if (method_exists($this->_owner,'saveModelCache'))
              $this->_ready = $this->_owner->saveModelCache($ret);
          }
        } else { $this->_ready = false; }
      } else { $ret = $data; }
      if ($this->_ready) {
        $this->_table  = $ret['table'];
        $this->_fields = $ret['fields'];
        $this->_add    = $ret['add'];
      }
    }

    /* CLASS:GET */
    function __get($n) { $N = "_$n"; return property_exists($this,$N) ? $this->$N : false; }

    /* **************** ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ **************** */
    /* CLASS:METHOD
      @name        : escape
      @description : Экранирование строки

      @param : $value | string | value | | Строка

      @return : string
    */
    public function escape($value) {
      if (is_object($this->_owner))
        if (method_exists($this->_owner,'escapeString')) return $this->_owner->escapeString($value);
      return addslashes($value);
    }

    /* CLASS:METHOD
      @name        : names
      @description : Получение полей формы

      @param : $data      | array  | value | @NULL  | Имеющиеся данные
      @param : $operation | string | value | create | Операция

      @return : array
    */
    public function names($flist=null,$operation='read') {
      if (!$this->_ready) return false;
      $op = xbData::operation($operation);
      if ($operation == 'table') $op = $operation;
      if (!$op) return false;
      $fnames = array();
      $_omain = false;
      if (($flist === true) || ($op == 'table')) {
        $_names = array_keys($this->_fields);
        $_omain = true;
      } elseif (is_array($flist)) { $_names = $flist;
      } elseif (!is_null($flist)) { $_names = explode(',',$flist);
      } else                      { $_names = array_keys($this->_fields); }
      foreach ($_names as $name)
        if (isset($this->_fields[$name]))
          if (!$_omain || (!$this->_fields[$name]['add'] && is_null($this->_fields[$name]['external'])))
            if ($operation == 'table') {
              $fnames[] = $name;
            } else {
              if ($this->_fields[$name]['access'][$op]) $fnames[] = $name;
            }
      return $fnames;
    }

    /* CLASS:METHOD
      @name        : prepare
      @description : Подготовка данных для запроса

      @param : $record | array | value |        | Запись данных
      @param : $av     | bool  | value | @FALSE | Проверка доступа

      @return : array
    */
    public function prepare($record,$av=false) {
      if (!$this->_ready) return false;

      $ret = array('primary' => 0);
      foreach (array('main','replace','delete') as $k)
        $ret[$k] = array('create' => array(),'update' => array());

      $rMain = true;
      foreach ($this->_fields as $alias => $field) {
        $val = array('create' => $field['default']);
        // Create
        if ($field['access']['create'] || !$av) {
          if (isset($record[$alias])) $val['create'] = $record[$alias];
          if (!$field['null'] && is_null($val['create'])) {
            if (!($field['primary'] && $field['auto'])) unset($val['create']);
            $val = array('create' => 0);
          }
        }
        // Update
        if (!$field['primary'] && ($field['access']['update'] || !$av)) {
          if (isset($record[$alias])) {
            $val['update'] = $record[$alias];
            if (!$field['null'] && is_null($val['update'])) unset($val['update']);
          }
        }
        // Определение значения
        if (!$field['add'] && is_null($field['external'])) {
          if (!isset($val['create']) && @!is_null($val['create'])) {
            // Неполный крейт невозможен (некорректно)
            $rMain = false;
            $ret['main']['create'] = false;
          }
          if ($rMain) {
            $ret['main']['create'][$alias] = $val['create'];
            if ($field['primary']) $ret['primary'] = $ret['main']['create'][$alias];
          }
          if (isset($val['update']))
            $ret['main']['update'][$alias] = $val['update'];
        } elseif ($field['add']) {
          $fid = $field['id'];
          foreach (array('create','update') as $op) {
            if (isset($val[$op])) {
              $def = true;
              if (!is_null($val[$op]))            $def = false;
              if ($val[$op] == $field['default']) $def = true;
              if (!$def) {
                $ret['replace'][$op][$alias] = $val[$op];
              } else { $ret['delete'][$op][] = $fid; }
            }
          }
        } else {
          if (isset($val['update']))
            $ret['external']['update'][$alias] = $val['update'];
        }
      }
      return $ret;
    }

    /* **************** ОСНОВНЫЕ ФУНКЦИИ **************** */
    /* CLASS:METHOD
      @name        : form
      @description : Получение полей формы

      @param : $data      | array  | value | @NULL  | Имеющиеся данные
      @param : $operation | string | value | create | Операция

      @return : array
    */
    public function form($data=null,$operation='create') {
      if (!$this->_ready) return false;
      return xbDataModels::form($this->_fields,$data,$operation);
    }

    /* CLASS:METHOD
      @name        : query
      @description : Запрос

      @param : $type   | string | value |       | Тип запроса
      @param : $data   | array  | value | @NULL | Данные
      @param : $fields | array  | value | @NULL | Поля запроса

      @return : xbDataQuery
    */
    public function query($type,$data=null,$fields=null) {
      if (!$this->_ready) return false;
      $DATA   = null;
      $FIELDS = $fields;
      $TYPE   = xbData::SQLOperation($type);
      if (!$TYPE) return false;
      if (in_array($TYPE,array('insert','replace','update'))) {
        $DATA   = array();
        $FIELDS = null;
        if (!is_array($data)) return false;
        foreach ($data as $record) {
          if (!is_array($record)) {
            if ($row = $this->prepare($data)) $DATA = array($row);
            break;
          }
          if ($row = $this->prepare($record)) $DATA[] = $row;
        }
      }
      return new xbDataQuery($this,$TYPE,$DATA,$FIELDS);
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>