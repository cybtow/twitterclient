<?php

namespace Cybtow\TwitterClient;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * TwitterClient
 * @see https://github.com/cybtow/twitterclient
 * @uses https://github.com/abraham/twitteroauth
 */
class TwitterClient
{
    const API_TWITTER_OAUTH_AUTHENTICATE_URL = 'https://api.twitter.com/oauth/authenticate?oauth_token=';

    private $consumerKey;
    private $consumerSecret;
    private $oauthToken;
    private $oauthTokenSecret;
    private $oauthCallback;

    /** @var TwitterOAuth */
    private $conn;

    /**
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $oauthCallback
     * @param string $oauthToken
     * @param string $oauthTokenSecret
     */
    public function __construct($consumerKey, $consumerSecret, $oauthCallback = null, $oauthToken = null, $oauthTokenSecret = null)
    {
        $this->consumerKey      = $consumerKey;
        $this->consumerSecret   = $consumerSecret;
        $this->oauthCallback    = $oauthCallback;
        $this->oauthToken       = $oauthToken;
        $this->oauthTokenSecret = $oauthTokenSecret;
    }

    /**
     * @return string
     */
    public function getOauthToken()
    {
        return $this->oauthToken;
    }

    /**
     * @return string
     */
    public function getOauthTokenSecret()
    {
        return $this->oauthTokenSecret;
    }

    /**
     * @param string $oauthToken
     * @return TwitterClient
     */
    public function setOauthToken($oauthToken)
    {
        $this->oauthToken = $oauthToken;
        return $this;
    }

    /**
     * @param string $oauthTokenSecret
     * @return TwitterClient
     */
    public function setOauthTokenSecret($oauthTokenSecret)
    {
        $this->oauthTokenSecret = $oauthTokenSecret;
        return $this;
    }

    /**
     * @param string $oauthToken
     * @param string $oauthSecret
     */
    private function setConnection($oauthToken = '', $oauthSecret = '')
    {
        if ($this->conn instanceof TwitterOAuth) {
            return;
        }

        $token       = $oauthToken;
        $tokenSecret = $oauthSecret;

        if (empty($token)) {
            $token = $this->oauthToken;
        }
        if (empty($tokenSecret)) {
            $tokenSecret = $this->oauthTokenSecret;
        }

        if (empty($token)) {
            $this->conn = new TwitterOAuth($this->consumerKey, $this->consumerSecret);
        } else {
            $this->conn = new TwitterOAuth($this->consumerKey, $this->consumerSecret, $token, $tokenSecret);
        }
    }

    /**
     * @return string
     */
    public function getUrlLogin()
    {
        $this->setConnection();
        $res      = $this->conn->oauth('oauth/request_token', ['oauth_callback' => $this->oauthCallback]);
        $urlLogin = false;

        if (is_array($res) && isset($res['oauth_callback_confirmed']) && $res['oauth_callback_confirmed'] == 'true') {
            $this->oauthToken       = $res['oauth_token'];
            $this->oauthTokenSecret = $res['oauth_token_secret'];

            $urlLogin = \sprintf('%s%s', static::API_TWITTER_OAUTH_AUTHENTICATE_URL, $this->oauthToken);
        }

        return $urlLogin;
    }

    /**
     * @param string $oauthToken
     * @param string $oauthVerifier
     * @return boolean
     */
    public function connectFromUrl($oauthToken, $oauthVerifier)
    {
        if ($oauthToken != $this->oauthToken) {
            return false;
        }

        $this->setConnection($this->oauthToken, $this->oauthTokenSecret);
        $res = $this->conn->oauth('oauth/access_token', ['oauth_verifier' => $oauthVerifier]);

        if (!is_array($res)) {
            return false;
        }

        $this->oauthToken       = $res['oauth_token'];
        $this->oauthTokenSecret = $res['oauth_token_secret'];

        return true;
    }

    /**
     * @return array
     */
    public function getAccountVerifyCredentials()
    {
        $this->setConnection($this->oauthToken, $this->oauthTokenSecret);
        return $this->conn->get('account/verify_credentials', []);
    }
}