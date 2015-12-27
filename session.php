<?php
  /* INFO
    @product     : xbLib
    @component   : xbSession
    @type        : class
    @description : Класс сессии
    @revision    : 2015-12-27 21:06:00
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
   * @property-read string $name
   *
   * @property-read array  $def
   * @property-read array  $data
   *
   * @property-read string $token
   * @property-read string $tokenGot
   * @property-read bool   $tokenValid
   */
  class xbSession {
    protected static $_time  = 86400;
    protected static $_https = false;
    protected static $_cd    = true;
    protected static $_em    = '';

    public static $cookieHost = '';
    public static $key        = 'cms';
    public static $tokenKey   = 'formtoken';

    protected $_ready = false;

    protected $_IPv4   = null;
    protected $_IPv6   = null;
    protected $_IPType = '';
    protected $_IP     = '';
    protected $_status = 'init';

    protected $_server = '';
    protected $_name   = '';

    protected $_def     = null;
    protected $_data    = null;

    protected $_token      = null;
    protected $_tokenGot   = null;
    protected $_tokenValid = false;

    /******** ОБЩИЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:CONSTRUCT */
    function __construct($prefix='cms') {
      $this->_UA = self::userAgent();
      $this->_getip();
      // Определяем хост
      $this->_status = 'settings';
      $C = strtoupper(self::$key.'_config_cookie_host');
      if (defined($C)) self::$cookieHost = constant($C);
      if (empty(self::$cookieHost)) {
        $this->_server = $_SERVER['SERVER_NAME'];
        if (preg_match('|^www\.(.+)$|si',$this->_server))
          $this->_server = preg_replace('|^www\.(.+)$|si','\1',$this->_server);
      } else { $this->_server = self::$cookieHost; }
      // Настройки кук
      $C = strtoupper(self::$key.'_config_cookie_expires');
      self::time(intval(defined($C) ? constant($C) : 86400));
      $C = strtoupper(self::$key.'_config_cookie_https');
      if (defined($C)) self::https(constant($C));
      $C = strtoupper(self::$key.'_config_cross_domain');
      if (defined($C)) self::crossDomain(constant($C));
      // Имя сессии
      $this->_name = $prefix.'session';
      $C = strtoupper(self::$key.'_config_session_name');
      if (defined($C)) {
        $C = constant($C);
        if (ctype_alnum($C)) $this->_name = $C;
      }
      // Шифрование имени сессии. Задел на будущее
      $this->_name = $this->_encrypt($this->_name);
      // Инициализация
      $this->_ready = true;
      $this->_start();
      $this->_gettoken();
      $this->getData();
      $this->_correct();
    }

    /* CLASS:GET */
    function __get($n) {
      switch ($n) {
        case 'tokenValid': return ($this->_tokenValid == 'ok');
        case 'ready'     : return ($this->_status == 'ok') && $this->_ready;
      }
      $N = "_$n";
      return property_exists($this,$N) ? $this->$N : false;
    }

    /******** ВНУТРЕННИЕ МЕТОДЫ КЛАССА ********/
    /* TODO Шифрование (для имени сессии) */
    protected function _encrypt($value) {
      switch (self::$_em) {
        default: return $value;
      }
    }

    /* Получение IP-адреса */
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

    /* Корректирование сессии */
    protected function _correct() {
      if (is_null($this->_def)) {
        $this->_def = array(
          'id'       => 0,
          'sid'      => '',
          'user'     => 0,
          'last'     => null,
          'ipv4'     => $this->_IPv4,
          'ipv6'     => $this->_IPv6,
          'agent'    => $this->_UA,
          'lc'       => 0,    // Попыток входа
          'bu'       => null, // Блокировать до
          'renew'    => true, // Обновить запись из БД
          'continue' => true  // Продолжить обработку
        );
      }
      foreach ($this->_def as $k => $d) {
        if (!isset($this->_data[$k])) $this->_data[$k] = $d;
        if (is_int($d))  $this->_data[$k] = intval($this->_data[$k]);
        if (is_bool($d)) $this->_data[$k] = self::bool($this->_data[$k]);
      }
      return $this->_data;
    }

    /* Старт сессии */
    protected function _start() {
      $this->_status = 'start';
      session_name($this->_name);
      session_start();
      $sid = session_id();
      if (!ctype_alnum($sid)) session_regenerate_id(true);
      $this->cookie('','');
      if (!isset($_SESSION[self::$key]))    $_SESSION[self::$key] = array();
      if (!is_array($_SESSION[self::$key])) $_SESSION[self::$key] = array();
      $this->_data = array(
        'sid'      => session_id(),
        'renew'    => true,
        'continue' => false
      );
      $this->_status = 'ok';
    }

    /* Получить токен */
    protected function _gettoken() {
      $this->_token = $this->variable(self::$tokenKey);
      if ($this->_token) {
        if (preg_match('/^([a-zA-Z0-9]{32})$/si',$this->_token)) {

        } else { $this->renewToken(); }
      } else { $this->renewToken(); }
      // Получение переменной из запроса
      $this->_tokenGot = isset($_POST[self::$tokenKey]) ? $_POST[self::$tokenKey] : null;
      if (!is_null($this->_tokenGot)) {
        $this->_tokenGot.= ''; // Принудительно превращаем в строку
        if (preg_match('/^([a-zA-Z0-9]{32})$/si',$this->_tokenGot)) {
          if ($this->_tokenGot != $this->_token) {
            $this->_tokenValid = 'notequal';
            $this->_status     = 'csrf_token';
          } else { $this->_tokenValid = 'ok'; }
        } else {
          $this->_tokenValid = 'incorrect';
          $this->_tokenGot   = '';
          $this->_status     = 'csrf_token';
        }
      } else { $this->_tokenValid = 'notset'; }
    }

    /******** ПЕРЕОПРЕДЕЛЯЕМЫЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:VIRTUAL
      @name        : getData
      @description : Получение данных.

      @return : array | Массив данных сессии
    */
    public function getData() { return $this->_data; }

    /******** ПУБЛИЧНЫЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:METHOD
      @name        : renewToken
      @description : Обновление токена

      @return : string | Новый токен
    */
    public function renewToken() { $v = self::keygen(); $this->variable(self::$tokenKey,$v); return $v; }

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
      @description : Переменная сессии

      @param : $path  | string | value |       | Путь
      @param : $value |        | value | @NULL | Данные

      @return : ?
    */
    public function variable($path,$value=null) {
      if (!is_null($value)) {
        $P   = is_array($path) ? $path : explode('/',$path);
        if (count($P) < 1) return false;
        $tmp = &$_SESSION[self::$key];
        foreach ($P as $key) {
          if (!isset($tmp[$key]) || !is_array($tmp[$key])) $tmp[$key] = array();
          $tmp = &$tmp[$key];
        }
        $tmp = $value;
        unset($tmp);
        return $value;
      } else {
        $P = is_array($path) ? $path : explode('/',$path);
        $V = $_SESSION[self::$key];
        foreach ($P as $i) if (isset($V[$i])) {
          $V = $V[$i];
        } else { return false; }
        return $V;
      }
    }

    /******** БИБЛИОТЕЧНЫЕ МЕТОДЫ КЛАССА ********/
    /* CLASS:STATIC
      @name        : keygen
      @description : Генерирует строку случайных символов

      @return : string | Сгенерированная строка
    */
    public static function keygen() {
      $R = ''; $s = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
      for($_ = 0; $_ < 32; $_++) $R.= $s[mt_rand(0,61)];
      return $R;
    }

    /* CLASS:STATIC
      @name        : bool
      @description : Получение булева значения

      @param : $v | | value | | Значение

      @return : boolean | Булево представление значения
    */
    public static function bool($v) { return ((strval($v)=='true')||($v===true)||(intval($v)>0)); }

    /* CLASS:STATIC
      @name        : userAgent
      @description : Вернуть текущее значение IPv4. Шифрует в base64, дабы не париться.
                     Разбор можно делать, если нужно, сторонними библиотеками

      @return : string
    */
    public static function userAgent() {
      if (!isset($_SERVER['HTTP_USER_AGENT'])) return '';
      $ret = base64_encode($_SERVER['HTTP_USER_AGENT']);
      if (strlen($ret) > 255) return '';
      return $ret;
    }

    /******** АКСЕССОРЫ К СТАТИЧЕСКИМ СВОЙСТВАМ КЛАССА ********/
    /* Время жизни куки */
    public static function time($v=null) { if (!is_null($v)) self::$_time = intval($v); return self::$_time; }

    /* Признак HTTPS */
    public static function https($v=null) { if (!is_null($v)) self::$_https = self::bool($v); return self::$_https; }

    /* Признак кроссдоменности */
    public static function crossDomain($v=null) { if (!is_null($v)) self::$_cd = self::bool($v); return self::$_cd; }

    /* Время истечения куки */
    public static function expires() { return (time()+self::$_time); }

    /* TODO Метод шифрования идентификатора сессии */
    public static function encryptMethod($v=null) {
      if (!is_null($v)) {
        if (in_array($v,array())) {
          self::$_em = $v;
        }
      }
      return self::$_em;
    }
  }
  /* CLASS ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>