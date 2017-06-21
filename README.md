# XFrame

Instructions for use: 
api directory： A return to json format api interface
index.php： entry file is generally used， index.php? M = class & act = function ，Each class represents a code set to deal with an object Each method represents an api 

pages: directory to return to the page .The normal use of index.php entry file is index.php? M = class & act = function Each class represents a code set that handles an object. Each method represents a page

includes:A common library file
  Config.php: database configuration file and other necessary parameters of the configuration file
  Connect.php: database operation file, you can read through the understanding of the database operation method

Lib: library files
   tmp.php: view renders the library file

View: view folder class_function.html conventions to correspond to the method of the controller
control: controller folder class name and file name must be consistent


 使用说明：
 api目录 一个返回json格式的api接口
 	index.php 入口文件 一般的用法是 index.php?m=class&act=function  每个类代表处理一个对象的代码集 每个方法代表一个api
 pages 目录 返回页面的正常网页
 	index.php 入口文件 一般的用法是 index.php?m=class&act=function  每个类代表处理一个对象的代码集 每个方法代表一个页面

 	includes 通用的库文件
 		config.php 数据库配置文件及其他必要参数的配置文件
 		connect.php 数据库操作文件，可以通过阅读了解数据库操作方法
 	lib  库文件
 		tmp.php 视图渲染库文件
 	view 视图文件夹 class_function.html的约定来和控制器的方法对应
 	control 控制器文件夹 类名和文件名必须一致 
