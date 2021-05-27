# adt_osago
This project was generated with [Laravel](https://laravel.com/docs/5.8) version 5.8

## Dependencies
* Redis
* MySQL >= 5.7
* PHP >= 7.4.0
* Imagick PHP Extension (php7.4-imagick)
* SOAP PHP Extension (php7.4-soap)
* Http PHP Extension (php7.4-http)
* Intl PHP Extension (php7.4-intl)
* BCMath PHP Extension (php7.4-bcmath)
* Ctype PHP Extension
* JSON PHP Extension
* Mbstring PHP Extension
* OpenSSL PHP Extension
* PDO PHP Extension
* XML PHP Extension

## Настройка окружения ###
* создать файл .env и настроить из .env.example
* Подтянуть зависимости
```
php composer u --no-scripts
```
* создать APP_KEY через команду:
```
php artisan key:generate
```
* Обновить пакеты
```
php composer i
```
* назначить права -R 777 на папки:
```
boostrap/cache
storage
vendor
```
* set .env FORCE_HTTPS=false - for local dev

## Folder permissions
```
sudo chown :www-data app storage bootstrap -R
sudo chmod 775 app storage bootstrap -R
```

### Настройка БД (localhost)
* установить и настроить MySQL
* в MySQL создать БД "ns"
* запустить миграции
```
php artisan migrate:fresh --seed
```

####  Данные в БД (если используются)
* выполнить, чтобы создать записи в таблицах:
```
php artisan programs:set // Обновление программ в БД.
```
