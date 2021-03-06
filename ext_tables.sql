#
# Table structure for table 'tx_devlog_domain_model_entry'
#
CREATE TABLE tx_devlog_domain_model_entry (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	run_id varchar(50) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	severity int(11) DEFAULT '0' NOT NULL,
	extkey varchar(255) DEFAULT '' NOT NULL,
	message text NOT NULL,
	location varchar(255) DEFAULT '' NOT NULL,
	ip varchar(255) DEFAULT '' NOT NULL,
	line int(11) DEFAULT '0' NOT NULL,
	extra_data blob,

	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)

);