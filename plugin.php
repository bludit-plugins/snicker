<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./plugin.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    require_once "system/functions.php";    // Load Basic Functions

    class SnickerPlugin extends Plugin{
        /*
         |  BACKEND VARIABLES
         */
        private $backend = false;               // Is Backend
        private $backendView = null;            // Backend View / File
        private $backendRequest = null;         // Backend Request Type ("post", "get", "ajax")

        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct(){
            global $SnickerPlugin;
            $SnickerPlugin = $this;
            parent::__construct();
        }


##
##  HELPER METHODs
##

        /*
         |  HELPER :: SELECTED
         |  @since  0.1.0
         |
         |  @param  string  The respective option key (used in `getValue()`).
         |  @param  multi   The value to compare with.
         |  @param  bool    TRUE to print `selected="selected"`, FALSE to return the string.
         |                  Use `null` to return as boolean!
         |
         |  @return multi   The respective string, nothing or a BOOLEAN indicator.
         */
        public function selected($field, $value = true, $print = true){
            if(sn_config($field) == $value){
                $selected = 'selected="selected"';
            } else {
                $selected = '';
            }
            if($print === null){
                return !empty($selected);
            }
            if(!$print){
                return $selected;
            }
            print($selected);
        }

        /*
         |  HELPER :: CHECKED
         |  @since  0.1.0
         |
         |  @param  string  The respective option key (used in `getValue()`).
         |  @param  multi   The value to compare with.
         |  @param  bool    TRUE to print `checked="checked"`, FALSE to return the string.
         |                  Use `null` to return as boolean!
         |
         |  @return multi   The respective string, nothing or a BOOLEAN indicator.
         */
        public function checked($field, $value = true, $print = true){
            if(sn_config($field) == $value){
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            if($print === null){
                return !empty($checked);
            }
            if(!$print){
                return $checked;
            }
            print($checked);
        }


##
##  PLUGIN HOOKs
##

        /*
         |  PLUGIN :: GET VALUE
         |  @since  0.1.2
         */
        public function getValue($field, $html = true){
            if(isset($this->db[$field])){
                $data = strpos($field, "string_") === 0? sn__($this->db[$field]): $this->db[$field];
                return ($html)? $data: Sanitize::htmlDecode($data);
            }
            return isset($this->dbFields[$field])? $this->dbFields[$field]: null;
        }

        /*
         |  PLUGIN :: INIT
         |  @since  0.1.0
         |  @update 0.1.1
         */
        public function init(){
            global $url;

            // Init Default Settings
            $this->dbFields = array(
                "moderation"                => true,
                "moderation_loggedin"       => true,
                "moderation_approved"       => true,
                "comment_on_public"         => true,
                "comment_on_static"         => false,
                "comment_on_sticky"         => true,
                "comment_title"             => "optional",
                "comment_limit"             => 0,
                "comment_depth"             => 3,
                "comment_markup_html"       => true,
                "comment_markup_markdown"   => false,
                "comment_vote_storage"      => "session",
                "comment_enable_like"       => true,
                "comment_enable_dislike"    => true,
                "frontend_captcha"          => function_exists("imagettfbbox")? "gregwar": "purecaptcha",
                "frontend_recaptcha_public" => "",
                "frontend_recaptcha_private"=> "",
                "frontend_terms"            => "default",
                "frontend_filter"           => "pageEnd",
                "frontend_template"         => "default",
                "frontend_order"            => "date_desc",
                "frontend_form"             => "top",
                "frontend_per_page"         => 15,
                "frontend_ajax"             => true,
                "frontend_avatar"           => "gravatar",
                "frontend_avatar_users"     => true,
                "frontend_gravatar"         => "mp",
                "subscription"              => false,
                "subscription_from"         => "ticker@{$_SERVER["SERVER_NAME"]}",
                "subscription_reply"        => "noreply@{$_SERVER["SERVER_NAME"]}",
                "subscription_optin"        => "default",
                "subscription_ticker"       => "default",

                // Frontend Messages, can be changed by the user
                "string_success_1"          => sn__("Thanks for your comment!"),
                "string_success_2"          => sn__("Thanks for your comment, please confirm your subscription via the link we sent to your eMail address!"),
                "string_success_3"          => sn__("Thanks for voting this comment!"),
                "string_error_1"            => sn__("An unknown error occured, please reload the page and try it again!"),
                "string_error_2"            => sn__("An error occured: The passed Username is invalid or too long (42 characters only)!"),
                "string_error_3"            => sn__("An error occured: The passed eMail address is invalid!"),
                "string_error_4"            => sn__("An error occured: The comment text is missing!"),
                "string_error_5"            => sn__("An error occured: The comment title is missing!"),
                "string_error_6"            => sn__("An error occured: You need to accept the Terms to comment!"),
                "string_error_7"            => sn__("An error occured: Your IP address or eMail address has been marked as Spam!"),
                "string_error_8"            => sn__("An error occured: You already rated this comment!"),
                "string_terms_of_use"       => sn__("I agree that my data (incl. my anonymized IP address) gets stored!")
            );

            // Check Backend
            $this->backend = (trim($url->activeFilter(), "/") == ADMIN_URI_FILTER);
        }

        /*
         |  PLUGIN :: OVERWRITE INSTALLED
         |  @since  0.1.0
         |  @update 0.1.1
         */
        public function installed(){
            global $Snicker,            // Main Comment Handler
                   $SnickerIndex,       // Main Comment Indexer
                   $SnickerUsers,       // Main Comment Users
                   $SnickerVotes;       // Main Comment Votes

            if(file_exists($this->filenameDb)){
                if(!defined("SNICKER")){
                    define("SNICKER", true);
                    define("SNICKER_PATH", PATH_PLUGINS . basename(__DIR__) . DS);
                    define("SNICKER_DOMAIN", DOMAIN_PLUGINS . basename(__DIR__) . "/");
                    define("SNICKER_VERSION", "0.1.2");

                    // DataBases
                    define("DB_SNICKER_COMMENTS", $this->workspace() . "pages" . DS);
                    define("DB_SNICKER_INDEX", $this->workspace() . "comments-index.php");
                    define("DB_SNICKER_USERS", $this->workspace() . "comments-users.php");
                    define("DB_SNICKER_VOTES", $this->workspace() . "comments-votes.php");

                    // Pages Filter
                    if(!file_exists(DB_SNICKER_COMMENTS)){
                        @mkdir(DB_SNICKER_COMMENTS);
                    }

                    // Load Plugin
                    require_once "system/abstract.comments-theme.php";
                    require_once "system/class.comment.php";
                    require_once "system/class.comments.php";
                    require_once "system/class.comments-index.php";
                    require_once "system/class.comments-users.php";
                    require_once "system/class.comments-votes.php";
                    require_once "system/class.snicker.php";
                    require_once "includes/autoload.php";
                } else {
                    $Snicker      = new Snicker();
                    $SnickerIndex = new CommentsIndex();
                    $SnickerUsers = new CommentsUsers();
                    $SnickerVotes = new CommentsVotes();
                    $this->request();
                }
                return true;
            }
            return false;
        }


##
##  API METHODs
##

        /*
         |  API :: HANDLE RESPONSE
         |  @since  0.1.0
         |
         |  @param  array   The response data, which MUST contain at least the status:
         |                      "error"     The error message (required).
         |                      "success"   The success message (required).
         |
         |                  ::  NON-AJAX ONLY
         |                      "referer"   A referer URL (The current URL is used, if not present)
         |
         |                  ::  AJAX-BASED ONLY
         |                      :any        Any additional data, which should return to the client.
         |
         |  @return none    This method calls the die(); method at any time!
         */
        public function response($data = array(), $key = null){
            global $url;

            // Validate
            if(isset($data["success"]) || isset($data["error"])){
                $status = isset($data["success"]);
            } else {
                $status = false;
                $data["error"] = sn__("An unknown error occured!");
            }

            // POST Redirect
            if($this->backendRequest !== "ajax"){
                if($status){
                    $key = empty($key)? "snicker-success": $key;
                    Alert::set($data["success"], ALERT_STATUS_OK, $key);
                } else {
                    $key = empty($key)? "snicker-alert": $key;
                    Alert::set($data["error"], ALERT_STATUS_FAIL, $key);
                }

                if($data["referer"]){
                    Redirect::url($data["referer"]);
                } else {
                    $action = isset($_GET["snicker"])? $_GET["snicker"]: $_POST["snicker"];
                    Redirect::url(HTML_PATH_ADMIN_ROOT . $url->slug() . "#{$action}");
                }
                die();
            }

            // AJAX Print
            if(!is_array($data)){
                $data = array();
            }
            $data["status"] = ($status)? "success": "error";
            $data = json_encode($data);

            header("Content-Type: application/json");
            header("Content-Length: " . strlen($data));
            print($data);
            die();
        }

        /*
         |  API :: HANDLE REQUESTS
         |  @since  0.1.0
         |  @update 0.1.1
         */
        public function request(){
            global $login, $security, $url, $Snicker;

            // Get POST/GET Request
            if(isset($_POST["action"]) && $_POST["action"] === "snicker"){
                $data = $_POST;
                $this->backendRequest = "post";
            } else if(isset($_GET["action"]) && $_GET["action"] === "snicker"){
                $data = $_GET;
                $this->backendRequest = "get";
            }
            if(!(isset($data) && isset($data["snicker"]))){
                $this->backendRequest = null;
                return null;
            }

            // Get AJAX Request
            $ajax = "HTTP_X_REQUESTED_WITH";
            if(strpos($url->slug(), "snicker/ajax") === 0){
                if(isset($_SERVER[$ajax]) && $_SERVER[$ajax] === "XMLHttpRequest"){
                    $this->backendRequest = "ajax";
                } else {
                    return Redirect::url(HTML_PATH_ADMIN_ROOT . "snicker/");
                }
            } else if(isset($_SERVER[$ajax]) && $_SERVER[$ajax] === "XMLHttpRequest"){
                print("Invalid AJAX Call"); die();
            }
            if($this->backendRequest === "ajax" && !sn_config("frontend_template")){
                print("AJAX Calls has been disabled"); die();
            }

            // Start Session
            if(!Session::started()){
                Session::start();
            }

            $key = null;
            if(in_array($data["snicker"], array("add", "edit", "delete", "config", "users", "backup", "moderate"))){
                $key = "alert";
            }

            // Check CSRF Token
            if(!empty($key)){
                if(!isset($data["tokenCSRF"])){
                    return $this->response(array(
                        "error" => sn__("The CSRF Token is missing!")
                    ));
                }
                if(!$security->validateTokenCSRF($data["tokenCSRF"])){
                    return $this->response(array(
                        "error" => sn__("The CSRF Token is invalid!")
                    ));
                }
            }

            // Check Permissions
            if(!empty($key)){
                if(!is_a($login, "Login")){
                    $login = new Login();
                }
                if(!$login->isLogged()){
                    return $this->response(array(
                        "error" => sn__("You don't have the permission to call this action!")
                    ));
                }
                if($login->role() !== "admin"){
                    return $this->response(array(
                        "error" => sn__("You don't have the permission to perform this action!")
                    ));
                }
            }

            // Route
            switch($data["snicker"]){
                case "comment": //@fallthrough
                case "reply":   //@fallthrough
                case "add":
                    return $Snicker->writeComment($data["comment"], $key);
                /* case "update": */        //@todo User can edit his own comments
                case "edit":
                    return $Snicker->editComment($data["uid"], $data["comment"], $key);
                /* case "remove": */        //@todo User can delete his own comments
                case "delete":
                    return $Snicker->deleteComment($data["uid"], $key);
                case "moderate":
                    return $Snicker->moderateComment($data["uid"], $data["status"], $key);
                case "list":    //@fallthrough
                case "get":
                    return $Snicker->renderComment($data);
                case "rate":
                    return $Snicker->rateComment($data["uid"], $data["type"]);
                case "users":
                    return $this->user($data);
                case "configure":
                    return $this->config($data);
                case "backup":
                    return $this->backup();
                case "captcha":
                    return $this->response(array(
                        "success"   => sn__("The Captcha Image could be successfully created!"),
                        "captcha"   => $Snicker->generateCaptcha(150, 40, true)
                    ));
            }
            return $this->response(array(
                "error" => sn__("The passed action is unknown or invalid!")
            ), "alert");
        }

        /*
         |  API :: HANDLE USERs
         |  @since  0.1.0
         */
        private function user($data){
            global $SnickerIndex, $SnickerUsers;

            // Validate Data
            if(!isset($data["uuid"]) || !isset($data["handle"])){
                return $this->response(array(
                    "error" => sn__("An unknown error is occured!")
                ), "alert");
            }

            // Validata UUID
            if(!$SnickerUsers->exists($data["uuid"])){
                return $this->response(array(
                    "error" => sn__("An unique user ID does not exist!")
                ), "alert");
            }

            // Handle
            if($data["handle"] === "delete"){
                $comments = $SnickerUsers->db[$data["uuid"]]["comments"];
                foreach($comments AS $uid){
                    if(!$SnickerIndex->exists($uid)){
                        continue;
                    }
                    $index = $SnickerIndex->getComment($uid);
                    $comment = new Comments($index["page_uuid"]);

                    if(isset($data["anonymize"]) && $data["anonymize"] === "true"){
                        $comment = new Comments($index["page_uuid"]);
                        $comment->edit($uid, array("author" => "anonymous"));
                    } else {
                        $comment = new Comments($index["page_uuid"]);
                        $comment->delete($uid);
                    }
                }
                $status = $SnickerUsers->delete($data["uuid"]);
            } else if($data["handle"] === "block"){
                $status = $SnickerUsers->edit($data["uuid"], null, null, true);
            } else if($data["handle"] === "unblock"){
                $status = $SnickerUsers->edit($data["uuid"], null, null, false);
            }

            // Redirect
            if(!isset($status)){
                return $this->response(array(
                    "error" => sn__("The passed action is unknown or invalid!")
                ), "alert");
            }
            if($status === false){
                return $this->response(array(
                    "error" => sn__("An unknown error is occured!")
                ), "alert");
            }
            return $this->response(array(
                "success" => sn__("The action has been performed successfully!")
            ), "alert");
        }

        /*
         |  API :: HANDLE CONFIGURATION
         |  @since  0.1.0
         |  @update 0.2.0
         */
        private function config($data){
            global $pages, $Snicker;
            $config = array();

            // Validations
            $text = array("frontend_recaptcha_public", "frontend_recaptcha_private");
            $numbers = array("comment_limit", "comment_depth", "frontend_per_page");
            $selects = array(
                "comment_title"         => array("optional", "required", "disabled"),
                "comment_vote_storage"  => array("cookie", "session", "database"),
                "frontend_captcha"      => array("disabled", "purecaptcha", "gregwar", "recaptchav2", "recaptchav3"),
                "frontend_avatar"       => array("gravatar", "identicon", "static", "initials"),
                "frontend_gravatar"     => array("mp", "identicon", "monsterid", "wavatar", "retro", "robohash", "blank"),
                "frontend_filter"       => array("disabled", "pageBegin", "pageEnd", "siteBodyBegin", "siteBodyEnd"),
                "frontend_order"        => array("date_desc", "date_asc"),
                "frontend_form"         => array("top", "bottom")
            );
            $emails = array("subscription_from", "subscription_reply");
            $pageid = array("frontend_terms", "subscription_optin", "subscription_ticker");

            // Loop DB Fields
            foreach($this->dbFields AS $field => $value){
                if(!isset($data[$field])){
                    $config[$field] = is_bool($value)? false: "";
                    continue;
                }

                // Sanitize Booleans
                if(is_bool($value)){
                    $config[$field] = ($data[$field] === "true" || $data[$field] === true);
                    continue;
                }

                // Sanitize Numbers
                if(in_array($field, $numbers)){
                    if($data[$field] < 0 || !is_numeric($data[$field])){
                        $config[$field] = 0;
                    }
                    $config[$field] = (int) $data[$field];
                    continue;
                }

                // Sanitize Selection
                if(array_key_exists($field, $selects)){
                    if(in_array($data[$field], $selects[$field])){
                        $config[$field] = $data[$field];
                    } else {
                        $config[$field] = $value;
                    }
                    continue;
                }

                // Sanitize eMails
                if(in_array($field, $emails)){
                    if(Valid::email($data[$field])){
                        $config[$field] = Sanitize::email($data[$field]);
                    } else {
                        $config[$field] = $value;
                    }
                    continue;
                }

                // Sanitize Pages
                if(in_array($field, $pageid)){
                    $default = in_array($data[$field], array("default", "disabled"));
                    if($default || $pages->exists($data[$field])){
                        $config[$field] = $data[$field];
                    } else {
                        $config[$field] = $value;
                    }
                    continue;
                }

                // Sanitize Template
                if($field == "frontend_template"){
                    if($Snicker->hasTheme($data[$field])){
                        $config[$field] = $data[$field];
                    } else {
                        $config[$field] = $value;
                    }
                    continue;
                }

                // Sanitize Strings
                if(strpos($field, "string_") === 0 || in_array($field, $text)){
                    $config[$field] = Sanitize::html(strip_tags($data[$field]));
                    if(empty($config[$field])){
                        $config[$field] = $value;
                    }
                    continue;
                }
            }

            // Save & Return
            $this->db = array_merge($this->db, $config);
            if(!$this->save()){
                return $this->response(array(
                    "error" => sn__("An unknown error is occured!")
                ), "alert");
            }
            return $this->response(array(
                "success" => sn__("The settings has been updated successfully!")
            ), "alert");
        }

        /*
         |  API :: CREATE BACKUP
         |  @since  0.1.0
         */
        private function backup(){
            $filename = "snicker-backup-" . time() . ".zip";

            // Create Backup
            $zip = new PIT\Zip();
            $zip->addFolder($this->workspace(), "/", true, true);
            $zip->save(PATH_TMP . $filename);

            // Return
            return $this->response(array(
                "success"   => sn__("The backup has been created successfully!"),
                "referer"   => DOMAIN_ADMIN . "uninstall-plugin/SnickerPlugin"
            ), "alert");
        }


##
##  BACKEND HOOKs
##

        /*
         |  HOOK :: INIT ADMINISTRATION
         |  @since  0.1.0
         */
        public function beforeAdminLoad(){
            global $url;

            // Check if the current View is the "snicker"
            if(strpos($url->slug(), "snicker") !== 0){
                return false;
            }
            checkRole(array("admin"));

            // Set Backend View
            $split = str_replace("snicker", "", trim($url->slug(), "/"));
            if(!empty($split) && $split !== "/" && isset($_GET["uid"])){
                $this->backendView = "edit";
            } else {
                $this->backendView = "index";
            }
        }

        /*
         |  HOOK :: LOAD ADMINISTRATION FILES
         |  @since  0.1.0
         */
        public function adminHead(){
            global $page, $security, $url;

            $js = SNICKER_DOMAIN . "admin/js/";
            $css = SNICKER_DOMAIN . "admin/css/";
            $slug = explode("/", str_replace(HTML_PATH_ADMIN_ROOT, "", $url->uri()));

            // Admin Header
            ob_start();
            if($slug[0] === "new-content" || $slug[0] === "edit-content"){
                ?>
                    <script type="text/javascript">
                        (function(){
                            "use strict";
                            var w = window, d = window.document;

                            // Render Field
                            var HANDLE_COMMENTS_FIELD = '<?php echo Bootstrap::formSelectBlock(array(
                                'name'      => 'allowComments',
                                'label'     => sn__('Page Comments'),
                                'selected'  => (!$page)? '1': ($page->allowComments()? '1': '0'),
                                'class'     => '',
                                'options'   => array(
                                    '1'         => sn__('Allow Comments'),
                                    '0'         => sn__('Disallow Comments')
                                )
                            )); ?>';

                            // Ready ?
                            d.addEventListener("DOMContentLoaded", function(){
                                if(d.querySelector("#jscategory")){
                                    var form = d.querySelector("#jscategory").parentElement;
                                    form.insertAdjacentHTML("afterend", HANDLE_COMMENTS_FIELD);
                                }
                            });
                        }());
                    </script>
                <?php
            } else if($slug[0] === "snicker"){
                ?>
                    <script type="text/javascript" src="<?php echo $js; ?>admin.snicker.js"></script>
                    <link type="text/css" rel="stylesheet" href="<?php echo $css; ?>admin.snicker.css" />
                <?php
            } else if($slug[0] === "plugins"){
                $link = DOMAIN_ADMIN . "snicker?action=snicker&snicker=backup&tokenCSRF=" . $security->getTokenCSRF();
                ?>
                    <script type="text/javascript">
                        document.addEventListener("DOMContentLoaded", function(){
                            var link = document.querySelector("tr#SnickerPlugin td a");
                            if(link){
                                link.addEventListener("click", function(event){
                                    event.preventDefault();
                                    jQuery("#dialog-deactivate-snicker").modal();
                                });
                                jQuery("#dialog-deactivate-snicker button[data-snicker='backup']").click(function(){
                                    console.log("owo");
                                    window.location.replace("<?php echo $link; ?>&referer=" + link.href);
                                });
                                jQuery("#dialog-deactivate-snicker button[data-snicker='deactivate']").click(function(){
                                    window.location.replace(link.href);
                                });
                            }
                        })
                    </script>
                <?php
            }
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }

        /*
         |  HOOK :: BEFORE ADMIN CONTENT
         |  @since  0.1.0
         */
        public function adminBodyBegin(){
            if(!$this->backend || !$this->backendView){
                return false;
            }
            ob_start();
        }

        /*
         |  HOOK :: AFTER ADMIN CONTENT
         |  @since  0.1.0
         */
        public function adminBodyEnd(){
            global $url, $SnickerPlugin;
            if(!$this->backend || !$this->backendView){
                $slug = explode("/", str_replace(HTML_PATH_ADMIN_ROOT, "", $url->uri()));
                if($slug[0] === "plugins"){
                    ?>
                        <div id="dialog-deactivate-snicker" class="modal fade" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php sn_e("Snicker Plugin Deactivation"); ?></h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>
                                            <?php sn_e("You are about to deactivate the <b>Snicker</b> Plugin, which will delete all written comments!"); ?>
                                            <?php sn_e("Do you want to Backup your comments before?"); ?>
                                        </p>
                                        <p>
                                            <?php sn_e("The Backup will be stored in %s!", array("<code>./bl-content/tmp/</code>")); ?>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" data-snicker="backup"><?php sn_e("Yes, create a Backup"); ?></button>
                                        <button type="button" class="btn btn-danger" data-snicker="deactivate"><?php sn_e("No, just Deactivate"); ?></button>
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php sn_e("Cancel"); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
                return false;
            }

            // Fetch Content
            $content = ob_get_contents();
            ob_end_clean();

            // Snicker Admin Content
            ob_start();
            if(file_exists(SNICKER_PATH . "admin" . DS . "{$this->backendView}.php")){
                require SNICKER_PATH . "admin" . DS . "{$this->backendView}.php";
                $add = ob_get_contents();
            }
            ob_end_clean();

            // Inject Code
            if(isset($add) && !empty($add)){
                $regexp = "#(\<div class=\"col-lg-10 pt-3 pb-1 h-100\"\>)(.*?)(\<\/div\>)#s";
                $content = preg_replace($regexp, "$1{$add}$3", $content);
            }
            print($content);
        }

        /*
         |  HOOK :: SHOW SIDEBAR MENU
         |  @since  0.1.0
         */
        public function adminSidebar(){
            global $SnickerIndex;

            $count = $SnickerIndex->count("pending");
            $count = ($count > 99)? "99+": $count;

            ob_start();
            ?>
                <a href="<?php echo HTML_PATH_ADMIN_ROOT; ?>snicker" class="nav-link" style="white-space: nowrap;">
                    <span class="oi oi-comment-square"></span>Snicker <?php sn_e("Comments"); ?>
                    <?php if(!empty($count)){ ?>
                        <span class="badge badge-success badge-pill"><?php echo $count; ?></span>
                    <?php } ?>
                </a>
            <?php
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }


##
##  FRONTEND HOOKs
##

        /*
         |  HOOK :: BEFORE FRONTEND LOAD
         |  @since  0.1.0
         |  @update 0.1.2
         */
        public function beforeSiteLoad(){
            global $comments, $page, $url;

            // Start Session
            if(!Session::started()){
                Session::start();
            }

            // Init Comments
            if(is_a($page, "Page") && $page->published() && !empty($page->uuid())){
                $comments = new Comments($page->uuid());
            } else {
                $comments = false;
            }
        }

        /*
         |  HOOK :: FRONTEND HEADER
         |  @since  0.1.0
         */
        public function siteHead(){
            global $Snicker;

            if(($theme = $Snicker->getTheme()) === false){
                return false;
            }
            if(!empty($theme::SNICKER_JS)){
                $file = SNICKER_DOMAIN . "themes/" . sn_config("frontend_template") . "/" . $theme::SNICKER_JS;
                ?>
                    <script type="text/javascript">
                        var SNICKER_AJAX = <?php echo sn_config("frontend_ajax")? "true": "false"; ?>;
                        var SNICKER_PATH = "<?php echo HTML_PATH_ADMIN_ROOT ?>snicker/ajax/";
                    </script>
                    <script id="snicker-js" type="text/javascript" src="<?php echo $file; ?>"></script>
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <?php
            }





            if(!empty($theme::SNICKER_CSS)){
                $file = SNICKER_DOMAIN . "themes/" . sn_config("frontend_template") . "/" . $theme::SNICKER_CSS;
                ?>
                    <link id="snicker-css" type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
                <?php
            }
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function siteBodyBegin(){
            global $Snicker;
            if(sn_config("frontend_filter") !== "siteBodyBegin"){
                return false; // owo
            }
            print($Snicker->render());
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function pageBegin(){
            global $Snicker;
            if(sn_config("frontend_filter") !== "pageBegin"){
                return false; // Owo
            }
            print($Snicker->render());
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function pageEnd(){
            global $Snicker;
            if(sn_config("frontend_filter") !== "pageEnd"){
                return false; // owO
            }
            print($Snicker->render());
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function siteBodyEnd(){
            global $Snicker;
            if(sn_config("frontend_filter") !== "siteBodyEnd"){
                return false; // OwO
            }
            print($Snicker->render());
        }
    }
