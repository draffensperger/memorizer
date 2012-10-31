-- phpMyAdmin SQL Dump
-- version 2.9.1.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Dec 16, 2006 at 12:40 PM
-- Server version: 5.0.27
-- PHP Version: 5.1.1
-- 
-- Database: `memorizer`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `config`
-- 

CREATE TABLE `config` (
  `Name` varchar(20) NOT NULL,
  `Value` varchar(100) NOT NULL,
  PRIMARY KEY  (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 
-- Dumping data for table `config`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `memoryhistory`
-- 

CREATE TABLE `memoryhistory` (
  `UserID` int(11) NOT NULL,
  `MemoryItemID` int(11) NOT NULL,
  `NumForwardTested` int(11) NOT NULL,
  `NumForwardCorrect` int(11) NOT NULL,
  `NumBackwardTested` int(11) NOT NULL,
  `NumBackwardCorrect` int(11) NOT NULL,
  `NumCorrectInARow` int(11) NOT NULL,
  `LastTimeTested` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`UserID`,`MemoryItemID`),
  KEY `MemoryItemID` (`MemoryItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 
-- Dumping data for table `memoryhistory`
-- 

INSERT INTO `memoryhistory` (`UserID`, `MemoryItemID`, `NumForwardTested`, `NumForwardCorrect`, `NumBackwardTested`, `NumBackwardCorrect`, `NumCorrectInARow`, `LastTimeTested`) VALUES 
(1, 1, 16, 16, 1, 1, 17, '2006-12-16 12:35:33'),
(1, 2, 1, 1, 1, 1, 2, '2006-12-16 12:35:54'),
(1, 3, 3, 1, 2, 2, 1, '2006-12-16 12:37:37');

-- --------------------------------------------------------

-- 
-- Table structure for table `memoryitem`
-- 

CREATE TABLE `memoryitem` (
  `MemoryItemID` int(11) NOT NULL auto_increment,
  `MemorySetID` int(11) NOT NULL,
  `CueOrder` int(11) NOT NULL,
  `CueText` varchar(255) NOT NULL,
  `DataText` varchar(255) NOT NULL,
  `DataSoundFile` varchar(50) default NULL,
  PRIMARY KEY  (`MemoryItemID`),
  KEY `MemorySetID` (`MemorySetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- 
-- Dumping data for table `memoryitem`
-- 

INSERT INTO `memoryitem` (`MemoryItemID`, `MemorySetID`, `CueOrder`, `CueText`, `DataText`, `DataSoundFile`) VALUES 
(1, 1, 1, '1:1', 'Paul, an apostle of Christ Jesus by the will of God, To the saints who are in Ephesus, and are faithful in Christ Jesus:', NULL),
(2, 1, 2, '1:2', 'Grace to you and peace from God our Father and the Lord Jesus Christ.', NULL),
(3, 1, 3, '1:3', 'Blessed be the God and Father of our Lord Jesus Christ, who has blessed us in Christ with every spiritual blessing in the heavenly places,', NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table `memoryset`
-- 

CREATE TABLE `memoryset` (
  `MemorySetID` int(11) NOT NULL auto_increment,
  `MemorySetName` varchar(100) NOT NULL,
  `ForwardTestRatio` double NOT NULL,
  `MinCorrectnessRatio` double NOT NULL,
  `MinNumCorrectInARow` int(11) NOT NULL,
  PRIMARY KEY  (`MemorySetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- 
-- Dumping data for table `memoryset`
-- 

INSERT INTO `memoryset` (`MemorySetID`, `MemorySetName`, `ForwardTestRatio`, `MinCorrectnessRatio`, `MinNumCorrectInARow`) VALUES 
(1, 'Ephesians (ESV) Sequential', 1, 0, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table `user`
-- 

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL auto_increment,
  `UserName` varchar(20) NOT NULL,
  PRIMARY KEY  (`UserID`),
  UNIQUE KEY `UserName` (`UserName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- 
-- Dumping data for table `user`
-- 

INSERT INTO `user` (`UserID`, `UserName`) VALUES 
(1, 'dave');

-- 
-- Constraints for dumped tables
-- 

-- 
-- Constraints for table `memoryhistory`
-- 
ALTER TABLE `memoryhistory`
  ADD CONSTRAINT `memoryhistory_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`),
  ADD CONSTRAINT `memoryhistory_ibfk_2` FOREIGN KEY (`MemoryItemID`) REFERENCES `memoryitem` (`MemoryItemID`);

-- 
-- Constraints for table `memoryitem`
-- 
ALTER TABLE `memoryitem`
  ADD CONSTRAINT `memoryitem_ibfk_1` FOREIGN KEY (`MemorySetID`) REFERENCES `memoryset` (`MemorySetID`);
