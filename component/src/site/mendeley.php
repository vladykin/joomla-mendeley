<?php

defined('_JEXEC') or die;

jimport('mendeley.mendeley');
jimport('mendeley.tokendb');

$input = JFactory::getApplication()->input;
$user = $input->getWord('user');
$docId = $input->getUint('doc');
$fileHash = $input->getAlnum('file');
$format = $input->getWord('format', 'pdf');

if ($user && $docId && $fileHash) {
    $params = JComponentHelper::getParams('com_mendeley');
    $destFileRel = '/' . $params->get('storage_folder') . '/' . $docId . '.' . $fileHash . '.' . $format;
    $destFileAbs = JPATH_BASE . $destFileRel;
    if (!file_exists($destFileAbs)) {
        $accessToken = MendeleyTokenDB::getAccessToken($user);
        $mendeleySession = new \mendeley\Session($accessToken);
        $mendeleySession->downloadFile($docId, $fileHash, $destFileAbs);
    }
    header('Location: ' . JURI::base(true) . $destFileRel);
    JFactory::getApplication()->close();
}
