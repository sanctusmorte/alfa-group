# alfa-group
Тестовое задание №2
описание - https://docs.google.com/document/d/1utlTjz01vF-NG-gqDaYloImih2x8cM2fz_ldWWj8RwE/edit#heading=h.gjdgxs

Установка:
1. git clone https://github.com/sanctusmorte/alfa-group.git
2. выставить настройки БД в файле .env
3. в консоли перейти в папку проекта и выполнить поочердно следующие команды:
4. php bin/console doctrine:schema:drop --full-database --force
5. php bin/console doctrine:migrations:diff
6. php bin/console doctrine:migrations:migrate
7. php bin/console doctrine:fixtures:load
8. php bin/console server:run
