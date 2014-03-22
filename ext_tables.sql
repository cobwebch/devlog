#
# Table structure for table 'tx_devlog'
#
CREATE TABLE tx_devlog (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	crmsec bigint(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	severity int(11) DEFAULT '0' NOT NULL,
	extkey varchar(100) DEFAULT '' NOT NULL,
	msg text NOT NULL,
	location varchar(255) DEFAULT '' NOT NULL,
	line int(11) DEFAULT '0' NOT NULL,
	data_var mediumtext,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY crdate (crdate),
	KEY crmsec (crmsec)
) ENGINE = InnoDB;