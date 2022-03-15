<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./system/class.comments.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    class Comments extends dbJSON{
        /*
         |  PAGE UUID
         */
        protected $uuid;

        /*
         |  DATABASE FIELDS
         */
        protected $dbFields = array(
            "type"          => "comment",   // Comment Type ("comment", "reply", "pingback")
            "depth"         => 1,           // Comment Depth (starting with 1)
            "title"         => "",          // Comment Title
            "status"        => "",          // Comment Status ("pending", "approved", "rejected", "spam")
            "comment"       => "",          // Comment Content
            "rating"        => [0, 0],      // Comment Rating
            "page_uuid"     => "",          // Comment Page UUID
            "parent_uid"    => "",          // Comment Parent UID

            "author"        => "",          // Comment Author (bludt::username or guest::uuid)
            "subscribe"     => false,       // eMail Subscription

            "date"          => "",          // Date Comment Written
            "dateModified"  => "",          // Date Comment Modified
            "dateAudit"     => "",          // Date Comment Audit
            "custom"        => array(),     // Custom Data
        );

        /*
         |  CONSTRUCTOR
         |  @since 0.1.0
         |
         |  @param  string  The UUID of the respective page.
         */
        public function __construct($uuid){
            global $pages;

            // Get Page
            if($pages->getByUUID($uuid) === false){
                $error = "The Page UUID couldn't be found in the database [{$uuid}]";
                Log::set(__METHOD__ . LOG_SEP . $error);
                throw new Exception($error);
            }
            $this->uuid = $uuid;
            parent::__construct(DB_SNICKER_COMMENTS . "comments-{$uuid}.php");
        }

        /*
         |  HELPER :: FILL LOG FILE
         |  @since  0.1.0
         |
         |  @param  string  The respective method for the log (Use __METHOD__)
         |  @param  string  The respective error message to be logged.
         |  @param  array   Additional values AS array for the `vsprintf` function.
         */
        private function log($method, $string, $args){
            $strings = array(
                "error-comment-uid"     => "The comment UID is invalid or does not exist [%s]",
                "error-page-uuid"       => "The page uuid is invalid or does not exist [%s]",
                "error-create-dir"      => "The comment directory could not be created [%s]",
                "error-create-file"     => "The comment file could not be created [%s]",
                "error-comment-file"    => "The comment file does not exist [%s]",
                "error-comment-update"  => "The comment file could not be updated [%s]",
                "error-comment-remove"  => "The comment file could not be deleted [%s]",
                "error-update-db"       => "The comment database could not be updated"
            );
            if(array_key_exists($string, $strings)){
                $string = $strings[$string];
            }
            Log::set($method . LOG_SEP . vsprintf("Error occured: {$string}", $args), LOG_TYPE_ERROR);
        }

        /*
         |  HELPER :: GENERATE UNIQUE COMMENT ID
         |  @since  0.1.0
         */
        private function generateUID(){
            if(function_exists("random_bytes")){
                return md5(bin2hex(random_bytes(16)) . time());
            } else if(function_exists("openssl_random_pseudo_bytes")){
                return md5(bin2hex(openssl_random_pseudo_bytes(16)) . time());
            }
            return md5(uniqid() . time());
        }


        /*
         |  PUBLIC :: GET DEFAULT FIELDS
         |  @since  0.1.0
         |
         |  @return array   An array with all default fields and values per entry.
         */
        public function getDefaultFields(){
            return $this->dbFields;
        }


        /*
         |  DATA :: GET DATABASE
         |  @since  0.1.0
         |
         |  @param  bool    TRUE to just return the keys, FALSE to return the complete DB.
         |
         |  @return array   The complete database entries (or keys) within an ARRAY.
         */
        public function getDB($keys = true){
            return ($keys)? array_keys($this->db): $this->db;
        }

        /*
         |  DATA :: CHECK IF COMMENT ITEM EXISTS
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID.
         |
         |  @return bool    TRUE if the comment ID exists, FALSE if not.
         */
        public function exists($uid){
            return isset($this->db[$uid]);
        }

        /*
         |  DATA :: GET COMMENT ITEM
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID.
         |
         |  @return array   The comment data array on success, FALSE on failure.
         */
        public function getCommentDB($uid){
            return ($this->exists($uid))? $this->db[$uid]: false;
        }

        /*
         |  DATA :: LIST COMMENTS
         |  @since  0.1.0
         |
         |  @param  int     The current comment page number, starting with 1.
         |  @param  int     The number of comments to be shown per page.
         |  @param  multi   The desired comment type as STRING, multiple as ARRAY.
         |                  Pass `null` to get each comment type.
         |  @param  multi   The desired comment status as STRING, multiple as ARRAY.
         |                  Pass `null` to get each comment status.
         |
         |  @return array   The respective database keys with an ARRAY or FALSE on failure.
         */
        public function getList($page, $limit, $type = array("comment", "reply"), $status = array("approved")){
            $type = is_string($type)? array($type): $type;
            if(!is_array($type)){
                $type = null;
            }

            $status = is_string($status)? array($status): $status;
            if(!is_array($status)){
                $type = null;
            }

            // Format List
            $list = array();
            foreach($this->db AS $key => $fields){
                if($type !== null && !in_array($fields["type"], $type)){
                    continue;
                }
                if($status !== null && !in_array($fields["status"], $status)){
                    continue;
                }
                array_push($list, $key);
            }

            // Limit
            if($limit == -1){
                return $list;
            }

            // Offset
            $offset = $limit * ($page - 1);
            $count  = min(($offset + $limit - 1), count($list));
            if($offset < 0 || $offset > $count){
                return false;
            }
            return array_slice($list, $offset, $limit, true);
        }

        /*
         |  DATA :: GENERATE A DEPTH LIST
         |  @since  0.1.0
         |
         |  @param  int     The current comment page number, starting with 1.
         |  @param  int     The number of comments to be shown per page.
         |  @param  multi   The desired comment type as STRING, multiple as ARRAY.
         |                  Pass `null` to get each comment type.
         |  @param  multi   The desired comment status as STRING, multiple as ARRAY.
         |                  Pass `null` to get each comment status.
         |
         |  @return array   The respective database keys with an ARRAY or FALSE on failure.
         */
        public function getDepthList($page, $limit, $type = array("comment", "reply"), $status = array("approved")){
            global $login, $SnickerUsers;
            $this->sortBy();

            // Validate Parameters
            $type = is_string($type)? array($type): $type;
            if(!is_array($type)){
                $type = null;
            }
            $status = is_string($status)? array($status): $status;
            if(!is_array($status)){
                $type = null;
            }

            // Get User Pending
            if(in_array("pending", $status)){
                if(!is_a($login, "Login")){
                    $login = new Login();
                }
                if($login->isLogged()){
                    $user = "bludit::" . $login->username();
                } else {
                    if(($user = $SnickerUsers->getCurrent()) !== false){
                        $user = "guest::" . $user;
                    }
                }
            }

            // Format List
            $list = array();
            $children = array();
            foreach($this->db AS $key => $fields){
                if($type !== null && !in_array($fields["type"], $type)){
                    continue;
                }
                if($status !== null && !in_array($fields["status"], $status)){
                    continue;
                }
                if($fields["status"] === "pending" && $fields["author"] !== $user){
                    continue;
                }

                if(!empty($fields["parent_uid"])){
                    if(!array_key_exists($fields["parent_uid"], $children)){
                        $children[$fields["parent_uid"]] = array();
                    }
                    array_push($children[$fields["parent_uid"]], $key);
                } else {
                    array_push($list, $key);
                }
            }

            // Offset
            $count = 0;
            $offset = $limit * ($page - 1);
            for(; $count < $offset ;){
                $key = array_shift($list);

                $count++;
                if(array_key_exists($key, $children)){
                    $count += count($children[$key]);
                    unset($children[$key]);
                }
            }

            // Generator
            $count = 0;
            foreach($list AS $key){
                if($count >= $limit){
                    break;
                }

                $count++;
                yield $key;
                if(!array_key_exists($key, $children)){
                    continue;
                }

                $loop = $key;
                $depth = array();
                while(true){
                    if(empty($depth) && empty($children[$key])){
                        break;
                    }
                    if(array_key_exists($loop, $children) && count($children[$loop]) > 0){
                        array_push($depth, $loop);
                        $loop = array_shift($children[$loop]);
                        $count++;
                        yield $loop;
                    } else {
                        $loop = array_pop($depth);
                        continue;
                    }
                }
            }
        }

        /*
         |  DATA :: COUNT COMMENTS
         |  @since  0.1.0
         |
         |  @param  multi   The desired comment type as STRING, multiple as ARRAY.
         |                  Pass `null` to get each comment type.
         |
         |  @return int     The total number of comments.
         */
        public function count($type = array("comment", "reply")){
            $type = is_string($type)? array($type): $type;
            if(!is_array($type)){
                $type = null;
            }

            // Count All
            if($type === null){
                return count($this->db);
            }

            // Count
            $count = 0;
            foreach($this->db AS $key => $fields){
                if(!in_array($fields["type"], $type)){
                    continue;
                }
                $count++;
            }
            return $count;
        }


        /*
         |  HANDLE :: ADD A NEW COMMENT
         |  @since  0.1.0
         |
         |  @param  array   The respective comment array.
         |
         |  @return multi   The comment UID on success, FALSE on failure.
         */
        public function add($args){
            global $SnickerIndex, $SnickerUsers;

            // Loop Default Fields
            $row = array();
            foreach($this->dbFields AS $field => $value){
                if(isset($args[$field])){
                    $final = $args[$field];
                } else {
                    $final = $value;
                }
                settype($final, gettype($value));
                $row[$field] = $final;
            }

            // Create (U)UIDs
            $uid = $this->generateUID();
            $row["page_uuid"] = $this->uuid;

            // Validate Parent UID
            if(!empty($row["parent_uid"]) && !$this->exists($row["parent_uid"])){
                $row["parent_uid"] = null;
            }

            // Validate Type and Depth
            if(!empty($row["parent_uid"])){
                $row["type"] = "reply";
                $row["depth"] = $this->db[$row["parent_uid"]]["depth"] + 1;
            } else {
                $row["type"] = "comment";
                $row["depth"] = 1;
            }

            // Validata Status
            if(!in_array($row["status"], array("pending", "approved", "rejected", "spam"))){
                $row["status"] = "pending";
            }

            // Sanitize Strings
            $row["title"] = Sanitize::html(strip_tags($row["title"]));
            $row["author"] = Sanitize::html($row["author"]);

            // Sanitize Comment
            $allowed  = "<a><b><strong><i><em><u><del><ins><s><strike><p><br><br/><br />";
            $allowed .= "<mark><abbr><acronym><dfn><ul><ol><li><dl><dt><dd><hr><hr/><hr />";
            if(sn_config("comment_markup_html")){
                $row["comment"] = strip_tags($row["comment"], $allowed);
            } else {
                $row["comment"] = strip_tags($row["comment"]);
            }
            $row["comment"] = Sanitize::html($row["comment"]);

            // Validate Comment
            $limit = sn_config("comment_limit");
            if($limit > 0 && strlen($row["comment"]) > $limit){
                $row["comment"] = substr($row["comment"], 0, $limit);
            }

            // Set Static Data
            $row["rating"] = array(0, 0);
            $row["subscribe"] = $row["subscribe"] === true;
            $row["date"] = Date::current(DB_DATE_FORMAT);
            $row["dateModified"] = "";
            if($row["status"] !== "pending"){
                $row["dateAudit"] = Date::current(DB_DATE_FORMAT);
            }

            // Add Index
            if(!is_a($SnickerIndex, "CommentsIndex")){
                $SnickerIndex = new CommentsIndex();
            }
            if(!$SnickerIndex->add($uid, $row)){
                return false;
            }
            if(strpos($row["author"], "guest::") === 0){
                $SnickerUsers->addComment(substr($row["author"], strlen("guest::")), $uid);
            }

            // Insert Comment
            $this->db[$uid] = $row;
            $this->sortBy();
            if($this->save() !== true){
                Log::set(__METHOD__, "error-update-db");
                return false;
            }
            return $uid;
        }

        /*
         |  HANDLE :: EDIT AN EXISTING COMMENT
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID as STRING.
         |  @param  array   The respective comment data, whcih you want to update.
         |
         |  @return multi   The comment UID on success, FALSE on failure.
         */
        public function edit($uid, $args){
            global $SnickerIndex;

            // Loop Default Fields
            $row = array();
            foreach($this->dbFields AS $field => $value){
                if(isset($args[$field])){
                    $final = is_string($args[$field])? Sanitize::html($args[$field]): $args[$field];
                } else {
                    $final = $this->db[$uid][$field];
                }
                settype($final, gettype($value));
                $row[$field] = $final;
            }

            // Create / Check (U)UIDs
            if(!$this->exists($uid)){
                $this->log(__METHOD__, "error-comment-uid", array($uid));
                return false;
            }
            $row["page_uuid"] = $this->uuid;

            // Validate Parent UID
            if(!empty($row["parent_uid"]) && !$this->exists($row["parent_uid"])){
                $row["parent_uid"] = $data["parent_uid"];
            }

            // Validate Type and Depth
            if(!empty($row["parent_uid"])){
                $row["type"] = "reply";
                $row["depth"] = $this->db[$row["parent_uid"]]["depth"] + 1;
            } else {
                $row["type"] = "comment";
                $row["depth"] = 1;
            }

            // Validata Status
            if(!in_array($row["status"], array("pending", "approved", "rejected", "spam"))){
                $row["status"] = $this->db[$uid]["status"];
            }

            // Sanitize Strings
            $row["title"] = Sanitize::html($row["title"]);
            $row["comment"] = Sanitize::html($row["comment"]);
            $row["author"] = Sanitize::html($row["author"]);

            // Set Static Data
            $row["subscribe"] = $row["subscribe"] === true;
            $row["dateModified"] = Date::current(DB_DATE_FORMAT);
            if($row["status"] !== $this->db[$uid]["status"]){
                $row["dateAudit"] = Date::current(DB_DATE_FORMAT);
            }

            // Update Index
            if(!$SnickerIndex->edit($uid, $row)){
                return false;
            }

            // Update and Return
            $this->db[$uid] = $row;
            $this->sortBy();
            if($this->save() !== true){
                Log::set(__METHOD__, "error-update-db");
                return false;
            }
            return $uid;
        }

        /*
         |  HANDLE :: DELETE AN EXISTING COMMENT
         |  @since  0.1.0
         |
         |  @param  array   The respective comment UID to delete.
         |
         |  @return bool    TRUE on success, FALSE on failure.
         */
        public function delete($uid){
            global $SnickerIndex, $SnickerUsers;
            if(!isset($this->db[$uid])){
                return false;
            }
            $row = $this->db[$uid];

            // Remove Index
            if(!$SnickerIndex->delete($uid)){
                return false;
            }
            if(strpos($row["author"], "guest::") === 0){
                $SnickerUsers->deleteComment(substr($row["author"], strlen("guest::")), $uid);
            }

            // Remove Database Item
            unset($this->db[$uid]);
            if($this->save() !== true){
                Log::set(__METHOD__, "error-update-db");
                return false;
            }
            return true;
        }

        /*
         |  INTERNAL :: SORT COMMENTS
         |  @since  0.1.0
         |
         |  @return bool    TRUE
         */
        public function sortBy(){
            global $SnickerPlugin;

            if($SnickerPlugin->getValue("frontend_order") === "date_asc"){
                uasort($this->db, function($a, $b){
                    return $a["date"] > $b["date"];
                });
            } else if($SnickerPlugin->getValue("frontend_order") === "date_desc"){
                uasort($this->db, function($a, $b){
                    return $a["date"] < $b["date"];
                });
            }
            return true;
        }
    }
