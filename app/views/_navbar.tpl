<nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">
            <i class="bi bi-trophy-fill text-warning me-2"></i>WhatIF Football
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbar-menu">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <li class="nav-item">
                    <a class="nav-link {if $current_page == 'home'}active{/if}" href="/">
                        <i class="bi bi-house-fill me-1"></i>Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {if $current_page == 'juegos'}active{/if}" href="/juegos/caraacara">
                        <i class="bi bi-controller me-1"></i>Juegos
                    </a>
                </li>
            </ul>
            <span class="text-secondary small">Datos reales de StatsBomb</span>
        </div>
    </div>
</nav>
