<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./system/class.comments-users.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    class CommentsUsers extends dbJSON{
        /*
         |  DATABASE FIELDS
         */
        protected $dbFields = array(
            "username"      => "",          // Username
            "email"         => "",          // User eMail Address
            "hash"          => "",          // Hashed IP + User Agent
            "blocked"       => false,       // Blocked?
            "comments"      => array()      // Page UIDs => array(CommentUIDs)
        );

        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct(){
            parent::__construct(DB_SNICKER_USERS);
            if(!file_exists(DB_SNICKER_USERS)){
                $this->db = array();
                $this->save();
            }
        }

        /*
         |  GET COMMENTS BY UNIQUE USER ID
         |  @since  0.1.0
         |
         |  @param  string  The unique user ID as string (or the user eMail address).
         |  @param  bool    TRUE to just return the keys, FALSE to return it as Comment objects.
         |
         |  @return multi   The comment keys / objects as ARRAY, FALSE on failure.
         */
        public function getComments($uuid, $keys = true){
            global $Snicker;

            // Validate Data
            if(Valid::email($uuid) !== false){
                $uuid = md5(strtolower(Sanitize::email($uuid)));
            }
            if(!array_key_exists($uuid, $this->db)){
                return false;
            }

            // Return Keys
            $data = $this->db[$uuid]["comments"];
            if($keys === true){
                return $data;
            }

            // Return Objects
            foreach($data AS &$key){
                $key = $Snicker->getComment($key);
            }
            return $key;
        }

        /*
         |  EXISTS
         |  @since   0.1.0
         */
        public function exists($uid){
            return isset($this->db[$uid]);
        }

        /*
         |  GET USER BY UUID
         |  @since  0.1.0
         |
         |  @param  string  The unique user ID as string (or the user eMail address).
         |
         |  @return multi   The user database array on success, FALSE on failure.
         */
        public function get($uuid){
            if(Valid::email($uuid) !== false){
                $uuid = md5(strtolower(Sanitize::email($uuid)));
            }
            if(!array_key_exists($uuid, $this->db)){
                return false;
            }
            $data = $this->db[$uuid];
            $data["uuid"] = $uuid;
            return $data;
        }

        /*
         |  GET CURRENT USER ID
         |  @since  0.1.0
         |
         |  @return multi   The user UUID on success, FALSE on failure.
         */
        public function getCurrent(){
            global $security;
            $hash = md5($security->getUserIp() . $_SERVER["HTTP_USER_AGENT"]);
            foreach($this->db AS $uuid => $fields){
                if($fields["hash"] === $hash){
                    return $uuid;
                }
            }
            return false;
        }

        /*
         |  GET USER
         |  @since  0.1.0
         |
         |  @param  string  Get the user by Comment Author STRING.
         |
         |  @return multi   The user data array on success, FALSE on failure.
         */
        public function getByString($string){
            global $users;

            // Check User Instance
            if(strpos($string, "bludit::") === 0){
                $username = substr($string, strlen("bludit::"));
                if($users->exists($username)){
                    $user = $users->getUserDB($username);
                    $user["username"] = $user["nickname"];
                    return $user;
                }
                return false;
            }

            // Check Guest Instance
            if(strpos($string, "guest::") === 0){
                $uuid = substr($string, strlen("guest::"));
                if($this->exists($uuid)){
                    return $this->db[$uuid];
                }
                return false;
            }

            // Return as Anonymous
            return array(
                "username"  => "Anonymous",
                "email"     => "anonymous@" . $_SERVER["SERVER_NAME"]
            );
        }

        /*
         |  GET LIST
         |  @since  0.1.0
         |
         |  @param  string  The string to be searched or NULL.
         |  @param  int     The current comment page number, starting with 1.
         |  @param  int     The number of comments to be shown per page.
         |
         |  @return array   The respective user keys with an ARRAY or FALSE on failure.
         */
        public function getList($search = null, $page = 1, $limit = -1){
            if($search !== null){
                $list = array();
                foreach($this->db AS $uuid => $fields){
                    if(stripos($fields["username"], $search) === false){
                        continue;
                    }
                    if(stripos($fields["email"], $search) === false){
                        continue;
                    }
                    $list[$uuid] = $fields;
                }
            } else {
                $list = $this->db;
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
         |  MAIN USER HANDLER
         |  @since  0.1.0
         |
         |  @param  string  The username as STRING.
         |  @param  string  The email address as STRING.
         |
         |  @return multi   The (new) UUID on success, FALSE on failure.
         */
        public function user($username, $email){
            global $security;

            // Validate Username
            $username = Sanitize::html(strip_tags(trim($username)));
            if(empty($username) || strlen($username) > 42){
                return false;
            }

            // Validate eMail Address
            $email = strtolower(Sanitize::email($email));
            if(empty($email) || Valid::email($email) === false){
                return false;
            }

            // Check User
            $uuid = md5($email);
            if(array_key_exists($uuid, $this->db)){
                return $uuid;
            }

            // Add User
            $this->db[$uuid] = array(
                "username"      => $username,
                "email"         => $email,
                "hash"          => md5($security->getUserIp() . $_SERVER["HTTP_USER_AGENT"]),
                "blocked"       => false,
                "comments"      => array()
            );
            if(!$this->save()){
                return false;
            }
            return $uuid;
        }
        public function add($username, $email, $meta = array()){
            return $this->user($username, $email, $meta);
        }

        /*
         |  EDIT USER DATA
         |  @since  0.1.0
         |
         |  @param  string  The unique user ID as string (or the user eMail address).
         |  @param  multi   The new username (or NULL to keep the existing one).
         |  @param  multi   The new eMail address (or NULL to keep the existing one).
         |                  ATTENTION: The new eMail address CANNOT be used already!
         |                  ATTENTION: The new eMail address CHANGES the unique user id (UUID)!
         |  @param  multi   TRUE to block the user, FALSE to unblock, null to keep the current.
         |
         |  @return multi   The (new) UUID on success, FALSE on failure.
         */
        public function edit($uuid, $username = null, $email = null, $blocked = null){
            if(Valid::email($uuid) !== false){
                $uuid = md5(strtolower(Sanitize::email($uuid)));
            }
            if(!array_key_exists($uuid, $this->db)){
                return false;
            }
            $data = $this->db[$uuid];

            // Change Username
            if($username !== null){
                $username = Sanitize::html(strip_tags(trim($username)));
                if(empty($username) || strlen($username) > 42){
                    return false;
                }
                $data["username"] = $username;
            }

            // Change eMail
            if($email !== null){
                $email = strtolower(Sanitize::email($uuid));
                if(Valid::email($email) === false){
                    return false;
                }
                $data["email"] = $email;
                $newuuid = md5($email);
            }

            // Change Blocked
            if(is_bool($blocked)){
                $data["blocked"] = $blocked;
            }

            // Update UUID
            if(isset($newuuid) && $uuid !== $newuuid){
                unset($this->db[$uuid]);
                $uuid = $newuuid;
            }

            // Store new Data
            $this->db[$uuid] = $data;
            if(!$this->save()){
                return false;
            }
            return $uuid;
        }

        /*
         |  ADD COMMENT ID TO USER
         |  @since  0.1.0
         |
         |  @param  string  The unique user ID as string (or the user eMail address).
         |  @param  string  The unique comment ID as STRING.
         |
         |  @return bool    TRUE on success, FALSE on failure.
         */
        public function addComment($uuid, $uid){
            if(Valid::email($uuid) !== false){
                $uuid = md5(strtolower(Sanitize::email($uuid)));
            }
            if(!array_key_exists($uuid, $this->db)){
                return false;
            }

            // Add Comment UID
            $user = $this->db[$uuid];
            if(!isset($user["comments"]) || !is_array($user["comments"])){
                $user["comments"] = array();
            }
            if(!in_array($uid, $user["comments"])){
                $user["comments"][] = $uid;
            }

            // Save & Return
            $this->db[$uuid] = $user;
            if(!$this->save()){
                return false;
            }
            return true;
        }

        /*
         |  DELETE COMMENT ID TO USER
         |  @since  0.1.0
         |
         |  @param  string  The unique user ID as string (or the user eMail address).
         |  @param  string  The unique comment ID as STRING.
         |
         |  @return bool    TRUE on success, FALSE on failure.
         */
        public function deleteComment($uuid, $uid){
            if(Valid::email($uuid) !== false){
                $uuid = md5(strtolower(Sanitize::email($uuid)));
            }
            if(!array_key_exists($uuid, $this->db)){
                return false;
            }

            // Delete Comment UID
            $user = $this->db[$uuid];
            if(!isset($user["comments"])){
                $user["comments"] = array();
            }
            if(in_array($uid, $user["comments"])){
                unset($user["comments"][array_search($uid, $user["comments"])]);
            }

            // Save & Return
            $this->db[$uuid] = $user;
            if(!$this->save()){
                return false;
            }
            return true;
        }

        /*
         |  DELETE USER
         |  @since  0.1.0
         |
         |  @param  string  The unique user ID as string (or the user eMail address).
         |
         |  @return bool    TRUE on success, FALSE on failure.
         */
        public function delete($uuid){
            if(Valid::email($uuid) !== false){
                $uuid = md5(strtolower(Sanitize::email($uuid)));
            }
            if(!array_key_exists($uuid, $this->db)){
                return false;
            }

            // Delete & Return
            unset($this->db[$uuid]);
            if(!$this->save()){
                return false;
            }
            return true;
        }
    }
