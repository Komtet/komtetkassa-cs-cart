# komtetkassa-cs-cart

Модуль КОМТЕТ Кассы для cs.cart

Данное решение позволяет подключить Ваш интернет-магазин к облачному сервису КОМТЕТ Касса с целью соответствия требованиям 54-ФЗ для регистрации расчетов с использованием электронного средства платежа в сети Интернет.

### Версия
1.1.7

### Возможности модуля
  - автоматическая фискализация платежей при оплате заказа клиентом,
  - автоматическая фискализация платежей при смене статуса заказа менеджером.

### Описание работы
Модуль реагирует событие когда клиент совершает оплату через один из подключенных модулей приема платежей (PayPal, Robokassa) либо менеджер магазина меняет статус заказа на один из завершающих статусов либо на "возврат", и направляет данные о заказе в систему КОМТЕТ Касса.

Как только данные по заказу появляются в системе КОМТЕТ Касса, формируется чек, который записывается на фискальный накопитель кассового аппарата и он же отправляется в ОФД (Оператор Фискальных Данных). Если указано в настройках, аппарат может распечатать бланк чека.

Важно! 54-ФЗ обязует выдать электронный чек клиенту, для того чтобы электронный чек был выслан клиенту на электронную почту необходимо сделать обязательным поле email на форме оформления заказа.

### Установка
Установка производится путем копирования файлов модуля в папки cs.cart:
- папку "app/addons/rus_komtet_kassa" необходимо поместить в папку "ваша_папка_с_cs_cart/app/addons/"
- файл "var/langs/ru/addons/rus_komtet_kassa.po" необходимо поместить в папку "ваша_папка_с_cs_cart/var/langs/ru/addons/"


### Настройка модуля

Прежде чем приступить к настройке модуля, вам потребуется зарегистрироваться в [личном кабинете на сайте КОМТЕТ Касса](https://kassa.komtet.ru/signup).

В настройках модуля необходимо указать:
1. ID Магазина. В личном кабинете на сайте КОМТЕТ Касса зайдите в меню «Магазины» (слева в выпадающем меню "Фискализация"), далее выберете нужный магазин и зайдите в его настройки, там вы и найдете необходимое значение (ShopId).
2. Secret Магазина. Аналогично предыдущему (Secret).
2. ID Очереди. В личном кабинете на сайте КОМТЕТ Касса зайдите в меню «Кассы» (слева в выпадающем меню "Фискализация"), далее найдите нужный магазин и слева от его названия вы найдете четырехзначное число (ID очереди).
4. Включить или отключить печать бумажного чека.
5. Указать систему налогообложения вашей компании. Данные о системе налогообложения будут использованы при формировании чека.
6. Указать налоговую ставку товаров. Данные о налоговой ставке будут использованы при формировании чека.
7. Указать способы оплаты, для которых выполнять фискализацию.
8. Указать статусы заказа, при смене на которые выполнять фискализацию чека продажи.
9. Указать статусы заказа, при смене на которые выполнять фискализацию чека возврата.
