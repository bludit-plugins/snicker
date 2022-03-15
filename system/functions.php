<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./system/functions.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    /*
     |  S18N :: FORMAT AND GET STRING
     |  @since  0.1.0
     |
     |  @param  string  The respective string to translate.
     |  @param  array   Some additional array for `printf()`.
     |
     |  @return string  The translated and formated string.
     */
    function sn__($string, $args = array()){
        global $L;
        $hash = "s18n-" . md5(strtolower($string));
        $value = $L->g($hash);
        if($hash === $value){
            $value = $string;
        }
        return (count($args) > 0)? vsprintf($value, $args): $value;
    }

    /*
     |  S18N :: FORMAT AND PRINT STRING
     |  @since  0.1.0
     |
     |  @param  string  The respective string to translate.
     |  @param  array   Some additional array for `printf()`.
     |
     |  @return <print>
     */
    function sn_e($string, $args = array()){
        print(sn__($string, $args));
    }

    /*
     |  SHORTFUNC :: GET VALUE
     |  @since  0.1.0
     |
     |  @param  string  The respective Snicker configuration key.
     |
     |  @return multi   The respective value or FALSE if the option doens't exist.
     */
    function sn_config($key){
        global $SnickerPlugin;
        return $SnickerPlugin->getValue($key);
    }

    /*
     |  SHORTFUNC :: RESPONSE
     |  @since  0.1.0
     |
     |  @return die();
     */
    function sn_response($data, $key = null){
        global $SnickerPlugin;
        return $SnickerPlugin->response($data, $key);
    }

    /*
     |  SHORTFUNC :: SELECTED
     |  @since  0.1.0
     |
     |  @return die();
     */
    function sn_selected($field, $value = true, $print = true){
        global $SnickerPlugin;
        return $SnickerPlugin->selected($field, $value, $print);
    }

    /*
     |  SHORTFUNC :: CHECKED
     |  @since  0.1.0
     |
     |  @return die();
     */
    function sn_checked($field, $value = true, $print = true){
        global $SnickerPlugin;
        return $SnickerPlugin->checked($field, $value, $print);
    }
