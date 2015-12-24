<?php
  /* INFO
    @product     : xbData
    @component   : xbDataModel
    @type        : сlass
    @description : Класс модели данных
    @revision    : 2015-12-22 14:03:00
  */

  if (!class_exists('xbDataFields')) require 'fields.php';
  if (!class_exists('xbDataQuery'))  require 'query.php';

  /* CLASS ~BEGIN */
  /**
   * Class xbDataModel
   * @property-read object $owner
   * @property-read string $table
   * @property-read array  $fields
   * @property-read array  $add
   * @property-read string $primary
   * @property-read array  $unique
   * @property-read array  $indexes
   * @property-read bool   $ready
   * @property-read string $error
   */
  class xbDataModel {
    protected $_owner   = null; // Владелец
    protected $_table   = null; // Название основной таблицы
    protected $_fields  = null; // Поля
    protected $_add     = null; // Информация по дополнительным полям
    protected $_primary = null; // Первичный ключ
    protected $_unique  = null; // Первичный ключ
    protected $_ready   = null; // Готовность
    protected $_error   = null; // Сообщение ошибки

    /* CLASS:CONSTRUCT */
    function __construct($data,$owner=null) {
      $this->_owner = $owner;
      $this->_ready = true;
      $cached = false;
      if (isset($data['cached'])) if ($data['cached']) $cached = true;
      if (!$cached) {
        if ($ret = self::correct($data)) {
          if (is_object($this->_owner)) {
            if (method_exists($this->_owner,'saveModelCache'))
              $this->_ready = $this->_owner->saveModelCache($ret);
          }
        } else { $this->_ready = false; }
      } else { $ret = $data; }
      if ($this->_ready) {
        $this->_table   = $ret['table'];
        $this->_fields  = $ret['fields'];
        $this->_add     = $ret['add'];
        $this->_primary = $ret['primary'];
        $this->_unique  = $ret['unique'];
        $this->_indexes = $ret['indexes'];
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
            } else { if ($this->_fields[$name]['access'][$op]) $fnames[] = $name; }
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
        if  ($field['access']['create'] || !$av) {
          if (array_key_exists($alias,$record)) $val['create'] = $record[$alias];
          if (!$field['null'] && is_null($val['create'])) {
            if (!($field['primary'] && $field['auto'])) unset($val['create']);
            $val = array('create' => 0);
          }
        }
        // Update
        if (($field['access']['update'] || !$av) && !$field['primary']) {
          if (array_key_exists($alias,$record)) {
            $val['update'] = $record[$alias];
            if (!$field['null'] && is_null($val['update'])) unset($val['update']);
          }
        }
        // Определение значения
        if (!$field['add'] && is_null($field['external'])) {
          if (array_key_exists('create',$val)) {
            // Неполный крейт невозможен (некорректно)
            $rMain = false;
            $ret['main']['create'] = false;
          }
          if ($rMain) {
            $ret['main']['create'][$alias] = $val['create'];
            if ($field['primary']) $ret['primary'] = $ret['main']['create'][$alias];
          }
          if (array_key_exists('update',$val))
            $ret['main']['update'][$alias] = $val['update'];
        } elseif ($field['add']) {
          $fid = $field['id'];
          foreach (array('create','update') as $op) {
            if (array_key_exists($op,$val)) {
              $def = true;
              if (!is_null($val[$op]))            $def = false;
              if ($val[$op] == $field['default']) $def = true;
              if (!$def) {
                $ret['replace'][$op][$alias] = $val[$op];
              } else { $ret['delete'][$op][] = $fid; }
            }
          }
        } else {
          if (array_key_exists('update',$val))
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
      $op = xbData::operation($operation);
      if (!in_array($op,array('create','read','update'))) return false;
      $ret = array();
      foreach ($this->_fields as $alias => $field) {
        if ($field['access'][$operation]) {
          $ret[$alias] = $field;
          $ret[$alias]['value'] = isset($data[$alias]) ? $data[$alias] : null;
        }
      }
      return $ret;
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

    /* **************** БИБЛИОТЕЧНЫЕ ФУНКЦИИ **************** */
    /* CLASS:STATIC
      @name        : correct
      @description : Корректирование модели

      @param : $data | array | value | | Данные

      @return : string
    */
    public static function correct($data) {
      if (!is_array($data)) return false;
      if (!isset($data['table']) || !isset($data['fields'])) return false;
      if (!is_array($data['fields'])) return false;
      $ret = array(
        'table'   => $data['table'],
        'primary' => null,
        'fields'  => array(),
        'add'     => array(
          'table'   => '',
          'field'   => '',
          'ids'     => array(),
          'aliases' => array()
        ),
        'unique'  => null,
        'indexes' => null
      );
      // Поправка полей
      foreach ($data['fields'] as $alias => $field) {
        $ret['fields'][$alias] = xbDataFields::correct($field,$alias);
        if ($ret['fields'][$alias]['primary']) $ret['primary'] = $alias;
        // Получение доступа
        $IV = $ret['fields'][$alias]['access'];
        $ret['fields'][$alias]['access'] = array();
        foreach (array('create','read','update','delete') as $i => $k)
          $ret['fields'][$alias]['access'][$k] = (($IV & (1 << $i)) != 0);
        // Кэширование данных дополнительных полей
        $ret['fields'][$alias]['add'] = intval($ret['fields'][$alias]['id']) != 0;
        if (intval($ret['fields'][$alias]['add'])) {
          $fid = $ret['fields'][$alias]['id'];
          $ret['add']['ids'][$fid]       = $alias;
          $ret['add']['aliases'][$alias] = $fid;
        }
      }
      // Поправка уникальных ключей
      if (isset($data['unique'])) if (is_array($data['unique']))
        foreach ($data['unique'] as $alias => $field) {
          $v = is_array($field) ? $field : explode(',',$field);
          foreach ($v as $key) if (isset($ret['fields'][$key])) {
            if (!is_array($ret['unique'])) $ret['unique'] = array();
            $ret['unique'][$alias][] = $key;
          }
        }
      // Поправка индексов
      if (isset($data['indexes'])) {
        $v = is_array($data['indexes']) ? $data['indexes'] : explode(',',$data['indexes']);
        foreach ($v as $key) if (isset($ret['fields'][$key])) {
          if (!is_array($ret['indexes'])) $ret['indexes'] = array();
          $ret['indexes'][] = $key;
        }
      }
      // Проверяем таблицы
      $add = false;
      if (is_array($data['add'])) {
        if (isset($data['add']['field'])) {
          $t = $ret['table'];
          $ret['add']['field'] = $data['add']['field'];
          $ret['add']['table'] = isset($data['add']['table']) ? $data['add']['table'] : "$t"."_values";
          $add = true;
        }
      }
      if (!$add) $ret['add'] = null;
      return $ret;
    }

    /* CLASS:STATIC
      @name        : request
      @description : Получение данных из запросов

      @param : $prefix | string | value | | Префикс форм

      @return : array
    */
    public static function request($fields,$prefix='',$operation='create',$source=null) {
      // Статика
      static $freq = null;
      static $fhid = null;
      if (is_null($freq)) $freq = xbDataFields::flagName('required');
      if (is_null($fhid)) $fhid = xbDataFields::flagName('hidden');
      // Инициализация
      $ret = array(
        'values'    => array(),
        'notset'    => array(),
        'incorrect' => array(),
        'ignored'   => array()
      );
      $op = xbData::operation($operation);
      if (!in_array($op,array('create','read','update'))) return false;
      // Поля
      foreach ($fields as $alias => $field) {
        $got = null;
        if (!is_array($source)) {
          $pa = $prefix.$alias;
          if       (array_key_exists($pa,$_POST)) { $got = $_POST[$pa];
          } elseif (array_key_exists($pa,$_GET))  { $got = urldecode($_GET[$pa]); }
        } else {
          if (!array_key_exists($alias,$source)) continue;
          $got = $source[$alias];
        }
        if (!$field['access'][$op] || (($field['flags'] & $fhid) != 0)) continue;
        // Проверка обязательности
        $req = (($field['flags'] & $freq) != 0);
        if ($req) {
          if (is_null($got) || empty($got)) {
            $ret['notset'][] = $alias;
            continue;
          }
        }
        // Регулярки
        $cor = true;
        if (!is_null($field['regexp'])) if (preg_match($field['regexp'],$got)) {
          if (!is_null($field['replace'])) if (!empty($field['replace']))
            $got = preg_replace($field['regexp'],$field['replace'],$got);
        } else { $cor = false; }
        // Элементы
        if ($cor && is_array($field['elements'])) {
          if (!is_null($got)) {
            if (!array_key_exists($got,$field['elements'])) $cor = false;
          } else { $cor = false; }
        }
        // Итог проверок на корректность
        if (!$cor) {
          if ($req) {
            $ret['incorrect'][] = $alias;
          } else { $ret['ignored'][] = $alias; }
          continue;
        }
        // Обработка
        if (!is_null($field['strip'])) $got = preg_replace($field['strip'],'',$got);
        $got = xbData::pack($field['type'],$got);
        $ret['values'][$alias] = $got;
      }
      return $ret;
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>