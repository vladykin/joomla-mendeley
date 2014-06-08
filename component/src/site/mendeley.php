<?php

defined('_JEXEC') or die;

jimport('mendeley.mendeley');
jimport('mendeley.tokendb');

$input = JFactory::getApplication()->input;
$user = $input->getWord('user');
$docId = $input->getUint('doc');
$fileHash = $input->getAlnum('file');

if ($user && $docId && $fileHash) {
    $accessToken = MendeleyTokenDB::getAccessToken($user);
    $mendeleySession = new \mendeley\Session($accessToken);

    $params = JComponentHelper::getParams('com_mendeley');
    $destFileRel = '/' . $params->get('storage_folder') . '/' . $docId . '.' . $fileHash . '.pdf';
    $destFileAbs = JPATH_BASE . $destFileRel;
    $mendeleySession->downloadFile($docId, $fileHash, $destFileAbs);
    header('Location: ' . JURI::base(true) . $destFileRel);
    JFactory::getApplication()->close();
}
