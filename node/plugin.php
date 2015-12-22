<?php
  /* INFO
    @product     : xbNode
    @component   : xbNodePlugin
    @type        : class
    @description : Класс плагина
    @revision    : 2015-12-22 13:34:00
  */

  /* CLASS ~BEGIN */
  /**
   * Class xbNodePlugin
   * @property-read string $key
   */
  class xbNodePlugin extends xbNodePrototype {
    const delegate = '/^(handle|hook|on)(\w+)$/';

    protected $_key = '';

    /* CLASS:CONSTRUCT */
    function __construct($owner) {
      parent::__construct($owner);
      $this->_key = $this->_module.(empty($this->_nodeName) ? '' : ".".$this->_nodeName);
      $api = get_class_methods($this);
      $me  = is_object($this->_owner);
      if ($me) $me = method_exists($this->_owner,'delegate');
      foreach ($api as $name) {
        if (preg_match(self::delegate,$name)) {
          $event = lcfirst(preg_replace(self::delegate,'\2',$name));
          $type  =         preg_replace(self::delegate,'\1',$name);
          if ($me) $this->owner->delegate($type,$this->_key,$event);
        }
      }
    }

    /* **************** ВНУТРЕННИЕ МЕТОДЫ **************** */
    /* Вызов */
    protected function _call($type,$params) {
      if (!$this->_ready) return $this->error('plugin','not ready',$this->_key);
      $args = $params;
      $cnt  = $type == 'handle' ? 2 : 1;
      if (count($args) < $cnt) return $this->error('plugin',"invalid $type call",$this->_key);
      $name = array_shift($args);
      $m = $type.ucfirst($name);
      if (method_exists($this,$m))
        return call_user_func_array(array($this,$m),$args);
      return ($type == 'handle' ? $args[1] : true);
    }

    /* **************** ОСНОВНЫЕ МЕТОДЫ **************** */
    public function handle() { $a = func_get_args(); return $this->_call('handle',$a); }
    public function hook()   { $a = func_get_args(); return $this->_call('hook',$a); }
    public function event()  { $a = func_get_args(); return $this->_call('event',$a); }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>