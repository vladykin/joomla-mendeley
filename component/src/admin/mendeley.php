<?php

defined('_JEXEC') or die('Restricted access');

$input = JFactory::getApplication()->input;
$task = $input->getCmd('task');

$controller = JControllerLegacy::getInstance('Mendeley');
$controller->execute($task);
