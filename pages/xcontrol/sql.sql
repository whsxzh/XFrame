

ALTER TABLE `highbase`.`hb_invitecode` 
DROP COLUMN `date_added`;

ALTER TABLE `highbase`.`hb_invitecode` 
ADD COLUMN `date_added` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
ADD COLUMN `end_date` DATETIME NULL AFTER `telephone`,
ADD COLUMN `url` VARCHAR(300) NULL AFTER `end_date`;

//------------

ALTER TABLE `highbase`.`hb_permission` 
ADD COLUMN `indexfrom` CHAR(1) NULL COMMENT '1 原来的路由\nx 新的路由' AFTER `date_added`;
