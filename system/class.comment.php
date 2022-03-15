<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./system/class.comment.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    class Comment{
        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         |
         |  @param  multi   The unique comment id or FALSE.
         |  @param  multi   The unique page comment ID.
         */
        public function __construct($uid, $uuid){
            $this->vars["uid"] = $uid;
            if($uid === false){
                $row = array(
                    "type"          => "comment",
                    "depth"         => 1,
                    "title"         => "",
                    "comment"       => "",
                    "rating"        => [0, 0],
                    "page_uuid"     => "",
                    "parent_uid"    => "",
                    "author"        => "",
                    "subscribe"     => false,
                    "date"          => "",
                    "dateModified"  => "",
                    "dateAudit"     => "",
                    "custom"        => array()
                );
            } else {
                $comments = new Comments($uuid);
                if(Text::isEmpty($uid) || !$comments->exists($uid)){
                    // @todo Throw Error
                }
                $row = $comments->getCommentDB($uid);
            }

            // Set Class
            foreach($row AS $field => $value){
                if(strpos($field, "date") === 0){
                    $this->vars["{$field}Raw"] = $value;
                } else {
                    $this->vars[$field] = $value;
                }
            }
        }

        /*
         |  PUBLIC :: GET VALUE
         |  @since  0.1.0
         |
         |  @param  string  The unique field key.
         |  @param  multi   The default value, which should return if the field key doesnt exist.
         |
         |  @multi  multi   The respective field value on success, $default otherwise.
         */
        public function getValue($field, $default = false){
            if(isset($this->vars[$field])){
                return $this->vars[$field];
            }
            return $default;
        }

        /*
         |  PUBLIC :: SET FIELD
         |  @since  0.1.0
         |
         |  @param  string  The unique field key.
         |  @param  multi   The respective field value, which you want to set.
         |
         |  @return bool    TRUE
         */
        public function setField($field, $value = NULL){
            if(is_array($field)){
                foreach($field AS $k => $v){
                    $this->setField($k,  $v);
                }
                return true;
            }
            $this->vars[$field] = $value;
            return true;
        }


        /*
         |  FIELD :: COMMENT RAW
         |  @since  0.1.0
         |
         |  @return string  The (sanitized) raw content on success, FALSE on failure.
         */
        public function commentRaw(){
            return $this->getValue("comment");
        }

        /*
         |  FIELD :: COMMENT
         |  @since  0.1.0
         |
         |  @return string  The (sanitized) content on success, FALSE on failure.
         */
        public function comment(){
            $content = $this->getValue("comment");
            if(sn_config("comment_markup_html")){
                $content = Sanitize::htmlDecode($content);
            }
            if(sn_config("comment_markup_markdown")){
                $parsedown = new Parsedown();
                $content = $parsedown->text($content);
            }
            return $content;
        }

        /*
         |  FIELD :: GET UID
         |  @since  0.1.0
         */
        public function uid(){
            return $this->getValue("uid");
        }
        public function key(){
            return $this->getValue("uid");
        }

        /*
         |  FIELD :: GET (COMMENT FILE) PATH
         |  @since  0.1.0
         */
        public function path(){
            return PATH_PAGES . $this->getValue("page_key") . DS . "comments";
        }

        /*
         |  FIELD :: GET (COMMENT FILE) PATH / FILE
         |  @since  0.1.0
         */
        public function file(){
            return $this->path() . DS . "c_" . $this->getValue("uid") . ".php";
        }

        /*
         |  FIELD :: GET TYPE
         |  @since  0.1.0
         */
        public function type(){
            return $this->getValue("type");
        }
        public function isComment(){
            return $this->getValue("type") === "comment";
        }
        public function isReply(){
            return $this->getValue("type") === "reply";
        }
        public function isPingback(){
            return $this->getValue("type") === "pingback";
        }

        /*
         |  FIELD :: GET DEPTH
         |  @since  0.1.0
         */
        public function depth(){
            return (int) $this->getValue("depth");
        }

        /*
         |  FIELD :: TITLE
         |  @since  0.1.0
         |
         |  @param  bool    TRUE to sanitize the content, FALSE to return it plain.
         |
         |  @return string  The respective comment title as STRING.
         */
        public function title($sanitize = true){
            if($sanitize){
                return Sanitize::html($this->getValue("title"));
            }
            return $this->getValue("title");
        }

        /*
         |  FIELD :: GET STATUS
         |  @since  0.1.0
         */
        public function status(){
            return $this->getValue("status");
        }
        public function isPending(){
            return $this->getValue("status") === "pending";
        }
        public function isPublic(){
            return $this->getValue("status") === "approved";
        }
        public function isApproved(){
            return $this->getValue("status") === "approved";
        }
        public function isRejected(){
            return $this->getValue("status") === "rejected";
        }
        public function isSpam(){
            return $this->getValue("status") === "spam";
        }

        /*
         |  FIELD :: GET RATING
         |  @since  0.1.0
         */
        public function rating(){
            return $this->getValue("rating");
        }

        /*
         |  FIELD :: GET LIKE
         |  @since  0.1.0
         */
        public function like(){
            $rating = $this->getValue("rating");
            if(is_array($rating) && count($rating) >= 1){
                return $rating[0];
            }
            return 0;
        }

        /*
         |  FIELD :: GET DISLIKE
         |  @since  0.1.0
         */
        public function dislike(){
            $rating = $this->getValue("rating");
            if(is_array($rating) && count($rating) >= 2){
                return $rating[1];
            }
            return 0;
        }

        /*
         |  FIELD :: GET PAGE KEY
         |  @since  0.1.0
         */
        public function page_key(){
            return $this->getValue("page_key");
        }

        /*
         |  FIELD :: GET PAGE UUID
         |  @since  0.1.0
         */
        public function page_uuid(){
            return $this->getValue("page_uuid");
        }

        /*
         |  FIELD :: GET PARENT UID
         |  @since  0.1.0
         */
        public function parent_uid(){
            return $this->getValue("parent_uid");
        }

        /*
         |  FIELD :: GET PARENT
         |  @since  0.1.0
         */
        public function parent(){
            global $comments;
            if($comments->exists($this->getValue("parent_uid"))){
                return new Comment($this->getValue("parent_uid"));
            }
            return false;
        }

        /*
         |  FIELD :: GET CHILDREN
         |  @since  0.1.0
         |
         |  @param  multi   The single comment status which should return, multiple as ARRAY.
         |                  Use `null` to return each children comment.
         |  @param  string  The return type, which allows the following strings:
         |                      "uids"      Return just the respective UID / keys
         |                      "keys"      Return just the respective UID / keys
         |                      "objects"   Return single Comment instances
         |                      "arrays"    Return the unformatted DB arrays
         |
         |  @return multi   FALSE on error, the respective array on succes.
         */
        public function children($status = "approved", $return = "objects"){
            global $comments;

            // Check Parameter
            if(is_string($status)){
                $status = array($status);
            }
            if(!is_array($status) && $status !== null){
                return false;
            }

            // Get Children
            $return = array();
            foreach($this->getDB(false) AS $uid => $value){
                if($value["parent"] !== $this->getValue("uid")){
                    continue;
                }
                if(is_array($status) && !in_array($value["status"], $status)){
                    continue;
                }

                if($return === "uids" || $return == "keys"){
                    $return[] = $uid;
                } else if($return === "objects"){
                    $return[$uid] = new Comment($uid);
                } else {
                    $return[$uid] = $value;
                }
            }
            return $return;
        }

        /*
         |  FIELD :: GET UUID
         |  @since  0.1.0
         */
        public function uuid(){
            return $this->getValue("uuid");
        }

        /*
         |  FIELD :: GET USERNAME
         |  @since  0.1.0
         */
        public function username(){
            global $L, $users, $SnickerUsers;

            list($type, $id) = array_pad(explode("::", $this->getValue("author"), 2), 2, null);
            switch($type){
                case "bludit":
                    if(!$users->exists($id)){
                        break;
                    }
                    $user = new User($id);
                    return $user->nickname();
                case "guest":
                    if(!$SnickerUsers->exists($id)){
                        break;
                    }
                    return $SnickerUsers->db[$id]["username"];
                case "unknown":
                    return $L->g("Unknown User");
            }
            return false;
        }

        /*
         |  FIELD :: GET EMAIL
         |  @since  0.1.0
         */
        public function email(){
            global $L, $users, $SnickerUsers;

            list($type, $id) = array_pad(explode("::", $this->getValue("author"), 2), 2, null);
            switch($type){
                case "bludit":
                    if(!$users->exists($id)){
                        break;
                    }
                    $user = new User($id);
                    return $user->email();
                case "guest":
                    if(!$SnickerUsers->exists($id)){
                        break;
                    }
                    return $SnickerUsers->db[$id]["email"];
                case "unknown":
                    return "unknown@" . $_SERVER["SERVER_NAME"];
            }
            return false;
        }

        /*
         |  FIELD :: SUBSCRIBE
         |  @since  0.1.0
         */
        public function subscribe(){
            return $this->getValue("subscribe");
        }
        public function hasSubscribed(){
            return $this->getValue("subscribe") === true;
        }

        /*
         |  FIELD :: GET AVATAR
         |  @since  0.1.0
         */
        public function avatar($size = "64"){
            $user = $this->username();
            $email = md5(strtolower(trim($this->email())));
            $avatar = $this->avatar_url($size);

            // Force Profile Picture
            $force = false;
            if(sn_config("frontend_avatar_users")){
                $force = (strpos($avatar, DOMAIN_UPLOADS_PROFILES) !== false);
            }

            // Return IMG Tag
            if(sn_config("frontend_avatar") === "identicon" && !$force){
                return '<img src="'.$avatar.'" width="'.$size.'px" height="'.$size.'px" data-identicon="'.$email.'" alt="'.$user.'" />';
            }
            return '<img src="'.$avatar.'" width="'.$size.'px" height="'.$size.'px" alt="'.$user.'" />';
        }

        /*
         |  FIELD :: GET AVATAR URL
         |  @since  0.1.0
         */
        public function avatar_url($size = "64"){
            global $users;

            // Return Profile Picture
            if(sn_config("frontend_avatar_users") && strpos($this->getValue("author"), "bludit") === 0){
                $username = substr($this->getValue("author"), strlen("bludit::"));
                if($users->exists($username)){
                    $user = new User($username);
                    if(($avatar = $user->profilePicture()) !== false){
                        return $avatar;
                    }
                }
            }

            // Return Gravatar
            if(sn_config("frontend_avatar") === "gravatar"){
                $hash = md5(strtolower(trim($this->email())));
                return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=" . sn_config("frontend_gravatar");
            }

            // Return Identicon
            if(sn_config("frontend_avatar") === "identicon"){
                $hash = md5(strtolower(trim($this->email())));
                $ident = new Identicon\Identicon();
                return $ident->getImageDataUri($hash, $size);
            }

            // Return Mystery Man
            return SNICKER_DOMAIN . "includes/img/default-avatar.jpg";
        }

        /*
         |  FIELD :: GET / FORMAT DATE
         |  @since  0.1.0
         |
         |  @param  string  The respective format, which should be used for the output.
         |
         |  @return string  The formatted Date Output.
         */
        public function date($format = false, $type = "date"){
            global $site;
            $date = $this->getValue("{$type}Raw");
            return Date::format($date, DB_DATE_FORMAT, ($format? $format: $site->dateFormat()));
        }
        public function dateModified($format = false){
            return $this->date($format, "dateModified");
        }
        public function dateAudit($format = false){
            return $this->date($format, "dateAudit");
        }

        /*
         |  FIELD :: GET CUSTOM
         |  @since  0.1.0
         |
         |  @param  string  The respective custom key, to get the value.
         |                  Use `null` to get all custom values.
         |
         |  @return multi   The custom value, all customs as ARRAY or FALSE on failure.
         */
        public function custom($key = NULL){
            $custom = $this->getValue("custom");
            if($key !== null){
                if(array_key_exists($key, $custom)){
                    return $custom[$key];
                }
                return false;
            }
            return $custom;
        }
    }
