<?php
  /* INFO
    @product     : xbLib
    @component   : xbLanguage
    @type        : clibrary
    @description : Библиотека функций для работы с локализацией
    @revision    : 2015-12-10 13:19:00
  */

  /* LIBRARY ~BEGIN */
  class xbLanguage {
    protected static $_key   = 'language';
    protected static $_def   = 'en';
    protected static $_sup   = null;
    protected static $_now   = null;
    protected static $_all   = null;
    protected static $_acc   = null;
    protected static $_data  = null;
    protected static $_paths = null;


    /* LIBRARY:FUNCTION
      @name        : active
      @description : Активность языковой системы

      @return : bool
    */
    public static function active() { return !empty(self::$_all); }

    /* LIBRARY:FUNCTION
      @name        : requestKey
      @description : Ключ запросов

      @param : $v | | value | @NULL | Новое значение

      @return : string
    */
    public static function requestKey($v=null) {
      if (!is_null($v)) if (ctype_alnum($v)) self::$_key = $v;
      return self::$_key;
    }

    /* LIBRARY:FUNCTION
      @name        : accepted
      @description : Допустимые языки

      @return : array
    */
    public static function accepted() {
      self::$_acc = array();
      if ($s = $_SERVER['HTTP_ACCEPT_LANGUAGE']) {
        if ($l = explode(',',$s)) {
          foreach ($l as $li) {
            if ($_ = explode(';',$li)) {
              $key = explode('-',$_[0]);
              $key = strtolower($key[0]);
              if (ctype_alnum($key)) {
                $val = floatval(isset($_[1]) ? $_[1] : 1);
                self::$_acc[$key] = $val;
              }
            }
          }
          arsort(self::$_acc,SORT_NUMERIC);
          self::$_acc = array_keys(self::$_acc);
        }
      }
      if (empty(self::$_acc)) self::$_acc[] = self::$_def;
      if (!empty(self::$_all)) {
        $tmp = self::$_acc;
        foreach (self::$_acc as $k => $l)
          if (!in_array($l,self::$_all)) unset($tmp[$k]);
        $tmp = array_values($tmp);
        self::$_acc = $tmp;
      }
      return self::$_acc;
    }

    /* LIBRARY:FUNCTION
      @name        : current
      @description : Текущий язык

      @param : $v | | value | @NULL | Новое значение

      @return : bool
    */
    public static function current($v=null) {
      self::all(); if (empty(self::$_all)) return false;
      // Определяем язык по умолчанию
      if (is_null(self::$_now)) {
        self::accepted();
        self::$_now = empty(self::$_acc) ? self::$_def : self::$_acc[0];
        if (isset($_COOKIE[self::$_key]))
          if (in_array($_COOKIE[self::$_key],self::$_all)) self::$_now = $_COOKIE[self::$_key];
        $_ = '';
        if (isset($_GET[self::$_key]))  $_ = $_GET[self::$_key];
        if (isset($_POST[self::$_key])) $_ = $_POST[self::$_key];
        if ((strlen($_) == 2)) self::$_now = $_;
      }
      // Устанавливаем язык
      if (!is_null($v)) {
        if (!in_array($v,self::$_all)) return false;
        self::$_now = $v;
      }
      return self::$_now;
    }

    /* LIBRARY:FUNCTION
      @name        : all
      @description : Допустимые языки

      @return : bool
    */
    public static function all() {
      self::paths();
      if (is_null(self::$_all)) {
        self::$_all = array();
        foreach (self::$_paths as $path) if (is_dir($path)) {
          $_ = scandir($path);
          foreach ($_ as $_i)
            if (($_i != '.') && ($_i != '..'))
              if (strlen($_i) == 2 && ctype_alnum($_i))
                if (!in_array($_i,self::$_all)) self::$_all[] = $_i;
        }
        if (!in_array(self::$_def,self::$_all))
          self::$_def = count(self::$_all) > 0 ? self::$_all[0] : '';
      }
      return self::$_all;
    }

    /* LIBRARY:FUNCTION
      @name        : dictionary
      @description : Текущий словарь

      @return : array
    */
    public static function dictionary() { return self::$_data; }

    /* LIBRARY:FUNCTION
      @name        : load
      @description : Загрузка языка

      @param : $value | | value | | Сигнатура языка

      @return : bool
    */
    public static function load($v=null) {
      self::current($v);
      foreach (self::$_paths as $loc) self::loadPath($loc);
      return self::$_data;
    }

    /* LIBRARY:FUNCTION
      @name        : add
      @description : Добавить словарь

      @param : $data | | value | | Данные словаря

      @return : void
    */
    public static function add($data) {
      if (is_null(self::$_sup)) self::$_sup = array('caption','hint');
      $strings = is_array($data) ? $data : preg_split('~\\r\\n?|\\n~',$data);
      foreach ($strings as $s) {
        if (is_string($s)) {
          if (trim($s) != '') {
            $a = explode('|',$s);
            $k = trim(array_shift($a));
            foreach (self::$_sup as $p => $e) {
              $d = isset($a[$p]) ? trim($a[$p]) : '';
              if (($d == '') && isset(self::$_data[$k][$e]))
                $d = self::$_data[$k][$e];
              self::$_data[$k][$e] = $d;
            }
          }
        }
      }
    }

    /* LIBRARY:FUNCTION
      @name        : loadPath
      @description : Загрузить по пути

      @param : $dn   | string | value |       | Путь
      @param : $lang | string | value | @NULL | Сигнатура языка

      @return : int | Количество загруженных файлов
    */
    public static function loadPath($dn,$lang=null) {
      // Формируем каталог
      $_ = rtrim(implode(DIRECTORY_SEPARATOR,explode('/',$dn)),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
      $_.= (is_null($lang) ? self::$_now : $lang);
      $_.= DIRECTORY_SEPARATOR.'*.lng';
      // Сканируем директорию
      $files = array();
      if ($_ = glob($_))
        foreach ($_ as $_i) $files[] = $_i;
      // Загружаем данные из файлов
      foreach ($files as $f) self::add(file($f));
      return count($files);
    }

    /* LIBRARY:FUNCTION
      @name        : key
      @description : Ключ языкового плейсхолдера

      @param : $key | string | value | | Ключ
      @param : $p   | string | link  | | Полученное свойство

      @return : string
    */
    public static function key($key,&$p) {
      if (is_null(self::$_sup)) self::$_sup = array('caption','hint');
      $_ = explode('.',$key);
      $l = count($_) - 1;
      $p = 'caption';
      if (($l > 0)) if (in_array($_[$l],self::$_sup)) {
        $p = $_[$l];
        unset($_[$l]);
      }
      $k = implode('.',$_);
      return $k;
    }

    /* LIBRARY:FUNCTION
      @name        : paths
      @description : Установка путей поиска

      @param : $v       |      | value | @NULL  | Значение
      @param : $replace | bool | link  | @FALSE | Заменить значением текущий список

      @return : array
    */
    public static function paths($v=null,$replace=false) {
      if (is_null(self::$_paths)) self::$_paths = array();
      if (is_array($v)) {
        $_ = DIRECTORY_SEPARATOR;
        if ($replace) self::$_paths = array();
        foreach ($v as $path)
          if (!in_array($path,self::$_paths))
            self::$_paths[] = rtrim(implode($_,explode('/',$path)),$_).$_;
      }
      return self::$_paths;
    }
  }
  /* LIBRARY ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>