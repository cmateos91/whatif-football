$(document).ready(function () {

    var seleccion = { j1: null, j2: null };
    var votoUsuario = null;

    function setupAutocomplete(inputId, listId, hiddenId, slot) {
        $('#' + inputId).on('input', function () {
            var texto = $(this).val().toLowerCase().trim();
            var $list = $('#' + listId);
            $list.empty();

            if (texto.length < 2) { $list.hide(); return; }

            var resultados = jugadores.filter(function (j) {
                return j.nombre.toLowerCase().indexOf(texto) !== -1;
            }).slice(0, 10);

            if (resultados.length === 0) { $list.hide(); return; }

            $.each(resultados, function (i, j) {
                $('<div class="autocomplete-item">')
                    .text(j.nombre + ' (' + j.equipo + ')')
                    .on('click', function () {
                        $('#' + inputId).val(j.nombre);
                        $('#' + hiddenId).val(j.id);
                        $list.hide();
                        seleccion[slot] = j;
                        actualizarEstado();
                    })
                    .appendTo($list);
            });

            $list.show();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.autocomplete-wrapper').length) {
                $('#' + listId).hide();
            }
        });
    }

    setupAutocomplete('search-j1', 'list-j1', 'id-j1', 'j1');
    setupAutocomplete('search-j2', 'list-j2', 'id-j2', 'j2');

    function actualizarEstado() {
        if (seleccion.j1 && seleccion.j2) {
            $('#nombre-voto-j1').text(seleccion.j1.nombre.split(' ')[0]);
            $('#nombre-voto-j2').text(seleccion.j2.nombre.split(' ')[0]);
            $('#zona-voto').removeClass('d-none');
            $('#zona-calcular').addClass('d-none');
            votoUsuario = null;
            $('#voto-j1, #voto-empate, #voto-j2').removeClass('active btn-primary btn-secondary').addClass('btn-outline-primary btn-outline-secondary');
            $('#voto-empate').removeClass('btn-primary').addClass('btn-outline-secondary');
        } else {
            $('#zona-voto').addClass('d-none');
            $('#zona-calcular').addClass('d-none');
        }
        $('#resultado-cara').html('');
    }

    $('#voto-j1').on('click', function () {
        votoUsuario = 1;
        $('#voto-j1').removeClass('btn-outline-primary').addClass('btn-primary');
        $('#voto-j2').removeClass('btn-primary').addClass('btn-outline-primary');
        $('#voto-empate').removeClass('btn-secondary').addClass('btn-outline-secondary');
        $('#zona-calcular').removeClass('d-none');
    });

    $('#voto-empate').on('click', function () {
        votoUsuario = 0;
        $('#voto-empate').removeClass('btn-outline-secondary').addClass('btn-secondary');
        $('#voto-j1, #voto-j2').removeClass('btn-primary').addClass('btn-outline-primary');
        $('#zona-calcular').removeClass('d-none');
    });

    $('#voto-j2').on('click', function () {
        votoUsuario = 2;
        $('#voto-j2').removeClass('btn-outline-primary').addClass('btn-primary');
        $('#voto-j1').removeClass('btn-primary').addClass('btn-outline-primary');
        $('#voto-empate').removeClass('btn-secondary').addClass('btn-outline-secondary');
        $('#zona-calcular').removeClass('d-none');
    });

    $('#btn-comparar').on('click', function () {
        if (votoUsuario === null) return;

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Calculando...');

        $.ajax({
            url: '/juegos/comparar',
            method: 'POST',
            data: { jugador1_id: seleccion.j1.id, jugador2_id: seleccion.j2.id },
            dataType: 'json',
            success: function (res) {
                if (!res.ok) {
                    $('#resultado-cara').html('<div class="alert alert-danger">' + res.error + '</div>');
                    return;
                }
                renderResultado(res);
            },
            error: function () {
                $('#resultado-cara').html('<div class="alert alert-danger">Error al conectar con el servidor.</div>');
            },
            complete: function () {
                $('#btn-comparar').prop('disabled', false).html('<i class="bi bi-play-fill me-1"></i>Comparar');
            }
        });
    });

    function renderResultado(res) {
        var j1 = res.jugador1;
        var j2 = res.jugador2;
        var ganador = res.ganador;

        var acertado = votoUsuario === ganador;
        var textoResultado = ganador === 1 ? '¡<strong>' + j1.nombre + '</strong> aportó más!'
            : ganador === 2 ? '¡<strong>' + j2.nombre + '</strong> aportó más!'
            : '¡Empate! Los dos aportaron lo mismo.';

        var html = '<div class="card shadow-sm mb-4">';
        html += '<div class="card-header ' + (acertado ? 'bg-success' : 'bg-danger') + ' text-white">';
        html += '<h5 class="mb-0">' + (acertado ? '<i class="bi bi-check-circle me-2"></i>¡Acertaste!' : '<i class="bi bi-x-circle me-2"></i>¡Fallaste!') + ' — ' + textoResultado + '</h5>';
        html += '</div>';
        html += '<div class="card-body">';
        html += '<div class="row g-4">';
        html += tarjetaJugador(j1, ganador === 1, ganador === 0);
        html += '<div class="col-12 col-md-2 d-flex align-items-center justify-content-center"><span class="fs-2 fw-bold text-secondary">VS</span></div>';
        html += tarjetaJugador(j2, ganador === 2, ganador === 0);
        html += '</div>';
        html += '</div></div>';

        $('#resultado-cara').html(html);
        $('html, body').animate({ scrollTop: $('#resultado-cara').offset().top - 20 }, 400);
    }

    function tarjetaJugador(j, esGanador, esEmpate) {
        var borde = esGanador ? 'border-success border-2' : (esEmpate ? 'border-secondary' : 'border-danger border-2');
        var badge = esGanador ? '<span class="badge bg-success ms-2"><i class="bi bi-trophy-fill me-1"></i>Ganador</span>'
            : (esEmpate ? '<span class="badge bg-secondary ms-2">Empate</span>'
            : '<span class="badge bg-danger ms-2">Perdedor</span>');

        var html = '<div class="col-12 col-md-5">';
        html += '<div class="card h-100 border ' + borde + '">';
        html += '<div class="card-header fw-semibold">' + j.nombre + badge + '</div>';
        html += '<div class="card-body">';
        html += '<p class="text-muted mb-3 small">' + j.equipo + '</p>';
        html += '<div class="row text-center g-2">';
        html += stat('bi-dribbble', j.goles, 'Goles');
        html += stat('bi-arrow-up-right-circle', j.asistencias, 'Asistencias');
        html += stat('bi-calendar-event', j.partidos, 'Partidos');
        html += stat('bi-exclamation-triangle', j.partidos_clave, 'Partidos clave');
        html += '</div>';
        html += '<hr>';
        html += '<div class="text-center">';
        html += '<span class="fs-4 fw-bold ' + (j.pts_aportados > 0 ? 'text-success' : 'text-danger') + '">';
        html += (j.pts_aportados > 0 ? '+' : '') + j.pts_aportados + ' pts</span>';
        html += '<div class="text-muted small">aportados a su equipo</div>';
        html += '</div>';
        html += '</div></div></div>';
        return html;
    }

    function stat(icon, valor, label) {
        return '<div class="col-6"><div class="p-2 bg-light rounded">'
            + '<i class="bi ' + icon + ' d-block fs-5 mb-1"></i>'
            + '<strong>' + valor + '</strong>'
            + '<div class="text-muted" style="font-size:0.75rem">' + label + '</div>'
            + '</div></div>';
    }
});
