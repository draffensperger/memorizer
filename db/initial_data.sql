INSERT INTO `memoryset`
(`MemorySetID`,
`MemorySetName`,
`ForwardTestRatio`,
`MinCorrectnessRatio`,
`MinNumCorrectInARow`,
`NumPracticeTimes`,
`WorkingSetSize`,
`NewVocabRatio`)
VALUES
(
1,
"German Animals",
0.2,
0.5,
2,
2,
10,
0.2
);

INSERT INTO `user` (`UserID`, `UserName`) VALUES 
(1, 'dave');
