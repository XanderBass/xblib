<?php
  /* INFO
    @product     : xbData
    @component   : xbDataTypes
    @type        : сlibrary
    @description : Библиотека типов
    @revision    : 2015-12-22 13:20:00
  */

  // Primary types
  define("XBDATA_TYPE_BOOL",0);
  define("XBDATA_TYPE_INTEGER",1);
  define("XBDATA_TYPE_STRING",2);
  define("XBDATA_TYPE_FLOAT",3);
  define("XBDATA_TYPE_SET",4);
  define("XBDATA_TYPE_STRUCTURE",5);
  define("XBDATA_TYPE_DATETIME",6);
  define("XBDATA_TYPE_VARIABLE",7);
  define("XBDATA_TYPE_NOTNULL",8);
  // Inputs
  define("XBDATA_INPUT_MULTIPLE",0x80);
  define("XBDATA_INPUT_NAMED",0x40);
  define("XBDATA_INPUT_SELECT",0x10);
  define("XBDATA_INPUT_RADIO",0x20);
  define("XBDATA_INPUT_SLIDE",0x30);
  define("XBDATA_INPUT_CLOCK",0x10);
  define("XBDATA_INPUT_TIME",0x20);
  define("XBDATA_INPUT_DATE",0x30);
  define("XBDATA_INPUT_TAGS",XBDATA_INPUT_SLIDE);
  define("XBDATA_INPUT_RANGE",XBDATA_INPUT_SLIDE);
  define("XBDATA_INPUT_COLOR",XBDATA_INPUT_NAMED + 0x10);
  define("XBDATA_INPUT_PASSWORD",XBDATA_INPUT_NAMED);
  define("XBDATA_INPUT_RTF",XBDATA_INPUT_NAMED + 0x20);
  define("XBDATA_INPUT_HTML",XBDATA_INPUT_NAMED + 0x30);
  define("XBDATA_INPUT_FILE",XBDATA_INPUT_NAMED + 0x10);
  define("XBDATA_INPUT_IMAGE",XBDATA_INPUT_NAMED + 0x20);
  define("XBDATA_INPUT_TABLE",XBDATA_INPUT_NAMED + 0x30);
  define("XBDATA_INPUT_DWEEK",XBDATA_INPUT_NAMED + 0x10);
  define("XBDATA_INPUT_MONTH",XBDATA_INPUT_NAMED + 0x20);
  define("XBDATA_INPUT_OPTIONS",XBDATA_INPUT_RADIO + XBDATA_INPUT_MULTIPLE);
  define("XBDATA_INPUT_TEXT",XBDATA_INPUT_MULTIPLE);
  define("XBDATA_INPUT_KEY",XBDATA_INPUT_PASSWORD + XBDATA_INPUT_MULTIPLE);
  define("XBDATA_INPUT_CODE",XBDATA_INPUT_COLOR + XBDATA_INPUT_MULTIPLE);
  define("XBDATA_INPUT_FLAGS",XBDATA_INPUT_OPTIONS);
  // Combined types
  define("XBDATA_TYPE_ONOFF",XBDATA_TYPE_BOOL + XBDATA_INPUT_SLIDE);
  define("XBDATA_TYPE_CLOCK",XBDATA_TYPE_DATETIME + XBDATA_INPUT_CLOCK);
  define("XBDATA_TYPE_TIME",XBDATA_TYPE_DATETIME + XBDATA_INPUT_TIME);
  define("XBDATA_TYPE_DATE",XBDATA_TYPE_DATETIME + XBDATA_INPUT_DATE);
  define("XBDATA_TYPE_TAGS",XBDATA_TYPE_STRING + XBDATA_INPUT_TAGS);
  define("XBDATA_TYPE_RANGE",XBDATA_TYPE_SET + XBDATA_INPUT_RANGE);
  define("XBDATA_TYPE_FLAGS",XBDATA_TYPE_INTEGER + XBDATA_INPUT_FLAGS);
  define("XBDATA_TYPE_COLOR",XBDATA_TYPE_INTEGER + XBDATA_INPUT_COLOR);
  define("XBDATA_TYPE_PASSWORD",XBDATA_TYPE_STRING + XBDATA_INPUT_PASSWORD);
  define("XBDATA_TYPE_RTF",XBDATA_TYPE_STRING + XBDATA_INPUT_RTF);
  define("XBDATA_TYPE_HTML",XBDATA_TYPE_STRING + XBDATA_INPUT_RTF);
  define("XBDATA_TYPE_FILE",XBDATA_TYPE_STRUCTURE + XBDATA_INPUT_FILE);
  define("XBDATA_TYPE_IMAGE",XBDATA_TYPE_STRUCTURE + XBDATA_INPUT_IMAGE);
  define("XBDATA_TYPE_TABLE",XBDATA_TYPE_STRUCTURE + XBDATA_INPUT_TABLE);
  define("XBDATA_TYPE_TEXT",XBDATA_TYPE_STRING + XBDATA_INPUT_MULTIPLE);
  define("XBDATA_TYPE_KEY",XBDATA_TYPE_STRING + XBDATA_INPUT_KEY);
  define("XBDATA_TYPE_CODE",XBDATA_TYPE_STRING + XBDATA_INPUT_CODE);
  // Flags
  define("XBDATA_TYPE_PRIMARY",0x40000000);
  define("XBDATA_TYPE_AUTOINCREMENT",0x20000000);
  define("XBDATA_TYPE_UNSIGNED",0x10000000);
  define("XBDATA_TYPE_BINARY",XBDATA_TYPE_UNSIGNED);

  /* LIBRARY ~BEGIN */
  class xbDataTypes {
    /* LIBRARY:FUNCTION
      @name        : primary
      @description : Упаковка значения

      @param : $type   | int    | value |      | Тип
      @param : $value  |        | value |      | Значение
      @param : $action | string | value | pack | Действие

      @return : ?
    */
    public static function value($type,$value,$action='pack') {
      $t =   $type & XBDATA_TYPE_VARIABLE;
      $n = (($type & XBDATA_TYPE_NOTNULL) == 0);
      $u =  ($action == 'unpack');
      if (is_null($value) && $n) return null;
      switch ($t) {
        case XBDATA_TYPE_BOOL     : return self::bool($value);
        case XBDATA_TYPE_INTEGER  : return intval($value);
        case XBDATA_TYPE_STRING   : return strval($value);
        case XBDATA_TYPE_FLOAT    : return floatval($value);
        case XBDATA_TYPE_SET      : return $u ? unserialize($value) : serialize($value);
        case XBDATA_TYPE_STRUCTURE: return $u ? json_decode($value,true) : json_encode($value);
        case XBDATA_TYPE_DATETIME : return $u ? strval($value) : strval($value); // TODO: DT
        case XBDATA_TYPE_VARIABLE : return $u ? strval($value) : var_export($value,true);
      }
      return false;
    }

    /* LIBRARY:FUNCTION
      @name        : type
      @description : Тип данных

      @param : $value | int | value | | Значение

      @return : int
    */
    public static function type($value) {
      if (is_int($value)) return $value;
      $items = null;
      if (is_array($value))  $items = $value;
      if (is_string($value)) $items = explode(',',$value);
      if (is_null($items)) return 0;
      $ret = 0;
      foreach ($items as $item) {
        if (!is_int($item)) {
          foreach (array("TYPE","INPUT") as $p) {
            $C = "XBDATA_$p"."_".strtoupper($item);
            if (defined($C)) {
              $ret |= constant($C);
              continue 2;
            }
          }
        } else { $ret |= intval($item); }
      }
      return $ret;
    }

    /* LIBRARY:FUNCTION
      @name        : bool
      @description : Получение булева значения

      @param : $v | | value | | Value

      @return : boolean | Булево представление значения
    */
    public static function bool($v) { return ((strval($v)=='true')||($v===true)||(intval($v)>0)); }
  }
  /* LIBRARY ~END */

  define("XBDATA_TYPE_INTKEY",xbDataTypes::type('integer,notnull,autoincrement,primary'));

  /* INFO @copyright: Xander Bass, 2015 */
?>