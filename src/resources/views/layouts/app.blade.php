<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'coachtechフリマ')</title>
</head>

<body>
    <header style="display:flex; gap:16px; align-items:center; padding:12px; border-bottom:1px solid #ddd;">
        <div>
            <a href="{{ route('items.index') }}">coachtechフリマ</a>
        </div>

        <form action="{{ route('items.search') }}" method="GET" style="flex:1; display:flex; gap:8px;">
            <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="商品名で検索" style="flex:1;">
            <button type="submit">検索</button>
        </form>

        <nav style="display:flex; gap:8px;">
            @guest
            <a href="{{ route('login') }}">login</a>
            <a href="{{ route('register') }}">register</a>
            @endguest

            @auth
            <a href="{{ route('sell.create') }}">出品</a>
            <a href="{{ route('mypage') }}">mypage</a>
            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit">logout</button>
            </form>
            @endauth
        </nav>
    </header>

    <main style="padding:16px;">
        @yield('content')
    </main>
</body>

</html>