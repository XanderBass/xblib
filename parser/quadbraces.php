<?php
  /* INFO
    @product     : xbParser
    @component   : xbParserQuadBraces
    @type        : class
    @description : Класс парсера QuadBraces
    @revision    : 2015-12-27 19:30:00
  */

  /* CLASS ~BEGIN @string : Обработанные данные */
  /**
   * Class xbParserQuadBraces
   * @property-read array  $tags
   * @property-read array  $tagsExts
   * @property-read string $templateName
   * @property-read array  $chunks
   * @property-read array  $strings
   * @property      array  $arguments
   * @property      array  $notice
   */
  class xbParserQuadBraces extends xbParserPrototype {
    protected $_tags         = null;
    protected $_tagsExts     = null;
    protected $_templateName = '';
    protected $_chunks       = array(); // Чанки
    protected $_strings      = array(); // Чанки строки
    protected $_arguments    = array();
    protected $_notice       = array();

    /******** ОБЩИЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:CONSTRUCT */
    function __construct($owner=null) {
      parent::__construct($owner);
      $this->_tags = array();
      $tags = xbParserLibQB::extensions();
      foreach ($tags as $cn => $key) {
        if (!class_exists($cn)) if (is_file(XBQB_TAGS_DIR."$key.php")) require XBQB_TAGS_DIR."$key.php";
        if (class_exists($cn)) { /** @var xbParserQuadBracesPrototype $to */
          $to = new $cn($this);
          $this->_tags[$key]     = $to->regexp();
          $this->_tagsExts[$key] = $to;
        }
      }
      $tags = xbParserLibQB::tags();
      foreach ($tags as $cn => $key) $this->_tags[$cn] = $key;
      $this->notice = 'common';
    }

    /******** АКСЕССОРЫ КЛАССА ********/
    /* CLASS:PROPERTY
      @name        : template
      @description : Шаблон
      @type        : string
      @mode        : rw
    */
    protected function set_template($name) {
      $content = '[*content*]';
      $this->_templateName = '';
      if (!empty($name))
        if ($fn = $this->search('template',$name)) {
          $content = @file_get_contents($fn);
          $this->_templateName = $name;
        }
      $this->_template = $content;
      return $this->_template;
    }

    /* CLASS:PROPERTY
      @name        : notice
      @description : Режим уведомлений
      @type        : array
      @mode        : rw
    */
    protected function set_notice($v) {
      static $K = null;
      if (is_null($K)) $K = array_keys($this->_tags);
      if (!is_int($v) && !is_numeric($v)) {
        switch ($v) {
          case 'strict': $this->_notice = $K; break;
          case 'common':
            $this->_notice = array('datae','table','structure','chunk','string','lib','cms','snippet');
            break;
          default:
            $this->_notice = array();
            $TMP = is_array($v) ? $v : explode(',',$v);
            foreach ($TMP as $Ti) if (in_array($Ti,$K)) $this->_notice[] = $Ti;
        }
      }
      return $this->_notice;
    }

    /******** ВНУТРЕННИЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:INTERNAL
      @name        : _parse_chunk
      @description : Обработка чанка

      @param : $m | array  | value |       | Данные от регулярки
      @param : $t | string | value | chunk | Тип

      @return : string
    */
    protected function _parse_chunk($m,$t='chunk') {
      $key = $this->parseStart($m);
      $v   = $this->getChunk($key,$t);
      if ($v === false) {
        if (in_array($t,$this->_notice)) return "<!-- not found: $t/$key -->";
        $v = '';
      }
      return $this->parseFinish($m,$t,$key,$v);
    }

    /* CLASS:INTERNAL
      @name        : _parse_data
      @description : Обработка переменной

      @param : $m | array  | value |          | Данные от регулярки
      @param : $t | string | value | variable | Тип

      @return : string
    */
    protected function _parse_data($m,$type='variable') {
      $key = $this->parseStart($m);
      $val = $this->$type($key);
      if ($val === false) {
        if (in_array($type,$this->_notice)) return "<!-- not found: $type/$key -->";
        $val = '';
      }
      return $this->parseFinish($m,$type,$key,$val);
    }

    /******** КОМПОНЕНТНЫЕ МЕТОДЫ ********/
    /* CLASS:INTERNAL
      @name        : iteration
      @description : Итерация

      @param : $tpls | array  | value |        | Шаблоны
      @param : $O    | string | value | @EMPTY | Входные данные

      @return : string
    */
    public function iteration(array $tpls,$O='') {
      $tplI = "#\[\+([\w\-\.]+)\[([^\]]*)\]((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)\+\]#si";
      $H = false;
      foreach ($tpls as $tpl) {
        if (preg_match($tpl[0],$O)) {
          $O = preg_replace($tpl[0],'[+\1['.$tpl[1].']\2+]',$O);
          $H = true;
        }
      }
      if ($H) $O = preg_replace_callback($tplI,array($this,"parseInternal"),$O);
      return $O;
    }

    /* CLASS:INTERNAL
      @name        : parseInternal
      @description : Внутренняя обработка

      @param : $m | array | value | | Данные от регулярки

      @return : string
    */
    public function parseInternal(array $m) {
      $v = $m[2];
      if (isset($m[3])) $v = $this->extensions($v,$m[3],true);
      return $v;
    }

    /* CLASS:INTERNAL
      @name        : parseStart
      @description : Начало обработки

      @param : $m | array | value | | Данные от регулярки

      @return : string | ключ элемента
    */
    public function parseStart(array $m) {
      $this->_arguments[$this->_level] = isset($m[8]) ? xbParserLibQB::arguments($m[8]) : array();
      return $m[1];
    }

    /* CLASS:INTERNAL
      @name        : parseFinish
      @description : Конец обработки

      @param : $m | array | value | | Данные от регулярки

      @return : string
    */
    public function parseFinish(array $m,$etype,$k,$v='') {
      if (isset($m[2])) $v = $this->extensions($v,$m[2]);
      return ($v != '') ? $this->parse($v,null,$etype,$k) : '';
    }

    /******** ОБРАБОТЧИКИ ПАРСЕРА ********/
    // ****** Чанки, библиотеки чанков
    public function parse_chunk($m)  { return $this->_parse_chunk($m); }
    public function parse_string($m) { return $this->_parse_chunk($m,'string'); }
    public function parse_lib($m)    { return $this->_parse_chunk($m,'lib'); }

    // ****** Константы
    public function parse_constant($m) {
      $key = $this->parseStart($m);
      if (empty($key) || !defined($key)) {
        if (in_array('constant',$this->_notice)) return "<!-- not found: constant/$key -->";
        $v = '';
      } else { $v = constant($key); }
      return $this->parseFinish($m,'constant',$key,$v);
    }

    // ****** Настройки, переменные
    public function parse_setting($m)  { return $this->_parse_data($m,'setting'); }
    public function parse_variable($m) { return $this->_parse_data($m,'variable'); }

    // ****** Колбеки CMS
    public function parse_cms($m) {
      $key = $this->parseStart($m);
      $v = '';
      if (is_object($this->_owner)) {
        $M = "parse".ucfirst($key);
        if (method_exists($this->_owner,$M)) {
          $v = $this->_owner->$M($this->_arguments[$this->_level]);
        } else { if (in_array('cms',$this->_notice)) return "<!-- not implemented: cms/$key -->"; }
      } else { if (in_array('cms',$this->_notice)) return "<!-- not implemented: cms/$key -->"; }
      return $this->parseFinish($m,'cms',$key,$v);
    }

    // ****** Сниппеты
    public function parse_snippet($m) {
      $key = $this->parseStart($m);
      $v   = '';
      if ($_ = $this->execute($key,$this->_arguments[$this->_level])) {
        $v = strval($_);
      } else {
        if (($_ === false) && in_array('snippet',$this->_notice))
          return "<!-- not found: snippet/$key -->";
      }
      return $this->parseFinish($m,'snippet',$key,$v);
    }

    // ****** Локальные плейсхолдеры
    public function parse_local($m) {
      $key = $this->parseStart($m);
      $v = isset($this->_arguments[$this->_level-1][$key]) ? $this->_arguments[$this->_level-1][$key] : '';
      return $this->parseFinish($m,'local',$key,$v);
    }

    // ****** Языковые плейсхолдеры
    public function parse_language($m) {
      $key = $this->parseStart($m);
      $v   = $this->word($key);
      return $this->parseFinish($m,'language',$key,$v);
    }

    /******** ПУБЛИЧНЫЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:METHOD
      @name        : getChunk
      @description : Получение чанка

      @param : $key | string | value | | Имя чанка

      @return : string
    */
    public function getChunk($key,$type='chunk') {
      switch ($type) {
        case 'string':
          list($K,$I) = xbParser::keys($key);
          if (!isset($this->_strings[$K])) {
            if ($fn = $this->search('chunk',$K)) {
              $this->_strings[$K] = @file($fn);
            } else { return false; }
          }
          return isset($this->_strings[$K][$I]) ? $this->_strings[$K][$I] : false;
        case 'lib':
          list($K,$I) = xbParser::keys($key);
          if (!isset($this->_chunks[$K])) {
            if ($fn = $this->search('chunk',$K)) {
              $fc = @file_get_contents($fn);
              $this->_chunks[$K] = preg_split('~\<\!\-\- quadbraces:splitter \-\-\>~si',$fc);
            } else { return false; }
          }
          return isset($this->_chunks[$K][$I]) ? $this->_chunks[$K][$I] : false;
        default: if ($fn = $this->search('chunk',$key)) return @file_get_contents($fn);
      }
      return false;
    }

    /* CLASS:METHOD
      @name        : getTemplates
      @description : Получение шаблонов

      @param : $arguments | array | value | | Аргументы

      @return : array
    */
    public function getTemplates($args,$def=array()) {
      $ret = $def;
      $K = array_keys($ret);
      $ret['type'] = 'chunk';
      if (isset($args['chunkType']))
        if (in_array($args['chunkType'],array('chunk','string','lib'))) $ret['type'] = $args['chunkType'];
      foreach ($K as $Key) if (isset($args[$Key]))
        if ($_ = $this->getChunk($args[$Key],$ret['type'])) $ret[$Key] = $_;
      return $ret;
    }

    /* CLASS:METHOD
      @name        : execute
      @description : Выполнение сниппета или расширения

      @param : $name | string | value |        | Имя сниппета
      @param : $A    | array  | value | @EMPTY | Аргументы
      @param : $I    | string | value | @EMPTY | Входные данные

      @return : int/string
    */
    public function execute($name,$A=array(),$I='') {
      $result = '';            /** @noinspection PhpUnusedLocalVariableInspection */
      $owner  = $this->_owner; /** @noinspection PhpUnusedLocalVariableInspection */
      $parser = $this;         /** @noinspection PhpUnusedLocalVariableInspection */
      $input  = strval($I);    /** @noinspection PhpUnusedLocalVariableInspection */
      $arguments = $A;
      if ($fn = $this->search('snippet',$name)) $result = include($fn);
      return strval($result);
    }

    /* CLASS:METHOD
      @name        : extensions
      @description : Обработка расширений

      @param : $value | string | value |        | Входные данные
      @param : $ext   | string | value | @EMPTY | Тип элемента

      @param : string
    */
    public function extensions($value,$ext='') {
      $RET = ''.trim($value);
      if (empty($ext)) return ''.$value;
      if ($_ = preg_match_all('|\:([\w\-\.]+)((\=`([^`]*)`)?)|si',$ext,$ms,PREG_SET_ORDER)) {
        for($c = 0; $c < count($ms); $c++) {
          $a = $ms[$c][1];
          $v = isset($ms[$c][4]) ? $ms[$c][4] : '';
          if (xbParser::isLogic($a)) {
            $cond  = xbParser::condition($a,$value,$v);
            $cthen = xbParser::isLogicFunction($a) ? $v : $RET;
            if (isset($ms[$c+1])) if ($ms[$c+1][1] == 'then') { $c++; $cthen = $ms[$c][4]; }
            $celse = $RET;
            if (isset($ms[$c+1])) if ($ms[$c+1][1] == 'else') { $c++; $celse = $ms[$c][4]; }
            $RET = $cond ? $cthen : $celse;
            if (preg_match($this->_tags['local'],$RET))
              $RET = preg_replace_callback($this->_tags['local'],array($this,"parse_local"),$RET);
          } elseif (in_array($a,array('import','css-link','js-link'))) {
            $RET = xbParserLibQB::jscss($a,$RET);
          } elseif (in_array($a,array('link','link-external'))) {
            $RET = xbParserLibQB::link($a,$RET,$v);
          } else {
            switch ($a) {
              case 'links': $RET = xbParser::autoLinks($RET,$v); break;
              case 'include':
                if (!empty($v)) {
                  $_   = xbParser::path("$RET/$v");
                  $RET = is_file($_) ? include($_) : '';
                }
                break;
              case 'ul':
              case 'ol': $RET = xbParser::autoList($RET,$v,$a); break;
              case 'for':
                $v = intval($v);
                $start = 1;
                if ($ms[$c+1][1] == 'start') { $c++; $start = intval($ms[$c][4]); }
                $splt  = '';
                if ($ms[$c+1][1] == 'splitter') { $c++; $splt = $ms[$c][4]; }
                $_R  = array();
                for ($pos = $start; $pos <= ($v - $start); $pos++) {
                  $tpls = array(
                    array("#\[\+(iterator\.index)((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)\+\]#si",$pos)
                  );
                  $_R[] = $this->iteration($tpls,$RET);
                }
                $RET = implode($splt,$_R);
                break;
              case 'foreach':
                $v = explode(',',$v);
                $splt  = '';
                if ($ms[$c+1][1] == 'splitter') { $c++; $splt = $ms[$c][4]; }
                $_R  = array();
                foreach ($v as $pos => $key) {
                  $tpls = array(
                    array("#\[\+(iterator\.index)((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)\+\]#si",$pos),
                    array("#\[\+(iterator|iterator\.key)((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)\+\]#si",$key)
                  );
                  $_R[] = $this->iteration($tpls,$RET);
                }
                $RET = implode($splt,$_R);
                break;
              default: if ($_ = $this->execute($a,$v,$RET)) $RET = $_;
            }
          }
        }
      }
      return $RET;
    }

    /* CLASS:METHOD
      @name        : search
      @description : Поиск элемента

      @param : $type | string | value | | Тип элемента
      @param : $name | string | value | | Имя элемента

      @param : string
    */
    public function search($type,$key) {
      return xbParserLibQB::search($this->paths,$type,$key,$this->language);
    }

    /******** ПЕРЕОПРЕДЕЛЯЕМЫЕ МЕТОДЫ КЛАССА ********/
    /* Главный алгоритм */
    public function parseMain($v) {
      $O = $v;
      foreach ($this->_tags as $k => $t) {
        $m = null;
        if (isset($this->_tagsExts[$k]))           { $m = array($this->_tagsExts[$k],"parse");
        } elseif (method_exists($this,"parse_$k")) { $m = array($this,"parse_$k"); }
        if (!is_null($m)) if (preg_match($t,$O)) $O = preg_replace_callback($t,$m,$O);
      }
      return $O;
    }

    /* Алгоритм санитизации */
    public function parseSanitize($v) { return xbParserLibQB::sanitize($v); }
  }
  /* CLASS ~END */

  /* INFO @copyright: xbLab 2015 */
?>