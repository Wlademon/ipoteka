# api_telemed
This project was generated with [Laravel](https://laravel.com/docs/5.8) version 5.8

## Dependencies
* Redis
* Mysql >= 5.7
* PHP >= 7.3.0
* BCMath PHP Extension
* Ctype PHP Extension
* JSON PHP Extension
* Mbstring PHP Extension
* OpenSSL PHP Extension
* PDO PHP Extension
* Tokenizer PHP Extension
* XML PHP Extension
* Imagick PHP Extension
* Intl PHP Extension
* Mysql PHP Extension
* Interbase PHP Extension
* Gd PHP Extension
* Http PHP Extension
* Soap PHP Extension
* Instant Client 11.2 + php.ini extension = oci8_11g

## Настройка окружения ###
* создать файл .env и настроить из .env.example
* выполнить команду
```
composer update --no-scripts
```
* создать APP_KEY через команду:
```
php artisan key:generate
```
* далее выполнить
```
composer install
composer dump-autoload - также запускать после создании нового класса
```
* запустить Redis
* назначить права -R 777 на папки:
```
boostrap/cache
storage
vendor
```
* set .env FORCE_HTTPS=false - for local dev

## Install Dependencies
ВНИМАНИЕ!!! Необходим доступ до прииватного репозитория [Strahovka/Payment](https://gitlab.com/strahovkaru-dev/pkg/str-laravel-payment.git)

Если вы получаете ошибку ``Your credentials are required to fetch private repository metadata``, то необходимо:
* Сгенерировать Token, [описание тут](https://docs.gitlab.com/ee/user/profile/personal_access_tokens.html)
* Прописать Token в файле ``composer.json`` в 'gitlab-token'
```
composer update --no-scripts
composer install
composer dump-autoload - также запускать после создании нового класса
php artisan key:generate
```

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
php artisan migrate
```

####  Данные в БД (если используются)
* выполнить, чтобы создать записи в таблицах:
```
php artisan programs:set // Обновление программ в БД.
```
