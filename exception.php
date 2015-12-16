<?php
  /* INFO
    @product     : xbLib
    @component   : xbException
    @type        : class
    @description : Класс исключения
    @revision    : 2015-12-16 16:23:00
  */

  /* CLASS ~BEGIN @string : Лог */
  class xbException extends Exception {
    protected $_data      = null;

    /* CLASS:CONSTRUCTOR */
    function __construct() {
      $input = func_get_args();
      $type  = 'unknown';
      $msg   = 'unknown';
      if (count($input) > 0) $type = array_shift($input);
      if (count($input) > 0) $msg  = array_shift($input);

      $this->_data = array('type' => $type,'message' => $msg,'data' => $input,'stack' => array());
      parent::__construct($this->_data['message']);

      $this->_data['stack'] = $this->getTrace();
      $_ = $this->_data['stack'][0];
      $this->_data['file'] = isset($_['file']) ? $_['file'] : '';
      $this->_data['line'] = isset($_['line']) ? $_['line'] : -1;
    }

    /******** ПУБЛИЧНЫЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:METHOD
      @name        : getData
      @description : Вернуть данные

      @param : array
    */
    public function getData() { return $this->_data; }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>