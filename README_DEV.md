komtet_kassa_cscart
===============================

Чтобы развернуть окружение и потестировать плагин в docker контейнере:
1. Создать папку /php, установить на неё права 777, скопировать в нее текущий код CMS CS-Cart
2. Создать папку для хранения данных БД /mysql
3. Запустить проект
```sh
make start
```
4. Перейти в браузере на localhost:8100 и выполнить установку CMS, параметры подключения к БД указать
```sh
    host: cscart_mysql,
    user: devuser,
    password: devpass,
    db: cscart_db
```
5. Установить и настроить плагин через инсталлер

* Обновление плагина Комтет Кассы (будет обновлён модуль в папке addons/rus_komtet_kassa)
```sh
make update_kassa
```

* Для отладки стоит использовать вывод значений во всплывающие уведомления при помощи функции 
    `fn_set_notification`, так как использование `print_r` при обработке события не дает эффекта

