<?php
  /* INFO
    @product     : xbData
    @component   : xbDataFields
    @type        : сlibrary
    @description : Библиотека функций для работы с полями данными
    @revision    : 2015-12-20 16:14:00
  */

  if (!class_exists('xbData')) require 'lib.php';

  /* LIBRARY ~BEGIN */
  class xbDataFields {
    /* **************** ВНУТРЕННИЕ ФУНКЦИИ **************** */
    protected static function _input_sr($a,$n,$m,$s='slider') {
      switch ($a) {
        case XBDATA_INPUT_SELECT: return $n ? '' : ($m ? 'list' : 'select');
        case XBDATA_INPUT_RADIO : return $n ? '' : ($m ? 'options' : 'radio');
        case XBDATA_INPUT_SLIDE : return $s;
      }
      return '';
    }

    /* **************** ФУНКЦИИ ДЛЯ РАБОТЫ С ФЛАГАМИ **************** */
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
      if (is_null($_flags)) $_flags = xbData::entityFlags(array(
         2 => 'hidden',  // Скрытое поле, не обрабатывается в запросах
         1 => 'repeat',  // Повторение поля
         0 => 'required' // Обязательное поле
      ));
      return xbData::flagsValue($_flags,$get,$int);
    }

    /* **************** ОСНОВНЫЕ ФУНКЦИИ **************** */
    /* CLASS:STATIC
      @name        : intLength
      @description : Длина

      @param : $type | string | value | | Тип
      @param : $v    | string | value | | Значение

      @return : int
    */
    public static function intLength($type,$v) {
      if (!is_null($v)) {
        if ($v < 4) {
          if ($type == XBDATA_TYPE_INTEGER) return $v;
        } elseif ($v > 4) { return 8; }
      }
      return 4;
    }

    /* CLASS:STATIC
      @name        : correct
      @description : Коррекция поля

      @param : $data | string | value |        | Данные
      @param : $name | string | value | @EMPTY | Ключ

      @return : array
    */
    public static function correct($data,$name='') {
      if (!is_array($data)) return false;
      $ret = $data;
      $ret['alias'] = $name;
      foreach (array(
                 'id','type','access',
                 'flags','length','default','elements','input',
                 'regexp','replace','strip','access'
               ) as $k) if (!isset($ret[$k])) $ret[$k] = null;
      // Коррекция типа
      $ret['type']    = is_null($ret['type']) ? 0 : xbDataTypes::type($ret['type']);
      $ret['default'] = xbDataTypes::value($ret['type'],$ret['default']);
      // Кеширование флагов типа
      $ret['primary']       = (($ret['type'] & XBDATA_TYPE_PRIMARY) != 0);
      $ret['null']          = (($ret['type'] & XBDATA_TYPE_NULLABLE) != 0);
      $ret['autoincrement'] = (($ret['type'] & XBDATA_TYPE_AUTOINCREMENT) != 0);
      $ret['unsigned']      = (($ret['type'] & XBDATA_TYPE_UNSIGNED) != 0);
      if ($ret['primary']) $ret['null'] = false;
      // Остальные параметры
      if (!is_int($ret['flags']))   $ret['flags']  = self::flags($ret['flags']);
      if (!is_null($ret['length'])) $ret['length'] = abs(intval($ret['length']));
      if (!is_null($ret['elements'])) {
        if (is_string($ret['elements'])) {
          $q = (strpos($ret['elements'],'@: ') === 0);
          $ret['elements'] = $q ? substr($ret['elements'],3) : unserialize($ret['elements']);
        }
      }
      if (is_null($ret['input'])) $ret['input'] = self::input($ret['type'],is_null($ret['elements']));
      // Дополнительные параметры
      $t =   $ret['type'] & XBDATA_TYPE_VARIABLE;
//      $m = (($ret['type'] & XBDATA_INPUT_MULTIPLE) != 0);
      switch ($t) {
        case XBDATA_TYPE_FLOAT:
        case XBDATA_TYPE_INTEGER:
          foreach (array('min','max') as $dk) {
            if (!isset($ret[$dk])) $ret[$dk] = null;
            if (!is_null($ret[$dk])) {
              $ret[$dk] = ($t == XBDATA_TYPE_FLOAT) ? floatval($ret[$dk]) : intval($ret[$dk]);
              if ($ret['unsigned'] && ($ret[$dk] <= 0)) $ret[$dk] = null;
            }
          }
          if ($ret['min'] > $ret['max']) list($ret['min'],$ret['max']) = array($ret['max'],$ret['min']);
          $l = self::intLength($t,$ret['length']);
          $max = (pow(256,$l) / ($ret['unsigned'] ? 2 : 1)) - 1;
          $min = 0 - ($ret['unsigned'] ? 0 : ($max + 1));
          if ($ret['min'] <= $min) $ret['min'] = null;
          if ($ret['max'] >= $max) $ret['max'] = null;
          break;
        case XBDATA_TYPE_STRING:
          break;
      }
      return $ret;
    }

    /* CLASS:STATIC
      @name        : sqlType
      @description : Тип поля SQL

      @param : $field | array | value | | Поле

      @return : string
    */
    public static function sqlType($field) {
      $t =   $field['type'] & XBDATA_TYPE_VARIABLE;
      $u = (($field['type'] & XBDATA_TYPE_UNSIGNED) != 0);
      $n = (($field['type'] & XBDATA_TYPE_NULLABLE) != 0);
      $m = (($field['type'] & XBDATA_INPUT_MULTIPLE) != 0);
      $D = is_null($field['default']) ? '' : " default '".$field['default']."'";
      switch ($t) {
        // Boolean
        case XBDATA_TYPE_BOOL: return 'char(0) null';
        case XBDATA_TYPE_SET:
        case XBDATA_TYPE_STRUCTURE: return 'text'.($n ? '' : ' not').' null';
        // Integer
        case XBDATA_TYPE_INTEGER:
          $l = self::intLength($t,$field['length']);
          switch ($l) {
            case  1: $R = 'tinyint'; break;
            case  2: $R = 'smallint'; break;
            case  3: $R = 'mediumint'; break;
            case  8: $R = 'bigint'; break;
            default: $R = 'int'; break;
          }
          if ($u) $R.= ' unsigned';
          $R.= ($n ? '' : ' not').' null';
          if (!$n) $R.= (($field['type'] & XBDATA_TYPE_AUTOINCREMENT) != 0) ? ' auto_increment' : $D;
          return $R;
        // String
        case XBDATA_TYPE_STRING:
          if (is_null($field['length'])) {
            $R = $m ? ($u ? 'blob' : 'text') : 'varchar(255)'.($u ? ' binary' : '');
            $R.= ($n ? '' : ' not').' null';
            return (!$m && !$n) ? $R.$D : $R;
          } elseif ($field['length'] < 256) {
            $R = $m ? 'tiny'.($u ? 'blob' : 'text') : 'varchar('.$field['length'].')'.($u ? ' binary' : '');
            $R.= ($n ? '' : ' not').' null';
            return (!$m && !$n) ? $R.$D : $R;
          } else {
            if ($field['length'] >= 16777216)    { $R = 'long';
            } elseif ($field['length'] >= 65536) { $R = 'medium';
            } else { $R = ''; }
            return $R.($u ? 'blob' : 'text').($n ? '' : ' not').' null';
          }
        // Float
        case XBDATA_TYPE_FLOAT:
          $l = self::intLength($t,$field['length']);
          $R = $l == 8 ? 'real' : 'float'.($u ? ' unsigned' : '').($n ? '' : ' not').' null';
          return (!$n) ? $R.$D : $R;
        // DateTime
        case XBDATA_TYPE_DATETIME:
          switch ($field['type'] & 0x70) {
            case XBDATA_INPUT_DWEEK: $R = 'tinyint'; break;
            case XBDATA_INPUT_MONTH: $R = 'tinyint'; break;
            case XBDATA_INPUT_CLOCK:
            case XBDATA_INPUT_TIME : $R = 'time'; break;
            case XBDATA_INPUT_DATE : $R = 'date'; break;
            default: $R = 'datetime';
          }
          return $R.($n ? '' : ' not').' null';
      }
      return false;
    }

    /* LIBRARY:FUNCTION
      @name        : input
      @description : Элемент ввода

      @param : $type | int  | value | | Тип
      @param : $nopt | bool | value | | Контроль наличия опций

      @return : ?
    */
    public static function input($type,$nopt=false) {
      $t =   $type & XBDATA_TYPE_VARIABLE;
      $m = (($type & XBDATA_INPUT_MULTIPLE) != 0);
      $a =   $type & 0x70;
      switch ($t) {
        // Boolean
        case XBDATA_TYPE_BOOL:
          if ($r = self::_input_sr($a,false,false,'onoff')) return $r;
          return 'checkbox';
        // Integer
        case XBDATA_TYPE_INTEGER:
          if ($r = self::_input_sr($a,$nopt,$m)) return $r;
          switch ($a) {
            case XBDATA_INPUT_SELECT: if ($m) return 'list'; break;
            case XBDATA_INPUT_RADIO : if ($m) return 'flags'; break;
            case XBDATA_INPUT_COLOR : return 'color';
          }
          return 'number';
        // String
        case XBDATA_TYPE_STRING:
          if ($r = self::_input_sr($a,$nopt,$m,'tags')) return $r;
          switch ($a) {
            case XBDATA_INPUT_PASSWORD: return $m ? 'key' : 'password';
            case XBDATA_INPUT_COLOR   : return $m ? 'code' : 'color';
            case XBDATA_INPUT_RTF     : return 'rtf';
            case XBDATA_INPUT_HTML    : return 'html';
          }
          return $m ? 'text' : 'string';
        // Float, set
        case XBDATA_TYPE_FLOAT: if ($r = self::_input_sr($a,$nopt,false)) return $r; return 'float';
        case XBDATA_TYPE_SET: if ($r = self::_input_sr($a,$nopt,$m,'range')) return $r; return 'set';
        // JSON
        case XBDATA_TYPE_STRUCTURE:
          switch ($a) {
            case XBDATA_INPUT_FILE : return 'file'.($m ? 's' : '');
            case XBDATA_INPUT_IMAGE: return 'image'.($m ? 's' : '');
            case XBDATA_INPUT_TABLE: return 'table';
          }
          return 'tree';
        // DateTime
        case XBDATA_TYPE_DATETIME:
          switch ($a) {
            case XBDATA_INPUT_DWEEK: return 'dweek';
            case XBDATA_INPUT_MONTH: return 'month';
            case XBDATA_INPUT_CLOCK: return 'clock';
            case XBDATA_INPUT_TIME : return 'time';
            case XBDATA_INPUT_DATE : return 'date';
          }
          return 'datetime';
      }
      return false;
    }
  }
  /* LIBRARY ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>