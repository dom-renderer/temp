@php
    $user = auth()->user();

    $userManagementPermissions = [
        'users.index', 'roles.index', 'engineers.index', 
        'co-ordinators.index', 'technicians.index', 'customers.index'
    ];

    $workManagementPermissions = [
        'departments.index', 'expertises.index', 'jobs.index', 'requisitions.index'
    ];

    $inventoryManagementPermissions = [
        'categories.index', 'products.index'
    ];

    $canViewUserManagement = collect($userManagementPermissions)->contains(fn($perm) => $user->can($perm));
    $canViewWorkManagement = collect($workManagementPermissions)->contains(fn($perm) => $user->can($perm));
    $canViewInventoryManagement = collect($inventoryManagementPermissions)->contains(fn($perm) => $user->can($perm));

    $segment = request()->segment(1);

    $userManagementSegments = ['users', 'roles', 'engineers', 'co-ordinators', 'technicians', 'customers'];
    $workManagementSegments = ['departments', 'expertises', 'jobs', 'requisitions'];
    $inventoryManagementSegments = ['categories', 'products'];

    $activeUserManagement = in_array($segment, $userManagementSegments);
    $activeWorkManagement = in_array($segment, $workManagementSegments);
    $activeInventoryManagement = in_array($segment, $inventoryManagementSegments);
@endphp

<nav id="sidebar" class="sidebar">
    <a class='sidebar-brand'>
        <img src="{{ Helper::logo() }}" style="height: 30px;margin-right: 8px;position: relative;bottom: 3px;" alt="Logo">
        <span style="font-size: initial;">{{ Helper::title() }}</span>
    </a>

    @auth
    <div class="sidebar-content">
        <div class="sidebar-user">
            <img src="{{ auth()->user()->userprofile }}" class="img-fluid rounded-circle mb-2" />
            <div class="fw-bold"> {{ auth()->user()->name }} </div>
            <small> {{ implode(', ', auth()->user()->roles()->pluck('name')->toArray()) }} </small>
        </div>

        <ul class="sidebar-nav">
            <li class="sidebar-header">Main</li>

            <li class="sidebar-item @if ($segment === 'dashboard') active @endif">
                <a href="{{ route('dashboard') }}" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-home"></i>
                    <span class="align-middle">Dashboards</span>
                </a>
            </li>

            @if ($canViewUserManagement)
            <li class="sidebar-item @if ($activeUserManagement) active @endif">
                <a data-bs-target="#usersmgmt" data-bs-toggle="collapse" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-users"></i>
                    <span class="align-middle">User Management</span>
                </a>
                <ul id="usersmgmt" class="sidebar-dropdown list-unstyled collapse @if ($activeUserManagement) show @endif" data-bs-parent="#sidebar">
                    @can('users.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('users.index') }}'>Users</a></li>
                    @endcan
                    @can('roles.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('roles.index') }}'>Roles</a></li>
                    @endcan
                    @can('engineers.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('engineers.index') }}'>Engineers</a></li>
                    @endcan
                    @can('co-ordinators.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('co-ordinators.index') }}'>Co-ordinators</a></li>
                    @endcan
                    @can('technicians.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('technicians.index') }}'>Technicians</a></li>
                    @endcan
                    @can('customers.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('customers.index') }}'>Customers</a></li>
                    @endcan
                </ul>
            </li>
            @endif

            @if ($canViewWorkManagement)
            <li class="sidebar-item @if ($activeWorkManagement) active @endif">
                <a data-bs-target="#commgmt" data-bs-toggle="collapse" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-briefcase"></i>
                    <span class="align-middle">Work Management</span>
                </a>
                <ul id="commgmt" class="sidebar-dropdown list-unstyled collapse @if ($activeWorkManagement) show @endif" data-bs-parent="#sidebar">
                    @can('departments.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('departments.index') }}'>Departments</a></li>
                    @endcan
                    @can('expertises.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('expertises.index') }}'>Expertise</a></li>
                    @endcan
                    @can('jobs.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('jobs.index') }}'>Jobs</a></li>
                    @endcan
                    @can('requisitions.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('requisitions.index') }}'>Requisitions</a></li>
                    @endcan
                </ul>
            </li>
            @endif

            @if ($canViewInventoryManagement)
            <li class="sidebar-item @if ($activeInventoryManagement) active @endif">
                <a data-bs-target="#invmgmt" data-bs-toggle="collapse" class="sidebar-link">
                    <i class="align-middle me-2 fas fa-fw fa-warehouse"></i>
                    <span class="align-middle">Inventory Management</span>
                </a>
                <ul id="invmgmt" class="sidebar-dropdown list-unstyled collapse @if ($activeInventoryManagement) show @endif" data-bs-parent="#sidebar">
                    @can('categories.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('categories.index') }}'>Categories</a></li>
                    @endcan
                    @can('products.index')
                        <li class="sidebar-item"><a class='sidebar-link' href='{{ route('products.index') }}'>Products</a></li>
                    @endcan
                </ul>
            </li>
            @endif

            <li class="sidebar-item">
                <form action="{{ route('logout') }}" method="POST"> @csrf
                    <button type="submit" class="sidebar-link" style="width: 100%; text-align: left; border: none;">
                        <i class="align-middle me-2 fas fa-fw fa-sign-out"></i>
                        <span class="align-middle">Sign out</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>
    @endauth
</nav>