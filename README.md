# Тестовое задание

## Задание
```
Тестовое задание. Написать функцию формирования sql-запросов (MySQL) из шаблона и значений параметров.

Места вставки значений в шаблон помечаются вопросительным знаком, после которого может следовать спецификатор преобразования.
Спецификаторы:
?d - конвертация в целое число
?f - конвертация в число с плавающей точкой
?a - массив значений
?# - идентификатор или массив идентификаторов

Если спецификатор не указан, то используется тип переданного значения, но допускаются только типы string, int, float, bool (приводится к 0 или 1) и null.
Параметры ?, ?d, ?f могут принимать значения null (в этом случае в шаблон вставляется NULL).
Строки и идентификаторы автоматически экранируются.

Массив (параметр ?a) преобразуется либо в список значений через запятую (список), либо в пары идентификатор и значение через запятую (ассоциативный массив).
Каждое значение из массива форматируется в зависимости от его типа (идентично универсальному параметру без спецификатора).

Также необходимо реализовать условные блоки, помечаемые фигурными скобками.
Если внутри условного блока есть хотя бы один параметр со специальным значением, то блок не попадает в сформированный запрос.
Специальное значение возвращается методом skip.
Условные блоки не могут быть вложенными.

При ошибках в шаблонах или значениях выбрасывать исключения.

В файле Database.php находится заготовка класса с заглушками в виде исключений.
В файле DatabaseTest.php находятся примеры.
```

## Поднятие
- docker-compose build
- docker-compose up -d

## Доступы
- Адресс: http://localhost:2095/
- Mysql: Default


## Test Case

### Test Case 1
- Запрос
```text
SELECT name FROM users WHERE user_id = 1
```
- Как должно быть
```sql
SELECT name FROM users WHERE user_id = 1
```
- Как вернул мой код
```sql
SELECT name FROM users WHERE user_id = 1
```

### Test Case 2
- Запрос
```text
'SELECT * FROM users WHERE name = ? AND block = 0', ['Jack']
```
- Как должно быть
```sql
SELECT * FROM users WHERE name = 'Jack' AND block = 0
```
- Как вернул мой код
```sql
SELECT * FROM users WHERE name = 'Jack' AND block = 0
```

### Test Case 3
- Запрос
```text
'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d', [['name', 'email'], 2, true]
```
- Как должно быть
```sql
SELECT name, email FROM users WHERE user_id = 2 AND block = 1
```
- Как вернул мой код
```sql
SELECT name, email FROM users WHERE user_id = 2 AND block = 1
```

### Test Case 4
- Запрос
```text
'UPDATE users SET ?a WHERE user_id = -1',[['name' => 'Jack', 'email' => null]]
```
- Как должно быть
```sql
UPDATE users SET name= 'Jack',email = NULL WHERE user_id = -1
```
- Как вернул мой код
```sql
UPDATE users SET name= 'Jack',email = NULL WHERE user_id = -1
```

### Test Case 5
- Запрос
```text
foreach ([null, true] as $block) {
    $results[] = $this->db->buildQuery(
    'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
     ['user_id', [1, 2, 3], $block ?? $this->db->skip()]
     );
}
```
- Как должно быть
```sql
SELECT name FROM users WHERE user_id IN (1, 2, 3)
SELECT name FROM users WHERE user_id IN (1, 2, 3) AND block = 1
```
- Как вернул мой код
```sql
SELECT name FROM users WHERE user_id IN (1, 2, 3)
SELECT name FROM users WHERE user_id IN (1, 2, 3) AND block = 1
```
