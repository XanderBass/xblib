<?php
  /* INFO
    @product     : xbLib
    @component   : xbSession
    @type        : class
    @description : Класс сессии
    @revision    : 2015-11-14 14:40:00
  */

  /* CLASS ~BEGIN */
  /**
   * Class xbSession
   * @property-read string $IPv4
   * @property-read string $IPv6
   * @property-read string $IPType
   * @property-read string $IP
   * @property-read string $status
   * @property-read bool   $ready
   *
   * @property-read string $server
   * @property      string $method
   * @property-read string $key
   * @property-read string $name
   *
   * @property-read array  $data
   *
   * @property-read string $token
   * @property-read string $tokenKey
   * @property-read string $tokenGot
   * @property-read bool   $tokenValid
   */
  class xbSession {
    protected static $_time  = 86400;
    protected static $_https = false;
    protected static $_cd    = true;
    protected static $_host  = '';
    protected static $_em    = '';
    protected static $_tk    = 'formtoken';
    protected static $_lat   = 0;
    protected static $_pref  = '';
    protected static $_key   = 'cms';

    protected $_ready = false;

    protected $_IPv4   = null;
    protected $_IPv6   = null;
    protected $_IPType = '';
    protected $_IP     = '';
    protected $_status = 'init';

    protected $_server = '';
    protected $_method = '';
    protected $_name   = '';

    protected $_data    = null;

    protected $_token      = null;
    protected $_tokenGot   = null;
    protected $_tokenValid = false;

    /******** ОБЩИЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:CONSTRUCT */
    function __construct($prefix='cms') {
      $this->_UA = self::userAgent();
      $this->_getip();
      // Server name
      $this->_status = 'settings';
      $C = strtoupper(self::$_key.'_config_cookie_host');
      if (defined($C)) self::host(constant($C));
      if (empty(self::$_host)) {
        $this->_server = $_SERVER['SERVER_NAME'];
        if (preg_match('|^www\.(.+)$|si',$this->_server))
          $this->_server = preg_replace('|^www\.(.+)$|si','\1',$this->_server);
      } else { $this->_server = self::$_host; }
      // Ключ сессии
      $C = strtoupper(self::$_key.'_config_cookie_expires');
      self::time(intval(defined($C) ? constant($C) : 86400));
      $C = strtoupper(self::$_key.'_config_cookie_https');
      if (defined($C)) self::https(constant($C));
      $C = strtoupper(self::$_key.'_config_cross_domain');
      if (defined($C)) self::crossDomain(constant($C));
      // Имя сессии
      $this->_name = $prefix.'session';
      $C = strtoupper(self::$_key.'_config_session_name');
      if (defined($C)) {
        $C = constant($C);
        if (ctype_alnum($C)) $this->_name = $C;
      }
      $this->_name = $this->_encrypt($this->_name);
      // Инициализация
      $this->_ready = true;
      $this->_start();
      $this->_gettoken();
      $this->_getData();
      $this->_correct();
    }

    /* CLASS:GET */
    function __get($n) {
      switch ($n) {
        case 'tokenValid': return ($this->_tokenValid == 'ok');
        case 'tokenKey'  : return self::$_tk;
        case 'ready'     : return ($this->_status == 'ok') && $this->_ready;
      }
      $N = "_$n";
      return property_exists($this,$N) ? $this->$N : false;
    }

    /******** ВНУТРЕННИЕ МЕТОДЫ КЛАССА ********/
    protected function _getip() {
      $A = array(
        4 => '/^(?:(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d{1,2})$/',
        6 => '/^(?:[A-F\d]{1,4}:){7}[A-F\d]{1,4}$/si'
      );
      foreach ($A as $Ai => $Ar) {
        if (preg_match($Ar,$_SERVER['REMOTE_ADDR'])) {
          $N = "_IPv".$Ai;
          $this->$N      = $_SERVER['REMOTE_ADDR'];
          $this->_IP     = $this->$N;
          $this->_IPType = 'IPv'.$Ai;
        }
      }
    }

    /* CLASS:INTERNAL
      @name        : encrypt
      @description : Шифрование
    */
    protected function _encrypt($value) {
      switch (self::$_em) {
        // TODO: encrypter
        default: return $value;
      }
    }

    /* CLASS:INTERNAL
      @name        : correct
      @description : Корректирование сессии

      @return : array
    */
    protected function _correct() {
      $def = array(
        'id'       => 0,
        'sid'      => '',
        'user'     => 0,
        'last'     => null,
        'ipv4'     => $this->_IPv4,
        'ipv6'     => $this->_IPv6,
        'agent'    => $this->_UA,
        'lc'       => 0,
        'bu'       => null,
        'renew'    => true,
        'continue' => true
      );
      foreach ($def as $k => $d) {
        if (!isset($this->_data[$k])) $this->_data[$k] = $d;
        if (is_int($d))  $this->_data[$k] = intval($this->_data[$k]);
        if (is_bool($d)) $this->_data[$k] = self::bool($this->_data[$k]);
      }
      return $this->_data;
    }

    /* CLASS:INTERNAL
      @name        : _start
      @description : Старт сессии
    */
    protected function _start() {
      $this->_status = 'start';
      session_name($this->_name);
      session_start();
      $sid = session_id();
      if (!ctype_alnum($sid)) session_regenerate_id(true);
      $this->cookie('','');
      if (!isset($_SESSION[self::$_key]))    $_SESSION[self::$_key] = array();
      if (!is_array($_SESSION[self::$_key])) $_SESSION[self::$_key] = array();
      $this->_data = array(
        'sid'      => session_id(),
        'renew'    => true,
        'continue' => false
      );
      $this->_status = 'ok';
    }

    /* CLASS:INTERNAL
      @name        : _gettoken
      @description : Получить токен
    */
    protected function _gettoken() {
      $this->_token = $this->variable(self::$_tk);
      if ($this->_token) {
        if (preg_match('/^([a-zA-Z0-9]{32})$/si',$this->_token)) {

        } else { $this->variable(self::$_tk,self::keygen()); }
      } else { $this->variable(self::$_tk,self::keygen()); }
      // Получение переменной из запроса
      $this->_tokenGot = isset($_POST[self::$_tk]) ? $_POST[self::$_tk] : null;
      if (!is_null($this->_tokenGot)) {
        $this->_tokenGot.= '';
        if (preg_match('/^([a-zA-Z0-9]{32})$/si',$this->_tokenGot)) {
          if ($this->_tokenGot != $this->_token) {
            $this->_tokenValid = 'notequal';
            $this->_status = 'csrf_token';
          } else {
            $this->_tokenValid = 'ok';
            $this->variable(self::$_tk,self::keygen());
          }
        } else {
          $this->_tokenValid = 'incorrect';
          $this->_tokenGot = '';
          $this->_status = 'csrf_token';
        }
      } else { $this->_tokenValid = 'notset'; }
    }

    /* CLASS:INTERNAL
      @name        : _getdata
      @description : Загрузка данных
    */
    protected function _getData() { return $this->_data; }

    /******** ПУБЛИЧНЫЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:METHOD
      @name        : cookie
      @description : Установка или чтение куки

      @param : $name  | string | value |       | Имя куки
      @param : $value |        | value | @NULL | Новое значение

      @return : ?
    */
    public function cookie($name='',$value=null) {
      $SN = $this->_name.(!empty($name) ? "_$name" : '');
      if (!is_null($value)) {
        $CD = self::$_cd ? '.' : '';
        if (setcookie(
          $SN, !empty($name) ? $value : session_id(),
          self::expires(),
          '/',$CD.$this->_server,self::$_https,
          true
        )) {
          $_COOKIE[$SN] = !empty($name) ? $value : session_id();
          return true;
        }
      };
      if (isset($_COOKIE[$SN])) return $_COOKIE[$SN];
      return false;
    }

    /* CLASS:METHOD
      @name        : variable
      @description : Старт сессии

      @param : $path  | string | value |       | Путь
      @param : $value |        | value | @NULL | Данные

      @return : ?
    */
    public function variable($path,$value=null) {
      if (!is_null($value)) {
        $P   = is_array($path) ? $path : explode('/',$path);
        if (count($P) < 1) return false;
        $tmp = &$_SESSION[self::$_key];
        foreach ($P as $key) {
          if (!isset($tmp[$key]) || !is_array($tmp[$key])) $tmp[$key] = array();
          $tmp = &$tmp[$key];
        }
        $tmp = $value;
        unset($tmp);
        return $value;
      } else {
        $P = is_array($path) ? $path : explode('/',$path);
        $V = $_SESSION[self::$_key];
        foreach ($P as $i) if (isset($V[$i])) {
          $V = $V[$i];
        } else { return false; }
        return $V;
      }
    }

    /******** БИБЛИОТЕЧНЫЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:STATIC
      @name        : userAgent
      @description : Вернуть текущее значение IPv4

      @return : string
    */
    public static function userAgent() {
      if (!isset($_SERVER['HTTP_USER_AGENT'])) return '';
      $ret = base64_encode($_SERVER['HTTP_USER_AGENT']);
      if (strlen($ret) > 255) return '';
      return $ret;
    }

    /* CLASS:STATIC
      @name        : key
      @description : Префикс СУБД

      @param : $v | string | value | @NULL | Новое значение

      @return : int
    */
    public static function key($v=null) { if (!is_null($v)) self::$_key = $v; return self::$_key; }

    /* CLASS:STATIC
      @name        : time
      @description : Время жизни куки

      @param : $v | int | value | @NULL | Новое значение

      @return : int
    */
    public static function time($v=null) { if (!is_null($v)) self::$_time = intval($v); return self::$_time; }

    /* CLASS:STATIC
      @name        : https
      @description : Время жизни куки

      @param : $v | int | value | @NULL | Новое значение

      @return : int
    */
    public static function https($v=null) { if (!is_null($v)) self::$_https = self::bool($v); return self::$_https; }

    /* CLASS:STATIC
      @name        : crossDomain
      @description : Время жизни куки

      @param : $v | int | value | @NULL | Новое значение

      @return : int
    */
    public static function crossDomain($v=null) { if (!is_null($v)) self::$_cd = self::bool($v); return self::$_cd; }

    /* CLASS:STATIC
      @name        : encryptMethod
      @description : Метод шифрования идентификатора сессии

      @param : $v | int | value | @NULL | Новое значение

      @return : int
    */
    public static function encryptMethod($v=null) {
      if (!is_null($v)) {
        if (in_array($v,array())) {
          self::$_em = $v; // TODO
        }
      }
      return self::$_em;
    }

    /* CLASS:STATIC
      @name        : tokenKey
      @description : Метод шифрования идентификатора сессии

      @param : $v | int | value | @NULL | Новое значение

      @return : int
    */
    public static function tokenKey($v=null) { if (!is_null($v)) self::$_tk = $v; return self::$_tk; }

    /* CLASS:STATIC
      @name        : LAT
      @description : Количество попыток входа

      @param : $v | int | value | @NULL | Новое значение

      @return : int
    */
    public static function LAT($v=null) { if (!is_null($v)) self::$_lat = intval($v); return self::$_lat; }

    /* CLASS:STATIC
      @name        : prefix
      @description : Префикс СУБД

      @param : $v | string | value | @NULL | Новое значение

      @return : int
    */
    public static function prefix($v=null) { if (!is_null($v)) self::$_pref = $v; return self::$_pref; }

    /* CLASS:STATIC
      @name        : host
      @description : Префикс СУБД

      @param : $v | string | value | @NULL | Новое значение

      @return : int
    */
    public static function host($v=null) { if (!is_null($v)) self::$_host = $v; return self::$_host; }

    /* CLASS:STATIC
      @name        : expires
      @description : Время истечения куки

      @return : int
    */
    public static function expires() { return (time()+self::$_time); }

    /* CLASS:STATIC
      @name        : key
      @description : Генерирует строку случайных символов

      @param : $c | integer | value | 32 | Количество символов

      @return : string | Сгенерированная строка
    */
    public static function keygen($c=32) {
      $s = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
      $R = '';
      for($_ = 0; $_ < $c; $_++) $R.= $s[mt_rand(0,61)];
      return $R;
    }

    /* CLASS:STATIC
      @name        : bool
      @description : Получение булева значения

      @param : $v | | value | | Значение

      @return : boolean | Булево представление значения
    */
    public static function bool($v) { return ((strval($v)=='true')||($v===true)||(intval($v)>0)); }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>