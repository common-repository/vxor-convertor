=== vXor Convertor ===
Contributors: yeyezai
Donate link: http://www.yeyezai.com
Tags: convert, blog, database, wordpress, AJAX, admin
Requires at least: 2.9.2
Tested up to: 3.0
Stable tag: 1.1.1

Convert multiple blogs to WordPress.


== Description ==

vXor Convertor is an easy-to-use blog data plugin for WordPress. This utility is able to convert multiple data formats (MySQL, Acess and XML files exported by Blog Service Provider) to WordPress. No user intervention is required for the conversion. 

vXor Convertor 是一款简单易用的 WordPress 博客数据转换平台插件。它支持多种博客数据格式（MySQL、Access 及 BSP 导出的 XML 文件）转到 WordPress。转换前仅需简单设置，转换过程无需人工干涉即可完成。

**Features**

* 设置简单，操作更简单，转换过程无需人工干涉。
* 支持多种博客数据格式（MySQL、Access、XML）。
* 支持同一服务器上不同数据库的转换。
* 不修改原博客数据库任何信息。
* 支持扩展转换步骤。
* 支持转换插件，可编写自己的转换插件。
* 支持错误信息提示。
* 转换完成后自动卸载。

**Supported Languages**

* US English/en_US (default)
* 简体中文/zh_CN (translate by [yeyezai](http://www.yeyezai.com/))


== Installation ==

**Installation**

1. Download.
2. Unzip.
3. Upload to the plugins directory (wp-content/plugins).
4. Activate the plugin.

**Usage**

1. Go to `Tools -> Imports` and select impoter/convertor.
2. Setting database (MySQL, Access), or upload/select xml file(BSP).
3. Setting extends.
4. Start to convert. Please read `readme.txt` in convert plugins folder.
5. finish.

Notice: Please copy the mdb or xml files to folder `/plugins/vxor-convertor/_temp`.

**安装** 

1. 下载。
2. 解压缩。
3. 上传至插件目录 (wp-content/plugins)。
4. 激活插件。

**使用**

1. 进入 `工具 -> 导入`，选择转换插件程序。
2. 设置数据库(MySQL, Access)，或是上传/选择 xml 文件(BSP)。
3. 设置扩展选项。
4. 开始转换。请阅读转换插件 `readme.txt` 文件。
5. 完成。

注意：请将 mdb 或 xml 文件放到 `/plugins/vxor-convertor/_temp` 文件夹下。


== Screenshots ==

1. Convertor selection. 选择转换插件。
2. Database settings. 数据库设置。
3. Extend settings. 扩展设置。
4. Converting. 转换进行中。


== Changelog ==
More changelog，please read changelog.txt.

更多更新内容，请见 Changelog.txt。


= 1.1.1 (2010/06/20) =
* 添加 部分函数。
* 修正 PJBlog3 转换插件。

= 1.1.0 (2010/06/20) =
* 添加 支持 Access 数据库转换。
* 添加 部分函数。
* 修正 部分 Bug。


= 1.0.0 (2010/05/17) =
* 添加 支持 WordPress 2.9.2。
* 添加 支持 MySQL 数据库转换
* 添加 附加选项设置。
* 添加 jQuery & AJAX。
* 添加 F2blog.cont 转换插件。


== Frequently Asked Questions ==

**我使用的是 XXX 博客，可以转换到 WordPress 吗？**

请见转换插件下载。
您也可以编写自己的转换插件，具体请见转换插件开发指南。

**我想修改转换步骤的第 X 步，可以吗？**

可以。
您也可以在转换插件文件夹下新建一个 `extend_step_对应步骤数.php` 的 php 文件，转换程序在转换第 X 步时将优先选择该步骤文件。

**我原来博客上的某些数据，转换到 WordPress 后怎么就不见了？**

通过插件完成的功能，默认是不支持转换的。
请确认这些数据在原博客上是否是通过插件实现的，或该数据在 WordPress 必须通过插件实现的。
您可以联系转换插件作者。


== Convertor Plugins ==

**Plugins Development**

转换插件开发指南：Coming soon...

**Plugins Downloads**

转换插件下载：Coming soon...
