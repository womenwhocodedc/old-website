womenwhocodedc.com
=======

This is the repo for the public-facing DC chapter of Women Who Code. Edits to website should be made with feature branches. Other branches are as follows:

```master```: Branch kept in sync across members for changes others should have locally but aren't ready to go live yet.

```production```: Branch that has a webhook for Heroku. Don't automatically push to production, submit a pull request. Pushes to production should probably receive two :+1:s before getting submitted.

## Vhost
Craft is happier with a virtual host. I've aliased mine to ```craft.dev``` in my ```httpd-vhosts.conf``` file: 

``` bash
  <VirtualHost *:80>
      ServerName craft.dev
      ServerAlias "craft.dev.192.168.7.72.xip.io"
      DocumentRoot '<path-to-website-folder>/public/'
      <Directory "<path-to-website-folder>/public/">
          Options Indexes FollowSymLinks
          AllowOverride All
          Order deny,allow
          Allow from all
      </Directory>
  </VirtualHost>
```

## Front-end Dependencies 
We're using ```Gulp``` to compile/concatenate/boring stuff on the front-end. (We're using Dan Tello's [Gulp Starter](https://github.com/greypants/gulp-starter).) In order to use it, you need [node](http://nodejs.org/). In order to install all the gulp things, run ```npm install```. To run gulp, run ```gulp watch```.
