<?php
  /* INFO
    @product     : xbLib
    @component   : xbValidator
    @type        : clibrary
    @description : Библиотека валидаторов по регулярным выражениям
    @revision    : 2015-12-27 21:07:00
  */

  /* LIBRARY ~BEGIN */
  class xbValidator {
    const rexMail     = '#^([\w\.\-]+)\@([a-zA-Z0-9\.\-]+)\.([a-zA-Z0-9]{2,16})$#si';
    const rexMailTo   = '#^mailto\:([\w\.\-]+)\@([a-zA-Z0-9\.\-]+)\.([a-zA-Z0-9]{2,16})$#si';
    const rexURL      = '#^(http|https)\:\/\/([\w\-\.]+)\.([a-zA-Z0-9]{2,16})\/(\S*)$#si';
    const rexIPv4     = '#^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$#si';
    const rexIPv6     = '#^(?:[0-9]{,3}\.){7}[0-9]{,3}$#si';
    const rexPhone    = '#^([\+\-]?)(\d*)(\(?)(\d+)(\)?)([\d\-\s]+)(\d)$#si';
    const rexDateTime = '#^(\d{4})\-(\d{2})\-(\d{2})(\s+)(\d{2})\:(\d{2})\:(\d{2})$#si';
    const rexNickName = '#^([[:alpha:]])([\w\s\.\-]+)([[:alpha:]\d])$#siu';

    protected static $_flags    = null;

    protected static function is_($name,$v) {
      $R = 'rex'.preg_replace('/is(\w+)/','\1',$name);
      if (defined(sprintf('self::%s',$R))) {
        $X = constant("self::$R");
        if (preg_match($X,$v)) return true;
      }
      return false;
    }

    public static function isMail($v)     { return self::is_('Mail',$v); }
    public static function isMailTo($v)   { return self::is_('MailTo',$v); }
    public static function isURL($v)      { return self::is_('URL',$v); }
    public static function isIPv4($v)     { return self::is_('IPv4',$v); }
    public static function isIPv6($v)     { return self::is_('IPv6',$v); }
    public static function isPhone($v)    { return self::is_('Phone',$v); }
    public static function isDateTime($v) { return self::is_('DateTime',$v); }
    public static function isNickName($v) { return self::is_('NickName',$v); }
  }
  /* LIBRARY ~END */

  /* INFO @copyright: Xander Bass, 2015 */
?>