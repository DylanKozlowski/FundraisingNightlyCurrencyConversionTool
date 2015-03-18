Create Table conversionRates
(
Id int NOT NULL AUTO_INCREMENT,
Currency char(3) UNIQUE,
Rate float(10),
inputTime Timestamp,
Primary Key (Id)

)