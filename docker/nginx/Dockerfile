FROM nginx:1.29-alpine AS nginx-base

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

RUN mkdir -p /var/cache/nginx /var/run/nginx \
    && chown -R nginx:nginx /var/cache/nginx /var/run/nginx /etc/nginx/conf.d

USER nginx
EXPOSE 8080

FROM nginx-base AS development

FROM nginx-base AS production

COPY --chown=nginx:nginx public /var/www/html/public
