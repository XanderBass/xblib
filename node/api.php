<?php
  /* INFO
    @product     : xbNode
    @component   : xbNodeAPI
    @type        : class
    @description : Класс API
    @revision    : 2015-12-16 17:53:00
  */

  /* CLASS ~BEGIN */
  /**
   * Class xbNodeAPI
   * @property-read array $exports
   */
  class xbNodeAPI extends xbNodePrototype {
    protected $_exports = null;

    /* CLASS:CONSTRUCTOR */
    function __construct($owner) {
      parent::__construct($owner);
      if (is_object($this->_owner)) if (method_exists($this->_owner,'registerMethod')) {
        if (is_array($this->_exports)) {
          foreach ($this->_exports as $method)
            $this->owner->registerMethod($method,array($this,$method));
        } else {
          $methods = get_class_methods($this);
          foreach ($methods as $method)
            if (!in_array($method,array('error','load','remove')))
              if (preg_match('/^([[:alpha:]])([[:alpha:]\d])$/',$method))
                $this->owner->registerMethod($method,array($this,$method));
        }
      }
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>