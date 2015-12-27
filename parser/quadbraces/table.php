<?php
  /* INFO
    @product     : xbParser
    @component   : xbParserQuadBracesTable
    @type        : class
    @description : Обработчик табличной информации
    @revision    : 2015-12-27 19:41:00
  */

  /* CLASS ~BEGIN */
  class xbParserQuadBracesTable extends xbParserQuadBracesPrototype {
    protected $start  = '\{\[';
    protected $finish = '\]\}';

    public function parse($m) {
      $key   = $this->owner->parseStart($m);
      $args  = $this->owner->arguments[$this->owner->level];
      $value = $this->owner->variable($key);
      if ($value === false) {
        if (in_array('table',$this->owner->notice)) return "<!-- not found: table/$key -->";
        $value = '';
      }
      if (is_array($value)) {
        $v = '';
        // Параметры обработки
        $LK = false; $LP = ''; $FL = array();
        if (isset($args['langKeys']))   $LK = xbParser::bool($args['langKeys']);
        if (isset($args['langPrefix'])) $LP = $args['langPrefix'];

        if (!isset($args['fields'])) {
          foreach ($value as $dataRow)
            if (is_array($dataRow))
              foreach ($dataRow as $fKey => $fVal)
                if (!in_array($fKey,$FL)) $FL[] = $fKey;
        } else { $FL = explode(',',$args['fields']); }

        $tpls = $this->owner->getTemplates($args,array(
          'table'    => '<table cellpadding="0" cellspacing="0"'.' border="0">[+content+]</table>',
          'heap'     => '<theap>[+rows+]</theap>',
          'heaprow'  => '<tr>[+cells+]</tr>',
          'heapcell' => '<th class="field-[+key+]">[+value+]</th>',
          'body'     => '<tbody>[+rows+]</tbody>',
          'bodyrow'  => '<tr data-id="[+id+]">[+cells+]</tr>',
          'bodycell' => '<td class="field-[+key+]">[+value+]</td>',
          'foot'     => '',
          'footrow'  => '<tr>[+cells+]</tr>',
          'footcell' => '<th class="field-[+key+]">[+value+]</th>'
        ));

        $tplsC = array(
          array("#\[\+(key)((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)\+\]#si",''),
          array("#\[\+(value)((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)\+\]#si",'')
        );

        $tplsR = array(
          array("#\[\+(id)((:?\:([\w\-\.]+)((=`([^`]*)`))?)*)\+\]#si",''),
        );
        // Шапка
        if (!empty($tpls['heap'])) {
          $row = array();
          foreach ($FL as $fKey) {
            $tplsC[0][1] = $fKey;
            $tplsC[1][1] = $LK ? "[%".(!empty($LP) ? $LP."." : "")."$fKey%]" : $fKey;;
            $row[]       = $this->owner->iteration($tplsC,$tpls['heapcell']);
          }
          $row = implode('',$row);
          $row = str_replace('[+cells+]',$row,$tpls['heaprow']);
          $v  .= str_replace('[+rows+]',$row,$tpls['heap']);
        }
        // Значения
        $rows = array();
        foreach ($value as $dataID => $dataRow) {
          if (is_array($dataRow)) {
            $tplsR[0][1] = $dataID;
            $row = array();
            foreach ($FL as $fKey) {
              $tplsC[0][1] = $fKey;
              $tplsC[1][1] = isset($dataRow[$fKey]) ? $dataRow[$fKey] : '';
              $row[]       = $this->owner->iteration($tplsC,$tpls['bodycell']);
            }
            $row = implode('',$row);
            $row = str_replace('[+cells+]',$row,$tpls['bodyrow']);
            $row = $this->owner->iteration($tplsR,$row);
            $rows[] = $row;
          }
        }
        $v.= str_replace('[+rows+]',implode('',$rows),$tpls['body']);
        // Подвал
        if (!empty($tpls['foot'])) {
          $row = array();
          foreach ($FL as $fKey) {
            $tplsC[0][1] = $fKey;
            $tplsC[1][1] = $LK ? "[%".(!empty($LP) ? $LP."." : "")."$fKey%]" : $fKey;;
            $row[]       = $this->owner->iteration($tplsC,$tpls['footcell']);
          }
          $row = implode('',$row);
          $row = str_replace('[+cells+]',$row,$tpls['footrow']);
          $v  .= str_replace('[+rows+]',$row,$tpls['foot']);
        }
        // Итог
        $v = str_replace('[+content+]',$v,$tpls['table']);
      } else { $v = strval($value); }
      return $this->owner->parseFinish($m,'table',$key,$v);
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>