<?php

defined('_JEXEC') or die;

$input = JFactory::getApplication()->input;
$task = $input->getCmd('task');

$controller = JControllerLegacy::getInstance('Mendeley');
$controller->execute($task);
