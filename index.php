<?php

echo 'Set the public directory as your document root. SarahPHP cannot function (properly) without the public directory as the document root. Example Apache Virtual Host configuration : ';

echo '<pre>';
echo '&lt;Directory "/var/www/SARAHPHPDIR/public"&gt;
AllowOverride .htaccess
Options -Indexes
&lt;/Directory&gt;
&lt;VirtualHost *:80&gt;
    ServerName "YOURHOSTNAME"
    DocumentRoot "/var/www/SARAHPHPDIR/public"
&lt;/VirtualHost&gt;';
echo '</pre>';

