<?php
  /* INFO
    @product     : xbLib
    @component   : xbClass
    @type        : class
    @description : Базовый класс
    @revision    : [+__make.revision+]
  */

  /* CLASS ~BEGIN
    @string : Имя класса
  */

  /**
   * Class xbClass
   * @property-read array  $debugTrace
   * @property-read string $GUID
   * @property-read array  $events
   * @property-read array  $methods
   *
   * @method bool onClassError()
   */
  class xbClass {
    protected $_debugTrace = array();
    protected $_events     = array();
    protected $_methods    = array();

    /* CLASS:CONSTRUCTOR */
    function __construct($owner=null) { $this->_debugTrace['starttime'] = microtime(true); }

    /* CLASS:GET */
    function __get($n) {
      if (method_exists($this,"get_$n"))       { $f = "get_$n"; return $this->$f();
      } elseif (property_exists($this,"_$n"))  { $f = "_$n"; return $this->$f;
      } elseif (method_exists($this,"set_$n")) { $e = 'write_only';
      } else { $e = property_exists($this,$n) ? 'protected' : 'not_exists'; }
      return (!$this->onClassError("property:$e",$n));
    }

    /* CLASS:SET */
    function __set($n,$v) {
      if (method_exists($this,"set_$n"))       { $f = "set_$n"; return $this->$f($v);
      } elseif (method_exists($this,"get_$n")) { $e = 'read_only';
      } else { $e = property_exists($this,$n) ? 'protected' : 'not_exists'; }
      return (!$this->onClassError("property:$e",$n));
    }

    /* CLASS:CALL */
    function __call($n,$p) {
      if (preg_match('/^on(\w+)$/',$n)) {
        $e = lcfirst(preg_replace('/^on(\w+)$/','\1',$n));
        return $this->call_event($e,$p);
      } elseif (isset($this->_methods[$n])) {
        return call_user_func_array($this->_methods[$n],$p);
      }
      return (!$this->onClassError("method:not_exists",$n));
    }

    /* CLASS:STRING */
    function __toString() { return $this->GUID; }

    /* CLASS:INTERNAL
      @name        : call_event
      @description : Вызов события

      @param : $e    | string | value | | Название события
      @param : $args | array  | value | | Аргументы события

      @return : bool
    */
    protected function call_event($e,$args) {
      if (!isset($this->_events[$e]))    return true;
      if (!is_array($this->_events[$e])) return true;
      $ret = true;
      foreach ($this->_events[$e] as $func) {
        // Первентивная проверка возможности вызова хука
        $EX = false;
        if (is_array($func)) {
          $obj = $func[0];
          $met = $func[1];
          if (is_object($func[0])) $EX = method_exists($obj,$met);
        } else { $EX = function_exists($func); }
        // Вызов хука
        if ($EX) {
          $res  = call_user_func_array($func,$args);
          // Алгоритм преобразования соответствует алгоритму функции в xbLib
          // Используется в обход для того, чтобы не было необходимости
          // подгружать всю библиотеку
          $ret &= ((strval($res)=='true')||($res===true)||(intval($res)>0));
        }
      }
      return $ret;
    }

    /* CLASS:PROPERTY
      @name        : GUID
      @description : GUID класса
      @type        : string
      @mode        : r
    */
    protected function get_GUID() { return strtoupper(md5(get_class($this))); }

    /* CLASS:METHOD
      @name        : registerEvent
      @description : Регистрация события

      @param : $event | string   | value |       | Название функции
      @param : $func  | callable | value | @NULL | Функция

      @param : bool
    */
    public function registerEvent($n,$f=null) {
      if (!self::isFunctionName($n)) return (!$this->onClassError("event:invalid_name",$n));
      if (!isset($this->_events[$n])) $this->_events[$n] = array();
      if (is_callable($f,true)) {
        $this->_events[$n][] = $f;
        return true;
      }
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
      if (!self::isFunctionName($n)) return (!$this->onClassError("method:invalid_name",$n));
      if (is_callable($f,true)) {
        $this->_methods[$n] = $f;
        return true;
      }
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
      return $this->call_event($n,$fargs);
    }

    /* CLASS:STATIC
      @name        : bool
      @description : Получение булева значения

      @param : $v | | value | | Значение

      @return : boolean | Булево представление значения
    */
    public static function isFunctionName($n) {
      if (preg_match('#^([[:alpha:]])(\w+)([[:alpha:]\d])$#si',$n)) return true;
      return false;
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>