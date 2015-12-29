<?php
  /* INFO
    @product     : xbNode
    @component   : xbNodeCache
    @type        : class
    @description : Класс обработчика кеша
    @revision    : 2015-12-22 13:33:00
  */

  /* CLASS ~BEGIN */
  /**
   * Class xbNodeCache
   * @property-read xbNodeLoader $owner
   * @property-read bool         $ready
   *
   * @property-read string $module
   * @property-read string $name
   * @property-read array  $path
   * @property-read string $fileName
   */
  class xbNodeCache {
    protected $_owner  = null;
    protected $_ready  = true;
    protected $_module = 'system';
    protected $_name   = 'main';
    protected $_path   = array();

    /* CLASS:CONSTRUCTOR */
    function __construct($owner,$v='') {
      $this->_owner = $owner;
      $this->_name  = 'main';
      if ($CD = xbNode::classification(get_class($this))) {
        if (!empty($CD['module'])) $this->_module = $CD['module'];
        if (!empty($CD['name']))   $this->_name   = $CD['name'];
      }
      if (!empty($v)) {
        $F = explode('/',$v);
        $L = count($F) - 1;
        $this->_name = $F[$L];
        unset($F[$L]);
        $this->_path = $F;
      }
      $this->_ready = $this->owner->ready;
    }

    /* CLASS:GET */
    function __get($n) {
      switch ($n) {
        case 'fileName':
          $F = xbNode::cacheDeploy().$this->_module.DIRECTORY_SEPARATOR;
          if (is_array($this->_path)) if (count($this->_path) > 0) {
            $F.= implode(DIRECTORY_SEPARATOR,$this->_path);
            if (!is_dir($F)) if (!mkdir($F,0744,true)) return false;
          }
          return $F.DIRECTORY_SEPARATOR.$this->_name.'.cache';
      }
      $N = "_$n";
      return property_exists($this,$N) ? $this->$N : false;
    }

    /******** ВИРТУАЛЬНЫЕ МЕТОДЫ КЛАССА ********/
    /* Запрос на создание данных */
    public function create() { return false; }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>