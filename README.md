# imageserver

A simple but powerfull imageserver in PHP, created for [Vici.org](https://vici.org).
It resizes images using an URL based API, and might even cache them too.

## why use this imageserver and not IIIF?

Who needs yet another imageserver and its custom API when there is the IIIF Image API?

The predecessor of this imageserver started serving images for [Vici.org](https://vici.org) in the year 2011, even before IIIF was born. Even now, there are various reasons to use this code and API instead of an implementation of the IIIF Image API (as for example by [Cantaloupe](https://cantaloupe-project.github.io)).

This imageserver

1. offers useful image processing functionality not provided by IIIF (specifically the `cover` command).
2. has a simple and intuitive API, based on CSS properties.
3. allows for configurable caching.

And of course, when you are doing a PHP project, it is convenient to stick to PHP based solutions. Besides that, the code is quite simple and elegant and based on the common Imagick extension.

And no, it will not boil your water if you need to the implement the IIIF Presentation API.

## using it

The imageserver implements 5 commands, that are comparable to CSS properties used for background images. Here are some examples of how to call it:

 1. https://server.com/cover/w280xh280/path/to/image.jpg
 2. https://server.com/contain/w120xh60/path/to/image.jpg
 3. https://server.com/crop/w100xh80/path/to/image.jpg
 4. https://server.com/size/h800/path/to/image.jpg
 5. https://server.com/auto/path/to/image.jpg

 
The **cover** command takes in the second segment of the path the width and height in pixels of the box *the smallest possible resized image should be able to cover fully*. The aspect ratio is kept.
 
The **contain** command takes in the second segment of the path the with and height of the box *the largest possible resized image should fit in*. The aspect ratio is kept.
  
The image is **crop**ped, using the width and height specified. The aspect ratio is kept. Equal parts will be cut off on both sides, essentially centering the image,
 
The **size** command resizes the image depending on the requested width and height. If both are supplied, the aspect ration is changed accordingly. If only a specific width or height is requested, the aspect ratio is kept.
 
The **auto** command returns the full image. It is not processed, and dealt with by the webserver.
 
 
## requirements and configuration
 
Imageserver is written for `PHP 7.4` or newer (it has been tested with `PHP 8.2`) It depends on `Imagick`.
The core for this service is in the `nginx` webserver configuration:

    location ~ ^/(.*)$ {
        try_files $uri @images;
    }

    location @images {
	    fastcgi_pass   php:9000;
        fastcgi_param  SCRIPT_FILENAME  /var/www/domain.host.images/src/controller.php;
	    fastcgi_param  QUERY_STRING    q=$uri&$args;
        include        fastcgi_params;
    }

The `try_files` directive will return the image from disk, and only when it is not there, `controller.php` is called.

The controller uses the configuration in `config/imageserver.json`.
The controller wil process the image and serve it, and depending on `cachePattern` as defined in `config/imageserver.json`, the result will be written to subdirectory of the webroot. For example, request `https://server.com/size/h800/path/to/image.jpg` will write a file `image.jp` in directory structure `size/h800/path/to/`, in the webroot. The file `image.jpg` is obtained from `auto/path/to/`.  So use `auto` as the mount point for your image storage.

Run imageserver using docker using the settings in `docker-compose.yml` with `docker-compose up -d imageserver-dev`.




