<?php
session_start();
require_once('oauth/twitteroauth/twitteroauth.php');
require_once('oauth/config.php');

function twitter_auth($user, &$result) {
    if (!isset($_COOKIE[session_name()])) {
        User::SetupSession();
    }

    if (method_exists($user, 'load')) {
        $user->load();
    }
    else {
        User::loadFromSession();
    }

    //if($user->mId != 0) {
    //    return false;
    //}

    /* No twitter credentials found, we're done here */
    if (empty($_SESSION['access_token'])
        || empty($_SESSION['access_token']['oauth_token'])
        || empty($_SESSION['access_token']['oauth_token_secret'])) {
        return false;
    }

    /* Unverified twitter credentials found, verify them */
    if (!isset($_SESSION['status']) || $_SESSION['status'] != 'verified') {
        /* Get user access tokens out of the session. */
        $access_token = $_SESSION['access_token'];

        /* Create a TwitterOauth object with consumer/user tokens. */
        $connection = new TwitterOAuth(CONSUMER_KEY,
                                       CONSUMER_SECRET,
                                       $access_token['oauth_token'],
                                       $access_token['oauth_token_secret']);

        /* If method is set change API call made. Test is called by default. */
        $obj = $connection->get('account/verify_credentials');
        $user = User::newFromName($obj->screen_name);
        if ($user->getID() == 0) {
            /* No account with this name found, so create one */
            $user->addToDatabase();
            $user->setRealName($obj->name);
            $user->setPassword(User::randomPassword());
            $user->setToken();
            
            /* Store user's twitter ID in separate table */
            $dbw = wfGetDB(DB_MASTER);
            $dbw->insert('twitter_users',
                         array('user_id' => $user->getID(), 'twitter_id' => $obj->screen_name),
                         'Database::insert',
                         array());
        }
        else {
            /* Check this user was created from twitter, or deny auth */ 
            $dbr = wfGetDB(DB_SLAVE);
            $res = $dbr->select('twitter_users',
                                'twitter_id',
                                array('user_id' => $user->getID()),
                                'Database::select');
            if ($row = $dbr->fetchObject($res)) {
                $dbr->freeResult($res);
                $user->saveToCache();
                #$user->loadFromDatabase();
            }
            else {
                $dbr->freeResult($res);
                return false;
            }
        }
    }
    else {
        /* Check this user was created from twitter, or deny auth */ 
        $user = User::newFromName($_SESSION['access_token']['screen_name']);
        if ($user->getID() == 0) {
            /* No account with this name found, so create one */
            $user->addToDatabase();
            //$user->setRealName($obj->name);
            $user->setPassword(User::randomPassword());
            $user->setToken();
            
            /* Store user's twitter ID in separate table */
            $dbw = wfGetDB(DB_MASTER);
            $dbw->insert('twitter_users',
                         array('user_id' => $user->getID(), 'twitter_id' => $_SESSION['access_token']['screen_name']),
                         'Database::insert',
                         array());
        }
        else {
            $dbr = wfGetDB(DB_SLAVE);
            $res = $dbr->select('twitter_users',
                                'twitter_id',
                                array('user_id' => $user->getID()),
                                'Database::select');
            if ($row = $dbr->fetchObject($res)) {
                $dbr->freeResult($res);
                $user->saveToCache();
                #$user->loadFromDatabase();
            }
            else {
                $dbr->freeResult($res);
                return false;
            }
        }
    }
    $user->setCookies();
    $user->saveSettings();
    
    return true;
}

function twitter_logout(&$user, &$inject_html, $old_name) {
    if (session_id() == '') {
        session_start();
    }
    //setcookie(session_name(), session_id(), 1, '/');
    session_destroy();
    return true;
}
