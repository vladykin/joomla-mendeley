<?php

defined('_JEXEC') or die;

jimport('mendeley.log');
jimport('mendeley.mendeley');
jimport('mendeley.tokendb');

$app = JFactory::getApplication();
$input = $app->input;
$user = $input->getWord('user');
$docId = $input->getUint('doc');
$fileHash = $input->getAlnum('file');
$type = $input->getWord('type', 'pdf');

if ($user && $docId && $fileHash) {
    $params = JComponentHelper::getParams('com_mendeley');
    $destFileRel = '/' . $params->get('storage_folder') . '/' . $docId . '.' . $fileHash . '.' . $type;
    $destFileAbs = JPATH_BASE . $destFileRel;
    try {
        if (!file_exists($destFileAbs)) {
            $accessToken = MendeleyTokenDB::getAccessToken($user);
            $mendeleySession = new \mendeley\Session($accessToken);
            $mendeleySession->downloadFile($docId, $fileHash, $destFileAbs);
        }
        header('Location: ' . JURI::base(true) . $destFileRel);
        $app->close();
    } catch (Exception $e) {
        MendeleyLog::exception($e);
        unlink($destFileAbs);
        JError::raiseError(404, JText::_("File not found"));
    }
}
