create table if not exists ipol_apiship
(
    ID int(11) NOT NULL auto_increment,
	MESS_ID int(6),
	PARAMS text,
	ORDER_ID int(11),
	apiship_ID int(12),
	STATUS varchar(40),
	MESSAGE text,
	OK varchar(1),
	UPTIME varchar(10),
	PRIMARY KEY(ID),
	INDEX ix_ipol_apishipoi (ORDER_ID)
);