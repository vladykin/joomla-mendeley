<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

require_once('lib/Mendeley.php');

class PlgContentMendeley extends JPlugin {

    public function onContentPrepare($context, &$row, &$params, $page = 0) {
        restore_exception_handler();
        $row->text = $this->replaceMendeleyTags($row->text);
    }

    private function replaceMendeleyTags($text) {
        return preg_replace_callback(
                '(\{mendeley\}\s*(\S+?)\s*\{/mendeley\})',
                function($matches) { return $this->getMendeleyBib($matches[1]); },
                $text);
    }

    private function getMendeleyBib($mendeley_user) {
        $items = $this->fetchMendeleyDocs($mendeley_user);
        $result = '<ol>';
        foreach ($items as $item) {
            $result .= '<li>'. $this->formatBibItem($item) . '</li>';
        }
        $result .= '</ol>';
        return $result;
    }

    private function formatBibItem($item) {
        $result = '';
        foreach ($item->authors as $author) {
            $result .= $author->surname . ' ' . $author->forename . ', ';
        }
        $result .= $item->title . ' / ' . $item->published_in . ' (' . $item->year . ') С. ' . $item->pages;
        $result .= "\n" . htmlspecialchars(json_encode($item)); 
        return $result;
    }

    private function fetchMendeleyDocs($user) {
        $result = array();
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
                ->where(array('doc_id = ' . $db->quote($doc_id), 'version = ' . $doc_version));
        $db->setQuery($query);
        $doc = $db->loadResult();
        if ($doc) {
            return json_decode($doc);
        } else {
            $doc = $m->get('library/documents/' . $doc_id);
            $query = $db->getQuery(true)
                    ->insert('#__mendeley_docs')
                    ->columns(array('doc_id', 'version', 'details'))
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
                ->select(array('access_token', 'refresh_token', 'expire_time'))
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
                ->set(array(
                        'access_token = ' . $db->quote($tokens->getAccessToken()),
                        'refresh_token = ' . $db->quote($tokens->getRefreshToken()),
                        'expire_time = ' . $db->quote($tokens->getExpireTime())))
                ->where('username = ' . $db->quote($user));
        $db->setQuery($query);
        $db->query();
    }

}

?>
