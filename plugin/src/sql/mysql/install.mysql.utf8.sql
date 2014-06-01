CREATE TABLE IF NOT EXISTS `#__mendeley_tokens` (
    `username` VARCHAR(100) NOT NULL,
    `access_token` VARCHAR(100) NOT NULL,
    `refresh_token` VARCHAR(100) NOT NULL,
    `expire_time` INT NOT NULL,
    PRIMARY KEY (`username`)
) ENGINE=MyISAM CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__mendeley_docs` (
    `doc_id` BIGINT NOT NULL,
    `version` BIGINT NOT NULL,
    `details` VARCHAR(10000) NOT NULL,
    PRIMARY KEY (`doc_id`, `version`)
) ENGINE=MyISAM CHARSET=utf8;
