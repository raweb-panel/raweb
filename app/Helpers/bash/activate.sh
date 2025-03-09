systemctl link /nginx/admin/public_html/app/Helpers/bash/nginx-watcher.service
systemctl link /nginx/admin/public_html/app/Helpers/bash/fpm84-watcher.service
systemctl enable nginx-watcher; systemctl start nginx-watcher
systemctl enable fpm84-watcher; systemctl start fpm84-watcher