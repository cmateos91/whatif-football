<div class="row justify-content-center">
    <div class="col-lg-10">

        {* Selector de liga y temporada *}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="form-temporada" method="POST" action="/main/cambiarTemporada">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="liga-select" class="form-label fw-semibold">
                                <i class="bi bi-globe-europe-africa me-1"></i>Liga
                            </label>
                            <select id="liga-select" name="liga_id" class="form-select">
                                {foreach $ligas as $l}
                                    <option value="{$l->id}" {if $l->id == $liga_actual_id}selected{/if}>{$l->nombre}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="temporada-select" class="form-label fw-semibold">
                                <i class="bi bi-calendar3 me-1"></i>Temporada
                            </label>
                            <select id="temporada-select" name="temporada_id" class="form-select" onchange="this.form.submit()">
                                {foreach $temporadas as $t}
                                    <option value="{$t->id}" {if $t->id == $temporada_actual_id}selected{/if}>{$t->nombre}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {* Pestañas *}
        <ul class="nav nav-tabs mb-0" id="game-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-libre-btn" data-bs-toggle="tab" data-bs-target="#tab-libre" type="button">
                    <i class="bi bi-person-fill-gear me-1"></i>Modo Libre
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-infinito-btn" data-bs-toggle="tab" data-bs-target="#tab-infinito" type="button">
                    <i class="bi bi-infinity me-1"></i>Modo Infinito
                </button>
            </li>
        </ul>

        <div class="tab-content">

            {* ── MODO LIBRE ── *}
            <div class="tab-pane fade show active" id="tab-libre" role="tabpanel">
                <div class="card shadow-sm border-top-0 rounded-top-0 mb-4">
                    <div class="card-body">
                        <p class="text-muted mb-4">Elige dos jugadores, vota quién crees que aportó más puntos a su equipo y descubre el resultado real.</p>
                        <div class="row g-4 align-items-end mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold"><i class="bi bi-person-fill me-1"></i>Jugador 1</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="search-j1" class="form-control" placeholder="Escribe un nombre..." autocomplete="off">
                                    <input type="hidden" id="id-j1">
                                    <div id="list-j1" class="autocomplete-list"></div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="fs-3 fw-bold text-secondary">VS</span>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold"><i class="bi bi-person-fill me-1"></i>Jugador 2</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="search-j2" class="form-control" placeholder="Escribe un nombre..." autocomplete="off">
                                    <input type="hidden" id="id-j2">
                                    <div id="list-j2" class="autocomplete-list"></div>
                                </div>
                            </div>
                        </div>

                        <div id="zona-voto" class="d-none">
                            <p class="text-center fw-semibold mb-3">¿Quién crees que aportó más puntos a su equipo?</p>
                            <div class="d-flex justify-content-center gap-3">
                                <button id="voto-j1" class="btn btn-outline-primary btn-lg px-4">
                                    <i class="bi bi-hand-index-thumb me-1"></i><span id="nombre-voto-j1">Jugador 1</span>
                                </button>
                                <button id="voto-empate" class="btn btn-outline-secondary btn-lg px-4">
                                    <i class="bi bi-dash-circle me-1"></i>Empate
                                </button>
                                <button id="voto-j2" class="btn btn-outline-primary btn-lg px-4">
                                    <i class="bi bi-hand-index-thumb me-1"></i><span id="nombre-voto-j2">Jugador 2</span>
                                </button>
                            </div>
                        </div>

                        <div id="zona-calcular" class="text-center d-none mt-3">
                            <button id="btn-comparar" class="btn btn-dark btn-lg px-5">
                                <i class="bi bi-play-fill me-1"></i>Comparar
                            </button>
                        </div>
                    </div>
                </div>
                <div id="resultado-cara"></div>
            </div>

            {* ── MODO INFINITO ── *}
            <div class="tab-pane fade" id="tab-infinito" role="tabpanel">
                <div class="card shadow-sm border-top-0 rounded-top-0 mb-4">
                    <div class="card-body">

                        {* Pantalla de inicio *}
                        <div id="inf-inicio" class="text-center py-4">
                            <i class="bi bi-infinity display-1 text-dark mb-3 d-block"></i>
                            <h4>Modo Infinito</h4>
                            <p class="text-muted mb-4">Se irán cargando pares de jugadores al azar. Vota quién crees que aportó más puntos.<br>El juego termina cuando falles. ¿Cuántas aciertas seguidas?</p>
                            <div class="d-flex justify-content-center gap-2">
                                <button id="btn-play" class="btn btn-dark btn-lg px-5">
                                    <i class="bi bi-play-fill me-1"></i>Jugar
                                </button>
                                <button id="btn-ranking-inicio" class="btn btn-outline-dark btn-lg px-4">
                                    <i class="bi bi-trophy me-1"></i>Ranking
                                </button>
                            </div>
                            <div id="ranking-inicio" class="mt-4 d-none text-start"></div>
                        </div>

                        {* Pantalla de juego *}
                        <div id="inf-juego" class="d-none">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-dark fs-6 px-3 py-2">
                                        <i class="bi bi-check-circle me-1"></i><span id="inf-aciertos">0</span> aciertos
                                    </span>
                                </div>
                                <div class="fs-4 fw-bold font-monospace" id="inf-timer">00:00</div>
                                <button id="btn-rendirse" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-flag me-1"></i>Rendirse
                                </button>
                            </div>
                            <div id="inf-par"></div>
                        </div>

                        {* Game over *}
                        <div id="inf-gameover" class="d-none"></div>

                    </div>
                </div>
            </div>

        </div>

        <script>
            var jugadores = [
                {foreach $jugadores_por_equipo as $equipo => $jugadores}
                    {foreach $jugadores as $j}
                        { id: {$j->id}, nombre: "{$j->nombre|escape:'javascript'}", equipo: "{$equipo|escape:'javascript'}" },
                    {/foreach}
                {/foreach}
            ];
        </script>

    </div>
</div>
