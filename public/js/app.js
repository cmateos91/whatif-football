$(document).ready(function () {
    console.log('app.js cargado, jQuery version:', $.fn.jquery);
    $('#scenario-form').on('submit', function (e) {
        e.preventDefault();

        var jugadorId = $('#player-select').val();

        if (!jugadorId) {
            alert('Selecciona un jugador primero');
            return;
        }

        $('#btn-calcular').prop('disabled', true).text('Calculando...');
        $('#resultado').html('');

        $.ajax({
            url: '/resultados/calcular',
            method: 'POST',
            data: { jugador_id: jugadorId },
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
        var html = '<h2>¿Qué pasaría sin los goles de ' + data.jugador + '?</h2>';
        html += '<div class="jugador-stats">';
        html += '<span>' + data.equipo + '</span>';
        html += '<span><strong>' + data.total_goles + '</strong> goles en <strong>' + data.partidos_jugados + '</strong> partidos</span>';
        html += '</div>'

        html += '<div class="tablas">';

        //Tabla original
        html += '<div class="tabla-bloque">';
        html += '<h3>Clasificación real</h3>';
        html += renderTabla(data.original, false);
        html += '</div>';

        //Tabla nueva
        html += '<div class="tabla-bloque">';
        html += '<h3>Sin los goles de ' + data.jugador + '</h3>';
        html += renderTabla(data.nueva, true);
        html += '</div>';

        html += '</div>';

        //Partidos afectados
        if (data.partidos_afectados.length > 0) {
            html += '<h3>Partidos que cambiarían (' + data.partidos_afectados.length + ')</h3>';
            html += '<table class="tabla-partidos">';
            html += '<thead><tr><th>Jornada</th><th>Partido</th><th>Original</th><th>Nuevo</th><th>Goles quitados</th></tr></thead>';
            html += '<tbody>';
            $.each(data.partidos_afectados, function (i, p) {
                html += '<tr>';
                html += '<td>' + p.jornada + '</td>';
                html += '<td>' + p.local + ' vs ' + p.visitante + '</td>';
                html += '<td>' + p.resultado_orig + '</td>';
                html += '<td>' + p.resultado_nuevo + '</td>';
                html += '<td>' + p.goles_quitados + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
        } else {
            html += '<p>Este jugador no marcó ningún gol — la clasificación no cambiaría.</p>';
        }

        $('#resultado').html(html);
    }

    function renderTabla(filas, esNueva) {
        var html = '<table class="tabla-clasificacion">';
        html += '<thead><tr><th>Pos</th><th>Equipo</th><th>Pts</th><th>GF</th><th>GC</th>';
        if (esNueva) html += '<th>Cambio</th>'
        html += '</tr></thead>'
        html += '<tbody>';

        $.each(filas, function (i, fila) {
            var clase = '';
            var flecha = '';

            if (esNueva) {
                if (fila.cambio_posicion > 0) { clase = 'subio'; flecha = '▲ ' + fila.cambio_posicion; }
                if (fila.cambio_posicion < 0) { clase = 'bajo'; flecha = '▼ ' + Math.abs(fila.cambio_posicion); }
                if (fila.cambio_posicion === 0) { flecha = '-'; }
            }

            html += '<tr class="' + clase + '">';
            html += '<td>' + fila.posicion + '</td>';
            html += '<td>' + fila.equipo + '</td>';
            html += '<td>' + fila.puntos + '</td>';
            html += '<td>' + fila.gf + '</td>';
            html += '<td>' + fila.gc + '</td>';
            if (esNueva) html += '<td>' + flecha + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table>';
        return html;
    }
});