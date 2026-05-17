<div class="scenario-wizard">
    <h2>Seleccion un jugador</h2>
    <p>Elige un jugador para ver cómo habría quedado la clasificación sin sus goles</p>

    <form id="form-temporada" method="POST" action="/main/cambiarTemporada">
      <label for="temporada-select">Temporada</label>
      <select id="temporada-select" name="temporada_id" onchange="this.form.submit()">
          {foreach $temporadas as $t}
              <option value="{$t->id}" {if $t->id == $temporada_actual_id}selected{/if}>
                  {$t->nombre}
              </option>
          {/foreach}
      </select>
  </form>

    <form id="scenario-form">
        <label for="player-select">Jugador:</label>
        <select id="player-select" name="player_id">
            <option value=""> -- Selecciona -- </option>
            {foreach $jugadores_por_equipo as $equipo => $jugadores}
                <optgroup label="{$equipo}">
                    {foreach $jugadores as $j}
                    <option value="{$j->id}">{$j->nombre}</option>
                    {/foreach}
                </optgroup>
            {{/foreach}}
        </select>
        <button type="submit" id="btn-calcular">Calcular</button>
    </form>

    <div id="resultado"></div>
</div>