
--If the database already exists but you want to create it
--again, use the following lines. Just remove the -- at
--the beginning of the following 2 lines.

--DROP DATABASE IF EXISTS TermRouter;
--CREATE DATABASE TermRouter;

USE TermRouter;

DROP TABLE IF EXISTS `fromterminal`;
CREATE TABLE IF NOT EXISTS `fromterminal` (
  `user` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `serverid` varchar(50) NOT NULL,
  `termid` varchar(50) NOT NULL,
  `pid` varchar(3) DEFAULT NULL,
  `chksum` varchar(3) DEFAULT NULL,
  `data` varchar(10000) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user` (`user`),
  KEY `termid` (`termid`),
  KEY `serverid` (`serverid`),
  KEY `password` (`password`),
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `fromserver`;
CREATE TABLE IF NOT EXISTS `fromserver` (
  `user` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `serverid` varchar(50) NOT NULL,
  `termid` varchar(50) NOT NULL,
  `pid` varchar(3) DEFAULT NULL,
  `data` varchar(64000) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user` (`user`),
  KEY `termid` (`termid`),
  KEY `serverid` (`serverid`),
  KEY `password` (`password`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `serverinit`;
CREATE TABLE IF NOT EXISTS `serverinit` (
  `user` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `serverid` varchar(50) NOT NULL,
  `termid` varchar(50) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `user` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `serverid` varchar(50) NOT NULL,
  `termid` varchar(50) NOT NULL,
  `data` varchar(64000) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user` (`user`),
  KEY `termid` (`termid`),
  KEY `serverid` (`serverid`),
  KEY `password` (`password`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `serverlog`;
CREATE TABLE IF NOT EXISTS `serverlog` (
  `user` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `serverid` varchar(50) NOT NULL,
  `termid` varchar(50) NOT NULL,
  `pid` varchar(3) DEFAULT NULL,
  `data` varchar(1000) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `appserver_ip` varchar(20) NOT NULL,
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `terminallog`;
CREATE TABLE IF NOT EXISTS `terminallog` (
  `user` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `serverid` varchar(50) NOT NULL,
  `termid` varchar(50) NOT NULL,
  `pid` varchar(3) DEFAULT NULL,
  `data` varchar(2000) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
