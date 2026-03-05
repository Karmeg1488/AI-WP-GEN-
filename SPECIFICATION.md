# Техническое Задание: WordPress Плагин AI WP GEN

**Версия ТЗ:** 1.0  
**Дата создания:** 27.02.2026  
**Целевая версия плагина:** 1.5.55  
**Статус:** Базовая спецификация

---

## 1. Общее описание

**Название проекта:** AI WP GEN  
**Тип:** WordPress плагин  
**Язык разработки:** PHP  
**Целевая аудитория:** Администраторы WordPress, блоггеры, веб-мастера

**Описание:**  
WordPress плагин для полной автоматизации заполнения сайта контентом через интеграцию с OpenAI API. Плагин позволяет автоматически генерировать авторов, категории, статьи, изображения, название сайта, логотип, фавикон и статические страницы.

**Цель:**  
Сократить время на настройку и заполнение нового WordPress сайта с несколько часов до нескольких минут за счет AI-генерации контента.

---

## 2. Функциональные требования

### 2.1 Модуль управления настройками

#### 2.1.1 Страница администратора
- Создать главное меню в админ-панели: "AI WP GEN"
- Позиция в меню: 75
- Иконка: `dashicons-admin-generic`
- Доступ: только пользователи с правом `manage_options`

#### 2.1.2 Вкладка "Настройки"
Должны быть следующие поля ввода:

| Поле | Тип | Обязательное | Описание |
|------|-----|-------------|---------|
| OpenAI API Key | Text | Да | API ключ для доступа к OpenAI |
| Категории | Text | Да | Список категорий через запятую (например: "IT, Science, Business") |
| Количество авторов | Number | Да | Кол-во авторов для создания (min=1, default=3) |
| Статей на категорию | Number | Да | Кол-во статей на одну категорию (min=1, default=3) |
| Название сайта | Text | Нет | Название веб-сайта для генерации контента |
| Custom AI Prompt | Textarea | Нет | Пользовательский prompt для руководства генерацией |
| CSS Style Prompt | Textarea | Нет | Описание стиля для генерации base.css |
| Язык | Select | Да | Выбор языка для генерируемого контента |

**Поддерживаемые языки:**
- English (en)
- Polish (pl)
- German (de)
- Hungarian (hu)
- Ukrainian (uk)
- French (fr)
- Dutch (nl)
- Turkish (tr)
- Italian (it)
- Czech (cs)

#### 2.1.3 Валидация и сохранение
- Все поля должны быть защищены nonce-проверкой
- API ключ должен быть захеширован или закодирован при сохранении
- Значения должны быть санитизированы перед сохранением
- При сохранении название сайта должно синхронизироваться с WordPress опцией `blogname`

### 2.2 Модуль генерации контента

#### 2.2.1 Генерация авторов
- Количество: определяется пользователем в настройках
- Параметры каждого автора:
  - Полное имя (генерируется через OpenAI)
  - Username: создается на основе имени (формат: `author_n` или на основе полного имени)
  - Role: 'author'
  - Meta-поле `aicg_generated = 1` (для отслеживания сгенерированных авторов)
- Требование: Имена должны быть реальные, уникальные, формат JSON массив

#### 2.2.2 Генерация категорий
- Создание категорий на основе списка из настроек
- Если категория существует → пропустить
- Если категория новая → создать через `wp_insert_term()`

#### 2.2.3 Генерация статей
**Для каждой категории:**
- Динамическое количество статей (определяется в настройках)
- Параметры статьи:
  - Название: уникальное, генерируется через ChatGPT
  - Содержание: полноценный контент (200-500 слов)
  - Автор: случайный из сгенерированных авторов
  - Категория: текущая категория
  - Статус: 'publish'
  - Язык: определяется в настройках плагина
  - Meta-поле `aicg_image_needed = 1` (флаг для генерации изображения)

**Требования к контенту:**
- Должен быть на выбранном пользователем языке
- Должен быть релевантен категории
- Допускается использовать custom prompt пользователя

#### 2.2.4 Проверка уникальности
- Перед созданием статьи проверить, что такое название еще не существует
- При дублировании → сгенерировать новое название

### 2.3 Модуль генерации изображений

#### 2.3.1 Генерация изображений для статей
- Триггер: статьи с meta-полем `aicg_image_needed = 1`
- Fallback: если нет помеченных статей → обработать последние 10 постов без thumbnail
- Model: DALL·E (через OpenAI API)
- Размер: 512x512 пиксели
- Формат: URL или PNG

**Требования к изображениям:**
- Стиль: реалистичные фотографии в стиле новостого агентства
- При генерации использовать случайный стиль из набора:
  - "A realistic photojournalistic image of"
  - "A modern, high-detail press photo about"
  - "A natural-looking editorial photo showing"
  - "A vivid news-style image depicting"
  - "A current, lifelike news photograph featuring"

#### 2.3.2 Сохранение изображений
- Загрузить изображение в медиатеку через `aicg_media_handle_sideload()`
- Установить как "Featured Image" (thumbnail) поста
- Удалить meta-флаг `aicg_image_needed` после успешной загрузки
- Максимум 10 изображений за один запрос (для экономии API)

### 2.4 Модуль генерации элементов сайта

#### 2.4.1 Генерация названия и слогана сайта
**Функция:** AJAX запрос `aicg_generate_title_tagline`
- Вход: доменное имя сайта
- Выход: название и слоган через " - " разделитель
- Сохранение:
  - Название → опция `blogname`
  - Слоган → опция `blogdescription`

#### 2.4.2 Генерация логотипа
**Функция:** AJAX запрос `aicg_generate_logo`
- Вход: название сайта + слоган
- Выход: URL изображения логотипа
- Требования:
  - Размер: 512x512 пиксели
  - Стиль: современный, минималистичный
  - Фон: прозрачный (PNG)
- Сохранение: загружить в медиатеку, установить как `custom_logo` theme mod

#### 2.4.3 Генерация фавикона
**Функция:** AJAX запрос `aicg_generate_favicon`
- Вход: название сайта
- Выход: файл фавикона
- Варианты:
  - Использовать логотип как фавикон (рекомендуется)
  - Или сгенерировать отдельно через DALL·E

#### 2.4.4 Генерация статических страниц
**Страницы для создания:**

1. **"Contact"** (Контакты)
   - Содержание: полная контактная информация (адрес, телефон, email, форма)
   - Форма: использовать существующие ContactForm7 или встроенную форму
   - Статус: draft/publish (по выбору)

2. **"About Us"** (О нас)
   - Содержание: история компании, миссия, ценности
   - Объем: 300-500 слов
   - Язык: выбранный пользователем

#### 2.4.5 Генерация главной страницы (Home Page)
**Структура:**
- Hero-секция с заголовком и CTA кнопкой
- About Us секция
- Последние блог-посты (3-5)
- Testimonials секция
- Footer с контактной информацией
- CSS классы для стилизации

### 2.5 Модуль генерации стилей CSS

#### 2.5.1 Генерация base.css
- Вход: CSS Style Prompt от пользователя
- Выход: полный CSS файл (base.css)
- Сохранение: `/wp-content/uploads/aicg-styles/base.css`
- Подключение: автоматически через `wp_enqueue_scripts` хук
- Требования:
  - Должен быть валидным CSS
  - Должен включать стили для основных элементов (header, footer, buttons, forms)
  - Должен быть адаптивным (mobile-first)

#### 2.5.2 Управление CSS файлом
- Проверка существования директории `/aicg-styles/`
- Создание директории если не существует
- URL хранится в опции `aicg_css_file_url` для кеширования

---

## 3. Нефункциональные требования

### 3.1 Безопасность

#### 3.1.1 Валидация и санитизация
- Все пользовательские input'ы должны быть санитизированы:
  - Использовать `sanitize_text_field()` для текста
  - Использовать `intval()` для чисел
  - Использовать `wp_unslash()` перед обработкой

#### 3.1.2 Эскейпинг вывода
- Все выведенные значения должны быть экранированы:
  - `esc_html()` для простого текста
  - `esc_attr()` для HTML атрибутов
  - `esc_textarea()` для textarea
  - `esc_url()` для URL

#### 3.1.3 Nonce-проверки
- Все формы должны содержать nonce-поле
- Действие: `aicg_admin_nonce`
- Проверка: `check_admin_referer()` перед обработкой
- AJAX запросы должны проверять nonce через `check_ajax_referer()`

#### 3.1.4 Проверка прав доступа
- На странице администратора: `current_user_can('manage_options')`
- На AJAX: обязательная проверка перед обработкой
- API ключ должен быть приватным (не выводиться в frontend)

#### 3.1.5 Rate Limiting
- Максимум 1 запрос на генерацию в 5 секунд (для избежания abuse)
- Логирование попыток с частотой > допустимой

### 3.2 Производительность

#### 3.2.1 Оптимизация запросов
- Использовать WP_Query кеширование где возможно
- Минимизировать количество DB запросов
- Для AJAX → использовать асинхронность (background processing)

#### 3.2.2 API ограничения
- Timeout для ChatGPT запросов: 30 секунд
- Timeout для DALL·E запросов: 60 секунд
- Retry механизм: максимум 2 попытки при ошибке

#### 3.2.3 Кеширование
- Кешировать результаты генерации
- max_tokens для ChatGPT: 250 (для экономии)
- Temperature: 0.7 (balance между creativity и stability)

### 3.3 Надежность

#### 3.3.1 Обработка ошибок
- Все API-запросы должны проверять `is_wp_error()`
- Возвращать user-friendly сообщения об ошибках
- Логировать технические ошибки в error_log

#### 3.3.2 Graceful Degradation
- Если API ключ не установлен → показать понятное сообщение
- Если категории не указаны → не выполнять генерацию
- Если genera ция не удалась → откатиться, не criar partial content

#### 3.3.3 Transient для длительных операций
- Использовать WordPress transients для временных данных
- TTL: 24 часа для кешированного контента

### 3.4 Масштабируемость

#### 3.4.1 Модульная архитектура
- Разделить код на логические модули (файлы):
  - `admin-page.php` - интерфейс администратора
  - `generator.php` - генерация контента
  - `image-generator.php` - генерация и загрузка изображений
  - `openai-helper.php` - обертки для OpenAI API
  - `ajax-handlers.php` - AJAX обработчики

#### 3.4.2 Callback функции
- Использовать hooks и filters для расширяемости
- Позволить третьему коду модифицировать prompts через filters

### 3.5 Документация

#### 3.5.1 Code Documentation
- PHPDoc комментарии для всех функций
- Описание параметров и возвращаемых значений
- Примеры использования для сложных функций

#### 3.5.2 User Documentation
- README.md с описанием плагина
- Installation guide
- Usage tutorial
- FAQ раздел
- Troubleshooting guide

---

## 4. Технические спецификации

### 4.1 Stack

| Компонент | Требование |
|-----------|-----------|
| WordPress | >= 5.0 |
| PHP | >= 7.4 |
| OpenAI API | Chat Completions (GPT-4o-mini), Images (DALL·E) |

### 4.2 OpenAI API Endpoints

#### 4.2.1 Chat Completions
```
POST https://api.openai.com/v1/chat/completions
```
**Параметры:**
- model: `gpt-4o-mini`
- max_tokens: 250
- temperature: 0.7
- messages: array with user prompt

#### 4.2.2 Image Generation
```
POST https://api.openai.com/v1/images/generations
```
**Параметры:**
- prompt: описание изображения
- n: 1
- size: `512x512`
- response_format: `url`

### 4.3 Database Schema

#### 4.3.1 WordPress Options (wp_options)
```php
aicg_api_key          // OpenAI API ключ
aicg_categories       // Список категорий (comma-separated)
aicg_author_count     // Количество авторов
aicg_articles_per_category  // Статей на категорию
aicg_site_name        // Название сайта
ang_site_topic        // Custom prompt пользователя
ang_style_prompt      // CSS Style Prompt
ang_language          // Код языка
aicg_css_file_url     // URL на сгенерированный CSS файл
```

#### 4.3.2 Post Meta (wp_postmeta)
```php
aicg_image_needed     // Flag: нужно ли генерировать изображение
aicg_generated        // Flag: пост сгенерирован плагином
```

#### 4.3.3 User Meta (wp_usermeta)
```php
aicg_generated        // Flag: пользователь создан плагином (значение 1)
```

### 4.4 File Structure

```
ai-news-generator.php                 # Главный файл плагина
├── includes/
│   ├── admin-page.php               # Интерфейс администратора
│   ├── generator.php                # Логика генерации контента
│   ├── image-generator.php          # Генерация и загрузка изображений
│   ├── openai-helper.php            # Обертки для OpenAI API
│   └── ajax-handlers.php            # AJAX обработчики
├── assets/
│   ├── js/
│   │   └── admin.js                 # JS для админ-панели
│   └── css/
│       └── admin.css                # Стили админ-панели
├── README.md                        # Документация пользователя
├── LICENSE                          # GPLv2 лицензия
└── languages/                       # Файлы локализации (опционально)
```

---

## 5. API Integration

### 5.1 OpenAI Chat Completions Function

```php
function aicg_openai_chat_request($api_key, $prompt) {
    // Отправляет request к OpenAI ChatGPT
    // Возвращает: string или false
}
```

### 5.2 OpenAI Image Generation Function

```php
function aicg_generate_image_from_prompt($api_key, $prompt, $width, $height) {
    // Генерирует изображение через DALL·E
    // Возвращает: URL изображения или false
}
```

### 5.3 Helper Functions

```php
function get_language_name_from_code($code)     // Конвертирует код в название языка
function aicg_media_handle_sideload($url, $post_id)  // Загружает изображение в медиатеку
function aicg_create_authors($count, $api_key)  // Создает авторов в БД
```

---

## 6. User Interface

### 6.1 Admin Interface Design

#### 6.1.1 Макет страницы
- **Header:** "✨ AI WP GEN" заголовок
- **Tab Navigation:**
  - Settings (активен по умолчанию)
  - Generation
- **Color Scheme:** Градиент фиолетово-голубой (для AI-ассоции)

#### 6.1.2 Settings Tab
- Форма с полями (описана в 2.1.2)
- Information box: "Getting Started" с инструкциями
- Save кнопка с типом `button-primary`

#### 6.1.3 Generation Tab
- **Section 1:** Authors & Articles
  - Кнопка "Generate Articles" (button-secondary)
  - Result area для вывода статуса

- **Section 2:** Images
  - Кнопка "Generate Images"
  - Result area

- **Section 3:** Site Elements
  - Кнопка "Generate Title & Tagline"
  - Кнопка "Generate Logo"
  - Кнопка "Generate Favicon"
  - Кнопка "Generate Contact Page"
  - Кнопка "Generate About Us Page"
  - Result areas для каждой кнопки

### 6.2 AJAX UI Components

#### 6.2.1 Loading State
- Показать spinner пока обрабатывается запрос
- Заблокировать кнопку от повторного нажатия

#### 6.2.2 Result Messages
- Success: зеленое сообщение с результатом
- Error: красное сообщение с описанием ошибки
- Info: синее информационное сообщение

---

## 7. Testing Requirements

### 7.1 Functional Testing
- [ ] Сохранение и загрузка настроек
- [ ] Генерация авторов (проверка количества и уникальности)
- [ ] Генерация категорий (проверка дублей)
- [ ] Генерация статей (проверка контента, языка, автора)
- [ ] Генерация изображений (проверка загрузки в медиатеку)
- [ ] Валидность сгенерированного CSS
- [ ] AJAX функции

### 7.2 Security Testing
- [ ] SQL injection tests
- [ ] XSS tests
- [ ] CSRF/Nonce tests
- [ ] Authorization tests
- [ ] API key protection tests

### 7.3 Performance Testing
- [ ] Время генерации 100 статей
- [ ] Memory usage
- [ ] Database query optimization
- [ ] API rate limiting

---

## 8. Deployment & Release

### 8.1 Version Control
- Git repository с history commits
- Tags для каждой версии (v1.5.55 и т.д.)
- Branch strategy: main + develop

### 8.2 Distribution
- WordPress Plugin Repository (если планируется)
- GitHub releases
- Changelog в README.md

### 8.3 Compatibility
- Tested up to: WordPress 6.8
- PHP 7.4 - 8.2+
- Протестирован на популярных темах

---

## 9. Constraints & Limitations

1. **API Cost:** Генерация контента требует оплат за OpenAI API (Chat + Images)
2. **Rate Limiting:** OpenAI API имеет лимиты на количество запросов
3. **Content Quality:** AI-генерированный контент может требовать редактирования
4. **Language Support:** Зависит от поддержки языков OpenAI и GPT моделей
5. **Image Size:** DALL·E имеет ограничения на размеры (512x512 max без апгрейда)

---

## 10. Future Enhancements

1. **Advanced Scheduling** - планирование публикации контента
2. **Batch Processing** - обработка больших объемов в фоне
3. **Content Customization** - дополнительные опции для fine-tuning
4. **Multi-provider Support** - поддержка других AI API (Google Palm, Anthropic)
5. **Content Variations** - генерация нескольких вариантов контента
6. **SEO Optimization** - автоматическое добавление meta-тегов, keywords
7. **Content Calendar** - планирование и управление контентом
8. **Analytics** - отслеживание производительности сгенерированного контента
9. **Rollback Feature** - возможность отката автоматически сгенерированного контента
10. **Multi-language Sync** - поддержка WPML для многоязычных сайтов

---

## 11. Success Criteria

✅ Плагин успешен если:
- Пользователь может заполнить настройки за < 2 минуты
- Автоматическая генерация полного сайта за < 5 минут
- Контент уникален, релевантен и на выбранном языке
- Все изображения успешно загружаются в медиатеку
- Нет критических ошибок в error_log
- Плагин разработан согласно WordPress best practices
- Код защищен от основных security vulnerabilities
- Документация полна и понятна для пользователя

---

**Конец ТЗ**
