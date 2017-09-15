Yii Shared tools
================

This extension provides shared components for Yii2 we use in our company.

Classes
-------
* DbModel - _ActiveRecord parent class_
* Def - _Common constants and definitions_
* DT - _Helper class for date time operations_
* Formatter - _Extended Yii formatter_
* FtsException - _Exception with automatic logging_
* IpFilter - _IP filter behavior_
* ModelSaveException - _Exception intended for handling model save_
* TestAssetManager - _Asset manager replacement used in functional tests to prevent asset publishing_
* Tools - _Common tools that requires Yii to work_

Changelog
---------

### 1.3.0
* Added TestAssetManager

### 1.2.0
* Added Formatter

### 1.1.0
* Added DT::displayDate()
* Added DT::displayDateTime()
* Added DT::displayTime()

### 1.0.0
* Added DT::toTimezone()
