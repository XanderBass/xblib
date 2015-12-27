<?php
  /* INFO
    @product     : xbParser
    @component   : xbParserQuadBracesDatae
    @type        : class
    @description : Обработчик DataE
    @revision    : 2015-12-27 19:37:00
  */

  /* CLASS ~BEGIN */
  class xbParserQuadBracesDatae extends xbParserQuadBracesPrototype {
    protected $start  = '\{\!';
    protected $finish = '\!\}';

    public function parse($m) {
      $key   = $this->owner->parseStart($m);
      $args  = $this->owner->arguments[$this->owner->level];
      $value = $this->owner->variable($key);
      if ($value === false) {
        if (in_array('datae',$this->owner->notice)) return "<!-- not found: datae/$key -->";
        $value = '';
      }
      if (is_array($value)) {
        $tplt = 'chunk';
        if (isset($args['chunkType']))
          if (in_array($args['chunkType'],array('chunk','string','lib'))) $tplt = $args['chunkType'];
        $tpli = '<li><span class="key">[+key+]</span><span class="value">[+value+]</span></li>';
        if (isset($args['chunk']))
          if ($fn = $this->owner->search('chunk',$args['chunk']))
            switch ($tplt) {
              case 'string': $tpli = '{('.$args['chunk'].' [+arguments+])}'; break;
              case 'lib'   : $tpli = '{<'.$args['chunk'].' [+arguments+]>}'; break;
              default      : $tpli = '{{'.$args['chunk'].' [+arguments+]}}'; break;
            }
        $LK = false;
        if (isset($args['langKeys']))   $LK = xbParser::bool($args['langKeys']);
        $LP = '';
        if (isset($args['langPrefix'])) $LP = $args['langPrefix'];
        $v = '';
        foreach ($value as $dataKey => $dataVal) {
          $DK = $LK ? "[%".(!empty($LP) ? $LP."." : "")."$dataKey%]" : $dataKey;
          if (is_array($dataVal)) {
            $_ = array();
            foreach ($dataVal as $dvKey => $dvVal) $_[] = "&".$dvKey."=`$dvVal`";
            $_[] = '&'.$key.".datakey=`$DK`";
            $v.= str_replace(array(
              '[+key+]','[+value+]','[+arguments+]'
            ),array(
              $DK,strval($dataVal),implode(' ',$_)
            ),$tpli);
          } else {
            $_ = strval($dataVal);
            $v.= str_replace(array(
              '[+key+]','[+value+]','[+arguments+]'
            ),array(
              $DK,$_,"&key=`$DK` &value=`$_`"
            ),$tpli);
          }
        }
      } else { $v = strval($value); }
      return $this->owner->parseFinish($m,'datae',$key,$v);
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>