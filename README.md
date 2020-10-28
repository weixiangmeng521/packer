# packer打包工具
PHP的web前端开发打包工具，借鉴了基于nodejs的vue-cli的模式。适用于小型模版页面的开发。打包可将html，css，js，image合并成一个html文件，并且对css具有兼容处理，并且会删除一些不必要的文件依赖。


### 环境依赖
- 采用了php的workerman用来做live server，所以php版本不低于7，且支持pcntl模块
- 使用postcss对css来做兼容处理，所以你需要下载nodejs，版本越高越好

### 功能和使用场景
将所有文件打包成一个文件，减少连接和请求次数，提高请求速度。主要是为了便捷模版页面的开发。支持php的模版语法，可用`{{ $key }}`来在模版中输出php变量。

### 怎么跑起来？
下载nodejs依赖
```
npm i
```
支持产品模式和开发模式。只用在启动程序的时候，指定工程文件项目index.html，就可以开发了。
- 修改index.php的`$path`变量，指定模版路径预览模版路径
```php
$path = __DIR__ . DIRECTORY_SEPARATOR . "web" . DIRECTORY_SEPARATOR."index.html";
$liveServer = new LiveServer($path);
```

- 修改packer中的`$input`变量，指定打包文件路径
```php
$input = __DIR__ . DIRECTORY_SEPARATOR . "web" . DIRECTORY_SEPARATOR . "index.html";
$w = new Writer($input);
$w->compile();
```

### 命令行操作指令
- 在工程根目录下，启动dev模式。这个模式下，可对web实时预览。
```
php packer dev
```
- 通过以下命令打包文件，可以在根目录的dist文件夹下，看到打包后的结果。对于html文件内的引用，暂时不支持外部连接。
```
php packer build
```

### 模版引擎的使用
编辑在根目录的mock文件夹下Mock.php设置需要渲染的变量。[暂时不支持热更新]
```php
return [
    "index.html" => [
        "amount"      => "50元",
        "pay_account" => "10元",
    ],
];
```
如：我在index.html里面会渲染amount和pay_account两个数据。

### 开发规范
web的依赖最好只在工程目录之下。
```
|--index.html
|--css
|-----main.css
|--js
|-----main.js
|--img
|-----1.jpg
```









