<?php
  /* INFO
    @product     : xbData
    @component   : xbData
    @type        : сlibrary
    @description : Библиотека функций для работы с данными
    @revision    : 2015-12-20 16:13:00
  */

  if (!class_exists('xbDataHandlers')) require 'handlers.php';
  if (!class_exists('xbDataTypes'))    require 'types.php';

  /* LIBRARY ~BEGIN */
  class xbData {
    /* **************** ФУНКЦИИ ДЛЯ РАБОТЫ С ФЛАГАМИ ПОЛЕЙ **************** */
    /* LIBRARY:FUNCTION
      @name        : entityFlags
      @description : Флаги сущностей

      @param : $n | string | value | | Имя

      @return : int
    */
    public static function entityFlags($add=null) {
      $ret = array(
        30 => 'deleted',  // Удалённая сущность
        29 => 'admin',    // Редактируется только администратором
        28 => 'cp'        // Только в административной панели
      );
      if (is_array($add)) foreach ($add as $k => $a) $ret[$k] = $a;
      return $ret;
    }

    /* LIBRARY:FUNCTION
      @name        : flagName
      @description : Имя флага ресурса

      @param : $n | string | value | | Имя

      @return : int
    */
    public static function flagName($n) {
      $f = self::flags();
      if ($k = array_search($n,$f)) return (1 << $k);
      return 0;
    }

    /* LIBRARY:FUNCTION
      @name        : flag
      @description : Значение флага ресурса

      @param : $f | int    | value | | Флаги
      @param : $n | string | value | | Имя

      @return : bool
    */
    public static function flag($f,$n) { return ((intval($f) & self::flagName($n)) != 0); }

    /* LIBRARY:FUNCTION
      @name        : flags
      @description : Флаги

      @param : $get |      | value | @NULL  | Флаги
      @param : $int | bool | value | @FALSE | Вернуть целочисленное

      @return : array / int
    */
    public static function flags($get=null,$int=false) {
      static $_flags = null;
      if (is_null($_flags)) $_flags = self::entityFlags();
      return self::flagsValue($_flags,$get,$int);
    }

    /* LIBRARY:FUNCTION
      @name        : flagsValue
      @description : Флаги

      @param : $flags |      | value |        | Флаги
      @param : $get   |      | value | @NULL  | Флаги
      @param : $int   | bool | value | @FALSE | Вернуть целочисленное

      @return : array / int
    */
    public static function flagsValue($flags,$get=null,$int=false) {
      if (is_null($get)) return $int ? 0x7fffffff : $flags;
      $ha = array();
      if (is_string($get) || is_array($get)) {
        $ha = $int ? 0 : array();
        $_  = is_array($get) ? $get : explode(',',$get);
        foreach ($_ as $k)
          if ($i = array_search($k,$flags))
            if ($int) { $ha |= (1 << $i); } else { $ha[] = $k; }
      } else {
        $G = intval($get);
        if ($int) return ($G & 0x7fffffff);
        for ($c = 0; $c < 30; $c++)
          if (($G & (1 << $c)) != 0)
            if (isset($flags[$c])) $ha[] = $flags[$c];
      }
      return $ha;
    }

    /* **************** ОБЩИЕ ФУНКЦИИ **************** */
    /* LIBRARY:FUNCTION
      @name        : operation
      @description : Получение унифицированного имени операции

      @param : $o | string | value | | Входное значение

      @return : string
    */
    public static function operation($o) {
      if (!in_array($o,array(
        'create','replace','insert',
        'update',
        'read','select',
        'delete'
      ))) return false;
      switch ($o) {
        case 'create': case 'replace': case 'insert': return 'create';
        case 'read'  : case 'select' : return 'read';
        default: return $o;
      }
    }

    /* LIBRARY:FUNCTION
      @name        : SQLOperation
      @description : Получение унифицированного имени операции

      @param : $o | string | value | | Входное значение

      @return : string
    */
    public static function SQLOperation($o) {
      if (in_array($o,array('table','clear','replace'))) return $o;
      $O = self::operation($o);
      if (!$O) return false;
      switch ($O) {
        case 'read'  : return 'select';
        case 'create': return 'insert';
      }
      return $O;
    }

    /* LIBRARY:FUNCTION
      @name        : pack
      @description : Упаковка значения

      @param : $type  | string | value | | Тип
      @param : $value |        | value | | Значение

      @return : ?
    */
    public static function pack($type,$value) {
      $h = xbDataHandlers::handlers($type);
      $v = $value;
      foreach ($h['system'] as $handler) $v = xbDataHandlers::execute($handler,$v,'pack');
      foreach ($h['user']   as $handler) $v = xbDataHandlers::execute($handler,$v,'pack');
      return xbDataTypes::value($type,$v,'pack');
    }

    /* LIBRARY:FUNCTION
      @name        : unpack
      @description : Распаковка значения

      @param : $type  | string | value | | Тип
      @param : $value |        | value | | Значение

      @return : ?
    */
    public static function unpack($type,$value) {
      $h = xbDataHandlers::handlers($type);
      $v = xbDataTypes::value($type,$value,'unpack');
      foreach ($h['user']   as $handler) $v = xbDataHandlers::execute($handler,$v,'unpack');
      foreach ($h['system'] as $handler) $v = xbDataHandlers::execute($handler,$v,'unpack');
      return $v;
    }
  }
  /* LIBRARY ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>