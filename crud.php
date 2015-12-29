<?php
  /* INFO
    @product     : xbLib
    @component   : xbCRUD
    @type        : clibrary
    @description : Библиотека для реализации логики ролей доступа
    @revision    : 2015-12-27 21:02:00
  */

  /* LIBRARY ~BEGIN */
  class xbCRUD {
    protected static $_roles = null;

    /* LIBRARY:FUNCTION
      @name        : roles
      @description : Установка алиасов ролей

      @param : $v | int | value | | Значение
      @param : $r | int | value | | Признак замены

      @return : array
    */
    public static function roles($v=null,$r=false) {
      if (is_null(self::$_roles))
        self::$_roles = array(
          'anonimous','authorized','notactive','blocked'
        );
      if (!is_null($v)) {
        $V = is_array($v) ? $v : explode(',',strval($v));
        if (!$r) {
          foreach ($V as $role) if (!in_array($role,self::$_roles)) self::$_roles[] = $role;
        } else { self::$_roles = $V; }
      }
      return self::$_roles;
    }

    /* LIBRARY:FUNCTION
      @name        : masked
      @description : Установка значения с маской

      @param : $v | int | value | | Значение с маской
      @param : $p | int | value | | Исходное значение

      @return : int
    */
    public static function masked($v,$p=0) { $m = (($v & 0xf0) >> 4); return ($p & ~$m | $v & $m); }

    /* LIBRARY:FUNCTION
      @name        : extract
      @description : Извлечение значения из байтового потока

      @param : $v | int | value | | Значение
      @param : $p | int | value | | Позиция в потоке

      @return : int
    */
    public static function extract($v,$p=0) { return ($v & (0x0f << ($p * 4))) >> ($p * 4); }

    /* LIBRARY:FUNCTION
      @name        : merge
      @description : Установка новых прав массивом

      @param : $value | array | value | | Значение
      @param : $prev  | array | value | | Исходные данные

      @return : array
    */
    public static function merge($value,$prev) {
      if (!is_array($value)) return false;
      $ret = $prev;
      foreach ($value as $action => $rights) if (isset($prev[$action])) {
        $V = self::masked($rights,$prev[$action]);
        $ret[$action] = $V;
      } else { $ret[$action] = $rights & 0x0f; }
      return $ret;
    }

    /* LIBRARY:FUNCTION
      @name        : access
      @description : Доступ к ресурсу

      @param : $v | string | value | | Значение

      @return : array
    */
    public static function access($v) {
      if (is_null($v)) return null;
      $roles = self::roles();
      $a = intval($v);
      $r = array('admin' => 0x0f);
      foreach ($roles as $i => $k) $r[$k] = self::extract($a,$i);
      return $r;
    }

    /* LIBRARY:FUNCTION
      @name        : name
      @description : Название флага доступа

      @param : $n | string | value | | Имя

      @return : int
    */
    public static function name($n) {
      switch ($n) {
        case 'create': case 'c': return 0x01;
        case 'read'  : case 'r': return 0x02;
        case 'update': case 'u': return 0x04;
        case 'delete': case 'd': return 0x08;
      }
      return 0;
    }

    /* LIBRARY:FUNCTION
      @name        : flag
      @description : Значение флага доступа

      @param : $v | int    | value | | Флаги
      @param : $n | string | value | | Имя

      @return : bool
    */
    public static function flag($v,$n) { return ((self::name($n) & $v) != 0); }
  }
  /* LIBRARY ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>