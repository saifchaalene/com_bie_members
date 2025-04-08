CREATE TABLE IF NOT EXISTS `#__bie_members_delegates` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`state` TINYINT(1)  NULL  DEFAULT 1,
`ordering` INT(11)  NULL  DEFAULT 0,
`checked_out` INT(11)  UNSIGNED,
`checked_out_time` DATETIME NULL  DEFAULT NULL ,
`created_by` INT(11)  NULL  DEFAULT 0,
`modified_by` INT(11)  NULL  DEFAULT 0,
`country_id` SMALLINT NULL  DEFAULT 0,
`gender_id` TINYTEXT NULL  DEFAULT "",
`contact_id` INT NULL  DEFAULT 0,
`prefix` TEXT NULL ,
`first_name` VARCHAR(100)  NULL  DEFAULT "",
`last_name` VARCHAR(255)  NULL  DEFAULT "",
`gender` TEXT NULL ,
`organisation` TEXT NULL ,
`group` TEXT NULL ,
`preferred_language` TEXT NULL ,
`date_of_announce` DATE NULL  DEFAULT NULL,
`job_title` VARCHAR(255)  NULL  DEFAULT "",
`primary_email` VARCHAR(255)  NULL  DEFAULT "",
`secondary_email` VARCHAR(255)  NULL  DEFAULT "",
`city` VARCHAR(255)  NULL  DEFAULT "",
`street_address` VARCHAR(255)  NULL  DEFAULT "",
`supplemental_address_1` VARCHAR(255)  NULL  DEFAULT "",
`supplemental_address_2` VARCHAR(255)  NULL  DEFAULT "",
`postal_code` VARCHAR(100)  NULL  DEFAULT "",
`country` TEXT NULL ,
`phone` VARCHAR(255)  NULL  DEFAULT "",
`mobile_phone` VARCHAR(255)  NULL  DEFAULT "",
`website` VARCHAR(255)  NULL  DEFAULT "",
`facebook` VARCHAR(255)  NULL  DEFAULT "",
`twitter` VARCHAR(255)  NULL  DEFAULT "",
`notes` TEXT NULL ,
PRIMARY KEY (`id`)
,KEY `idx_state` (`state`)
,KEY `idx_checked_out` (`checked_out`)
,KEY `idx_created_by` (`created_by`)
,KEY `idx_modified_by` (`modified_by`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `#__bie_members_delegates_gender_id` ON `#__bie_members_delegates`(`gender_id`);

CREATE INDEX `#__bie_members_delegates_contact_id` ON `#__bie_members_delegates`(`contact_id`);

