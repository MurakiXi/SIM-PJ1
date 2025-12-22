<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'COACHTECHフリマ')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <div class="header__logo">
                <a href="{{ route('items.index') }}">
                    <img src="{{ asset('assets/images/coachtech-header-logo.png') }}" alt="COACHTECHフリマ">
                </a>
            </div>
        </div>
        <div class="header__form">
            <form class="header__form-inner" action="{{ route('items.search') }}" method="GET">
                <input class="header__form-input" type="text" name="keyword" value="{{ request('keyword') }}" placeholder="なにをお探しですか？">
                <button class="header__form-submit" type="submit">検索</button>
            </form>
        </div>
        @guest
        <div class="header__nav-inner">
            <a class="header__nav-item" href="{{ route('login') }}">
                ログイン
            </a>
            <a class="header__nav-item" href="{{ route('register') }}">
                マイページ
            </a>
            <a class="header__nav-item-sell" href="{{ route('sell.create') }}">
                出品
            </a>
        </div>
        @endguest

        @auth
        <div class="header__nav">
            <div class="header-nav__item">
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit">ログアウト</button>
                </form>
            </div>
            <a href="{{ route('register') }}">
                <div class="header-nav__item">
                    マイページ
                </div>
            </a>
            <a href="{{ route('sell.create') }}">
                <div class="header-nav__item-sell">
                    出品
                </div>
            </a>
        </div>
        @endauth
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>