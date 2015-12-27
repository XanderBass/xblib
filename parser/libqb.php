<?php
  /* INFO
    @product     : xbParser
    @component   : xbParserLibQB
    @type        : clibrary
    @description : Библиотека функций QuadBraces
    @revision    : 2015-12-27 19:26:00
  */

  if (!defined("XBQB_TAGS_DIR"))
    define("XBQB_TAGS_DIR",dirname(__FILE__).DIRECTORY_SEPARATOR.'quadbraces'.DIRECTORY_SEPARATOR);

  /* LIBRARY ~BEGIN */
  class xbParserLibQB {
    /******** ТЕГИ ********/
    /* LIBRARY:FUNCTION
      @name        : tags
      @description : Инициализация тегов

      @return : array
    */
    public static function tags() {
      static $_tags = null;
      if (is_null($_tags)) {
        $map = array(
          'cms'      => array('\[\:','\:\]'),
          'chunk'    => array('\{\{','\}\}'),
          'string'   => array('\{\(','\)\}'),
          'lib'      => array('\{\<','\>\}'),
          'constant' => array('\{\*','\*\}'),
          'setting'  => array('\[\(','\)\]'),
          'variable' => array('\[\*','\*\]'),
          'snippet'  => array('\[\!','\!\]'),
          'local'    => array('\[\+','\+\]'),
          'language' => array('\[\%','\%\]')
        );
        $_tags = array();
        foreach ($map as $k => $d) $_tags[$k] = self::regexp($d[0],$d[1]);
      }
      return $_tags;
    }

    /* LIBRARY:FUNCTION
      @name        : regexp
      @description : Регулярное выражение

      @param : $start  | string | value | | Начало
      @param : $finish | string | value | | Конец

      @return : string
    */
    public static function regexp($start,$finish) {
      return "#".$start.'([\w\.\-]+)'         // Alias
      . '((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)' // Extensions
      . '((:?\s*\&([\w\-\.]+)=`([^`]*)`)*)'   // Parameters
      . $finish.'#si';
    }

    /* LIBRARY:FUNCTION
      @name        : arguments
      @description : Аргументы

      @param : $v | string | value | | Исходная строка

      @return : array
    */
    public static function arguments($v) {
      $arguments = array();
      if (!empty($v))
        if ($_ = preg_match_all('|\&([\w\-\.]+)\=`([^`]*)`|si',$v,$ms,PREG_SET_ORDER))
          foreach ($ms as $pr) $arguments[$pr[1]] = $pr[2];
      return $arguments;
    }

    /* LIBRARY:FUNCTION
      @name        : sanitize
      @description : Очищение от тегов

      @param : $data | string | value | @EMPTY | Текстовые данные

      @return : string
    */
    public static function sanitize($data='') {
      $tags = self::tags();
      if (empty($data)) return '';
      $O = $data;
      foreach ($tags as $t) if (preg_match($t,$O)) $O = preg_replace($t,'',$O);
      return $O;
    }

    /******** ОБЩИЕ ФУНКЦИИ ********/
    /* LIBRARY:FUNCTION
      @name        : search
      @description : Поиск элемента

      @param : $type | string | value | | Тип элемента
      @param : $name | string | value | | Имя элемента

      @param : string
    */
    public static function search($paths,$type,$name,$lang='') {
      $ext = 'html';
      $DS  = DIRECTORY_SEPARATOR;

      switch ($type) {
        case 'template': $sdir = 'pages'; break;
        case 'chunk'   : $sdir = 'chunks'; break;
        case 'snippet' : $sdir = 'snippets'; $ext = 'php'; break;
        default: return false;
      }

      $dname = explode('.',$name);
      $ename = $dname[count($dname)-1];
      unset($dname[count($dname)-1]);
      $dname = count($dname) > 0 ? implode($DS,$dname).$DS : '';
      $found = '';

      foreach ($paths as $epath) {
        $D = $epath.$sdir.DIRECTORY_SEPARATOR.$dname;
        $fname = $D."$ename.$ext";
        if (is_file($fname)) $found = $fname;
        if ($lang != '') {
          $fname = $D.$lang.DIRECTORY_SEPARATOR."$ename.$ext";
          if (is_file($fname)) $found = $fname;
        }
      }

      return $found;
    }

    /* LIBRARY:FUNCTION
      @name        : extensions
      @description : Расширения

      @return : array
    */
    public static function extensions() {
      $ret = array();
      if ($f = glob(XBQB_TAGS_DIR."*.php")) foreach ($f as $e) {
        $key = basename($e,'.php');
        if ($key == 'prototype') continue;
        $CN = "xbParserQuadBraces".ucfirst($key);
        $ret[$CN] = $key;
      }
      return $ret;
    }

    /******** РАСШИРЕНИЯ ********/
    /* LIBRARY:FUNCTION
      @name        : link
      @description : Ссылки

      @param : $a     | string | value | | Тип
      @param : $value | string | value | | Значение
      @param : $add   | string | value | | Дополнительные данные

      @param : string
    */
    public static function link($a,$value,$add) {
      $tpls = array(
        'link'          => '<a href="[+content+]">[+value+]</a>',
        'link-external' => '<a href="[+content+]" target="_blank">[+value+]</a>'
      );
      if (isset($tpls[$a]) && !empty($add)) {
        $val = empty($v) ? $value : $add;
        return str_replace(array('[+content+]','[+value+]'),array($value,$val),$tpls[$a]);
      }
      return $value;
    }

    /* LIBRARY:FUNCTION
      @name        : jscss
      @description : Ссылки JS, CSS

      @param : $a     | string | value | | Тип
      @param : $value | string | value | | Значение

      @param : string
    */
    public static function jscss($a,$value) {
      $tpls = array(
        'js-link'  => '<script type="text/javascript" src="[+content]"></script>',
        'css-link' => '<link rel="stylesheet" type="text/css" href="[+content+]" />',
        'import'   => '@import url("[+content+]");'
      );
      if (isset($tpls[$a])) return str_replace('[+content+]',"$value",$tpls[$a]);
      return $value;
    }
  }
  /* LIBRARY ~END */

  /* INFO @copyright: xbLab 2015 */
?>