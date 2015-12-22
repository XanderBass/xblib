<?php
  /* INFO
    @product     : xbData
    @component   : xbDataModels
    @type        : сlibrary
    @description : Библиотека функций для работы моделями
    @revision    : 2015-12-22 13:25:00
  */

  if (!class_exists('xbDataFields')) require 'fields.php';

  /* LIBRARY ~BEGIN */
  class xbDataModels {
    /* LIBRARY:FUNCTION
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
        )
      );
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

    /* LIBRARY:FUNCTION
      @name        : form
      @description : Получение полей формы

      @param : $fields    |        | value |        | Поля модели
      @param : $data      | array  | value | @NULL  | Имеющиеся данные
      @param : $operation | string | value | create | Операция

      @return : array
    */
    public static function form($fields,$data,$operation) {
      $op = xbData::operation($operation);
      if (!in_array($op,array('create','read','update'))) return false;
      $ret = array();
      foreach ($fields as $alias => $field) {
        if ($field['access'][$operation]) {
          $ret[$alias] = $field;
          $ret[$alias]['value'] = isset($data[$alias]) ? $data[$alias] : null;
        }
      }
      return $ret;
    }

    /* LIBRARY:FUNCTION
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

    /* LIBRARY:FUNCTION
      @name        : fill
      @description : Заполнение системных полей

      @param : $v | | value | | Список алиасов

      @return : array
    */
    public static function fill($v) {
      $ret   = array();
      $names = is_array($v) ? $v : explode(',',strval($v));
      foreach ($names as $name) $ret[$name] = array(
        'caption' => ucfirst($name),
        'access'  => 0x22222222
      );
      return $ret;
    }
  }
  /* LIBRARY ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>