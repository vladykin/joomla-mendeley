<?php

class MendeleyLog {

    const LOGGER_NAME = 'mendeley';

    private static $loggerInitialized = false;

    public static function exception(Exception $e) {
        self::initLogger();
        JLog::add($e->getMessage(), JLog::ERROR, self::LOGGER_NAME);
    }

    private static function initLogger() {
        if (!self::$loggerInitialized) {
            JLog::addLogger(
                    ['text_file' => 'mendeley.errors.php'],
                    JLog::ALL,
                    self::LOGGER_NAME);
            self::$loggerInitialized = true;
        }
    }
}
