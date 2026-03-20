# -----------------------------
# base
# -----------------------------
FROM nginx:stable AS base

# Diretório onde o Nginx vai enxergar o projeto
WORKDIR /var/www/html

# Preparação do nginx conf
RUN mv /etc/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf.disabled 2>/dev/null || true

COPY ./.docker/nginx/default.conf.tpl /etc/nginx/.

COPY ./.docker/nginx/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80
EXPOSE 443


# -----------------------------
# 11) Nginx produção
# -----------------------------
FROM base AS production

# Install build tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    python3 python3-dev python3-venv libaugeas-dev gcc \
    cron \
    supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install pip
RUN python3 -m venv /opt/certbot/
RUN /opt/certbot/bin/pip install --upgrade pip

# Install Certbot
RUN /opt/certbot/bin/pip install certbot certbot-nginx
RUN ln -s /opt/certbot/bin/certbot /usr/local/bin/certbot

# API Control
COPY ./.docker/nginx/api-control.py /opt/api-control.py

# Schedule renewal
RUN echo "0 0,12 * * * root certbot renew -q && nginx -s reload" >> /etc/crontab

# Copy supervisor conf
COPY ./.docker/nginx/supervisord.conf /etc/supervisor/supervisord.conf

# Entrypoint
COPY ./.docker/nginx/certbot-init /usr/local/bin/certbot-init
RUN chmod +x /usr/local/bin/certbot-init

ENTRYPOINT ["start-container"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]


# -----------------------------
# 12) Nginx para desenvolvimento
# -----------------------------
FROM base AS development

ENTRYPOINT ["start-container"]
CMD ["nginx", "-g", "daemon off;"]
