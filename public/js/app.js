$(document).ready(function () {
    console.log('app.js cargado, jQuery version:', $.fn.jquery);

    $('#liga-select').on('change', function () {
        var ligaId = $(this).val();
        $.ajax({
            url: '/main/temporadasPorLiga',
            method: 'GET',
            data: { liga_id: ligaId },
            dataType: 'json',
            success: function (temporadas) {
                var $select = $('#temporada-select');
                $select.empty();
                $.each(temporadas, function (i, t) {
                    $select.append('<option value="' + t.id + '">' + t.nombre + '</option>');
                });
                $select.trigger('change');
            }
        });
    });
    // Sync select ↔ autocomplete
    $('#player-select-ui').on('change', function () {
        var selectedId = $(this).val();
        if (!selectedId) {
            $('#player-search').val('');
            $('#player-select').val('');
            return;
        }
        var jugador = jugadores.find(function (j) { return j.id == selectedId; });
        if (jugador) {
            $('#player-search').val(jugador.nombre);
            $('#player-select').val(jugador.id);
        }
    });

    $('#player-search').on('input', function () {
        var texto = $(this).val().toLowerCase().trim();
        var $list = $('#autocomplete-list');
        $list.empty();

        // Clear select if text changed
        if (texto.length < 2) {
            $list.hide();
            return;
        }

        var resultados = jugadores.filter(function (j) {
            return j.nombre.toLowerCase().indexOf(texto) !== -1;
        }).slice(0, 10);

        if (resultados.length === 0) {
            $list.hide();
            return;
        }

        $.each(resultados, function (i, j) {
            var $item = $('<div class="autocomplete-item">')
                .text(j.nombre + ' (' + j.equipo + ')')
                .on('click', function () {
                    $('#player-search').val(j.nombre);
                    $('#player-select').val(j.id);
                    $('#player-select-ui').val(j.id);
                    $list.hide();
                });
            $list.append($item);
        });

        $list.show();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.autocomplete-wrapper').length) {
            $('#autocomplete-list').hide();
        }
    });

    $('#scenario-form').on('submit', function (e) {
        e.preventDefault();

        var jugadorId = $('#player-select').val();
        var modo = $('#modo-select').val();

        if (!jugadorId) {
            alert('Selecciona un jugador primero');
            return;
        }

        $('#btn-calcular').prop('disabled', true).text('Calculando...');
        $('#resultado').html('');

        $.ajax({
            url: '/resultados/calcular',
            method: 'POST',
            data: { jugador_id: jugadorId, modo: modo },
            dataType: 'json',
            success: function (response) {
                if (!response.ok) {
                    $('#resultado').html('<p calss="error">' + response.error + '</p>');
                    return;
                }

                renderResultado(response.data);
            },
            error: function () {
                $('#resultado').html('<p class"error">Error al conectar con el servidor</p>');
            },
            complete: function () {
                $('#btn-calcular').prop('disabled', false).text('Calcular');
            }
        });
    });

    function renderResultado(data) {
        var modo = $('#modo-select').val();

        var textoModo = modo === 'goles' ? 'los goles'
            : modo === 'asistencias' ? 'las asistencias'
            : 'los goles y asistencias';

        var html = '<div class="card shadow-sm mb-4">';
        html += '<div class="card-header bg-dark text-white">';
        html += '<h5 class="mb-0">¿Qué pasaría sin ' + textoModo + ' de ' + data.jugador + '?</h5>';
        html += '</div>';
        html += '<div class="card-body">';
        html += '<div class="d-flex flex-wrap gap-3 align-items-center">';
        html += '<span class="badge bg-secondary fs-6">' + data.equipo + '</span>';
        if (modo === 'goles' || modo === 'ambos') {
            html += '<span><i class="bi bi-dribbble me-1"></i><strong>' + data.total_goles + '</strong> goles</span>';
        }
        if (modo === 'asistencias' || modo === 'ambos') {
            html += '<span><i class="bi bi-arrow-up-right-circle me-1"></i><strong>' + data.total_asistencias + '</strong> asistencias</span>';
        }
        html += '<span><i class="bi bi-calendar-event me-1"></i><strong>' + data.partidos_jugados + '</strong> partidos</span>';
        html += '</div>';
        html += '</div></div>';

        html += '<div class="row g-4 mb-4">';

        html += '<div class="col-md-6">';
        html += '<div class="card shadow-sm h-100">';
        html += '<div class="card-header"><h6 class="mb-0"><i class="bi bi-list-ol me-1"></i>Clasificación real</h6></div>';
        html += '<div class="card-body p-0">';
        html += renderTabla(data.original, false);
        html += '</div></div></div>';

        html += '<div class="col-md-6">';
        html += '<div class="card shadow-sm h-100">';
        html += '<div class="card-header"><h6 class="mb-0"><i class="bi bi-arrow-left-right me-1"></i>Sin ' + textoModo + ' de ' + data.jugador + '</h6></div>';
        html += '<div class="card-body p-0">';
        html += renderTabla(data.nueva, true);
        html += '</div></div></div>';

        html += '</div>';

        if (data.partidos_afectados.length > 0) {
            var afectados = data.partidos_afectados.slice().sort(function (a, b) { return a.jornada - b.jornada; });
            var totalAportado = 0;
            $.each(afectados, function (i, p) { totalAportado += (p.puntos_orig - p.puntos_nuevo); });

            html += '<div class="card shadow-sm mb-4">';
            html += '<div class="card-header"><h6 class="mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Partidos que cambiarían <span class="badge bg-danger ms-1">' + afectados.length + '</span></h6></div>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-bordered table-hover table-sm mb-0">';
            html += '<thead class="table-dark"><tr><th>J.</th><th>Partido</th><th>Original</th><th>Nuevo</th><th class="text-center">Pts real</th><th class="text-center">Pts sin él</th><th class="text-center">Aportación</th><th class="text-center">Acumulado</th><th>Motivo</th></tr></thead>';
            html += '<tbody>';
            var acumulado = 0;
            $.each(afectados, function (i, p) {
                var diff = p.puntos_orig - p.puntos_nuevo;
                acumulado += diff;

                var motivoBadge = p.motivo === 'goles' ? '<span class="badge bg-primary">Gol</span>'
                    : p.motivo === 'asistencias' ? '<span class="badge bg-info text-dark">Asistencia</span>'
                    : '<span class="badge bg-warning text-dark">Ambos</span>';

                var diffHtml = diff > 0
                    ? '<span class="text-success fw-bold">+' + diff + '</span>'
                    : '<span class="text-danger fw-bold">' + diff + '</span>';

                var acumHtml = acumulado > 0
                    ? '<span class="badge bg-success">+' + acumulado + '</span>'
                    : '<span class="badge bg-danger">' + acumulado + '</span>';

                html += '<tr>';
                html += '<td>' + p.jornada + '</td>';
                html += '<td>' + p.local + ' vs ' + p.visitante + '</td>';
                html += '<td><strong>' + p.resultado_orig + '</strong></td>';
                html += '<td><strong>' + p.resultado_nuevo + '</strong></td>';
                html += '<td class="text-center">' + p.puntos_orig + '</td>';
                html += '<td class="text-center">' + p.puntos_nuevo + '</td>';
                html += '<td class="text-center">' + diffHtml + '</td>';
                html += '<td class="text-center">' + acumHtml + '</td>';
                html += '<td>' + motivoBadge + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '</div>';

            var resumenColor = totalAportado > 0 ? 'success' : 'danger';
            var resumenIcono = totalAportado > 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow';
            html += '<div class="card-footer d-flex justify-content-between align-items-center">';
            html += '<span class="text-muted small"><i class="bi bi-info-circle me-1"></i>' + afectados.length + ' partido' + (afectados.length !== 1 ? 's' : '') + ' habrían tenido un resultado diferente.</span>';
            html += '<span class="fw-bold text-' + resumenColor + '"><i class="bi ' + resumenIcono + ' me-1"></i>' + (totalAportado > 0 ? '+' : '') + totalAportado + ' pts aportados por ' + data.jugador + '</span>';
            html += '</div>';
            html += '</div>';
        } else {
            html += '<div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>Este jugador no tuvo impacto en los resultados — la clasificación no cambiaría.</div>';
        }

        $('#resultado').html(html);
    }

    function renderTabla(filas, esNueva) {
        var html = '<table class="table table-bordered table-hover table-sm mb-0">';
        html += '<thead class="table-dark"><tr><th>Pos</th><th>Equipo</th><th>Pts</th><th>GF</th><th>GC</th>';
        if (esNueva) html += '<th>Cambio</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        $.each(filas, function (i, fila) {
            var clase = '';
            var flecha = '-';

            if (esNueva) {
                if (fila.cambio_posicion > 0) { clase = 'subio'; flecha = '<span class="text-success fw-bold">▲ ' + fila.cambio_posicion + '</span>'; }
                else if (fila.cambio_posicion < 0) { clase = 'bajo'; flecha = '<span class="text-danger fw-bold">▼ ' + Math.abs(fila.cambio_posicion) + '</span>'; }
            }

            html += '<tr class="' + clase + '">';
            html += '<td class="text-center fw-bold">' + fila.posicion + '</td>';
            html += '<td>' + fila.equipo + '</td>';
            html += '<td class="text-center fw-bold">' + fila.puntos + '</td>';
            html += '<td class="text-center">' + fila.gf + '</td>';
            html += '<td class="text-center">' + fila.gc + '</td>';
            if (esNueva) html += '<td class="text-center">' + flecha + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table>';
        return html;
    }
});