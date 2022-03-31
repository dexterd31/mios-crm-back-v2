# Soul CRM Back

### Framework utilizado: _Lumen v7.2.2_
[Documentación](https://lumen.laravel.com/docs/7.x)

## Librerías utilizadas en este proyecto
* [Pusher 4.1.1](https://packagist.org/packages/pusher/pusher-php-server)


## Despliegue semaforización de bandejas
#### por Juan Pablo Camargo Vanegas [juan.cv@montechelo.com.co](mailto:juan.cv@montechelo.com.co)

1. Ejecutar el comando `composer install` y validar que no presenten errores de dependencias o paquetes, de ser asi resolverlos.
2. Ejecutar el comando `composer update` y validar que no presenten errores de dependencias o paquetes, de ser asi resolverlos.
3. Ejecutar migraciones `php artisan migrate`.
4. Validar que existan las siguientes tablas: 
   * `traffic_trays_config` 
   * `traffic_trays_log`
   * `jobs`
   * `failed_jobs`
5. Validar que se encuentren las variables de entorno para el funcionamiento de pusher:
* `QUEUE_CONNECTION=database`
* `BROADCAST_DRIVER=pusher`
* `PUSHER_APP_ID=`
* `PUSHER_APP_KEY=`
* `PUSHER_APP_SECRET=`
* `PUSHER_APP_CLUSTER=us2`  

## Colas de Trabajo

### Ambiente Local
Ejecutar el comando `php artisan queue:work`

### Ambientes Develop, Quálity y Producción

#### Configurar Supervisor

Instalar Supervisor:

```bash
sudo apt-get install supervisor
```

Las configuraciones del supervisor se almacenan en el directorio **/etc/supervisor/conf.d**, en el cual se creara un archivo por [cola que se requiera](#colas-que-se-requieren), en `[program:nombre_de_la_cola]` y `queue:nombre_de_la_cola` , reemplazar "**nombre_de_la_cola**" por el nombre de la cola que se vaya a ejecutar; en `user=nombre_usuario` reemplazar "**nombre_usuario**" por el usuario que ejecutara el proceso o tendra los permisos; la estructura del archivo es la siguiente:

```shell
[program:nombre_de_la_cola]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/app.com/artisan queue:nombre_de_la_cola --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=nombre_usuario
numprocs=8
redirect_stderr=true
stdout_logfile=/home/forge/app.com/worker.log
stopwaitsecs=3600
```

#### Iniciar el Supervisor

Usamos la siguiente secuencia de comandos:

```bash
sudo supervisorctl reread
```

```bash
sudo supervisorctl update
```

Para iniciar un solo trabajo usamos:

```bash
sudo supervisorctl start nombre_del_trabajo:*
```

ó:

```bash
sudo supervisorctl start nombre_del_trabajo
```

Para iniciar todos usamos:

```bash
sudo supervisorctl start all
```

Para reiniciar todas las colas usamos:

```bash
php artisan queue:restart
```

Para detener un solo trabajo usamos:

```bash
sudo supervisorctl stop nombre_del_trabajo
```

ó para detener todas:

```bash
sudo supervisorctl stop all
```


