Tickets system for MODX Revolution 3

## Установка

### 1. скачать корневой композер modx

```bash
cd /to/modx/root/ # тут путь до корня сайта с MODX3  
wget https://raw.githubusercontent.com/modxcms/revolution/v3.1.2-pl/composer.json composer.json # тут версия важна 
composer update
```

curl windows

```bash
curl https://raw.githubusercontent.com/modxcms/revolution/v3.1.2-pl/composer.json -o composer.json
```

### 2. скачиваем пакет (локально либо из гитхаба)

локально:

```json
"require": {
	...,
	"oleganto2000/tickets": "dev-master"
},
"repositories": [
	{
		"type": "path",
		"url": "C:\\Projects\\GitHub\\Mod3Tickets",
		"options": {
			"symlink": true
		}
	}
]
```

из гитхаб:

```json
"require": {
	...,
	"oleganto2000/tickets": "dev-master"
},
"repositories": [
	{
		"type": "vsc",
		"url": "https://github.com/OlegAnTo2000/Mod3Tickets"
	}
]
```

```
composer require oleganto2000/tickets
```

```
composer update oleganto2000/tickets
```

### 3. ставим пакет

⚠️ На Windows работает только при запуске консоли от имени администратора, так как создаются символические ссылки для `assets/components/tickets` и `core/components/tickets`

```
composer exec tickets install
```

### 4. удаление пакета

❌ Логика комманды Remove написана не полностью

```
composer exec tickets remove
```

## Локальная разработка

Например у вас стоит актуальный MODX в папке `C:\OSPanel\home\modx315.com\public_html` а пакет лежит в `C:\Projects\GitHub\Mod3Tickets`

В пакете нужно указать путь к конфигу MODX, в самом MODX - в composer указать путь локального репозитория пакета.

В пакете в файл `core/modx/config.core.php` прописываем:

```php
define('MODX_CORE_PATH', 'C:/OSPanel/home/modx315.com/public_html/core/');
```

В Composer этой MODX установки прописываем локальный вариант репозитория из пункта загрузки пакета, где `"type": "path"`.

## План работ

### Что уже сделано для перевода Tickets с MODX2 на MODX3

- Переделана структура файлов для соответствия PSR-4 (процессоры, ядро, модели)
- Переписаны классы с указанием пространства имен в xml схеме
- Перегенирированы файлы схемы базы данных с учетом новой xml схемы
- Добавлена bin комманда для локальной перегенирации файлов схемы БД
- Добавлен файл bootstrap.php с автопоиском файла конфигурации и возможностью указать локальный путь к MODX для разработки
- Используется установка через composer методом Василия Наумкина
- Переписаны вызовы сервисов MODX3 (`$modx->services`)
- Используются пространства имён и новые классы MODX3 (например, `\MODX\Revolution\Error\modError`)
- Переписаны все вызовы методов, требующих `className` с указанием `className::class` вместо псеводнимов
- Обновлен способ вызова процессоров через пространства имён (`\Tickets\Processors\...`)
- Удалена регистрация modAction
- 😊 доступна поддержка Markdown Extra разметки для комментариев и тикетов
- Symphony Sanitizer вместо Jevix, Jevix вырезан полностью
- 😊 Вырезан редактор, сделан похожий минималистичный встроенный Markdown редактор
- Изменен вид предпросмотра текста
- Добавлены чанки на Fenom (надо сделать системную настройку для Fenom наверное, пока не используются)
- Создание и изменение таблиц БД через миграции Phinx

### Что еще нужно сделать

- Дописать и протестировать корректность установки/удаления пакета и всех элементов
- Протестировать корректность работы процессоров
- Доделать кастомный редактор, проверить верстку
- Вынести редактор в отдельный чанк
- Проверить и обновить все вызовы API MODX на совместимость с MODX3
- Провести тестирование всех функций пакета в окружении MODX3
- Чанки на Fenom сделать как системную настройку?