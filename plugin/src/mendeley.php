<?php

defined('_JEXEC') or die('Restricted access');

jimport('mendeley.log');
jimport('mendeley.mendeley');
jimport('mendeley.tokendb');

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
                $result .= '<li>'. $this->formatDoc($doc, $formatter, $mendeley_user) . '</li>';
            }
            $result .= '</ol>';
            return $result;
        } catch (Exception $e) {
            MendeleyLog::exception($e);
            return 'Failed to insert Mendeley bibliography';
        }
    }

    private function formatDoc($doc, \mendeley\DocFormatter $formatter, $user) {
        $item = htmlspecialchars($formatter->format($doc));
        foreach ($doc->files as $file) {
            $item .= ' ' . '<a href="' . JURI::base(true) .'/index.php?option=com_mendeley&amp;user=' . $user . '&amp;doc=' . $doc->id . '&amp;file=' . $file->file_hash . '&amp;ext=' . $file->file_extension . '">' . $file->file_extension . '</a>';
        }
        return $item;
    }

    private function fetchMendeleyDocs($user) {
        $result = [];
        $accesToken = MendeleyTokenDB::getAccessToken($user);
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
}

?>
