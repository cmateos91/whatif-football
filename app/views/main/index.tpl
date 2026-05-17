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

        {* Formulario de escenario *}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-magic me-2"></i>Simula un escenario</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Elige un jugador y descubre cómo habría cambiado la clasificación sin su aportación.</p>

                <form id="scenario-form">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="player-search" class="form-label fw-semibold">
                                <i class="bi bi-person-fill me-1"></i>Jugador
                            </label>
                            <div class="autocomplete-wrapper">
                                <input type="text" id="player-search" class="form-control" placeholder="Escribe un nombre..." autocomplete="off">
                                <input type="hidden" id="player-select" name="player_id">
                                <div id="autocomplete-list"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="modo-select" class="form-label fw-semibold">
                                <i class="bi bi-sliders me-1"></i>Descontar
                            </label>
                            <select id="modo-select" name="modo" class="form-select">
                                <option value="ambos">Goles y asistencias</option>
                                <option value="goles">Solo goles</option>
                                <option value="asistencias">Solo asistencias</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" id="btn-calcular" class="btn btn-dark w-100">
                                <i class="bi bi-play-fill me-1"></i>Calcular
                            </button>
                        </div>
                    </div>
                </form>

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

        <div id="resultado"></div>

    </div>
</div>
