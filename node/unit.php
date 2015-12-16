<?php
  /* INFO
    @product     : xbNode
    @component   : xbNodeUnit
    @type        : class
    @description : Класс модуля обработки
    @revision    : 2015-12-16 16:46:00
  */

  /* CLASS ~BEGIN */
  /**
   * Class xbNodeUnit
   * @property string $formPrefix
   */
  class xbNodeUnit extends xbNodePrototype {
    protected $_formPrefix = '';

    /* CLASS:CONSTRUCT */
    function __construct($owner) { parent::__construct($owner); }

    /* CLASS:SET */
    function __set($n,$v) {
      switch ($n) {
        case 'formPrefix':
          $V = strtolower($this->_module.'-'.$this->_nodeName);
          if (!is_null($v)) $V = strtolower($v);
          if (!empty($V)) $this->_formPrefix = $V.'-';
          return $this->_formPrefix;
      }
      return false;
    }

    /* **************** ОСНОВНЫЕ МЕТОДЫ **************** */
    public function execute() {
      if (!$this->_ready) return $this->error('unit','not ready');
      $a = func_get_args();
      if (count($a) < 1) return $this->error('unit','no action name');
      $n = array_shift($a);
      $m = 'do'.ucfirst($n);
      if (!method_exists($this,$m)) {
        if (is_object($this->_owner))
          if (method_exists($this->_owner,'notImplemented'))
            return $this->_owner->notImplemented($this->module,$this->nodeName,$n);
        return $this->error('unit','not implemented');
      }
      return call_user_func_array(array($this,$m),$a);
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>