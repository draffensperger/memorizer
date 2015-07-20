-- phpMyAdmin SQL Dump
-- version 2.9.1.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Apr 04, 2007 at 11:56 PM
-- Server version: 5.0.27
-- PHP Version: 5.1.1
-- 
-- Database: `memorizer`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `common_words`
-- 

CREATE TABLE `common_words` (
  `frequency` int(11) NOT NULL,
  `word` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `config`
-- 

CREATE TABLE `config` (
  `Name` varchar(20) NOT NULL,
  `Value` varchar(100) NOT NULL,
  PRIMARY KEY  (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `NumPracticeTimesNeeded` int(11) NOT NULL,
  `LastTimeTested` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`UserID`,`MemoryItemID`),
  KEY `MemoryItemID` (`MemoryItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `memoryitem`
-- 

CREATE TABLE `memoryitem` (
  `MemoryItemID` int(11) NOT NULL auto_increment,
  `MemorySetID` int(11) NOT NULL,
  `CueText` varchar(255) NOT NULL,
  `DataText` varchar(255) NOT NULL,
  `CueOrder` int(11) NOT NULL,
  PRIMARY KEY  (`MemoryItemID`),
  UNIQUE KEY `CueText_DataText` (`CueText`,`DataText`),
  KEY `MemorySetID` (`MemorySetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=3873 ;

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
  `NumPracticeTimes` int(11) NOT NULL,
  `WorkingSetSize` int(11) NOT NULL,
  `NewVocabRatio` double NOT NULL,
  PRIMARY KEY  (`MemorySetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

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

-- --------------------------------------------------------

-- 
-- Table structure for table `memoryitempriority`
-- 

CREATE VIEW `memoryitempriority` AS select `memoryitem`.`DataText` AS `DataText`,`memoryitem`.`CueText` AS `CueText`,(case when isnull(`memoryhistory`.`NumForwardTested`) then _utf8'Untested' when (`memoryhistory`.`NumPracticeTimesNeeded` > 0) then _utf8'NeedsPractice' when (((`memoryhistory`.`NumForwardCorrect` + `memoryhistory`.`NumBackwardCorrect`) / (`memoryhistory`.`NumForwardTested` + `memoryhistory`.`NumBackwardTested`)) < `memoryset`.`MinCorrectnessRatio`) then _utf8'Unlearned' else _utf8'Learned' end) AS `Category`,(case when isnull(`memoryhistory`.`NumForwardTested`) then 3 when (`memoryhistory`.`NumPracticeTimesNeeded` > 0) then 0 when (((`memoryhistory`.`NumForwardCorrect` + `memoryhistory`.`NumBackwardCorrect`) / (`memoryhistory`.`NumForwardTested` + `memoryhistory`.`NumBackwardTested`)) < `memoryset`.`MinCorrectnessRatio`) then 1 else 2 end) AS `CategoryRank`,(case when isnull(`memoryhistory`.`NumForwardTested`) then 0 else ((`memoryhistory`.`NumForwardCorrect` + `memoryhistory`.`NumBackwardCorrect`) / (`memoryhistory`.`NumForwardTested` + `memoryhistory`.`NumBackwardTested`)) end) AS `CorrectnessRatio`,`memoryitem`.`MemoryItemID` AS `MemoryItemID`,`memoryitem`.`MemorySetID` AS `MemorySetID`,`memoryhistory`.`UserID` AS `UserID`,ifnull(`memoryhistory`.`NumForwardTested`,0) AS `NumForwardTested`,ifnull(`memoryhistory`.`NumForwardCorrect`,0) AS `NumForwardCorrect`,ifnull(`memoryhistory`.`NumBackwardTested`,0) AS `NumBackwardTested`,ifnull(`memoryhistory`.`NumBackwardCorrect`,0) AS `NumBackwardCorrect`,ifnull(`memoryhistory`.`NumCorrectInARow`,0) AS `NumCorrectInARow`,`memoryhistory`.`LastTimeTested` AS `LastTimeTested`,`memoryhistory`.`NumPracticeTimesNeeded` AS `NumPracticeTimesNeeded` from ((`memoryitem` left join `memoryhistory` on((`memoryitem`.`MemoryItemID` = `memoryhistory`.`MemoryItemID`))) join `memoryset` on((`memoryset`.`MemorySetID` = `memoryitem`.`MemorySetID`))) order by `memoryhistory`.`LastTimeTested` desc;

-- --------------------------------------------------------

-- 
-- Table structure for table `progress_snapshot`
-- 

CREATE VIEW `progress_snapshot` AS select `memoryitempriority`.`Category` AS `Category`,count(0) AS `COUNT( * )` from `memoryitempriority` group by `memoryitempriority`.`Category` order by 2 desc;

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

-- Add QuizStartTime
ALTER TABLE `memoryset` ADD COLUMN QuizStartTime datetime;
