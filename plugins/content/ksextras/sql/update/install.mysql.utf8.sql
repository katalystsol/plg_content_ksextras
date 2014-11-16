CREATE TABLE IF NOT EXISTS `#__content_ksextras` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`article_id` int(11) unsigned NOT NULL COMMENT 'Foreign Key: the corresponding article id',
	`data` text COMMENT 'Stores the extra field values in JSON format',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'The datetime the item was created',
	`created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign Key: the user id for who created the item',
	`modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'The datetime the item was last modified',
	`modified_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign Key: the user id for who last modified the item',
	PRIMARY KEY (`id`),
	KEY `idx_article_id` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 COMMENT='This table is a companion table to #__content and will use its access levels, etc.';