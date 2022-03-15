Snicker
=======

Snicker is the first native FlatFile comment system for the Content Management System 
[Bludit](https://github.com/bludit/bludit). It allows to write and publish comments using basic 
HTML Syntax or Markdown. The Plugin also offers an extensive environment, many settings and 
possibilities and is also completely compliant with the GDPR!

Features
--------
-   Level-Based, AJAX-enabled Commenting for Guests and Users
-   Many Configurations and adaptable Strings and Themes
-   Guest Management for Not-Logged-In Comment Authors
-   Moderatable Comments (Pending, Approved, Rejected, Spam)
-   Extensive Backend with many possibilities
-   Compliant with the European GDPR

Requirements
------------
-   PHP v5.6.0+
-   Bludit v3.5.0+

Dependencies
------------
-   Snicker use the awesome [Captcha PHP Library](https://github.com/Gregwar/Captcha) made by Grégoire Passault
-   Snicker uses also the [PureCaptcha PHP Library](https://github.com/OWASP/PureCaptcha) as fallback by Abbas Naderi
-   The Avatars are served per default by [Gravatar](https://de.gravatar.com/), made by Automattic / WordPress
-   **But** you can also directly use [Identicons](http://identicon.net) instead...
-   ... where we use the [Identicon PHP Library](https://github.com/yzalis/Identicon) from Benjamin Laugueux
-   ... and the [Identicon JavaScript Library](https://github.com/stewartlord/identicon.js) from Stewart Lord
-   ... which itself depends on the [PNG JavaScript Library](https://www.xarg.org/2010/03/generate-client-side-png-files-using-javascript/) by Robert Eisele

Thanks for this awesome packages and projects!

Installation
------------
-   Download the [Snicker Plugin](https://github.com/pytesNET/snicker/zipball/master)
-   Upload it to your `bl-plugins` folder of your Bludit Website
-   Visit the Bludit Administration and enable the "Snicker" Plugin through "Settings" > "Plugins"

Copyright & License
-------------------
Published under the MIT-License; Copyright &copy; 2019 SamBrishes, pytesNET
