<?php
  /* INFO
    @product     : xbLib
    @component   : xbClass
    @type        : class
    @description : Базовый класс
    @revision    : 2015-12-16 16:36:00
  */

  /* CLASS ~BEGIN
    @string : GUID класса
  */

  /**
   * Class xbClass
   * @property-read array  $debugTrace
   * @property-read string $GUID
   * @property-read array  $events
   * @property-read array  $methods
   * @property-read object $owner
   *
   * @method bool onError()
   */
  class xbClass {
    const functionName = '#^([[:alpha:]])(\w+)([[:alpha:]\d])$#si';

    protected static $_errorHandler = null;

    protected $_debugTrace = array();
    protected $_events     = array();
    protected $_methods    = array();
    protected $_owner      = null;

    /* CLASS:CONSTRUCTOR */
    function __construct($owner=null) {
      $this->_debugTrace['starttime'] = microtime(true);
      $this->_owner = $owner;
    }

    /* CLASS:GET */
    function __get($n) {
      if (method_exists($this,"get_$n"))       { $f = "get_$n"; return $this->$f();
      } elseif (property_exists($this,"_$n"))  { $f = "_$n"; return $this->$f;
      } elseif (method_exists($this,"set_$n")) { $e = 'write only';
      } else { $e = property_exists($this,$n) ? 'protected' : 'not exists'; }
      return (!$this->error('class',"property $e",$n));
    }

    /* CLASS:SET */
    function __set($n,$v) {
      if (method_exists($this,"set_$n"))       { $f = "set_$n"; return $this->$f($v);
      } elseif (method_exists($this,"get_$n")) { $e = 'read only';
      } else { $e = property_exists($this,$n) ? 'protected' : 'not exists'; }
      return (!$this->error('class',"property $e",$n));
    }

    /* CLASS:CALL */
    function __call($n,$p) {
      if (preg_match('/^on(\w+)$/',$n)) {
        $e = lcfirst(preg_replace('/^on(\w+)$/','\1',$n));
        return $this->_callEvent($e,$p);
      } elseif (isset($this->_methods[$n])) {
        return call_user_func_array($this->_methods[$n],$p);
      }
      return (!$this->error('class',"method not exists",$n));
    }

    /* CLASS:STRING */
    function __toString() { return $this->GUID; }

    /******** ВНУТРЕННИЕ МЕТОДЫ КЛАССА ********/
    /* Вызов события */
    protected function _callEvent($e,$args) {
      if (!isset($this->_events[$e]))    return true;
      if (!is_array($this->_events[$e])) return true;
      $ret = true;
      foreach ($this->_events[$e] as $func) {
        // Превентивная проверка возможности вызова хука
        $EX = false;
        if (is_array($func)) {
          $obj = $func[0];
          $met = $func[1];
          if (is_object($func[0])) $EX = method_exists($obj,$met);
        } else { $EX = function_exists($func); }
        // Вызов хука
        if ($EX) {
          $res  = call_user_func_array($func,$args);
          $ret &= ((strval($res)=='true')||($res===true)||(intval($res)>0));
        }
        if (!$ret) break;
      }
      return $ret;
    }

    /******** АКСЕССОРЫ КЛАССА ********/
    /* CLASS:PROPERTY
      @name        : GUID
      @description : GUID класса
      @type        : string
      @mode        : r
    */
    protected function get_GUID() { return strtoupper(md5(get_class($this))); }

    /******** ПУБЛИЧНЫЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:METHOD
      @name        : registerEvent
      @description : Регистрация события

      @param : $event | string   | value |       | Название функции
      @param : $func  | callable | value | @NULL | Функция

      @param : bool
    */
    public function registerEvent($n,$f=null) {
      if (preg_match(self::functionName,$n)) {
        if (!isset($this->_events[$n])) $this->_events[$n] = array();
        if (is_callable($f,true)) {
          $this->_events[$n][] = $f;
          return true;
        }
      } else { return (!$this->error('class',"invalid event name",$n)); }
      return true;
    }

    /* CLASS:METHOD
      @name        : registerMethod
      @description : Регистрация метода

      @param : $name | string   | value |       | Название функции
      @param : $func | callable | value | @NULL | Функция

      @param : bool
    */
    public function registerMethod($n,$f=null) {
      if (preg_match(self::functionName,$n)) {
        if (is_callable($f,true)) {
          $this->_methods[$n] = $f;
          return true;
        }
      } else { return (!$this->error('class',"invalid method name",$n)); }
      return false;
    }

    /* CLASS:METHOD
      @name        : invoke
      @description : Вызов события

      @param : [1] | string | value | | Название события
      @params : аргументы функции

      @return : ?
    */
    public function invoke() {
      $fargs = func_get_args();
      if (!isset($fargs[0])) return true;
      $n = array_shift($fargs);
      return $this->_callEvent($n,$fargs);
    }

    /* CLASS:METHOD
      @name        : error
      @description : Ошибка

      @return : ?
    */
    public function error() {
      $a = func_get_args();
      if (!$this->_callEvent('error',$a)) return false;
      if (is_callable(self::$_errorHandler,true)) {
        return call_user_func_array(self::$_errorHandler,$a);
      } elseif (self::$_errorHandler === true) { return true; }
      throw new Exception('xbLib error: '.implode('|',$a));
    }

    /******** АКСЕССОРЫ К СТАТИЧЕСКИМ СВОЙСТВАМ КЛАССА ********/
    /* Установка функции обработки ошибок */
    public static function errorHandler($v=null) {
      if (is_callable($v,true) || ($v === true)) {
        self::$_errorHandler = $v;
      } elseif ($v === false) {
        self::$_errorHandler = null;
      }
      return self::$_errorHandler;
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>