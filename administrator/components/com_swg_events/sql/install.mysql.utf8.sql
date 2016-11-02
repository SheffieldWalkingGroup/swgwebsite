DROP TABLE IF EXISTS `#__swg_events_protocolreminders;

CREATE TABLE `j_swg_events_protocolreminders` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `eventtype` tinyint(3) unsigned NOT NULL COMMENT 'Display this reminder if this type of event is on the page',
  `ordering` smallint(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `eventtype` (`eventtype`,`ordering`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;