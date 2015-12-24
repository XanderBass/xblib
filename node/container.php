<?php
  /* INFO
    @product     : xbNode
    @component   : xbNodeContainer
    @type        : class
    @description : Класс контейнера
    @revision    : 2015-12-22 13:31:00
  */

  define("XBNODE_PM_NO_EVENTS",0);
  define("XBNODE_PM_CONTAINER_ONLY",1);
  define("XBNODE_PM_PLUGINS_ONLY",2);
  define("XBNODE_PM_CONTAINER_FIRST",3);
  define("XBNODE_PM_PLUGINS_FIRST",7);

  /* CLASS ~BEGIN */
  /**
   * Class xbNodeContainer
   *
   * @property      int   $eventMethod
   *
   * @property-read string $prefix
   * @property-read array  $events
   * @property-read array  $method
   * @property-read array  $plugins
   *
   * @property-read array $handleList
   * @property-read array $handleMap
   * @property-read array $hookList
   * @property-read array $hookMap
   * @property-read array $eventList
   * @property-read array $eventMap
   * @property-read array $APIExtensions
   *
   * @property-read xbNodeLoader $cache
   */
  class xbNodeContainer {
    const functionName = '#^([[:alpha:]])(\w+)([[:alpha:]\d])$#si';

    protected static $_errorHandler = null;

    protected $_eventMethod   = 3;

    protected $_prefix        = '';
    protected $_events        = array();
    protected $_methods       = array();
    protected $_plugins       = array();

    protected $_handleList    = array();
    protected $_handleMap     = array();
    protected $_hookList      = array();
    protected $_hookMap       = array();
    protected $_eventList     = array();
    protected $_eventMap      = array();
    protected $_APIExtensions = array();

    protected $_cache         = null;

    /* CLASS:CONSTRUCT */
    function __construct($prefix=null) {
      $this->_cache = new xbNodeLoader($this);
      if (!is_null($prefix)) {
        $this->_prefix = $prefix;
        xbNode::classPrefix($this->_prefix);
      }
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

    function set_eventMethod($v) { $this->_eventMethod = intval($v) & 0x07; return $this->_eventMethod; }

    /* **************** ВНУТРЕННИЕ МЕТОДЫ **************** */
    /* Задание последовательности вызова */
    protected function _pluginMap($type,$event,$data=null) {
      switch ($type) {
        case 'handle': $dList = $this->_handleList; break;
        case 'hook'  : $dList = $this->_hookList;   break;
        case 'event' : $dList = $this->_eventList;  break;
        default: return false;
      }
      if (is_null($data)) return true;
      if (@!is_array($dList[$event]) || @empty($dList[$event])) return true;
      $keys = $dList[$event];
      $seq  = array();
      if (is_array($data))  $seq = array_values($data);
      if (is_string($data)) $seq = explode(',',$data);
      if (empty($seq)) return false;
      $ret = array();
      foreach ($seq as $plugin)
        if ($pos = array_search($plugin,$keys)) {
          $ret[] = $plugin;
          unset($keys[$pos]);
        }
      foreach ($keys as $plugin)
        if (!is_null($plugin)) $ret[] = $plugin;
      return $ret;
    }

    /* Вызов событий контейнера */
    protected function _callEContainer($e,$args) {
      if (!isset($this->_events[$e]))    return true;
      if (!is_array($this->_events[$e])) return true;
      foreach ($this->_events[$e] as $func) {
        // Превентивная проверка возможности вызова хука
        $EX = false;
        if (is_array($func)) {
          $obj = $func[0];
          $met = $func[1];
          if (is_object($func[0])) $EX = method_exists($obj,$met);
        } else { $EX = function_exists($func); }
        // Вызов хука
        $res = true;
        if ($EX) {
          $res = call_user_func_array($func,$args);
          $res = ((strval($res)=='true')||($res===true)||(intval($res)>0));
        }
        if (!$res) return false;
      }
      return true;
    }

    /* Вызов событий плагинов */
    protected function _callEPlugins($e,$args) {
      /** @var xbNodePlugin $plugin */
      if (isset($this->_eventMap[$e]) && @is_array($this->_eventMap[$e])) {
        $eList = $this->_eventMap[$e];
      } else { $eList = $this->_eventList[$e]; }
      if (is_array($eList)) {
        foreach ($eList as $pluginName) {
          if (!isset($this->_plugins[$pluginName])) continue;
          $plugin = $this->_plugins[$pluginName];
          $res = call_user_func_array(array($plugin,'event'),$args);
          $res = ((strval($res)=='true')||($res===true)||(intval($res)>0));
          if (!$res) return false;
        }
      }
      return true;
    }

    /* Вызов события */
    protected function _callEvent($e,$args) {
      switch ($this->_eventMethod) {
        case 1: case 5: return $this->_callEContainer($e,$args);
        case 2: case 6: return $this->_callEPlugins($e,$args);
        case 3: return ($this->_callEContainer($e,$args) ? $this->_callEPlugins($e,$args) : false);
        case 7: return ($this->_callEPlugins($e,$args) ? $this->_callEContainer($e,$args) : false);
        default: return true;
      }
    }

    /* **************** ОБЩИЕ МЕТОДЫ **************** */
    /* CLASS:METHOD
      @name        : getKey
      @description : Формирование ключа

      @param : $module | string | value | @EMPTY | Название модуля
      @param : $name   | string | value | @EMPTY | Название плагина

      @param : string
    */
    public function getKey($module='',$name='') {
      $ret = empty($module) ? 'system' : $module;
      $ret.= empty($name) ? '' : ".$name";
      return $ret;
    }

    /* CLASS:METHOD
      @name        : delegate
      @description : Делегация колбека

      @param : $type  | string | value | | Тип колбека
      @param : $key   | string | value | | Ключ плагина
      @param : $event | string | value | | Название события

      @param : bool
    */
    public function delegate($type,$key,$event) {
      switch ($type) {
        case 'handle':
          $this->_handleList[$event] = $key;
          if (@is_array($this->_handleMap[$event])) $this->_handleMap[$event][] = $key;
          break;
        case 'hook':
          $this->_hookList[$event] = $key;
          if (@is_array($this->_hookMap[$event])) $this->_hookMap[$event][] = $key;
          break;
        case 'event':
          $this->_eventList[$event] = $key;
          if (@is_array($this->_eventMap[$event])) $this->_eventMap[$event][] = $key;
          break;
        default: return false;
      }
      return true;
    }

    /* CLASS:METHOD
      @name        : loadPlugin
      @description : Загрузка плагина

      @param : $module | string | value | @EMPTY | Название модуля
      @param : $name   | string | value | @EMPTY | Название плагина

      @param : bool
    */
    public function loadPlugin($module='',$name='') {
      // Проверяем загружен ли плагин
      $key = $this->getKey($module,$name);
      if (isset($this->_plugins[$key]))
        if ($this->_plugins[$key] instanceof xbNodePlugin) return $this->_plugins[$key]->ready;
      // Загружаем плагин
      /** @var xbNodePlugin $plugin */
      if ($plugin = xbNode::create($module,'plugin',$name,$this)) {
        if (!$plugin->ready) return !$this->event('pluginNotReady',$module,$name);
        $this->_plugins[$key] = $plugin;
        return true;
      } else { return empty($name) ? true : !$this->event('pluginNotFound',$module,$name); }
    }

    /* CLASS:METHOD
      @name        : loadAPI
      @description : Загрузка API

      @param : $module | string | value | @EMPTY | Название модуля
      @param : $name   | string | value | @EMPTY | Название плагина

      @param : bool
    */
    public function loadAPI($module='',$name='') {
      // Проверяем загружен ли API
      $key = $this->getKey($module,$name);
      if (isset($this->_APIExtensions[$key]))
        if ($this->_APIExtensions[$key] instanceof xbNodeAPI)
          return $this->_APIExtensions[$key]->ready;
      // Загружаем API
      /** @var xbNodeAPI $api */
      if ($api = xbNode::create($module,'api',$name,$this)) {
        if (!$api->ready) return !$this->event('APINotReady',$module,$name);
        $this->_APIExtensions[$key] = $api;
        return true;
      } else { return empty($name) ? true : !$this->event('APINotFound',$module,$name); }
    }

    /* CLASS:METHOD
      @name        : loadModule
      @description : Загрузка модуля

      @param : $module | string | value | @EMPTY | Название модуля

      @param : bool
    */
    public function loadModule($module) {
      if ($this->loadAPI($module)) {
        return $this->loadPlugin($module);
      }
      return !$this->event('ModuleNotLoaded',$module);
    }

    /* **************** ХЕНДЛЫ **************** */
    /* CLASS:METHOD
      @name        : setHandleMap
      @description : установка очереди обработчиков

      @param : $event | string | value |       | Название события
      @param : $data  | array  | value | @NULL | Последовательность

      @param : bool
    */
    public function setHandleMap($event,$data=null) {
      $map = $this->_pluginMap('handle',$event,$data);
      if (!is_array($map)) {
        if ($map === true) unset($this->_handleMap[$event]);
        return $map;
      }
      $this->_handleMap[$event] = $map;
      return true;
    }

    /* CLASS:METHOD
      @name        : handle
      @description : Запуск обработчика данных

      @param : [0] | string | value |       | Название события
      @param : [1] | array  | value | @NULL | Данные

      @param : ? | Обработанные данные
    */
    public function handle() {
      /** @var xbNodePlugin $plugin */
      $a = func_get_args();
      if (count($a) < 2) return $this->error('class','invalid handle');
      $event = $a[0];
      if (isset($this->_handleMap[$event]) && @is_array($this->_handleMap[$event])) {
        $eList = $this->_handleMap[$event];
      } else { $eList = isset($this->_handleList[$event]) ? $this->_handleList[$event] : false; }
      if (!is_array($eList)) return $a[1];
      foreach ($eList as $pluginName) {
        if (!isset($this->_plugins[$pluginName])) continue;
        $plugin = $this->_plugins[$pluginName];
        $ret  = call_user_func_array(array($plugin,'handle'),$a);
        $a[1] = $ret;
      }
      return $a[1];
    }

    /* **************** ХУКИ **************** */
    /* CLASS:METHOD
      @name        : setHookMap
      @description : установка очереди хуков

      @param : $event | string | value |       | Название события
      @param : $data  | array  | value | @NULL | Последовательность

      @param : bool
    */
    public function setHookMap($event,$data=null) {
      $map = $this->_pluginMap('hook',$event,$data);
      if (!is_array($map)) {
        if ($map === true) unset($this->_hookMap[$event]);
        return $map;
      }
      $this->_hookMap[$event] = $map;
      return true;
    }

    /* CLASS:METHOD
      @name        : hook
      @description : Запуск хука

      @param : [0] | string | value | | Название события

      @param : bool
    */
    public function hook() {
      /** @var xbNodePlugin $plugin */
      $a = func_get_args();
      if (count($a) < 1) return false;
      $e = $a[0];
      if (empty($e)) return false;
      if (isset($this->_hookMap[$e]) && @is_array($this->_hookMap[$e])) {
        $eList = $this->_hookMap[$e];
      } else { $eList = isset($this->_hookList[$e]) ? $this->_hookList[$e] : false; }
      if (!is_array($eList)) return false;
      foreach ($eList as $pluginName) {
        if (!isset($this->_plugins[$pluginName])) continue;
        $plugin = $this->_plugins[$pluginName];
        call_user_func_array(array($plugin,'hook'),$a);
      }
      return true;
    }

    /* **************** СОБЫТИЯ **************** */
    /* CLASS:METHOD
      @name        : setEventMap
      @description : установка очереди события

      @param : $event | string | value |       | Название события
      @param : $data  | array  | value | @NULL | Последовательность

      @param : bool
    */
    public function setEventMap($event,$data=null) {
      $map = $this->_pluginMap('event',$event,$data);
      if (!is_array($map)) {
        if ($map === true) unset($this->_eventMap[$event]);
        return $map;
      }
      $this->_eventMap[$event] = $map;
      return true;
    }

    /* CLASS:METHOD
      @name        : event
      @description : Запуск события

      @param : [0] | string | value | | Название события

      @param : bool
    */
    public function event() {
      $fargs = func_get_args();
      if (!isset($fargs[0])) return true;
      $n = array_shift($fargs);
      return $this->_callEvent($n,$fargs);
    }

    /* **************** РЕГИСТРАЦИЯ МЕТОДОВ КОНТЕЙНЕРА **************** */
    /* CLASS:METHOD
      @name        : registerEvent
      @description : Регистрация события контейнера

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
      } else { return (!$this->error('class',"event invalid name",$n)); }
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
      } else { return (!$this->error('class',"method invalid name",$n)); }
      return false;
    }

    /* CLASS:METHOD
      @name        : methodExists
      @description : Существование метода

      @param : $n | string | value | | Название функции

      @param : bool
    */
    public function methodExists($n) { return isset($this->_methods[$n]); }

    /* **************** ПРОЧЕЕ **************** */
    /* CLASS:METHOD
      @name        : execute
      @description : Выполнение действия

      @param : $module | string | value | | Название модуля
      @param : $unit   | string | value | | Название модуля обработки
      @param : $action | string | value | | Название действия

      @param : bool
    */
    public function execute($module,$unit,$action) {
      if ($unit = xbNode::create($module,'unit',$unit))
        if (method_exists($unit,'execute'))
          return $unit->execute($action);
      if (method_exists($this,'notImplemented')) $this->notImplemented($module,$unit,$action);
      return false;
    }

    /* CLASS:METHOD
      @name        : error
      @description : Ошибка

      @return : ?
    */
    public function error() {
      $a = func_get_args();
      if (!$this->_callEvent('error',$a)) return false;
      $h = xbNode::errorHandler();
      if (is_callable($h,true)) {
        return call_user_func_array($h,$a);
      } elseif ($h === true) { return true; }
      throw new Exception('xbNode error: '.implode('|',$a));
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>