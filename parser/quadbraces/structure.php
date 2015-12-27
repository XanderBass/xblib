<?php
  /* INFO
    @product     : xbParser
    @component   : xbParserQuadBracesStructure
    @type        : class
    @description : Обработчик структурной информации
    @revision    : 2015-12-27 19:40:00
  */

  /* CLASS ~BEGIN */
  class xbParserQuadBracesStructure extends xbParserQuadBracesPrototype {
    protected $start  = '\{\~';
    protected $finish = '\~\}';

    public function parse($m) {
      $key       = $this->owner->parseStart($m);
      $arguments = $this->owner->arguments[$this->owner->level];
      $value     = $this->owner->variable($key);
      if ($value === false) {
        if (in_array('structure',$this->owner->notice)) return "<!-- not found: structure/$key -->";
        $value = '';
      }
      if (is_array($value)) {
        // Параметры обработки
        $FL = isset($arguments['fields']) ? explode(',',$arguments['fields']) : array();

        $tpls = $this->owner->getTemplates($arguments,array(
          'outer' => '<ul>[+items+]</ul>',
          'item'  => '<li><span class="key">[+key+]</span><span>[+value+]</span></li>'
        ));

        $args = array();
        foreach ($arguments as $aKey => $aVal) $args[] = "&$aKey=`$aVal`";
        $args = implode(' ',$args);
        if (!empty($args)) $args = " $args";

        $tplsC = array(
          array("#\[\+(key)((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)\+\]#si",''),
          array("#\[\+(value)((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)\+\]#si",'')
        );

        // Значения
        $rows = array();
        foreach ($value as $dataKey => $dataVal) if (empty($FL) || in_array($dataKey,$FL)) {
          $tplsC[0][1] = $dataKey;
          $tplsC[1][1] = is_array($dataVal) ? "{~$key.$dataKey"."$args~}" : strval($dataVal);
          $rows[] = $this->owner->iteration($tplsC,$tpls['item']);
        }

        $v = str_replace('[+items+]',implode('',$rows),$tpls['outer']);
      } else { $v = strval($value); }
      return $this->owner->parseFinish($m,'structure',$key,$v);
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>