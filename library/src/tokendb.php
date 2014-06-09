<?php

defined('_JEXEC') or die('Restricted access');

jimport('mendeley.mendeley');

class MendeleyTokenDB {

    public static function getAccessToken($user) {
        $oldTokens = self::loadTokens($user);
        if ($oldTokens) {
            $params = JComponentHelper::getParams('com_mendeley');
            $oauth = new \mendeley\OAuth(
                    $params->get('client_id'),
                    $params->get('client_secret'),
                    $params->get('redirect_uri'));
            $newTokens = $oauth->getFreshTokens($oldTokens);
            if ($newTokens != $oldTokens) {
                self::saveTokens($user, $newTokens);
            }
            return $newTokens->getAccessToken();
        } else {
            throw new Exception('No tokens available for ' . $user);
        }
    }

    private static function loadTokens($user) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                ->select(['access_token', 'refresh_token', 'expire_time'])
                ->from('#__mendeley_tokens')
                ->where('username = ' . $db->quote($user));
        $db->setQuery($query);
        $row = $db->loadAssoc();
        if ($row) {
            return new \mendeley\Tokens(
                    $row['access_token'],
                    $row['expire_time'],
                    $row['refresh_token']);
        } else {
            return false;
        }
    }

    private static function saveTokens($user, $tokens) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                ->update('#__mendeley_tokens')
                ->set([
                        'access_token = ' . $db->quote($tokens->getAccessToken()),
                        'refresh_token = ' . $db->quote($tokens->getRefreshToken()),
                        'expire_time = ' . $db->quote($tokens->getExpireTime())])
                ->where('username = ' . $db->quote($user));
        $db->setQuery($query);
        $db->query();
    }
}
