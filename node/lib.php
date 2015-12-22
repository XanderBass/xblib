<?php
  /* INFO
    @product     : xbNode
    @component   : xbNode
    @type        : clibrary
    @description : Библиотека нодовой логики
    @revision    : 2015-12-22 13:34:00
  */

  /* LIBRARY ~BEGIN */
  class xbNode {
    protected static $_classPrefix   = 'module';
    protected static $_modulesPath   = '';
    protected static $_modulesDeploy = null;
    protected static $_modulesPaths  = null;
    protected static $_cacheDeploy   = null;
    protected static $_errorHandler  = null;
    protected static $_classTypes    = null;

    /******** КЛЮЧЕВЫЕ МЕТОДЫ ********/
    /* LIBRARY:FUNCTION
      @name        : create
      @description : Создать или загрузить компонент

      @param : $module | string | value | @EMPTY | Название модуля
      @param : $type   | string | value | lib    | Тип компонента
      @param : $name   | string | value | @EMPTY | Название компонента
      @param : $owner  | object | value | @NULL  | Владелец, контейнер
      @param : $value  |        | value | @NULL  | Дополнительные данные

      @return : string
    */
    public static function create($module='',$type='lib',$name='',$owner=null,$value=null) {
      $MN = (empty($module) || ($module == 'system')) ? '' : ucfirst($module);
      $CT = strtolower($type);
      $CT = $type == 'api' ? 'API' : ucfirst($CT);
      $CN = self::$_classPrefix.$MN.$CT.ucfirst($name);

      if (!class_exists($CN)) {
        if ($F = self::fileName($module,$type,$name)) {
          require $F;
          if (!class_exists($CN)) return false;
        } else { return false; }
      }

      switch ($type) {
        case 'cache':
          if (empty($value)) return false;
          return new $CN($owner,$value);
        case 'lib': return true;
        default: return new $CN($owner);
      }
    }

    /* LIBRARY:FUNCTION
      @name        : error
      @description : Ошибка

      @return : ?
    */
    public static function error() {
      if (self::$_errorHandler == true) return true;
      $a = func_get_args();
      if (is_callable(self::$_errorHandler,true))
        return call_user_func_array(self::$_errorHandler,$a);
      throw new Exception('xbNode error: '.implode('|',$a));
    }

    /******** АКСЕССОРЫ К СТАТИЧЕСКИМ ПЕРЕМЕННЫМ ********/
    /* Префикс классов */
    public static function classPrefix($v=null) {
      if (!is_null($v) && ctype_alnum($v)) self::$_classPrefix = $v;
      return self::$_classPrefix;
    }

    /* Типы классов */
    public static function classTypes() {
      if (is_null(self::$_classTypes))
        self::$_classTypes = array('Lib','API','Plugin','Cache','Unit');
      return self::$_classPrefix;
    }

    /* Регулярное выражение классов */
    public static function classRegEx() {
      return '/^'.(self::classPrefix()).'(\w*)('.implode('|',self::classTypes()).')(\w*)$/';
    }

    /* Путь к модулям в репозитории */
    public static function modulesPath($v=null) {
      if (!is_null($v))
        if ($_ = realpath($v)) {
          self::$_modulesPath  = $_.DIRECTORY_SEPARATOR;
          self::$_modulesPaths = null;
        }
      return self::$_modulesPath;
    }

    /* Путь к модулям в проекте */
    public static function modulesDeploy($v=null) {
      if (is_null(self::$_modulesDeploy)) self::$_modulesDeploy = self::deploy('system/modules');
      if (!is_null($v)) {
        self::$_modulesDeploy = self::deploy($v);
        self::$_modulesPaths  = null;
      }
      return self::$_modulesDeploy;
    }

    /* Пути к модулям в репозитории */
    public static function modulesPaths() {
      if (is_null(self::$_modulesPaths)) {
        self::$_modulesPaths   = array();
        if (!empty(self::$_modulesPath)) self::$_modulesPaths[] = self::modulesPath();
        self::$_modulesPaths[] = self::modulesDeploy();
      }
      return self::$_modulesPaths;
    }

    /* Путь к кешу */
    public static function cacheDeploy($v=null) {
      if (is_null(self::$_cacheDeploy)) self::$_cacheDeploy = self::deploy('content/cache');
      if (!is_null($v)) self::$_cacheDeploy = self::deploy($v);
      return self::$_cacheDeploy;
    }

    /* Установка функции обработки ошибок */
    public static function errorHandler($v=null) {
      if (is_callable($v,true) || ($v === true)) {
        self::$_errorHandler = $v;
      } elseif ($v === false) {
        self::$_errorHandler = null;
      }
      return self::$_errorHandler;
    }

    /******** БИБЛИОТЕЧНЫЕ ФУНКЦИИ ********/
    /* LIBRARY:FUNCTION
      @name        : fileName
      @description : Получить файл

      @param : $module | string | value | @EMPTY | Название модуля
      @param : $type   | string | value | lib    | Тип компонента
      @param : $name   | string | value | @EMPTY | Название компонента

      @return : string
    */
    public static function fileName($module='',$type='lib',$name='') {
      if (empty($type)) return false;
      $MN = (empty($module) || ($module == 'system')) ? '' : $module;
      $SP = empty($name) ? '' : strtolower($type);
      $F  = '';
      $paths = self::modulesPaths();
      foreach ($paths as $path) {
        $_ = $path
          . (empty($MN) ? 'system' : $module).DIRECTORY_SEPARATOR
          . (empty($SP) ? '' : $SP.(in_array($SP,array('plugin','unit')) ? 's' : '').DIRECTORY_SEPARATOR)
          . (empty($SP) ? strtolower($type) : strtolower($name)).".php";
        if (is_file($_)) $F = $_;
      }
      if (empty($F)) return false;
      if (!is_file($F)) return false;
      return $F;
    }

    /* LIBRARY:FUNCTION
      @name        : path
      @description : Корректировать путь

      @param : $v | string | value | | Путь

      @return : string
    */
    public static function path($v) {
      return trim(implode(DIRECTORY_SEPARATOR,explode('/',$v)),DIRECTORY_SEPARATOR);
    }

    /* LIBRARY:FUNCTION
      @name        : deploy
      @description : Корректировать путь деплоя

      @param : $v | string | value | | Путь

      @return : string
    */
    public static function deploy($v) {
      $F = realpath($_SERVER['DOCUMENT_ROOT']).DIRECTORY_SEPARATOR;
      $F.= self::path($v);
      return realpath($F).DIRECTORY_SEPARATOR;
    }

    /* LIBRARY:FUNCTION
      @name        : classification
      @description : Получение данных классификации

      @param : $classname | string | value | | Имя класса

      @return : @FALSE/array | Массив с данными либо false при несоответствии
    */
    public static function classification($classname) {
      $re = self::classRegEx();
      if (preg_match($re,$classname)) {
        return array(
          'module' => preg_replace($re,'\1',$classname),
          'type'   => preg_replace($re,'\2',$classname),
          'name'   => preg_replace($re,'\3',$classname),
        );
      }
      return false;
    }

    /* LIBRARY:FUNCTION
      @name        : bool
      @description : Получение булева значения

      @param : $v | | value | | Значение

      @return : boolean | Булево представление значения
    */
    public static function bool($v) { return ((strval($v)=='true')||($v===true)||(intval($v)>0)); }
  }
  /* LIBRARY ~END */

  /* AUTOLOAD ~BEGIN */
  function xbNodeAutoload($classname) {
    if ($CD = xbNode::classification($classname)) {
      if ($F = xbNode::fileName($CD['module'],$CD['type'],$CD['name'])) {
        require $F;
        if (!class_exists($classname))
          throw new Exception('No class in file for "'.$classname.'"');
        return true;
      } else { throw new Exception('No class file for "'.$classname.'"'); }
    } else { return true; }
  }

  spl_autoload_register('xbNodeAutoload');
  /* AUTOLOAD ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>