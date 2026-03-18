server {
    listen 80;
    listen [::]:80;
    server_name ${APP_HOST} www.${APP_HOST};

    index index.php index.html;
    root /var/www/html/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    server_tokens off;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/livewire-[a-f0-9]+/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~* \.(css|js|mjs|jpg|jpeg|png|gif|svg|ico|webp|ttf|otf|woff|woff2)$ {
        expires 7d;
        add_header Cache-Control "public";
        access_log off;
    }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass app_backend;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;

        include fastcgi_params;

        fastcgi_param HTTP_X_FORWARDED_PROTO $http_x_forwarded_proto;
        fastcgi_param HTTP_X_FORWARDED_HOST  $http_x_forwarded_host;
        fastcgi_param HTTP_X_FORWARDED_PORT  $http_x_forwarded_port;
        fastcgi_param HTTP_X_FORWARDED_FOR   $proxy_add_x_forwarded_for;
        fastcgi_param HTTP_X_REAL_IP         $remote_addr;

        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.ht { deny all; }
    location ~ /\.(?!well-known).* { deny all; }

    location /ws/ {
        proxy_pass http://reverb_backend/;
        proxy_http_version 1.1;

        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

}
