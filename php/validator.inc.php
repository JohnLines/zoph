<?php
/*
 * A class to validate a user.
 */
class validator {

    var $username;
    var $password;

    /*
     * The constructor.
     */
    function validator($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    /*
     * Validate a user.
     */
    function validate() {
        global $VALIDATOR;
        return $this->$VALIDATOR();
    }

    /*
     * Validate users using Zoph's database.
     */
    function default_validate() {
        $user = null;

        $query =
            "select user_id from " . DB_PREFIX . "users where " .
            "user_name = '" .  escape_string($this->username) . "' and " .
            "password = password('" . escape_string($this->password) . "')";

        $result = mysql_query($query);

        if (mysql_num_rows($result) == 1) {
            $row = mysql_fetch_array($result);

            $user = new user($row["user_id"]);
        }
        // couldn't log in, but is there a default user?
        else if (DEFAULT_USER) {
            $user = new user(DEFAULT_USER);
        }

        return $user;
    }

    /*
     * Validate users using Zoph's database against the
     * PHP_AUTH_USER and PHP_AUTH_PW variables.
     *
     * Contributed by Samuel Keim
     */
    function php_validate() {
        $user = null;

        if (empty($this->username)) {
            $this->username = $_SERVER['PHP_AUTH_USER'];

            if (empty($pword)) {
                $this->password = $_SERVER['PHP_AUTH_PW'];
            }
        }

        return $this->default_validate();
    }

    /*
     * Validate using an htpasswd file.
     * (C) and GPL Asheesh Laroia, 2002
     * Uses code by Jason Geiger and include()s "cdi@thewebmasters.net"'s
     * Htpasswd PHP class
     */
    function htpasswd_validate() {
        /*
            Due to licensing issues, the Htpaswd class is not distrubuted with
            Zoph.  You must download it before making use of this feature.
        */
        $user = null;
        include("Passwd.php");
        $htpass = new File_Passwd(HTPASS_FILE);

        if ($htpass->verifyPassword($this->username, $this->password)) {
            $query =
                "select user_id from " . DB_PREFIX . "users where " .
                "user_name = '" .  escape_string($this->username) . "'";

            $result = mysql_query($query);

            if (mysql_num_rows($result) == 0) {
                // make a new user
                $tmpUser = new user();
                $tmpUser->set('user_name', $this->username);
                $tmpUser->set('password', $this->password);

                // make a new person
                $tmpPerson = new person();
                $tmpPerson->set('first_name', $this->username);

                // put him in DB
                $tmpPerson->insert();
                $tmpUser->set('person_id', $tmpPerson->get('person_id'));

                if (DEFAULT_USER) {
                    // Give user same privileges as Guest:
                    $guestUser = new user(DEFAULT_USER);

                    $privNames = array(
                        'browse_people',
                        'browse_places',
                        'detailed_people',
                        'import',
                        'lightbox_id');

                     foreach ($privNames as $q) {
                         $tmpUser->set($q, $guestUser->get($q));
                     }

                     // Now, grant special privileges of being registered
                     $privNames = array(
                         'detailed_people' => 1,
                         'import' => 1);

                     foreach ($privNames as $k => $v) {
                         $tmpUser->set($k, $v);
                     }
                }

                // Put a row in the DB with this cool dude's info
                $tmpUser->insert();

                // And return a new user of that row number
                $user = new user($tmpUser->get('user_id'));
            }
            else if ((mysql_num_rows($result) == 1)) {
                $row = mysql_fetch_array($result);
                $user = new user($row["user_id"]);
            }
        }
        // Fall back to DEFAULT_USER
        // Would it have been better to just fall back to default_validate()?
        // For code reuse's sake, perhaps this should be its own function,
        // and in each we just say "$user = default_user();"
        else if (DEFAULT_USER) {
            $user = new user(DEFAULT_USER);
        }

        return $user;
    }

}

?>