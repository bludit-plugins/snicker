<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./system/class.snicker.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    class Snicker{
        /*
         |  AVAILABLE THEMES
         */
        public $themes = array();

        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct(){
            $this->initThemes();
        }


##
##  SECURITY HANDLER
##

        /*
         |  SECURITY :: GET COMMENT TOKEN
         |  @since  0.1.0
         */
        public function getToken($action){
            global $security;

            // Create Nonce
            if(!Session::started()){
                Session::start();
            }
            $nonce = sha1($security->getUserIp() . $_SERVER["HTTP_USER_AGENT"] . session_id());

            // Get Database
            if(isset($security->db["snicker"])){
                $db = $security->db["snicker"];
            } else {
                $db = array();
            }

            // Check Database
            if(isset($db[$nonce])){
                list($token, $time) = explode("::", $db[$nonce]);
                if($token !== sha1($action . $nonce . session_id()) || time() >= (int) $time){
                    unset($token);
                    unset($db[$nonce]);
                }
            }

            // Create Token
            if(!isset($token)){
                $token = sha1($action . $nonce . session_id());
            }
            $db[$nonce] = $token . "::" . (time() + (6 * 60 * 60));

            // Store and Return
            $security->db["snicker"] = $db;
            $security->save();
            return $nonce;
        }

        /*
         |  SECURITY :: VALIDATE COMMENT TOKEN
         |  @since  0.1.0
         */
        public function validateToken($action, $nonce){
            global $security;

            // Get Database
            if(isset($security->db["snicker"])){
                $db = $security->db["snicker"];
            } else {
                return false;
            }
            if(!isset($db[$nonce])){
                return false;
            }
            $data = $db[$nonce];

            // Check Token
            if(empty($data) || strpos($data, "::") === false){
                return false;
            }
            list($token, $time) = explode("::", $data);

            // Validate Token
            if(!Session::started()){
                Session::start();
            }
            if($token === sha1($action . $nonce . session_id())){
                if(time() > (int) $time){
                    return true;
                }
                unset($db[$nonce]);
            }

            // Store and Return
            $security->db["snicker"] = $db;
            $security->save();
            return false;
        }

        /*
         |  SECURITY :: GENERATE CAPTCHA
         |  @since  0.1.0
         |  @update 0.2.0
         |
         |  @param  string  The user input phrase.
         |
         |  @return bool    TRUE if the phrase is valid, FALSE if not.
         */
        public function generateCaptcha($width = 150, $height = 40, $src = false){
            $captcha = sn_config("frontend_captcha");
            if($captcha === "gregwar" && !function_exists("imagettfbbox")){
                $captcha = "purecaptcha";
            }

            switch($captcha){
                case "gregwar":
                    $captcha = new Gregwar\Captcha\CaptchaBuilder();
                    $captcha->build($width, $height);
                    $_SESSION["captcha"] = $captcha->getPhrase();
                    if($src){
                        return $captcha->inline();
                    }
                    return '<img src="'.$captcha->inline().'" width="'.$width.'px"  height="'.$height.'px" />';

                case "purecaptcha":
                    $captcha = new OWASP\PureCaptcha();
                    $_SESSION["captcha"] = $captcha->text;
                    if($src){
                        return "data:image/bmp;base64," . $captcha->show(false, 2.8);
                    }
                    return '<img src="data:image/bmp;base64,'.$captcha->show(false, 2.8).'" width="auto"  height="'.$height.'px" />';

                case "recaptchav2":     //@fallthrough
                case "recaptchav3":
                    if($src){
                        return false;
                    }
                    return '<div class="g-recaptcha" data-sitekey="'.sn_config("frontend_recaptcha_public").'"></div>';
            }
            return false;
        }

        /*
         |  SECURITY :: VALIDATE CAPTCHA
         |  @since  0.1.0
         |  @update 0.2.0
         |
         |  @param  int     The desired captcha width.
         |  @param  int     The desired captcha height.
         |
         |  @return string  The captcha image within an <img /> tag.
         */
        public function validateCaptcha($phrase){
            if(!isset($_SESSION["captcha"])){
                return false;
            }

            $captcha = sn_config("frontend_captcha");
            switch($captcha){
                case "gregwar":         //@fallthrough
                case "purecaptcha":
                    $captcha = new Gregwar\Captcha\CaptchaBuilder($_SESSION["captcha"]);
                    return $captcha->testPhrase($phrase);

                case "recaptchav2":     //@fallthrough
                case "recaptchav3":
                    $repcatcha = new ReCaptcha\ReCaptcha(sn_config("frontend_recaptcha_private"));
                    return $recaptcha->isSuccess();
            }
            return false;
        }


##
##  THEME HANDLER
##

        /*
         |  THEME :: INIT THEMES
         |  @since  0.1.0
         |
         |  @return bool    TRUE if everything is fluffy, FALSE if not.
         */
        public function initThemes(){
            $dir = SNICKER_PATH . "themes" . DS;
            if(!is_dir($dir)){
                //@todo Error
                return false;
            }

            // Fetch Themes
            $themes = array();
            if(($handle = opendir($dir))){
                while(($theme = readdir($handle)) !== false){
                    if(!is_dir($dir . $theme) || in_array($theme, array(".", ".."))){
                        continue;
                    }
                    if(!file_exists($dir . $theme . DS . "snicker.php")){
                        continue;
                    }
                    require_once($dir . $theme . DS . "snicker.php");

                    // Load Class
                    if(!class_exists(ucFirst($theme) . "_SnickerTemplate")){
                        continue;
                    }
                    $class = ucFirst($theme) . "_SnickerTemplate";
                    $themes[$theme] = new $class();
                }
            }

            // Check Themes
            if(empty($themes)){
                //@todo Error
                return false;
            }
            $this->themes = $themes;
            return true;
        }

        /*
         |  THEME :: GET METHOD
         |  @since  0.1.0
         |
         |  @param  string  The respective theme name as STRING, which instance you need.
         |                  Use null to get the systen-enabled theme.
         |
         |  @return multi   The theme instance on success, FALSE on failure.
         */
        public function getTheme($theme = null){
            if(empty($this->themes)){
                return false;
            }

            // Current Theme
            if(empty($theme)){
                $theme = sn_config("frontend_template");
            }

            // Get Theme
            if(!array_key_exists($theme, $this->themes)){
                return false;
            }
            return $this->themes[$theme];
        }

        /*
         |  THEME :: GET METHOD
         |  @since  0.1.0
         |
         |  @param  string  The respective theme name as STRING to be checked.
         |
         |  @return bool    TRUE if the theme is available, FALSE if not.
         */
        public function hasTheme($theme){
            return is_array($this->themes) && array_key_exists($theme, $this->themes);
        }

        /*
         |  THEME :: RENDER METHOD
         |  @since  0.1.0
         |
         |  @param  string  The class method to render.
         |  @param  array   The method parameters, which should be passed.
         |  @param  bool    TRUE to print the output directly, FALSE to return it as STRING.
         |
         |  @return string  The respective output as STRING, or print();
         */
        private function renderTheme($method, $args = array(), $print = false){
            if(($theme = $this->getTheme()) === false){
                //@todo Error
                return false;
            }

            // Render Theme
            ob_start();
            call_user_func_array(array($theme, $method), $args);
            $content = ob_get_contents();
            ob_end_clean();

            // Print or Return
            if(!$print){
                return $content;
            }
            print($content);
        }

        /*
         |  THEME :: RENDER FORM
         |  @since  0.1.0
         |
         |  @param  string  The username to overwrite the default / Session.
         |  @param  string  The eMail address to overwrite the default / Session.
         |  @param  string  The title to overwrite the default / Session.
         |  @param  string  The comment to overwrite the default / Session.
         |
         |  @return string  The comments form as STRING.
         */
        public function renderForm($username = "", $email = "", $title = "", $comment = ""){
            global $login, $page;

            // Check Session
            if(!Session::started()){
                Session::start();
            }

            // Get Temporary Data
            $data = Session::get("snicker-comment");
            if(!is_array($data)){
                $data = array();
            }

            // Prepare Data
            foreach(array("username", "email", "title", "comment") AS $var){
                if(empty($$var) && !empty($data[$var])){
                    $$var = $data[$var];
                }
            }
            if(sn_config("comment_title") === "disabled"){
                $title = false;
            }

            $login = is_a($login, "Login")? $login: new Login();
            if($login->isLogged()){
                $user = new User($login->username());
                $username = array($user->username(), md5($user->tokenAuth()), $user->nickname());
            }

            // Render Form
            ob_start();
            ?><div id="comments-form" class="snicker-comments-form"><?php
                if($this->commentsAllowed($page)){
                    $this->renderTheme("form", array($username, $email, $title, $comment), true);
                } else {
                    ?>
                        <div class="disabled-comments">
                            <?php sn_e("The comment section on this page has been disabled!"); ?>
                        </div>
                    <?php
                }
            ?></div><?php
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }

        /*
         |  THEME :: RENDER COMMENTS
         |  @since  0.1.0
         |  @update 0.1.1
         |
         |  @param  string  The unqiue Page UUID, or NULL to use the current comments object,
         |                  based on the current frontend view.
         |
         |  @return string  The comments list as STRING.
         */
        public function renderComments($page_uuid = null){
            global $comments, $page, $pages;

            // Validate Call
            if(empty($page_uuid) && !is_a($comments, "Comments")){
                return false;
            } else if(!empty($page_uuid)){
                if(!$pages->exists($page_uuid)){
                    return false;
                }
                $comments = new Comments($page_uuid);
            }

            // Prepare Comment Meta
            $limit = sn_config("frontend_per_page");        // Comments per Page
            if($limit > 0){
                $count = $comments->count();                    // Total Number of Comments
                $total = ceil($count / $limit);                 // Total Number of C-Pages
                $cpage = 1;                                     // Current Comment Page
                if(isset($_GET["cpage"]) && $_GET["cpage"] > 1){
                    $cpage = ((int) $_GET["cpage"] < $max)? $_GET["cpage"]: $max;
                }
            } else {
                $limit = $comments->count();
                $count = $comments->count();
                $total = 1;
                $cpage = 1;
            }

            // Render Comments
            $list = $comments->getList($cpage, $limit);
            ob_start();
            ?><div id="comments-list" class="snicker-comments-list"><?php
                if(count($list) < 1){
                    if($this->commentsAllowed($page)){
                        ?>
                            <div class="no-comments">
                                <?php sn_e("Currently there are no comments, so be the first!"); ?>
                            </div>
                        <?php
                    }
                } else {
                    if($count > $limit){
                        $this->renderTheme("pagination", array("top", $cpage, $limit, $count, $total), true);
                    }
                    $list = $comments->getDepthList($cpage, $limit, array("comment", "reply"), array("approved", "pending"));
                    foreach($list AS $key){
                        $comment = new Comment($key, $page->uuid());
                        $this->renderTheme("comment", array($comment, $key, $comment->depth()), true);
                    }
                    if($count > $limit){
                        $this->renderTheme("pagination", array("bottom", $cpage, $limit, $count, $total), true);
                    }
                }
            ?></div><?php
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }

        /*
         |  THEME :: RENDER SECTION
         |  @since  0.1.0
         |  @update 0.1.2
         |
         |  @param  bool    TRUE to print the output directly, FALSE to return it as STRING.
         |
         |  @return string  The respective output as STRING, or print();
         */
        public function render($print = false){
            global $comments, $page, $url;

            // Validate Call
            if($url->whereAmI() !== "page" || empty($page->uuid())){
                return false;
            }
            if(!sn_config("comment_on_public") && $page->published()){
                return false;
            }
            if(!sn_config("comment_on_static") && $page->isStatic()){
                return false;
            }
            if(!sn_config("comment_on_sticky") && $page->sticky()){
                return false;
            }
            if(!is_a($comments, "Comments")){
                $comments = new Comments($page->uuid());
            }

            ob_start();
            ?>
                <div id="comments" class="snicker-comments">
                    <?php
                        if(sn_config("frontend_form") === "top"){
                            print($this->renderForm());
                        }
                        print($this->renderComments());
                        if(sn_config("frontend_form") === "bottom"){
                            print($this->renderForm());
                        }
                    ?>
                </div>
            <?php
            $content = ob_get_contents();
            ob_end_clean();

            // Print or Return
            if(!$print){
                return $content;
            }
            print($content);
        }


##
##  COMMENTS HANDLER
##

        /*
         |  GET A SINGLE COMMENT
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID.
         |  @param  string  The value, which should return:
         |                      "list"    => Just some basic data.
         |                      "item"    => All available comment values.
         |                      "object"  => The comment as `new Comment()` instance.
         |
         |  @return multi   The new Comment instance on success, FALSE on failure.
         */
        public function getComment($uid, $return = "list"){
            global $SnickerIndex;
            if(!is_a($SnickerIndex, "CommentsIndex")){
                $SnickerIndex = new CommentsIndex();
            }

            // Get Comment
            $comment = $SnickerIndex->getComment($uid);
            if($return === "list"){
                return $comment;
            }

            // Return Comment
            if($return === "object"){
                return new Comment($uid, $comment["page_uuid"]);
            }
            $comments = new Comments($comment["page_uuid"]);
            return $comments->getComment($uid);
        }

        /*
         |  GET COMMENTS BY PAGE UUID
         |  @since  0.1.0
         |
         |  @param  string  The page UUID.
         |
         |  return  multi   The respective Comments instance or false on failure.
         */
        public function getByPage($uuid){
            global $pages;

            // Get Page
            if(($page = $pages->getByUUID($uuid)) === false){
                return false;
            }

            // Return Comments
            return new Comments($uuid);
        }

        /*
         |  GET COMMENTS BY PAGE SLUG
         |  @since  0.1.0
         |
         |  @param  string  The page slug.
         |
         |  return  multi   The respective Comments instance or false on failure.
         */
        public function getByPageSlug($key){
            global $pages;
            return $this->getByPage($pages->getUUID($key));
        }

        /*
         |  GET COMMENTS INDEX
         |  @since  0.1.0
         |
         |  @param  string  The respective comment status:
         |                  "pending", "approved" or "public", "rejected", "spam" or just "all".
         |
         |  @return multi   The array with all respective comments, NULL otherwise.
         */
        public function getIndex($type = null){
            global $SnickerIndex;
            switch($type){
                case "pending":
                    return $SnickerIndex->getPending();
                case "approved":
                    return $SnickerIndex->getApproved();
                case "rejected":
                    return $SnickerIndex->getRejected();
                case "spam":
                    return $SnickerIndex->getSpam();
                case "all":
                    return $SnickerIndex->getDB();
            }
            return $SnickerIndex;
        }


##
##  COMMENT HANDLER
##

        /*
         |  VALIDATE AND GET BLUDIT USER
         |  @since  0.1.0
         |
         |  @param  string  The username as STRING.
         |  @param  string  The hashed user auth token as STRING.
         |
         |  @return multi   The user object instance on success, FALSE on failure.
         */
        public function validateUser($user, $token){
            global $users;

            // Check Username
            if(!$users->exists($user)){
                return false;
            }
            $user = new User($user);

            // Check Token
            if(md5($user->tokenAuth()) !== $token){
                return false;
            }
            return $user;
        }

        /*
         |  COMMENT :: CHECK IF COMMENTS ARE ALLOWED
         |  @since  0.1.0
         |
         |  @param  object  The respective Page object.
         |
         |  @return bool    TRUE if (new) comments are allowed, FALSE if not.
         */
        public function commentsAllowed($page){
            if(!$page->allowComments() || $page->draft() || $page->scheduled()){
                return false;
            }
            if($page->published() && !sn_config("comment_on_public")){
                return false;
            }
            if($page->sticky() && !sn_config("comment_on_sticky")){
                return false;
            }
            if($page->isStatic() && !sn_config("comment_on_static")){
                return false;
            }
            return true;
        }

        /*
         |  COMMENT :: CHECK IF USER HAS RATED
         |  @since  0.1.0
         |
         |  @param  string  The comment UID as string.
         |  @param  multi   The desired rating type "like" or "dislike".
         |                  Use `null` to check if user has rated in general.
         |
         |  @return bool    TRUE if the user has rated, FALSE if not.
         */
        public function hasRated($uid, $type = null){
            global $SnickerVotes;
            return $SnickerVotes->hasVoted($uid, $type);
        }
        public function hasLiked($uid){
            return $this->hasRated($uid, "like");
        }
        public function hasDisliked($uid){
            return $this->hasRated($uid, "dislike");
        }

        /*
         |  COMMENT :: WRITE A NEW ONE
         |  @since  0.1.0
         |  @update 0.1.1
         |
         |  @param  array   The comment data as ARRAY.
         |
         |  @return <response>
         */
        public function writeComment($data, $key = null){
            global $login, $pages, $users, $url, $SnickerIndex, $SnickerUsers;

            // Temp
            if(!is_a($login, "Login")){
                $login = new Login();
            }
            if(!Session::started()){
                Session::start();
            }
            Session::set("snicker-comment", $data);
            $referer = DOMAIN . $url->uri();

            // Check Page UUID
            if(!isset($data["page_uuid"]) || ($page = $pages->getByUUID($data["page_uuid"])) === false){
                return sn_response(array(
                    "referer"   => $referer . "#snicker-comments-form",
                    "error"     => sn_config("")
                ), $key);
            }
            $comments = $this->getByPage($data["page_uuid"]);

            // Check Captcha
            $captcha = ($login->isLogged())? "disabled": sn_config("frontend_captcha");
            if($captcha !== "disabled"){
                if(!(isset($data["captcha"]) && $this->validateCaptcha($data["captcha"]))){
                    return sn_response(array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => sn__("The answer to the Captcha hasn't been passed or is wrong!"),
                        "captcha"   => $this->generateCaptcha(150, 40, true)
                    ), $key);
                }
            }

            // Check Terms
            $terms = ($login->isLogged())? "disabled": sn_config("frontend_terms");
            if($terms !== "disabled"){
                if(!isset($data["terms"]) || $data["terms"] !== "1"){
                    return sn_response(array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => sn_config("string_error_6")
                    ), $key);
                }
            }

            // Check Title
            if(sn_config("comment_title") === "required"){
                if(!isset($data["title"]) || empty($data["title"])){
                    return sn_response(array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => sn_config("string_error_5")
                    ), $key);
                }
            }

            // Check Comment
            if(!isset($data["comment"]) || empty($data["comment"])){
                return sn_response(array(
                    "referer"   => $referer . "#snicker-comments-form",
                    "error"     => sn_config("string_error_4")
                ), $key);
            }

            // Sanitize User
            if(isset($data["user"]) && isset($data["token"])){
                if(($user = $this->validateUser($data["user"], $data["token"])) === false){
                    return sn_response(array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => sn_config("string_error_1")
                    ), $key);
                }
                $data["author"] = "bludit::" . $user->username();
            } else if(isset($data["username"]) && isset($data["email"])){
                if(($user = $SnickerUsers->user($data["username"], $data["email"])) === false){
                    $email = strtolower(Sanitize::email($data["email"]));
                    $error = !Valid::email($email)? "string_error_3": "string_error_2";
                    return sn_response(array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => sn_config($error)
                    ), $key);
                }
                if($SnickerUsers->db[$user]["blocked"]){
                    return sn_response(array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => sn_config("string_error_7")
                    ), $key);
                }
                $data["author"] = "guest::" . $user;
            } else {
                return sn_response(array(
                    "referer"   => $referer . "#snicker-comments-form",
                    "error"     => sn_config("string_error_1")
                ), $key);
            }
            $data["subscribe"] = isset($data["subscribe"]);

            // Comment Status
            while(true){
                if(!sn_config("moderation")){
                    $data["status"] = "approved";
                    break;
                }
                if($login->isLogged()){
                    if(sn_config("moderation_loggedin")){
                        $data["status"] = "approved";
                        break;
                    } else if($login->role() === "admin"){
                        $data["status"] = "approved";
                        break;
                    } else if($page->username() === $login->username()){
                        $data["status"] = "approved";
                        break;
                    }
                }
                if(sn_config("moderation_approved")){
                    if(strpos($data["author"], "guest::") === 0){
                        $user = $SnickerUsers->get(substr($data["author"], strlen("guest::")));
                        foreach($user["comments"] AS $uuid){
                            if(($check = $SnickerIndex->getComment($uuid)) === false){
                                continue;
                            }
                            if($check["status"] === "approved"){
                                $_status = "approved";
                                break;
                            }
                        }
                        if(isset($_status)){
                            $data["status"] = "approved";
                            break;
                        }
                    }
                }
                $data["status"] = "pending";
                break;
            }

            // Add Comment
            if(($uid = $comments->add($data)) === false){
                return sn_response(array(
                    "referer"   => $referer . "#snicker-comments-form",
                    "error"     => sn_config("string_error_1")
                ), $key);
            }

            // Clear Temp and Return
            Session::set("snicker-comment", null);
            $comment = new Comment($uid, $data["page_uuid"]);
            return sn_response(array(
                "referer"   => $referer . "#comment-" . $uid,
                "success"   => sn_config("string_success_" . ((int) $data["subscribe"] + 1)),
                "comment"   => $this->renderTheme("comment", array($comment, $uid, $comment->depth()))
            ), $key);
        }

        /*
         |  COMMENT :: EDIT AN EXITING ONE
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID.
         |  @param  array   The comment data as ARRAY.
         |
         |  @return <response>
         */
        public function editComment($uid, $data, $key = null){
            global $url, $SnickerIndex, $SnickerUsers;

            // Start Session
            if(!Session::started()){
                Session::start();
            }

            // Get Comment
            if(($comment = $SnickerIndex->getComment($uid)) === false){
                return sn_response(array(
                    "error"     => sn__("The comment UID doesn't exist or is invalid!"),
                    "referer"   => $referer
                ));
            }
            $comments = $this->getByPage($comment["page_uuid"]);

            // Create Redirect
            if($key === "alert"){

                $referer = "#" . (isset($data["status"])? $data["status"]: $comments["status"]);
                $referer = DOMAIN . HTML_PATH_ADMIN_ROOT . $url->slug() . $referer;
            } else {
                $referer = DOMAIN . $url->uri();
            }

            // Sanitize User
            if(isset($data["user"]) && isset($data["token"])){
                if(!$users->exists($data["user"])){
                    return sn_response(array(
                        "error"     => sn_config("string_error_2"),
                        "referer"   => $referer
                    ), $key);
                }
                $user = new User($data["user"]);

                if(md5($user->tokenAuth()) !== $data["token"]){
                    return sn_response(array(
                        "error"     => sn_config("string_error_2"),
                        "referer"   => $referer
                    ), $key);
                }
                $data["author"] = "bludit::" . $user->username();
            } else if(isset($data["username"]) && isset($data["email"])){
                if(($user = $SnickerUsers->user($data["username"], $data["email"])) === false){
                    return sn_response(array(
                        "error"     => sn_config("string_error_2"),
                        "referer"   => $referer
                    ), $key);
                }
                $data["author"] = "guest::" . $user;
            } else {
                unset($data["author"]);
            }

            // Edit Comment
            if(($uid = $comments->edit($uid, $data)) === false){
                return sn_response(array(
                    "error"     => sn_config("string_error_1"),
                    "referer"   => $referer
                ), $key);
            }

            // Clear Temp and Return
            return sn_response(array(
                "success"   => sn_config("string_success_" . ((int) $comment["subscribe"] + 1)),
                "comment"   => $this->renderTheme("comment", array(new Comment($uid, $comment["page_uuid"]), $uid)),
                "referer"   => $referer
            ), $key);
        }

        /*
         |  COMMENT :: MODERATE ONE
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID.
         |  @param  string  The new comment status: "approved", "rejected", "pending" or "spam".
         |
         |  @return <response>
         */
        public function moderateComment($uid, $status = "approved", $key = null){
            global $url, $SnickerIndex;
            $referer = DOMAIN . HTML_PATH_ADMIN_ROOT . $url->slug();

            // Check Parameters
            if(($comment = $SnickerIndex->getComment($uid)) === false){
                return sn_response(array(
                    "error"     => sn__("The comment UID doesn't exist or is invalid!"),
                    "referer"   => $referer . "#{$_GET["status"]}"
                ), $key);
            }
            if(!in_array($status, array("pending", "rejected", "approved", "spam"))){
                return sn_response(array(
                    "error"     => sn__("The comment status is unknown or invalid!"),
                    "referer"   => $referer . "#{$_GET["status"]}"
                ), $key);
            }

            // Handle & Return
            $comments = new Comments($comment["page_uuid"]);
            if($comments->edit($uid, array("status" => $status)) === false){
                return sn_response(array(
                    "error"     => sn__("The new comment status couldn't updated successfully!"),
                    "referer"   => $referer . "#{$_GET["status"]}"
                ), $key);
            }
            return sn_response(array(
                "success"   => sn__("The new comment status has been stored successfully!"),
                "referer"   => $referer . "#{$status}"
            ), $key);
        }

        /*
         |  COMMENT :: RATE ONE
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID.
         |  @param  array   The rating type as STRING "like" or "dislike".
         |
         |  @return <response>
         */
        public function rateComment($uid, $type){
            global $pages, $SnickerIndex, $SnickerVotes;

            // Check Parameters
            if(($comment = $SnickerIndex->getComment($uid)) === false){
                return sn_response(array(
                    "error"     => sn__("The comment UID doesn't exist or is invalid!")
                ), $key);
            }
            $comments = new Comments($comment["page_uuid"]);

            // Create Referer
            $referer = new Page($pages->getByUUID($comment["page_uuid"]));
            $referer = $referer->permalink() . "#comment-{$uid}";

            // Check Rating Type
            if(!in_array($type, array("like", "dislike"))){
                return sn_response(array(
                    "error"     => sn_config("string_error_1"),
                    "referer"   => $referer
                ));
            }
            if(!sn_config("comment_enable_{$type}")){
                return sn_response(array(
                    "error"     => sn_config("string_error_1"),
                    "referer"   => $referer
                ));
            }
            $rating = $comments->getCommentDB($uid)["rating"];

            // Get Ratings
            if($SnickerVotes->hasVoted($uid, null)){
                if($SnickerVotes->hasVoted($uid, $type)){
                    return sn_response(array(
                        "error"   => sn_config("string_error_8"),
                        "referer"   => $referer
                    ));
                }
                $SnickerVotes->delete($uid);
                $rating[($type == "like")? 1: 0]--;
            }
            if(!$SnickerVotes->add($uid, $type)){
                return sn_response(array(
                    "error"   => sn_config("string_error_1"),
                    "referer"   => $referer
                ));
            }
            $rating[($type == "like")? 0: 1]++;

            // Handle & Return
            if($comments->edit($uid, array("rating" => $rating)) === false){
                return sn_response(array(
                    "error"     => sn_config("string_error_1"),
                    "referer"   => $referer
                ));
            }
            return sn_response(array(
                "success"   => sn_config("string_success_3"),
                "referer"   => $referer,
                "rating"    => $rating
            ));
        }

        /*
         |  COMMENT :: DELETE ONE
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID.
         |
         |  @return <response>
         */
        public function deleteComment($uid, $key = null){
            global $url, $SnickerIndex;
            $referer = DOMAIN . HTML_PATH_ADMIN_ROOT . "snicker";

            // Check Parameters
            if(($comment = $SnickerIndex->getComment($uid)) === false){
                return sn_response(array(
                    "error"     => sn__("The comment UID doesn't exist or is invalid!"),
                    "referer"   => $referer
                ), $key);
            }

            // Handle & Return
            $comments = new Comments($comment["page_uuid"]);
            if($comments->delete($uid) === false){
                return sn_response(array(
                    "error"     => sn__("The comment couldn't deleted successfully!"),
                    "referer"   => $referer
                ), $key);
            }
            return sn_response(array(
                "success"   => sn__("The comment could be deleted successfully!"),
                "referer"   => $referer
            ), $key);
        }
    }
