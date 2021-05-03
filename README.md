# DsFramework PHP
dsframework is framework written in php syntax.
This framework required php version 7.3 or higher.

## Getting Started
### Terminal Command
Run it with command terminal in <code>'/dscommunity'</code> folder <br />
for share local server with ip, write this command below
<code>php ds run</code><br />
or with your ip for open site <code>php ds run:yourip</code>
example to run on your localhost <code>php ds run:localhost</code><br />
terminal will be shown message : <br />
```
X:\xxx\dscommunity> php ds run
Ds server started : localhost:8000
Ctrl+C to exit the server.
```
### Running Page
Open browser and write url when server running <br />
Ex : <code>http://localhost:8000</code>

## Setup
Configuration environment 
<ul>
  <li> routing</li>
<li> database</li>
<li> folder structure</li>
<li> host server</li>
  <li> etc </li>
</ul>
it's in <code>/dscommunity/config/config.inc.php</code> file
<br />

## Controller
### Generate Controller File
Create controller file with command <code>php ds add:controller controllername</code> <br />
and controller file will generated automatically created in <code>'/app/controllers/controllernameController.php'</code> folder.<br />

### Generate Controllers
To generate bulk controllers just adding controller name separate by comma.<br />
Examples : <code>php ds add:controller Name1,Name2,Name3,....</code><br />
and response message will be shown :
```
X:\xxx\dscommunity> php ds add:controller Flight1,Plane2,Office3
DsResponse : Name1Controller has been successfully created!
DsResponse : Name2Controller has been successfully created!
DsResponse : Name3Controller has been successfully created!
```

### Remove Controller File
To remove controller file just write this command <code>php ds delete:controller controllername</code> <br />
or to bulk remove controller with <code>php ds delete:controller controllername1,controllername2,controllername3,....</code><br />
and all of that controllers file will be delete automatically. <br />

### Restore Controller File
Restore controller file when file was deleted just write command <code>php ds restore:controller controllername</code> <br />
or to restore many controller command write <code>php ds restore:controller controllername1,controllername2,controllername3,....</code><br />
and controller file will be restored automatically. <br />

## View
Create view file in <code>'/app/views/yourview.php'</code> folder. <br />
Or generate via command 'php ds add:view yourviewname'<br />
view with format <code>'.pie.php'</code> it will render as pie file.<br />

## Model
### Creating Model File
Model file is located in <code>/app/models/..</code> folder. Model file can be generate by command with 
<code>php ds add:model MyModelName</code>, And if you want to generate many models just write separate by comma, ex <code>php ds add:model myModel1,myModel2,myModel3</code> and then the model file will be automatically generated. <br />

## Keep Save
### Restore File
Don't worry when accidentally remove controller file or model file. The Controller and Model file can be remove with command and can be restore too. To restore the Controller File write command <code>php ds restore:controller myOldController</code>. And to restore Model File write command <code>php ds restore:model myOldModel</code>. Backup your current file before because the file restore will overwrite your current file with old file. <br />

## Q&A
### Why Ds use MVC structure?
Ds Framework is a php framework with MVC structure. Why we use that structure? Because a php web application must have efficient, organized, and easy to maintenance. If a feature is under repair or development it will not affect other features without having to change the structure.<br />

### What is Pie Template?
Pie template is php template engine for write php code with simple and reuseable code. <br />Pie PHP written with <code>'.pie.php'</code> file extension.
When use `<?php $var ?>` it can be simple write with `<< $var >>`, the pie engine will generate `<?php $var ?>` automatically in <code>'app/cache'</code> folder.

### How it Work?
When ds is running, web server is active in the background as <code>http://localhost:8000</code>. Ds web server will response by address request. 
for example:
create <code>welcome.php</code>(or <code>welcome.pie.php</code> if you want to use pie template) in <code>app/views/</code> folder.
```html
<!DOCTYPE html>
<html>
  <head>
    <title>Demo Page</title>
  </head>
  <body>
    <h2> <?php echo $WelcomeVariable ?> </h2>
  </body>
</html>
```
or if you want to implement pie template in 'welcome.pie' you can replace ```php echo ``` with \_(( value )) syntax.

```html
<!DOCTYPE html>
<html>
  <head>
    <title>Demo Page</title>
  </head>
  <body>
    <h2> _(( $WelcomeVariable )) </h2>
  </body>
</html>
```

#### Cheat sheet pie template
|syntax|compiled|end|
|---|---|---|
|\_(( value ))|`<?php echo value ?>`||
|\_(! value !)|`<?php echo htmlspecialchars("value") ?>`||
|<< php_syntax >>|`<?php php_syntax ?>`||
|@if(condition):|`<?php if(condition){ ?>`|@endif|
|@else|`<?php }else{ ?>`||
|@elseif(condition):|`<?php }elseif(condition){?>`|@endif|
|@isempty(value):|`<?php if(value === ''){ ?>`|@endisempty|
|@!isempty(value):|`<?php if(value !== ''){ ?>`|@!endisempty|
|@isnull(value):|`<?php if(value === NULL){ ?>`|@endisnull|
|@!isnull(value):|`<?php if(value !== NULL){ ?>`|@!endisnull|
|@switch(value)|`<?php switch(value){ ?>`|@endswitch|
|@case value:|`<?php case value: ?>`|<i>autoclose</i>|
|@default:|`<?php default: ?>`|<i>autoclose</i>|
|@for(expression)|`<?php for(expression){ ?>`|@endfor|
|@foreach(expression)|`<?php foreach(expression){ ?>`|@endforeach|
|@break|`<?php break; ?>`||

### Pie special cheat sheets
#### @join 
Sample :<br>
file : `views/layout.pie.php`
```html
<html>
  <head>
    <title>_(( app_name() ))</title>
  </head>
  <body>
    @join('components/menu', ['menu_list' => ['Home','Messages','Notifications']]);
    ....
  </body>
</html>
```
file : `views/components/menu.pie.php`
```html
<ul class="menu">
  @foreach($menu_list as $menu):
    <li class="menu-item">_(( $menu ))</li>
  @endforeach
</ul>
```
##### Output will be generated
```html
<html>
  <head>
    <title>My Web</title>
  </head>
  <body>
    <ul class="menu">
        <li class="menu-item">Home</li>
        <li class="menu-item">Messages</li>
        <li class="menu-item">Notifications</li>
    </ul>
    ....
  </body>
</html>
```

#### @pie
Sample :<br>
file : `views/parts/bootstrap.pie.php`
```html
@pie('email')
  <div class="form-group">
    <label for="exampleInputEmail1">Email address</label>
    <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
    <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
  </div>
@endpie

@pie('password')
  <div class="form-group">
    <label for="exampleInputPassword1">Password</label>
    <input type="password" class="form-control" id="exampleInputPassword1" placeholder="Password">
  </div>
@endpie

@pie('submit')
  <button type="submit" class="btn btn-primary">Submit</button>
@endpie
```
file : `views/layout.pie.php`
```html
@import('parts/bootstrap', 'bs4')
<html>
  <head>
    <title>My Web</title>
  </head>
  <body>
    ....
    <form action="login" method="POST">
      @bs4('email')
      @bs4('password')
      @bs4('submit')
    </form>
  </body>
</html>
```
##### Output will be generated
```html
<html>
  <head>
    <title>My Web</title>
  </head>
  <body>
    ....
    <form action="login" method="POST">
      <div class="form-group">
        <label for="exampleInputEmail1">Email address</label>
        <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
        <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
      </div>
      <div class="form-group">
        <label for="exampleInputPassword1">Password</label>
        <input type="password" class="form-control" id="exampleInputPassword1" placeholder="Password">
      </div>
      <button type="submit" class="btn btn-primary">Submit</button>
    </form>
  </body>
</html>
```
(note: more guide about pie template will comming soon)
then, create controller file with name <code>'welcome'</code> for example <code>'app/controllers/WelcomeController.php'</code> file, then see it:

```php
<?php
/**
 * WelcomeController
 */
class WelcomeController extends dsController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function index()
    {
        $data = array(
            'WelcomeVariable' 		=> "this is sample text variable"
        );
        view('Welcome',$data);
    }
}
```
write http://localhost:8000/welcome.
'index()' method is default page from controller, the pattern to write url 'host/controllername/methodname'.
make sure view of welcome file is exist in 'app/views' folder, if not exist it will be crashed, because 
```php
  view('Welcome', $data);
```
it's the same to call 'app/views/Welcome.php' file. So, the 'Welcome.php' must be created before trying to call it.<br />
Write command 'php ds add:view welcome'. Response message will appear in terminal
```
DsResponse: welcome has been successfully created!
```
Then, Open http://localhost:8000/welcome address on a browser. Congrats for your ds web page.

**Author** 
&copy; Muhamad Deva Arofi
