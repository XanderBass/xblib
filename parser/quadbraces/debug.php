<?php
  /* INFO
    @product     : xbParser
    @component   : xbParserQuadBracesDebug
    @type        : class
    @description : Обработчик отладочной информации
    @revision    : 2015-12-27 19:38:00
  */

  /* CLASS ~BEGIN */
  class xbParserQuadBracesDebug extends xbParserQuadBracesPrototype {
    protected $start  = '\[\^';
    protected $finish = '\^\]';

    public function parse($m) {
      $key = $this->owner->parseStart($m);
      $dd = explode('.',$key);
      $dk = $dd[0];
      $KP = $this->owner->prefix;

      $atm = array('ms' => 1000,'us' => 1000000,'ns' => 1000000000);

      switch ($dk) {
        case 'memory':
        case 'time':
          $v = $this->owner->debug['time'];
          if (count($dd) > 1) if (array_key_exists($dd[1],$atm)) $v *= $atm[$dd[1]];
          $v = strval(round($v,2));
          break;
        case 'totaltime':
          $v = "<!-- $KP:TOTALTIME";
          if (count($dd) > 1) if (array_key_exists($dd[1],$atm)) $v.= ' '.$dd[1];
          $v.= " -->";
          break;
        case 'log'        : $v = "<!-- $KP:LOG -->"; break;
        case 'logstatus'  : $v = "<!-- $KP:LOGSTATUS -->"; break;
        case 'queries'    : $v = "<!-- $KP:QUERYCOUNT -->"; break;
        case 'timepoints' : $v = "<!-- $KP:TIMEPOINTS -->"; break;
        case 'querypoints': $v = "<!-- $KP:QUERYPOINTS -->"; break;
        default: $v = '';
      }

      if (empty($v)) {
        if (!isset($this->owner->debug[$key])) {
          if (in_array('debug',$this->owner->notice)) return "<!-- not found: debug/$key -->";
          $v = '';
        } else { $v = $this->owner->debug[$key]; }
      }

      return $this->owner->parseFinish($m,'debug',$key,$v);
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>