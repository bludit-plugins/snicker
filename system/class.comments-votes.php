<?php
/*
 |  Snicker     The first native FlatFile Comment Plugin 4 Bludit
 |  @file       ./system/class.comments-votes.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.2 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    class CommentsVotes extends dbJSON{
        const KEY = "snicker-ratings";

        /*
         |  DATABASE FIELDS
         */
        protected $dbFields = array( );

        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct(){
            parent::__construct(DB_SNICKER_VOTES);
            if(!file_exists(DB_SNICKER_VOTES)){
                $this->db = array();
                $this->save();
            }
        }

        /*
         |  HANDLE :: CURRENT USER
         |  @since  0.1.0
         */
        public function currentUser(){
            global $login, $security;

            if(!is_a($login, "Login")){
                $login = new Login();
            }

            // Get Current User
            if($login->isLogged()){
                return "bludit::" . $login->username();
            }
            return "guest::" . md5($security->getUserIp() . $_SERVER["HTTP_USER_AGENT"]);
        }

        /*
         |  HANDLE :: HAS VOTED
         |  @since  0.1.0
         */
        public function hasVoted($uid, $vote = null){
            $user = $this->currentUser();
            $config = sn_config("comment_vote_storage");

            // Database Storage
            $db = strpos($user, "bludit::") === 0 || $config === "database";
            if($db){
                if(!array_key_exists($user, $this->db)){
                    return false;
                }
                $data = $this->db[$user];
            } else {
                $store = ($config == "cookie")? "Cookie": "Session";
                $data = $store::get(self::KEY);
                $data = !empty($data)? @unserialize($data): false;
                if(!is_array($data)){
                    return false;
                }
            }

            // Check Data
            if(!array_key_exists($uid, $data)){
                return false;
            }
            return ($vote === null || $data[$uid] === $vote);
        }
        public function hasLiked($uid){
            return $this->hasVoted($uid, "like");
        }
        public function hasDisliked($uid){
            return $this->hasVoted($uid, "dislike");
        }

        /*
         |  HANDLE :: ADD NEW COMMENT VOTING
         |  @since  0.1.0
         */
        public function add($uid, $vote = "like"){
            $user = $this->currentUser();
            $config = sn_config("comment_vote_storage");

            // Database Storage
            $db = strpos($user, "bludit::") === 0 || $config === "database";
            if($db){
                if(!array_key_exists($user, $this->db)){
                    $this->db[$user] = array();
                }
                if(array_key_exists($uid, $this->db[$user])){
                    return false;
                }
                $this->db[$user][$uid] = $vote;
                return $this->save() !== false;
            }

            // Cookie | Session Storage
            $store = ($config == "cookie")? "Cookie": "Session";
            $data = $store::get(self::KEY);
            $data = !empty($data)? @unserialize($data): false;
            if(is_array($data)){
                if(array_key_exists($uid, $data)){
                    return false;
                }
            } else {
                $data = array();
            }
            $data[$uid] = $vote;
            $store::set(self::KEY, serialize($data));
            return true;
        }

        /*
         |  HANDLE :: EDIT COMMENT VOTING
         |  @since  0.1.0
         */
        public function edit($uid, $vote = "like"){
            $user = $this->currentUser();
            $config = sn_config("comment_vote_storage");

            // Database Storage
            $db = strpos($user, "bludit::") === 0 || $config === "database";
            if($db){
                if(!array_key_exists($user, $this->db)){
                    $this->db[$user] = array();
                }
                if(array_key_exists($uid, $this->db[$user]) && $this->db[$user][$uid] === $vote){
                    return false;
                }
                $this->db[$user][$uid] = $vote;
                return $this->save() !== false;
            }

            // Cookie | Session Storage
            $store = ($config == "cookie")? "Cookie": "Session";
            $data = $store::get(self::KEY);
            $data = !empty($data)? @unserialize($data): false;
            if(is_array($data)){
                if(array_key_exists($uid, $data) && $data[$uid] === $vote){
                    return false;
                }
            } else {
                $data = array();
            }
            $data[$uid] = $vote;
            $store::set(self::KEY, serialize($data));
            return true;
        }

        /*
         |  HANDLE :: DELETE COMMENT VOTING
         |  @since  0.1.0
         */
        public function delete($uid){
            $user = $this->currentUser();
            $config = sn_config("comment_vote_storage");

            // Database Storage
            $db = strpos($user, "bludit::") === 0 || $config === "database";
            if($db){
                if(!array_key_exists($user, $this->db)){
                    return true;
                }
                if(!array_key_exists($uid, $this->db[$user])){
                    return true;
                }
                unset($this->db[$user][$uid]);
                return $this->save() !== false;
            }

            // Cookie | Session Storage
            $store = ($config == "cookie")? "Cookie": "Session";
            $data = $store::get(self::KEY);
            $data = !empty($data)? @unserialize($data): false;
            if(!is_array($data)){
                return true;
            }
            if(!array_key_exists($uid, $data)){
                return true;
            }
            unset($data[$uid]);
            $store::set(self::KEY, serialize($data));
            return true;
        }

        /*
         |  HANDLE :: DELETE BY USER
         |  @since  0.1.0
         */
        public function deleteByUser($user){
            $config = sn_config("comment_vote_storage");

            // Database Storage
            $db = strpos($user, "bludit::") === 0 || $config === "database";
            if($db){
                if(!array_key_exists($user, $this->db)){
                    return true;
                }
                unset($this->db[$user]);
                return $this->save() !== false;
            }

            // Cookie | Session Storage
            $store = ($config == "cookie")? "Cookie": "Session";
            $data = $store::get(self::KEY);
            $data = !empty($data)? @unserialize($data): false;
            if(!is_array($data)){
                return true;
            }
            $store::set(self::KEY, serialize(array()));
            return true;
        }
    }
