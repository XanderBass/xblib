<?php
  /* INFO
    @product     : xbParser
    @component   : xbParser
    @type        : clibrary
    @description : Библиотека общих функций
    @revision    : 2015-12-27 19:27:00
  */

  /* LIBRARY ~BEGIN */
  class xbParser {
    public static function create($owner=null,$v=null,$prefix='project_') {
      static $sup = null;
      if (is_null($sup)) $sup = array('QuadBraces');
      $type = $sup[0];
      $C = strtoupper($prefix."config_parser");
      if (defined($C))           $type = constant($C);
      if (!in_array($type,$sup)) $type = $sup[0];
      if (in_array($v,$sup))     $type = $v;
      $CN = 'xbParser'.$type;
      return new $CN($owner);
    }

    /******** ФУНКЦИИ ДЛЯ РАБОТЫ С СОСТАВНЫМИ КЛЮЧАМИ ********/
    /* LIBRARY:FUNCTION
      @name        : getByKey
      @description : Получить значение по ключу

      @param : $input | array | value | | Входное значение
      @param : $key   | array | value | | Ключ

      @return : ?
    */
    public static function getByKey(array $input,$key) {
      $P = explode('.',$key);
      $V = $input;
      foreach ($P as $i) {
        if (!isset($V[$i])) return '';
        $V = $V[$i];
      }
      return $V;
    }

    /* LIBRARY:FUNCTION
      @name        : setByKey
      @description : Установить значение по ключу

      @param : $input | array | value | | Входное значение
      @param : $key   | array | value | | Ключ
      @param : $value |       | value | | Значение

      @return : ?
    */
    public static function setByKey(array &$input,$key,$value) {
      $P = explode('.',$key);
      if (count($P) < 1) return false;
      $tmp = &$input;
      foreach ($P as $K) {
        if (!isset($tmp[$K]) || !is_array($tmp[$K])) $tmp[$K] = array();
        $tmp = &$tmp[$K];
      }
      $tmp = $value;
      unset($tmp);
      return $input;
    }

    /* LIBRARY:FUNCTION
      @name        : keys
      @description : Разделение ключей

      @param : $key | string | value | | Исходный ключ

      @return : array
    */
    public static function keys($key) {
      $pkey = explode('.',$key);
      $item = intval($pkey[count($pkey)-1]);
      unset($pkey[count($pkey)-1]);
      $pkey = implode('.',$pkey);
      return array('key' => $pkey,'item' => $item);
    }

    /******** ФУНКЦИИ ДЛЯ РАБОТЫ С ДАННЫМИ ********/
    /* LIBRARY:FUNCTION
      @name        : mergeData
      @description : Слить данные

      @param : $input | array | value | | Входной массив
      @param : $value | array | value | | Значение

      @return : array
    */
    public static function megreData($input,$value) {
      if (!is_array($value) || !is_array($input)) return $input;
      if (empty($input)) return $value;
      $ret = $input;
      foreach ($value as $k => $v) $ret[$k] = $v;
      return $ret;
    }

    /******** ФУНКЦИИ ДЛЯ РАБОТЫ С ФАЙЛОВОЙ СИСТЕМОЙ ********/
    /* LIBRARY:FUNCTION
      @name        : path
      @description : Корректировать путь

      @param : $path | string | value | | Путь

      @return : string
    */
    public static function path($path) {
      $_ = DIRECTORY_SEPARATOR;
      $T = ($_ == '/' ? '/' : '').$_;
      $T = rtrim($path,$T);
      return implode($_,explode('/',$T)).$_;
    }

    /* LIBRARY:FUNCTION
      @name        : paths
      @description : Корректировать пути

      @param : $path | string | value | | Путь

      @return : string
    */
    public static function paths($v) {
      $ret = array();
      $_   = is_array($v) ? $v : explode(',',$v);
      foreach ($_ as $path) {
        $vv = self::path($path);
        if (is_dir($vv)) if (!in_array($vv,$ret)) $ret[] = $vv;
      }
      return $ret;
    }

    /******** ОБЩИЕ ФУНКЦИИ ********/
    /* LIBRARY:FUNCTION
      @name        : bool
      @description : Булево значение

      @param : $v | | value | | входное значение

      @return : bool
    */
    public static function bool($v) {
      return ((strval($v) === 'true') || ($v === true) || (intval($v) > 0));
    }

    /* LIBRARY:FUNCTION
      @name        : condition
      @description : Условие

      @param : $cond | string | value | | Условие
      @param : $v1   |        | value | | Первая переменная
      @param : $v2   |        | value | | Вторая переменная

      @return : bool
    */
    public static function condition($cond,$v1,$v2=null) {
      $vt = ''.trim($v1);
      $vi = intval($vt);
      switch ($cond) {
        case 'is'      :
        case 'eq'      : return ($v1 == $v2);
        case 'isnot'   :
        case 'neq'     : return ($v1 != $v2);
        case 'lt'      : return ($v1 <  $v2);
        case 'lte'     : return ($v1 <= $v2);
        case 'gt'      : return ($v1 >  $v2);
        case 'gte'     : return ($v1 >= $v2);
        case 'even'    : return (($vi % 2) == 0);
        case 'odd'     : return (($vi % 2) != 0);
        case 'empty'   : return  empty($vt);
        case 'notempty': return !empty($vt);
        case 'null'    :
        case 'isnull'  : return  is_null($vt);
        case 'notnull' : return !is_null($vt);
      }
      return false;
    }

    /* LIBRARY:FUNCTION
      @name        : isLogic
      @description : Признак условия

      @param : $cond | string | value | | Условие

      @return : bool
    */
    public static function isLogic($cond) {
      return in_array($cond,array(
        'is','eq','isnot','neq','lt','lte','gt','gte',
        'even','odd','notempty','empty','null','isnull','notnull'
      ));
    }

    /* LIBRARY:FUNCTION
      @name        : isLogicFunction
      @description : Признак условия

      @param : $cond | string | value | | Условие

      @return : bool
    */
    public static function isLogicFunction($cond) {
      return in_array($cond,array('even','odd','notempty','empty','null','isnull','notnull'));
    }

    /******** ФУНКЦИИ ДЛЯ РАБОТЫ С ТЕКСТОМ ********/
    /* LIBRARY:FUNCTION
      @name        : placeholders
      @description : Простая замена

      @param : $data  | array  | value |        | данные
      @param : $value | string | value | @EMPTY | Шаблон

      @return : string
    */
    public static function placeholders($data,$value='') {
      $O = $value;
      foreach ($data as $key => $val) $O = str_replace("[+$key+]",$val,$O);
      return $O;
    }

    /* CLASS:STATIC
      @name        : autoLinks
      @description : Автоматическое преобразование ссылок

      @param : $data  | string | value | | Исходное значение
      @param : $value | string | value | | Атрибуты ссылок

      @return : string
    */
    public static function autoLinks($data,$value) {
      $rexURL    = '#^(http|https)\:\/\/([\w\-\.]+)\.([a-zA-Z0-9]{2,16})\/(\S*)$#si';
      $rexMailTo = '#^mailto\:([\w\.\-]+)\@([a-zA-Z0-9\.\-]+)\.([a-zA-Z0-9]{2,16})$#si';
      $RET = $data;
      foreach (array(
                 array($rexURL,'<a href="\1://\2.\3/\4"[+C+]>\1://\2.\3/\4</a>'),
                 array($rexMailTo,'<a href="mailto:\1@\2.\3">\1@\2.\3</a>',)
               ) as $item)
        $RET = preg_replace($item[0],$item[1],$RET);
      // [+C+] - атрибуты ссылок
      return str_replace('[+C+]',(empty($value) ? '' : " $value"),$RET);
    }

    /* CLASS:STATIC
      @name        : autoList
      @description : Списки

      @param : $value | string | value |        | Атрибуты ссылок
      @param : $row   | string | value | @NULL  | Шаблон ряда
      @param : $ord   | bool   | value | @FALSE | Порядковый список

      @return : string
    */
    public static function autoList($value,$row=null,$ord=false) {
      $tpl   = empty($row) ? '<li[+classes+]>[+item+]</li>' : $row;
      $items = preg_split('~\\r\\n?|\\n~',$value);
      $type  = $ord ? 'ol' : 'ul';
      $RET   = "<$type>";
      for ($c = 0; $c < count($items); $c++) if (!empty($items[$c])) {
        $CL = '';
        if ($c == 0) $CL = ' classes="first"';
        if ($c == (count($items) - 1)) $CL = ' classes="last"';
        $IC  = explode('|',$items[$c]);
        $_ = str_replace(array('[+classes+]','[+item+]'),array($CL,$items[$c]),$tpl);
        for ($ic = 0; $ic < count($IC); $ic++)
          $_ = str_replace("[+item.$ic+]",$IC[$ic],$_);
        $RET.= $_;
      }
      return "$RET</$type>";
    }

    /******** ФУНКЦИИ ДЛЯ РАБОТЫ С ЛОКАЛИЗАЦИЕЙ ********/
    /* LIBRARY:FUNCTION
      @name        : languageKey
      @description : Ключ языкового плейсхолдера

      @param : $key | string | value | | Ключ
      @param : $p   | string | link  | | Полученное свойство

      @return : string
    */
    public static function languageKey($key,&$p) {
      static $_sup = null;
      if (is_null($_sup)) $_sup = array('caption','hint');
      $_ = explode('.',$key);
      $l = count($_) - 1;
      $p = 'caption';
      if (($l > 0)) if (in_array($_[$l],$_sup)) {
        $p = $_[$l];
        unset($_[$l]);
      }
      $k = implode('.',$_);
      return $k;
    }

    /* LIBRARY:FUNCTION
      @name        : loadLanguage
      @description : Сканирование языковой папки и получения слооварных данных

      @param : $lang | string | value | | Язык

      @return : array
    */
    public static function loadLanguage($lang=null,$paths=null) {
      static $_sup    = null;
      static $_pcache = null;
      if (is_null($_sup))   $_sup = array('caption','hint');
      if (!is_null($paths)) $_pcache = self::paths($paths);
      if (empty($_pcache))  $_pcache = null;
      if (is_null($_pcache)) return false;
      if (is_null($lang))    return true;

      $retval = array();
      $files  = array();

      foreach ($_pcache as $path)
        if ($_ = glob($path.$lang.DIRECTORY_SEPARATOR.'*.lng'))
          foreach ($_ as $_i) $files[] = $_i;
      foreach ($files as $f) {
        $data = file($f);
        foreach ($data as $s) {
          $str = trim($s);
          if (!empty($str)) {
            $a = explode('|',$str);
            $k = trim(array_shift($a));
            foreach ($_sup as $p => $e) {
              $d = isset($a[$p]) ? trim($a[$p]) : '';
              if (($d == '') && isset($retval[$k][$e])) $d = $retval[$k][$e];
              $retval[$k][$e] = $d;
            }
          }
        }
      }
      return $retval;
    }
  }
  /* LIBRARY ~END */

  /* INFO @copyright: xbLab 2015 */
?>