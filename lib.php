<?php
  /* INFO
    @product     : xbLib
    @component   : xbLib
    @type        : clibrary
    @description : Основная библиотека
    @revision    : 2015-12-27 21:04:00
  */

  /* LIBRARY ~BEGIN */
  class xbLib {
    protected static $_roles = null;

    /* **************** ФУНКЦИИ ДЛЯ РАБОТЫ С MIME-типами **************** */
    /* LIBRARY:FUNCTION
      @name        : MIMETypes
      @description : MIME-типы
      @comment     : Данная функция является скорее основой стандарта хранения
                     MIME-типов в источниках данных. При первом вызове функция
                     инициализирует соответствующие переменные статического кэша.

      @param : $d | array | value | @NULL | Пользовательские MIME-типы

      @return : string | Найденный файл
    */
    public static function MIMETypes($d=null) {
      static $cache = null;
      // Инициализация статического кэша реестра
      if (is_null($cache)) {
        $cache = array(
          'user' => array(),
          'text' => array(
            'txt'   => 'text/plain',
            'html'  => 'text/html',
            'htm'   => 'text/html',
            'tpl'   => 'text/html',
            'css'   => 'text/css',
            'theme' => 'text/css',
            'js'    => 'text/javascript',
            'json'  => 'application/json',
            'xml'   => 'text/xml'
          ),
          'images' => array(
            'png'  => 'image/png',
            'jpe'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'ico'  => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif'  => 'image/tiff',
            'psd'  => 'image/vnd.adobe.photoshop',
            'svg'  => 'image/svg+xml',
            'svgz' => 'image/svg+xml'
          ),
          'executable' => array(
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload'
          ),
          'media' => array(
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            'mp3' => 'audio/mpeg',
            'qt'  => 'video/quicktime',
            'mov' => 'video/quicktime'
          ),
          'files' => array(
            'zip'  => 'application/zip',
            'rar'  => 'application/x-rar-compressed',
            'cab'  => 'application/vnd.ms-cab-compressed',
            'pdf'  => 'application/pdf',
            'ai'   => 'application/postscript',
            'eps'  => 'application/postscript',
            'ps'   => 'application/postscript',
            'doc'  => 'application/msword',
            'rtf'  => 'application/rtf',
            'xls'  => 'application/vnd.ms-excel',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'odt'  => 'application/vnd.oasis.opendocument.text',
            'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
            'eot'  => 'application/octet-stream',
            'ttf'  => 'application/octet-stream',
            'woff' => 'application/octet-stream'
          )
        );
      }
      // Пользовательские типы
      if (is_array($d)) {
        foreach ($d as $k => $v) $cache['user'][$k] = $v;
        self::MIMEIndexes(true);
        self::MIMEByExt('_',true);
      }
      return $cache;
    }

    /* LIBRARY:FUNCTION
      @name        : MIMEIndexes
      @description : MIME-индексы

      @param : $refresh | bool | value | @FALSE | Обновить кеш

      @return : array
    */
    public static function MIMEIndexes($refresh=false) {
      static $cache = null;
      if (is_null($cache) || $refresh) {
        $mimes = self::MIMETypes();
        $cache = array();
        $mimeC = 0;
        foreach ($mimes as $cat) {
          $mimeI  = $mimeC;
          foreach ($cat as $mime) {
            if (!in_array($mime,$cache)) {
              $cache[$mimeI] = $mime;
              $mimeI++;
            }
          }
          $mimeC += (1 << 8);
        }
      }
      return $cache;
    }

    /* LIBRARY:FUNCTION
      @name        : MIMEByExt
      @description : MIME-тип по расширению

      @param : $value   | string | value |        | Расширение
      @param : $refresh | bool   | value | @FALSE | Обновить кеш

      @return : string
    */
    public static function MIMEByExt($value,$refresh=false) {
      static $cache = null;
      if (is_null($cache) || $refresh) {
        $mimes = self::MIMETypes();
        foreach ($mimes as $cat)
          foreach ($cat as $ext => $mime) $cache[$ext] = $mime;
      }
      return isset($cache[$value]) ? $cache[$value] : false;
    }

    /* LIBRARY:FUNCTION
      @name        : MIMEByIndex
      @description : MIME-тип по индексу

      @param : $value   | string | value |        | Расширение
      @param : $refresh | bool   | value | @FALSE | Обновить кеш

      @return : string
    */
    public static function MIMEByIndex($value,$refresh=false) {
      $indexes = self::MIMEIndexes($refresh);
      return isset($indexes[$value]) ? $indexes[$value] : false;
    }

    /* LIBRARY:FUNCTION
      @name        : MIMEIndex
      @description : Индекс MIME-типа

      @param : $value   | string | value |        | Расширение
      @param : $refresh | bool   | value | @FALSE | Обновить кеш

      @return : int
    */
    public static function MIMEIndex($value,$refresh=false) {
      $indexes = self::MIMEIndexes($refresh);
      if ($i = array_search($value,$indexes)) return $i;
      return false;
    }

    /* **************** ФУНКЦИИ ДЛЯ РАБОТЫ С ФАЙЛАМИ **************** */
    /* LIBRARY:FUNCTION
      @name        : correctPath
      @description : Корректировать путь

      @param : $path | string | value | | Путь

      @return : string
    */
    public static function correctPath($path) {
      return rtrim(implode(DIRECTORY_SEPARATOR,explode('/',$path)),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    /* LIBRARY:FUNCTION
      @name        : correctDir
      @description : Корректировать путь

      @param : $path | string | value | | Путь

      @return : string
    */
    public static function correctDir($path) {
      return rtrim(implode('/',explode(DIRECTORY_SEPARATOR,$path)),'/').'/';
    }

    /* LIBRARY:FUNCTION
      @name        : search
      @description : Ищет файлы по маске

      @param : $q       | string | value |        | Запрос
      @param : $paths   | array  | value | @NULL  | Массив корневых директорий
      @param : $replace | bool   | value | @FALSE | Заменять одинаковые базовые имена

      @return : array | Найденные файлы
    */
    public static function searchFiles($q,$paths=null,$replace=false) {
      $ret   = array();
      $names = array();
      // Пути
      $P = array();
      if (is_array($paths))  $P = $paths;
      if (is_string($paths)) $P = explode(',',$paths);
      if (empty($P))         $P = array('');
      // Сканирование
      foreach ($P as $path) {
        $F = self::correctPath($path.$q);
        if ($_ = glob($F)) foreach ($_ as $fn) if (is_file($fn)) {
          $nn = basename($fn);
          if ($replace) {
            if (in_array($nn,$names)) {
              $i = array_search($nn,$names);
              $ret[$i] = $fn;
            } else {
              $ret[]   = $fn;
              $names[] = $nn;
            }
          } else { $ret[] = $fn; }
        }
      }
      // Очистка и возврат
      unset($names);
      return $ret;
    }

    /* LIBRARY:FUNCTION
      @name        : getFiles
      @description : Вывод файлов c выводом типа

      @param : $q       | string | value |        | Запрос
      @param : $paths   | array  | value | @NULL  | Массив корневых директорий
      @param : $replace | bool   | value | @FALSE | Заменять одинаковые базовые имена

      @return : array | Найденные файлы
    */
    public static function getFiles($q,$paths=null,$replace=false) {
      if (!empty($q)) {
        $qb = basename($q);
        $rt = array(
          'content-category' => '',
          'content-type'     => '',
          'extension'        => '',
          'files'            => array()
        );
        // Ищем расширение и информацию по нему
        $qr = '|^(.+)\.([[:alpha:]]+)$|si';
        if (preg_match($qr,$qb)) {
          $ext = preg_replace($qr,'\2',$qb);
          $mimes = self::MIMETypes();
          foreach ($mimes as $mcn => $mc) {
            if (isset($mc[$ext])) {
              $rt['content-category'] = $mcn;
              $rt['content-type']     = $mc[$ext];
              $rt['extension']        = $ext;
              break;
            }
          }
        }
        // Ищем файлы
        if (!empty($ret['content-category']))
          if ($_ = self::searchFiles($q,$paths,$replace)) {
            $ret['files'] = $_;
            return $ret;
          }
      }
      return false;
    }

    /* LIBRARY:FUNCTION
      @name        : ghostFile
      @description : Имя "теневого" файла

      @param : $ext | string | value | txt | Расширение

      @return : string | Имя файла
    */
    public static function ghostFile($ext='txt') {
      $fn = dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR
          . basename($_SERVER['SCRIPT_FILENAME'],'.php').".$ext";
      return $fn;
    }

    /* **************** ФУНКЦИИ ДЛЯ РАБОТЫ С МАССИВАМИ **************** */
    /* LIBRARY:FUNCTION
      @name        : getByPath
      @description : Получить значение по пути

      @param : $path |       | value | | Путь
      @param : $A    | array | value | | Входной массив

      @return : ?
    */
    public static function getByPath($path,$input,$splitter='/') {
      $P = is_array($path) ? $path : explode($splitter,$path);
      $V = $input;
      foreach ($P as $i) if (isset($V[$i])) {
        $V = $V[$i];
      } else { return false; }
      return $V;
    }

    /* LIBRARY:FUNCTION
      @name        : setByPath
      @description : Установить значение по пути

      @param : $path  |       | value |   | Путь
      @param : $input | array | value |   | Входной массив
      @param : $value |       | value |   | Значение

      @return : ?
    */
    public static function setByPath($path,$input,$value,$splitter='/') {
      $P   = is_array($path) ? $path : explode($splitter,$path);
      if (count($P) < 1) return false;
      $A   = is_array($input) ? $input : array();
      $tmp = &$A;
      foreach ($P as $key) {
        if (!isset($tmp[$key]) || !is_array($tmp[$key])) $tmp[$key] = array();
        $tmp = &$tmp[$key];
      }
      $tmp = $value;
      unset($tmp);
      return $A;
    }

    /* LIBRARY:FUNCTION
      @name        : mergeArrays
      @description : Слить массив со значением по умолчанию

      @param : $def   | array | value | | Массив по умолчанию
      @param : $input | array | value | | Входной массив

      @return : array
    */
    public static function mergeArrays($def,$input) {
      $out = array();
      foreach ($def as $k => $v) {
        $s = isset($input[$k]) ? $input[$k] : $v;
        $out[$k] = is_array($v) ? self::mergeArrays(
          $v,(is_array($input[$k]) ? $input[$k] : array())
        ) : (is_array($input[$k]) ? $v : (is_int($v) ? intval($s) : $s));
      }
      return $out;
    }

    /* LIBRARY:FUNCTION
      @name        : correctArray
      @description : Корректировка массива

      @param : $v |        | value |        | Input value
      @param : $s |        | value | @FALSE | Supported values
      @param : $e | string | value | ,      | Splitter

      @return : array
    */
    public static function correctArray($v,$s=false,$e=',') {
      $_ = is_array($v) ? $v : explode($e,$v);
      if (is_array($s)) {
        $_v = array();
        foreach ($_ as $i) if (in_array($i,$s)) $_v[] = $i;
        return $_v;
      } else { return $_; }
    }

    /* **************** ПРОЧЕЕ **************** */
    /* LIBRARY:FUNCTION
      @name        : maskedValue
      @description : Установка значения с маской

      @param : $v | int | value |   | Значение с маской
      @param : $p | int | value |   | Исходное значение
      @param : $d | int | value | 4 | Длина значения и маски

      @return : int
    */
    public static function maskedValue($v,$p=0,$d=4) {
      $a = 0; for ($_ = 0; $_ < $d; $_++) $a += 1 << $_;
      $m = (($v & 0xf0) >> $d);
      return ($p & ~$m | $v & $m);
    }

    /* LIBRARY:FUNCTION
      @name        : extractData
      @description : Извлечь метаданные

      @param : $tpl | string | value |       | Код шаблона
      @param : $def | array  | value | @NULL | Данные по умолчанию
      @param : $cln | string | value | @NULL | Имя класса

      @return : array | массив:
                        элемент body содержит очищенный шаблон,
                        элемент data содержит извлечённые данные
    */
    public static function extractData($tpl,$def=null,$cln='') {
      $_tpl = $tpl;
      $out  = array('data' => array(),'body' => '');
      $rex  = '#\<\!--(?:\s+)DATA'.(empty($cln)?'':'(?:\s+)'.$cln)
            . '\:\[+key+\](?:\s+)`([^\`]*)`(?:\s+)--\>#si';
      if (is_array($def)) {
        foreach ($def as $key => $val) {
          $t = str_replace('[+key+]',strtolower($key),$rex);
          $out['data'][$key] = $val;
          if ($r = preg_match_all($t,$_tpl,$_,PREG_PATTERN_ORDER)) {
            $out['data'][$key] = $_[1][0];
            $_tpl = preg_replace($t,'',$_tpl);
          }
        }
      } else {
        $rex = str_replace('[+key+]','([\w\-\.]+)',$rex);
        $out['rex'] = $rex;
        if (preg_match_all($rex,$_tpl,$am,PREG_SET_ORDER))
          foreach ($am as $d) $out['data'][$d[1]] = $d[2];
      }
      $out['body'] = $_tpl;
      return $out;
    }

    /* CLASS:STATIC
      @name        : key
      @description : Генерирует строку случайных символов

      @param : $c | integer | value | 32 | Количество символов

      @return : string | Сгенерированная строка
    */
    public static function key($c=32) {
      $R = ''; $s = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
      for($_ = 0; $_ < $c; $_++) $R.= $s[mt_rand(0,61)];
      return $R;
    }

    /* LIBRARY:FUNCTION
      @name        : requestVar
      @description : Получение переменной из массивов GET или POST

      @param : $key | string | value |       | Ключ
      @param : $def |        | value | false | Значение по умолчанию, если переменной нет в массивах
      @param : $udd | bool   | value | true  | Декодировать значение

      @return : ? | Значение переменной
    */
    public static function requestVar($key,$def=false,$udd=null) {
      $_  = $def;
      $ud = $udd;
      if (isset($_POST[$key])) {
        if (is_null($udd)) $ud = false;
        $_ = $ud ? urldecode($_POST[$key]) : $_POST[$key];
      } elseif (isset($_GET[$key])) {
        if (is_null($udd)) $ud = true;
        $_ = $ud ? urldecode($_GET[$key]) : $_GET[$key];
      }
      return $_;
    }

    /* **************** СОВМЕСТИМОСТЬ **************** */
    /* LIBRARY:FUNCTION
      @name        : HTTPStatus
      @description : Строка статуса HTTP-запроса

      @param : $status | int | value | | Номер статуса

      @return : string
    */
    public static function HTTPStatus($status) {
      $statuses = array(
        // 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        // 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported'
      );
      if (isset($statuses[$status])) return $statuses[$status];
      return 'Unknown code '.intval($status);
    }

    /* CLASS:STATIC
      @name        : bool
      @description : Получение булева значения

      @param : $v | | value | | Значение

      @return : boolean | Булево представление значения
    */
    public static function bool($v) { return ((strval($v)=='true')||($v===true)||(intval($v)>0)); }
  }
  /* LIBRARY ~END */

  /* COMPAT ~BEGIN */
  /* @function ctype_word */
  if (!function_exists('ctype_word')) {
    function ctype_word($v) {
      return ctype_alnum(str_replace('_','',$v));
    }
  }

  /* @function http_responce_code */
  if (!function_exists('http_response_code')) {
    // Для версий до 5.4
    function http_response_code($code=null,$sendheader=true) {
      static $_code = null;
      static $_prot = null;

      if (is_null($_prot))
        $_prot = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
      if ($code !== null) {
        $_code = intval($code);
        $_text = xbLib::HTTPStatus($_code);
        if ($sendheader) header("$_prot $_code $_text");
      }
      return $_code;
    }
  }

  /* @function mime_content_type */
  if (!function_exists('mime_content_type')) {
    function mime_content_type($filename) {
      $ext = strtolower(array_pop(explode('.',$filename)));
      if ($ret = xbLib::MIMEByExt($ext)) return $ret;
      if (function_exists('finfo_open')) {
        $finfo    = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo,$filename);
        finfo_close($finfo);
        return $mimetype;
      } else { return 'application/octet-stream'; }
    }
  }
  /* COMPAT ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>