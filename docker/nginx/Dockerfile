FROM nginx:alpine

RUN rm /etc/nginx/conf.d/default.conf

COPY ./server/nginx.conf /etc/nginx/conf.d/default.conf

ENV TZ=America/Sao_Paulo

WORKDIR /var/www

EXPOSE 80