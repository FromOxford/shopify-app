<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Инструкция по установке Shopify приложения</title>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
            background: #f9f9f9;
            color: #1a1a1a;
            line-height: 1.6;
            padding: 2rem;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        h2 {
            font-size: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        pre {
            background: #f0f0f0;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
        }

        ol {
            margin-left: 1rem;
        }

        li {
            margin-bottom: 1rem;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <h1>Инструкция по установке и использованию Shopify приложения</h1>

    <ol>
        <li>
            <h2>Создай приложение в Shopify</h2>
            <p>Перейди в <a href="https://partners.shopify.com/" target="_blank">Shopify Partners</a> и войди в аккаунт.
            </p>
            <p>Создай <strong>Custom app</strong> для нужного магазина:</p>
            <ul>
                <li>Дай приложению имя.</li>
                <li>Укажи, что это <strong>Custom app</strong> (или Public, если нужен App Store).</li>
                <li>Сохрани <strong>API Key</strong> и <strong>API Secret Key</strong>. Они понадобятся для настройки
                    Laravel.</li>
            </ul>
        </li>

        <li>
            <h2>Настрой Laravel проект</h2>
            <p>Создай или обнови файл <code>.env</code>:</p>
            <pre>
SHOPIFY_KEY=ваш_api_key
SHOPIFY_SECRET=ваш_api_secret
SHOPIFY_REDIRECT=https://your-laravel-domain.com/oauth/callback
APP_FRONTEND_URL=https://shopify-app-front.vercel.app
            </pre>
            <p>Проверь, что твой Laravel проект доступен по HTTPS (Shopify требует безопасный редирект).</p>
        </li>

     

        <li>
            <h2>Установка приложения в магазин</h2>
            <p>Открой браузер и перейди по ссылке:</p>
            <pre>https://your-laravel-domain.com/install?shop=your-shop.myshopify.com</pre>
            <p>Shopify перенаправит на страницу авторизации магазина. После подтверждения Shopify вернет
                <code>code</code>, <code>hmac</code> и <code>shop</code> на <code>/oauth/callback</code>.
            </p>
        </li>

        <li>
            <h2>Обработка callback</h2>
            <ul>
                <li>Laravel проверяет <code>state</code> и <code>hmac</code> для безопасности.</li>
                <li>Создается или обновляется запись магазина в таблице <code>shops</code>.</li>
                <li>Запускается <code>RegisterShopJob</code>, если магазин еще не синхронизирован.</li>
                <li>После этого происходит редирект на фронтенд Nuxt.js с параметром <code>domain</code>:</li>
            </ul>
            <pre>https://shopify-app-front.vercel.app/?domain=your-shop.myshopify.com</pre>
        </li>

        <li>
            <h2>Фронтенд Nuxt.js</h2>
            <ul>
                <li>Nuxt.js получает <code>domain</code> из URL.</li>
                <li>Использует <code>domain</code> для запросов к Laravel API через GraphQL.</li>
                <li>Можно получать и синхронизировать товары, заказы, клиентов и т.д.</li>
            </ul>
        </li>

        <li>
            <h2>Советы и полезные моменты</h2>
            <ul>
                <li>Для локальной разработки используйте <a href="https://ngrok.com/" target="_blank">ngrok</a> или
                    другой туннель для HTTPS.</li>
                <li>Убедись, что <code>scopes</code> в <code>OAuthController</code> совпадают с функционалом приложения.
                </li>
                <li>Все запросы к Shopify API делаются через токен <code>access_token</code>, сохраненный в базе.</li>
                <li>Если нужно добавить новые права доступа, обнови <code>scope</code> в методе <code>install</code>.
                </li>
            </ul>
        </li>
    </ol>
</body>

</html>