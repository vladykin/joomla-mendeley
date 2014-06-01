<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.log.log');
jimport('mendeley.mendeley');

class PlgContentMendeley extends JPlugin {

    public function onContentPrepare($context, &$row, &$params, $page = 0) {
        $row->text = $this->replaceMendeleyTags($row->text);
    }

    private function replaceMendeleyTags($text) {
        return preg_replace_callback(
                '(\{mendeley\}\s*(\S+?)\s*\{/mendeley\})',
                function($matches) { return $this->getMendeleyBib($matches[1]); },
                $text);
    }

    private function getMendeleyBib($mendeley_user) {
        try {
            $docs = $this->fetchMendeleyDocs($mendeley_user);
            $formatter = new \mendeley\DocFormatter();
            $result = '<ol>';
            foreach ($docs as $doc) {
                $result .= '<li>'. htmlspecialchars($formatter->format($doc)) . '</li>';
            }
            $result .= '</ol>';
            return $result;
        } catch (Exception $e) {
            JLog::addLogger(
                    ['text_file' => 'mendeley.errors.php'],
                    JLog::ALL,
                    'mendeley');
            JLog::add($e->getMessage(), JLog::ERROR, 'mendeley');
            return 'Failed to insert Mendeley bibliography';
        }
    }

    private function fetchMendeleyDocs($user) {
        $result = [];
        $accesToken = $this->getAccessToken($user);
        $m = new \mendeley\Session($accesToken);
        $docs = $m->get('library/documents/authored');
        foreach ($docs->documents as $doc) {
            $result[] = $this->fetchMendeleyDoc($doc->id, $doc->version, $m);
        }
        return $result;
    }

    private function fetchMendeleyDoc($doc_id, $doc_version, $m) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                ->select('details')
                ->from('#__mendeley_docs')
                ->where(['doc_id = ' . $db->quote($doc_id), 'version = ' . $doc_version]);
        $db->setQuery($query);
        $doc = $db->loadResult();
        if ($doc) {
            return json_decode($doc);
        } else {
            $doc = $m->get('library/documents/' . $doc_id);
            $query = $db->getQuery(true)
                    ->insert('#__mendeley_docs')
                    ->columns(['doc_id', 'version', 'details'])
                    ->values($db->quote($doc_id).','.$db->quote($doc_version).','.$db->quote(json_encode($doc)));
            $db->setQuery($query);
            $db->query();
            return $doc;
        }
    }

    private function getAccessToken($user) {
        $oldTokens = $this->loadTokens($user);
        if ($oldTokens) {
            $mauth = new \mendeley\OAuth(
                    $this->params->get('client_id'),
                    $this->params->get('client_secret'),
                    $this->params->get('redirect_uri'));
            $newTokens = $mauth->getFreshTokens($oldTokens);
            if ($newTokens != $oldTokens) {
                $this->saveTokens($user, $newTokens);
            }
            return $newTokens->getAccessToken();
        } else {
            throw new Exception('No tokens available for ' . $user);
        }
    }

    private function loadTokens($user) {
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

    private function saveTokens($user, $tokens) {
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

?>
