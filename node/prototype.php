<?php
  /* INFO
    @product     : xbNode
    @component   : xbNodePrototype
    @type        : class
    @description : Класс прототипа ноды
    @revision    : 2015-12-22 13:35:00
  */

  /* CLASS ~BEGIN */
  /**
   * Class xbNodePrototype
   * @property-read object $owner
   * @property-read bool   $ready
   *
   * @property-read string $module
   * @property-read string $nodeType
   * @property-read string $nodeName
   *
   * @property-read array  $uses
   */
  class xbNodePrototype {
    protected $_classTypes = array('API','Plugin','Cache','Unit');

    protected $_owner = null;
    protected $_ready = false;

    protected $_module   = '';
    protected $_nodeType = '';
    protected $_nodeName = '';

    protected $_uses     = array();

    /* CLASS:CONSTRUCT */
    function __construct($owner) {
      // Основные поля
      $this->_owner = $owner;
      $this->_ready = true;
      // Инициализируем идентификационные поля класса
      if ($CD = xbNode::classification(get_class($this))) {
        $this->_module   = $CD['module'];
        $this->_nodeType = $CD['type'];
        $this->_nodeName = $CD['name'];
      }
      if (empty($this->_module)) $this->_module = 'system';
      // Подключаем зависимости
      if (is_array($this->_uses) && !empty($this->_uses))
        if (is_object($this->_owner) && method_exists($this->_owner,'loadAPI')) {
          foreach ($this->_uses as $key) {
            $d      = explode('.',$key);
            $module = $d[0];
            $name   = isset($d[1]) ? $d[1] : '';
            $ret = $this->_owner->loadAPI($module,$name);
            if (!$ret) {
              $this->_ready = false;
              break;
            }
          }
        }
    }

    /* CLASS:GET */
    function __get($n) { $N = "_$n"; return property_exists($this,$N) ? $this->$N : false; }

    /* **************** ПУБЛИЧНЫЕ МЕТОДЫ **************** */
    /* CLASS:METHOD
      @name        : error
      @description : Ошибка

      @return : ?
    */
    public function error() {
      $a = func_get_args();
      $h = xbNode::errorHandler();
      if (is_callable($h,true)) {
        return call_user_func_array($h,$a);
      } elseif ($h === true) { return true; }
      throw new Exception('xbNode error: '.implode('|',$a));
    }

    /* CLASS:METHOD
      @name        : load
      @description : Загрузка кэша

      @param : $v | string | value | | Путь

      @return : array
    */
    public function load($v) {
      if ($this->_owner instanceof xbNodeContainer) {
        return $this->_owner->cache->load($v,$this->_module);
      }
      return false;
    }

    /* CLASS:METHOD
      @name        : remove
      @description : Загрузка кэша

      @param : $v | string | value | | Путь

      @return : array
    */
    public function remove($v) {
      if ($this->_owner instanceof xbNodeContainer) {
        return $this->_owner->cache->remove($v,$this->_module);
      }
      return false;
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>