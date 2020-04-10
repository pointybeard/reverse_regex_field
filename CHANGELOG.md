# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

**View all [Unreleased][] changes here**

## [2.0.0][] - 2020-04-10
#### Added
-   Added `extension.json`
#### Changed
-   Requring the vendor autoloader in `extension.driver.php`
-   Removed unnecessary includes of core interfaces
-   Removed PHP 5.6.x constraint and added PHP 7.3 or newer requirement to composer.json
-   Using latest version of `icomefromthenet/reverse-regex` and added `pointybeard/symphony-extended` to composer.json
-   Extension driver now Extends `AbstractExtension` from [Symphony CMS: Extended Base Class Library](https://github.com/pointybeard/symphony-extended)

## 1.0.0 - 2018-10-09
#### Added
-   Initial release

[Unreleased]: https://github.com/pointybeard/uuidfield/compare/2.0.0...integration
[2.0.0]: https://github.com/pointybeard/uuidfield/compare/1.0.0...2.0.0
