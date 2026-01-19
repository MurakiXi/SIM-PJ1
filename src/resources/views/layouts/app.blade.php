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
        <div class="header__logo">
            <a href="{{ route('items.index') }}">
                <img src="{{ asset('assets/images/coachtech-header-logo.png') }}" alt="COACHTECHフリマ">
            </a>
        </div>
        @if(Route::is('login') || Route::is('register') || Route::is('verification.*'))
        @else
        <div class="header__form">
            <form class="header__form-inner" action="{{ route('items.index') }}" method="GET">
                @if(request('tab')==='mylist')
                <input type="hidden" name="tab" value="mylist">
                @endif
                <input id="q" type="search" name="keyword" value="{{ request('keyword') }}" placeholder="なにをお探しですか？" class="header__form-input" enterkeyhint="search">
                <button class="header__form-submit" type="submit">検索</button>
            </form>
        </div>
        @guest
        <div class="header__nav-inner">
            <a class="header__nav-item" href="{{ route('login') }}">
                ログイン
            </a>
            <a class="header__nav-item" href="{{ route('mypage') }}">
                マイページ
            </a>
            <a class="header__nav-item-sell" href="{{ route('sell.create') }}">
                出品
            </a>
        </div>
        @endguest

        @auth
        <div class="header__nav-inner">
            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button class="header__nav-logout" type="submit">ログアウト</button>
            </form>
            <a class="header__nav-item" href="{{ route('mypage') }}">
                マイページ
            </a>
            <a class="header__nav-item-sell" href="{{ route('sell.create') }}">
                出品
            </a>
        </div>
        @endauth
        @endif
    </header>

    <main>
        @yield('content')
    </main>
    @yield('js')
</body>

</html>