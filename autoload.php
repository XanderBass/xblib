<?php
  /*
   * Данный файл необходимо подгружать в проект в самом начале.
   * Собственно автозагрузка классов библиотеки выполняется перед
   * остальными автозагрузками.
   *
   * Поддерживает архитектуру RepoDyn.
   * XBLIB_DEPLOY_ROOT должен содержать путь от корня web-проекта (см. значение по умолчанию).
   */

  /* INFO
    @product     : xbLib
    @component   : autoload
    @type        : autoload
    @description : Файл автоподгрузки
    @revision    : 2015-12-16 12:23:00
  */

  if (!defined("XBLIB_DEPLOY_ROOT")) define("XBLIB_DEPLOY_ROOT",'system/external/xblib');

  /* AUTOLOAD ~BEGIN */
  function xbLibAutoload($classname,$ex=false) {
    static $paths = null;
    static $subs  = null;
    // Пропуск, если не тутошний класс.
    if (!preg_match('/^xb(\w+)$/',$classname)) return true;
    // Пути поиска
    if (is_null($paths)) {
      $paths = array(dirname(__FILE__).DIRECTORY_SEPARATOR);
      if (XBLIB_DEPLOY_ROOT != '') {
        $D = realpath($_SERVER['DOCUMENT_ROOT']).DIRECTORY_SEPARATOR;
        $_ = explode('/',XBLIB_DEPLOY_ROOT);
        $l = count($_) - 1;
        if (empty($_[$l])) unset($_[$l]);
        $D.= implode(DIRECTORY_SEPARATOR,$_);
        $D = realpath($D).DIRECTORY_SEPARATOR;
        if ($D != $paths[0]) $paths[] = $D;
      }
    }
    // Суб-папки, пакеты
    if (is_null($subs)) {
      $subs = array();
      if ($_ = scandir($paths[0]))
        foreach ($_ as $N)
          if (is_dir($paths[0].$N) && !in_array($N,array('.','..'))) $subs[] = $N;
    }
    // Инициализация
    $folder = '';
    $fname  = '';
    // Проверка субпапок пакетов
    foreach ($subs as $sub) {
      $tpl = '/^xb'.ucfirst($sub).'(\w+)$/';
      if (preg_match($tpl,$classname)) {
        $folder = $sub.DIRECTORY_SEPARATOR;
        $fname  = strtolower(preg_replace($tpl,'\1',$classname));
        if (empty($fname)) $fname = 'lib';
      }
    }
    // Проверка общих файлов
    if (empty($folder)) if (preg_match('/^xb(\w+)$/',$classname))
      $fname = strtolower(preg_replace('/^xb(\w+)$/','\1',$classname));
    // Ерунда в названии
    if (empty($fname)) {
      if ($ex) return false;
      throw new Exception('No class match for "'.$classname.'"');
    }
    // Наличие файла
    $F = '';
    foreach ($paths as $path) {
      $_ = $path.$folder."$fname.php";
      if (is_file($_)) $F = $_;
    }
    if ($ex) return !empty($F);
    // Подгрузка
    if (!empty($F)) {
      require $F;
      if (!class_exists($classname))
        throw new Exception('No class in file for "'.$classname.'"');
      return true;
    } else { throw new Exception('No class file for "'.$classname.'"'); }
  }

  spl_autoload_register('xbLibAutoload');
  /* AUTOLOAD ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>