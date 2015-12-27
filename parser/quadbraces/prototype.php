<?php
  /* INFO
    @product     : xbParser
    @component   : xbParserQuadBracesPrototype
    @type        : class
    @description : Прототип кастомного обработчика тегов
    @revision    : 2015-12-27 19:36:00
  */

  /* CLASS ~BEGIN */
  /**
   * Class xbParserQuadBracesPrototype
   * @property xbParserQuadBraces $owner
   */
  class xbParserQuadBracesPrototype {
    protected $owner  = null;
    protected $ready  = false;
    protected $start  = '';
    protected $finish = '';

    /* CLASS:CONSTRUCT */
    function __construct($owner) {
      if ($owner instanceof xbParserQuadBraces) {
        $this->owner = $owner;
        $this->ready = true;
      }
    }

    /* CLASS:METHOD
      @name        : regexp
      @description : Регулярное выражение

      @return : string
    */
    public function regexp() { return xbParserLibQB::regexp($this->start,$this->finish); }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>