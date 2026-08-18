<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">

        <a href="{{ route('home') }}" class="">
            <span class="app-brand-logo ms-5">
                {{-- Logo Here --}}
                <img src="{{ APP_LOGO() }}" alt height=40 width=100 />
            </span>
            <span class="app-brand-text menu-text fw-bold">{{APP_NOMBRE()}}</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block">
            <i class="bx bx-chevron-left bx-sm d-flex align-items-center justify-content-center"></i>
        </a>

        <!--<a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="align-middle bx bx-chevron-left bx-sm"></i>
        </a>-->
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="py-1 menu-inner">

        {{-- Admin --}}
        <li class="menu-item {{ request()->is('home') ? 'active' : '' }}">
            <a href="{{ route('home.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Beranda">INICIO</div>
            </a>
        </li>

        @role('Administrator')
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">MENU</span>
            </li>

            @foreach (function_a() as $menu)

            <li class="menu-item {{ request()->is(strtolower($menu->controlador).'/*') ? 'open active' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx {{$menu->icono}}"></i>
                    <div data-i18n="Kelola Pengguna">{{$menu->nombre}}</div>
                </a>

                @foreach (getSubMenu($menu->id) as $submenu)
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->is(strtolower($menu->controlador).'/'.$submenu->link) ? 'active' : '' }}">
                        <a href="{{ route($submenu->link) }}" class="menu-link">
                            <div data-i18n="Pengguna">{{$submenu->nombre}}</div>
                        </a>
                    </li>
                </ul>
                @endforeach

            </li>            
            
            @endforeach

            <!--<li class="menu-header small text-uppercase">
                <span class="menu-header-text">System Management</span>
            </li>

            <li class="menu-item {{ request()->is('users') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-layout"></i>
                    <div data-i18n="Pengaturan Sistem">Pengaturan Sistem</div>
                </a>

                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="" class="menu-link">
                            <div data-i18n="Pengaturan">Pengaturan</div>
                        </a>
                    </li>
                </ul>
            </li>-->
        @endrole
    </ul>
</aside>