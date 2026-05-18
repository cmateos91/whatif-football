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
                                    <option value="{$l->id}" {if $l->id == $liga_actual_id}selected{/if}>
                                        {$l->nombre}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="temporada-select" class="form-label fw-semibold">
                                <i class="bi bi-calendar3 me-1"></i>Temporada
                            </label>
                            <select id="temporada-select" name="temporada_id" class="form-select" onchange="this.form.submit()">
                                {foreach $temporadas as $t}
                                    <option value="{$t->id}" {if $t->id == $temporada_actual_id}selected{/if}>
                                        {$t->nombre}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {* Cara a cara *}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-controller me-2"></i>Cara a cara — ¿Quién aportó más?</h5>
            </div>
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

                {* Botones de voto *}
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

                <div id="zona-calcular" class="text-center d-none">
                    <button id="btn-comparar" class="btn btn-dark btn-lg px-5">
                        <i class="bi bi-play-fill me-1"></i>Comparar
                    </button>
                </div>
            </div>
        </div>

        <div id="resultado-cara"></div>

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
