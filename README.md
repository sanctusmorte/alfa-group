# alfa-group
Тестовое задание №2
описание - https://docs.google.com/document/d/1utlTjz01vF-NG-gqDaYloImih2x8cM2fz_ldWWj8RwE/edit#heading=h.gjdgxs

Установка:
1. git clone https://github.com/sanctusmorte/alfa-group.git
2. выставить настройки БД в файле .env
3. в консоли перейти в папку проекта и выполнить поочердно следующие команды:
4. composer update
5. composer install
6. php bin/console doctrine:schema:drop --full-database --force
7. php bin/console doctrine:migrations:migrate
8. php bin/console doctrine:fixtures:load
9. php bin/console server:run

Требуется версия php 7.4

Просмотреть журналы и авторов можно по ссылке http://127.0.0.1:8000/admin
Требуется авторизация, для этого есть тестовый аккаунт:
1. email - admin@example.com
2. пароль - 123654
