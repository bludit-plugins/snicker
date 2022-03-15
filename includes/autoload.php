<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./includes/autoload.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */

    spl_autoload_register(function($class){
        foreach(array("Gregwar", "Identicon",  "PIT", "OWASP") AS $allowed){
            if(strpos($class, $allowed) !== 0){
                continue;
            }
            $path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
			$class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            require_once $class . ".php";
        }
        return false;
    });
