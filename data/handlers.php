<?php
  /* INFO
    @product     : xbData
    @component   : xbDataHandlers
    @type        : сlibrary
    @description : Библиотека функций обработки данных
    @revision    : 2015-12-20 16:37:00
  */

  /* LIBRARY ~BEGIN */
  class xbDataHandlers {
    protected static $_shandlers = null;
    protected static $_uhandlers = null;
    protected static $_functions = null;

    protected static $_stc = 8; // Количество системных бит типа
    protected static $_shc = 8; // Количество системных обработчиков
    protected static $_uhc = 8; // Количество пользовательских обработчиков
    protected static $_msk = null;
    protected static $_pem = 'md5';

    /* **************** ВНУТРЕННИЕ ФУНКЦИИ **************** */
    protected static function _init() {
      if (is_null(self::$_shandlers)) {
        self::$_shandlers = array('htmlcode','striptags','md5','password','base64','rtf');
        foreach (self::$_shandlers as $k) self::$_functions[$k] = array(null,$k);
      }
      if (is_null(self::$_uhandlers)) self::$_uhandlers = array();
      if (is_null(self::$_msk)) {
        self::$_msk = 0;
        $s = self::$_stc;
        for ($c = $s; $c < (($s + self::$_shc + self::$_uhc) - 1); $c++) self::$_msk |= (1 << $c);
      }
    }

    /* **************** СИСТЕМНЫЕ ФУНКЦИИ ОБРАБОТКИ **************** */
    /* LIBRARY:FUNCTION
      @name        : htmlcode
      @description : Преобразование кода-HTML

      @param : $v | string | value | | Входное значение

      @return : string
    */
    public static function htmlcode($v) { return htmlspecialchars($v,ENT_QUOTES); }

    /* LIBRARY:FUNCTION
      @name        : striptags
      @description : Санитизация

      @param : $v | string | value | | Входное значение

      @return : string
    */
    public static function striptags($v) { return strip_tags($v); }

    /* LIBRARY:FUNCTION
      @name        : md5
      @description : MD5-кодирование

      @param : $v | string | value | | Входное значение

      @return : string
    */
    public static function md5($v) { return md5($v); }

    /* LIBRARY:FUNCTION
      @name        : password
      @description : Односторонне шифрование пароля, исходя из настроек

      @param : $v | string | value | | Входное значение

      @return : string
    */
    public static function password($v) {
      switch (self::$_pem) {
        case 'md5': return md5($v);
      }
      return $v;
    }

    /* LIBRARY:FUNCTION
      @name        : base64
      @description : Кодирование в base64

      @param : $v | string | value |      | Входное значение
      @param : $a | string | value | pack | Действие

      @return : string
    */
    public static function base64($v,$a='pack') { return ($a=='unpack'?base64_decode($v):base64_encode($v)); }

    /* LIBRARY:FUNCTION
      @name        : rtf
      @description : Санитизация RTF и кодирование для безопасного хранения в БД

      @param : $v | string | value |      | Входное значение
      @param : $a | string | value | pack | Действие

      @return : string
    */
    public static function rtf($v,$a='pack') { return self::striptags($v,$a); /* TODO: RTF */ }

    /* **************** ОБРАБОТЧИКИ **************** */
    /* LIBRARY:FUNCTION
      @name        : handlers
      @description : Получить обработчики по заданному значению

      @param : $get | | value | @NULL | Получить хэндлеры по запросу

      @return : array
    */
    public static function handlers($get=null,$int=false) {
      self::_init();
      if (!is_null($get)) {
        $ha = $int ? 0 : array();
        // String value
        if (is_string($get) || is_array($get)) {
          $_  = is_array($get) ? $get : explode(',',$get);
          foreach (self::$_shandlers as $i => $k)
            if (in_array($k,$_))
              if ($int) { $ha |= (1 << $i); } else { $ha[] = $k; }
          foreach (self::$_uhandlers as $i => $k)
            if (in_array($k,$_))
              if ($int) { $ha |= (1 << ($i + self::$_shc)); } else { $ha[] = $k; }
        } else {
          $G = intval($get) & self::$_msk;
          if ($int) return $G; // Возращаем целочисленное значение
          // Системные обработчики
          $K = self::$_stc;
          for ($c = $K, $i = 0; $i < (self::$_shc - 1); $c++, $i = $c - $K)
            if (($G & (1 << $c)) != 0)
              if (isset(self::$_shandlers[$i])) $ha[] = self::$_shandlers[$i];
          // Пользовательские обработчики
          $K = self::$_stc + self::$_shc;
          for ($c = $K, $i = 0; $i < (self::$_uhc - 1); $c++, $i = $c - $K)
            if (($G & (1 << $c)) != 0)
              if (isset(self::$_uhandlers[$i])) $ha[] = self::$_uhandlers[$i];
        }

        return $int ? ($ha << self::$_stc) : $ha;
      }
      if ($int) return self::$_msk;
      return array('system' => self::$_shandlers,'user' => self::$_uhandlers);
    }

    /* LIBRARY:FUNCTION
      @name        : handler
      @description : Обработка значения поля

      @param : $name   | string | value |      | Имя обработчика
      @param : $value  |        | value |      | Значение
      @param : $action | string | value | pack | Действие обработчика

      @return : ?
    */
    public static function execute($name,$value,$action='pack') {
      self::_init();
      if (!isset(self::$_functions[$name])) return $value;
      if (is_array(self::$_functions[$name])) {
        $ob = self::$_functions[$name][0];
        $fn = self::$_functions[$name][1];
        if (is_null($ob))   return self::$fn($value,$action);
        if (is_object($ob)) return $ob->$fn($value,$action);
      } else {
        $fn = self::$_functions[$name];
        if (function_exists($fn)) $fn($value,$action);
      }
      return $value;
    }

    /* LIBRARY:FUNCTION
      @name        : set
      @description : Установка обработчика

      @param : $name | string | value |       | Имя обработчика
      @param : $func |        | value | @NULL | Функция

      @return : int
    */
    public static function set($name,$func=null) {
      static $old = null;
      if (is_null($old)) { self::_init(); $old = self::$_functions; }
      // Восстановить старый обработчик
      if (is_null($func)) {
        if (!isset(self::$_functions[$name])) return false;
        self::$_functions[$name] = isset($old[$name]) ? $old[$name] : null;
        return true;
      }
      // Установка нового обработчика
      if (is_array($func)) {
        if (!isset($func[0]) || !isset($func[1])) return false;
        if (is_null($func[0])) {
          // Библиотечный метод
          if (!method_exists('xbDataHandlers',$func[1])) return false;
          self::$_functions[$name] = $func;
        } elseif (is_object($func[0])) {
          // Метод объекта
          if (!method_exists($func[0],$func[1])) return false;
          self::$_functions[$name] = $func;
        } else { return false; }
      } else {
        // Агрегатная функция
        if (!function_exists($func)) return false;
        self::$_functions[$name] = $func;
      }
      // Установка нового обработчика
      $ret = self::$_stc;
      if (!in_array($name,self::$_shandlers)) {
        if (!in_array($name,self::$_uhandlers)) {
          $uhc = count(self::$_uhandlers);
          if ($uhc >= self::$_uhc) return false;
          $ret += count(self::$_uhandlers);
          self::$_uhandlers[] = $name;
        } else { $ret += intval(array_search($name,self::$_uhandlers)); }
        $ret += self::$_shc;
      } else { $ret += intval(array_search($name,self::$_shandlers)); }
      return (1 << $ret); // Возврат флага обработчика
    }

    /* **************** АКСЕССОРЫ **************** */
    /* Установка метода шифрование пароля */
    public static function passwordMethod($v=null) {
      if (!in_array($v,array('md5'))) self::$_pem = $v;
      return self::$_pem;
    }
  }
  /* LIBRARY ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>