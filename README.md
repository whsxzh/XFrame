# XFrame

XFrame is a data-oriented programming, very easy to learn php framework, features as follows
1. easy to learn to get started 
2. powerful 
3. object-oriented
4. particularly good at mysql processing 
5. view and business logic completely separate 
6. own rendering Template engine 
7. own data cache engine, you need to install the local memcache environment 
8. easy to expand, you can be transformed into any applicable to their own framework 
9. template engine and data engine separation, you can arbitrarily assembled, used alone or according to their own The need for transformation

Author: Gordon Xi Email: zuoruanjian@qq.com please feel free to communicate with me, I am willing to support you free access to this framework
 
XFrame 是一个面向数据编程的、非常容易学习的php框架，特点如下
1.容易学习上手
2.功能强大
3.面向对象
4.特别擅长mysql的处理
5.视图和业务逻辑彻底分离
6.自带渲染模版引擎
7.自带数据缓存引擎，需要本地安装memcache环境
8.便于扩展，你可以根据需要改造成任何适用自己的框架
9.模版引擎和数据引擎分离，你可以任意组装，单独使用或者根据自己的需要改造

作者：Gordon Xi 
Email:zuoruanjian@qq.com 欢迎随时和我交流，我愿意支持你免费使用这个框架

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
