<?php
  /* INFO
    @product     : xbParser
    @component   : xbParserPrototype
    @type        : class
    @description : Базовый класс парсера
    @revision    : 2015-12-27 19:30:00
  */

  /* CLASS ~BEGIN @string : Обработанные данные */
  /**
   * Class xbParserPrototype
   * @property-read object $owner
   * @property      array  $paths
   * @property      array  $data
   * @property      array  $settings
   * @property      string $language
   * @property      array  $dictionary
   * @property-read int    $maxLevel
   * @property-read int    $level
   * @property-read array  $debug
   * @property      string $template
   * @property      string $prefix
   */
  class xbParserPrototype {
    protected $_owner      = null;
    protected $_paths      = array();
    protected $_data       = array();
    protected $_idata      = null;
    protected $_settings   = array();
    protected $_language   = null;
    protected $_dictionary = null;

    protected $_maxLevel = 32;
    protected $_level    = null;
    protected $_debug    = array();
    protected $_template = '';
    protected $_prefix   = 'parser';

    protected $_templateExtension = 'html';

    /* CLASS:CONSTRUCT */
    function __construct($owner=null) { $this->_owner = $owner; }

    /* CLASS:GET */
    function __get($n) {
      if (method_exists($this,"get_$n"))      { $f = "get_$n"; return $this->$f();
      } elseif (property_exists($this,"_$n")) { $f = "_$n";    return $this->$f; }
      return false;
    }

    /* CLASS:SET */
    function __set($n,$v) {
      switch ($n) {
        case 'data'    : $this->_data     = xbParser::megreData($this->_data,$v);     return $this->_data;
        case 'settings': $this->_settings = xbParser::megreData($this->_settings,$v); return $this->_settings;
        case 'language': if (!empty($v)) $this->_language = strval($v); return $this->_language;
        case 'prefix'  : if (!empty($v)) $this->_prefix = strval($v);   return $this->_prefix;
        case 'maxLevel': $this->_maxLevel = intval($v); return $this->_maxLevel;
        case 'dictionary':
          if (!is_array($v)) return false;
          $this->_dictionary = $v;
          return $this->_dictionary;
        case 'paths':
          if (!is_array($v)) return false;
          $this->_paths = xbParser::paths($v);
          return $this->_paths;
      }
      if (method_exists($this,"set_$n")) { $f = "set_$n"; return $this->$f($v); }
      return false;
    }

    /* CLASS:STRING */
    function __toString() { return $this->parse(); }

    /******** ОБЩИЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:METHOD
      @name        : setting
      @description : Чтение, запись "настройки" шаблонизатора

      @param : $key   | string | value |       | Ключ
      @param : $value |        | value | @NULL | Значение

      @return : ?
    */
    public function setting($key,$value=null) {
      if (!is_null($value)) {
        xbParser::setByKey($this->_settings,$key,$value);
        return $value;
      } else { return xbParser::getByKey($this->_settings,$key); }
    }

    /* CLASS:METHOD
      @name        : setting
      @description : Чтение, запись "настройки" шаблонизатора

      @param : $key   | string | value |       | Ключ
      @param : $value |        | value | @NULL | Значение

      @return : ?
    */
    public function variable($key,$value=null) {
      if (!is_null($value)) {
        if (is_array($this->_idata)) {
          xbParser::setByKey($this->_idata,$key,$value);
        } else { xbParser::setByKey($this->_data,$key,$value); }
        return $value;
      } else {
        if (is_array($this->_idata)) {
          return xbParser::getByKey($this->_idata,$key);
        } else { return xbParser::getByKey($this->_data,$key); }
      }
    }

    /* CLASS:METHOD
      @name        : word
      @description : Слово из словаря

      @param : $key | string | value | | Ключ

      @return : ?
    */
    public function word($key) {
      $P = 'caption';
      $K = xbParser::languageKey($key,$P);
      if (isset($this->_dictionary[$K][$P])) return $this->_dictionary[$K][$P];
      return ucfirst(str_replace(array('.','_','-'),' ',$key));
    }

    /* CLASS:METHOD
      @name        : parse
      @description : Обработка

      @param : $d   | string | value | @EMPTY | Входные данные
      @param : $elt | string | value | @EMPTY | Тип элемента
      @param : $key | string | value | @EMPTY | Ключ элемента

      @param : string
    */
    public function parse($d='',$data=null,$elt='',$key='') {
      static $_levels = null;
      static $_stime  = null;
      // Инициализация
      $p1 =  is_null($_levels) ? 2 : 1;
      $O  = (is_null($_levels) && empty($d)) ? $this->template : strval($d);
      if (empty($O)) return $O;
      if (is_null($_levels)) $_levels = array();
      if (is_null($_stime))  $_stime  = microtime(true);
      $this->_debug['time'] = 0;
      // Каркас обработки
      if (is_array($data)) $this->_idata = $data;
      for ($c1 = 0; $c1 < $p1; $c1++) {
        $this->_level++;
        if ($this->_level <= $this->_maxLevel) {
          $_levels[$this->_level] = array('element' => $elt,'key' => $key);
          $this->_debug['levels'] = $_levels;
          $O = $this->parseMain($O);
        } else { $O = $this->parseSanitize($O); }
        $this->_level--;
      }
      if (is_array($data)) $this->_idata = null;
      // Финализация
      if ($this->_level == -1) {
        $this->_debug['time'] = microtime(true) - $_stime;
        $O = $this->parseSanitize($O);
        $O = $this->parseBenchmark($O);
        $_levels = null;
        $_stime  = null;
      }
      return $O;
    }

    /* CLASS:METHOD
      @name        : search
      @description : Поиск элемента

      @param : $type | string | value | | Тип элемента
      @param : $name | string | value | | Имя элемента

      @param : string
    */
    public function search($type,$name) {
      $ext = $this->_templateExtension;
      $DS  = DIRECTORY_SEPARATOR;

      if (!in_array($type,array('template','chunk','snippet'))) return false;
      $sdir = $type.'s';
      if ($type == 'snippet') $ext = 'php';

      $dname = explode('.',$name);
      $ename = $dname[count($dname)-1];
      unset($dname[count($dname)-1]);
      $dname = count($dname) > 0 ? implode($DS,$dname).$DS : '';
      $found = '';

      $paths = $this->paths;
      $lang  = $this->language;

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

    /******** ПЕРЕОПРЕДЕЛЯЕМЫЕ МЕТОДЫ КЛАССА ********/
    /* Главный алгоритм */
    public function parseMain($v)     { return $v; }

    /* Алгоритм санитизации */
    public function parseSanitize($v) { return $v; }

    /* Алгоритм санитизации */
    public function parseBenchmark($v) { return $v; }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>