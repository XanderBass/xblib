<?php
  /* INFO
    @product     : xbNode
    @component   : xbNodeLoader
    @type        : class
    @description : Класс процессора кеша
    @revision    : 2015-12-22 13:34:00
  */

  /* CLASS ~BEGIN */
  /**
   * Class xbNodeLoader
   * @property      bool   $new
   * @property      bool   $active
   * @property-read string $status
   *
   * @property-read array  $handlers
   */
  class xbNodeLoader {
    protected $_ready  = true;
    protected $_owner  = null;
    protected $_path   = null;
    protected $_new    = false; // Флаг необходимости сохранения
    protected $_active = false; // Флаг активности кэша

    /* CLASS:CONSTRUCT */
    function __construct($owner=null) {
      $this->_owner = $owner;
    }

    /* CLASS:GET */
    function __get($n) {
      switch ($n) {
        case 'status': return $this->_active ? ($this->_ready ? 'active' : 'not_ready') : 'not_active';
        case 'active': return $this->_active && $this->_ready;
      }
      $N = "_$n";
      return property_exists($this,$N) ? $this->$N : false;
    }

    /* CLASS:SET */
    function __set($n,$v) {
      switch ($n) {
        case 'new'   : $this->_new = xbNode::bool($v); return $this->_new; break;
        case 'active':
          $this->_active = false;
          if ($v) $this->_active = xbNode::bool($v);
          return $this->_active;
      }
      return false;
    }

    /* **************** ВНУТРЕННИЕ МЕТОДЫ **************** */
    /* Создание */
    protected function _create($obj) {
      if (method_exists($obj,'create')) {
        $_ = $obj->create();
        if (is_array($_)) {
          $this->_new = true;
          return $_;
        } else { return $this->error('cache','no_data'); }
      }
      return false;
    }

    /* Получение объекта */
    /**
     * @param string $v
     * @param string $module
     * @return xbNodeCache
     */
    protected function _obj($v,$module='') {
      $name = '';
      $path = array();
      if (!empty($v)) {
        $F = explode('/',$v);
        $name = array_shift($F);
        $path = $F;
      }

      $path = implode('/',$path);
      return xbNode::create($module,'cache',$name,$path);
    }

    /* **************** ПУБЛИЧНЫЕ МЕТОДЫ **************** */
    /* CLASS:METHOD
      @name        : load
      @description : Загрузка кэша

      @param : $v      | string | value |        | Путь
      @param : $module | string | value | @EMPTY | Модуль

      @return : bool | Результат операции
    */
    public function load($v,$module='') {
      $this->_new = false;
      $obj = $this->_obj($v,$module);
      if (!($obj instanceof xbNodeCache)) return $this->error('cache','no_object');
      $this->_ready = true;
      if ($this->_active) {
        if ($_ = @file_get_contents($obj->fileName)) {
          $data = unserialize($_);
        } else {
          $data = $this->_create($obj);
          $this->_ready = is_array($data);
        }
        if ($this->_ready && $this->_new) {
          if (!is_array($data)) return $this->error('cache','no_data','save');
          if (@file_put_contents($obj->fileName,serialize($data))) {
            $this->_new = false;
          } else { return $this->error('cache','no_file',$obj->fileName); }
        } else {
          if (!$this->_ready) return $this->error('cache','not_ready',$this->status);
        }
      } else {
        $data = $this->_create($obj);
        $this->_ready = is_array($data);
      }

      if ($this->_ready) {
        if (method_exists($obj,'load')) {
          $ret = $obj->load($data);
          if (is_array($ret)) return $ret;
        } else { return $data; }
      }

      return false;
    }

    /* CLASS:METHOD
      @name        : remove
      @description : Загрузка кэша

      @param : $v | string | value | | Путь

      @return : bool | Результат операции
    */
    public function remove($v,$module='') {
      $obj = $this->_obj($v,$module);
      if (!($obj instanceof xbNodeCache)) return $this->error('cache','no_object');
      if (method_exists($obj,'remove')) {
        $ret = $obj->remove();
      } else { $ret = is_file($obj->fileName) ? unlink($obj->fileName) : true; }
      return $ret;
    }

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
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>