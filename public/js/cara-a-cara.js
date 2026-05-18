$(document).ready(function () {

    /* ─────────────────────────────────────────
       MODO LIBRE
    ───────────────────────────────────────── */

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

            if (!resultados.length) { $list.hide(); return; }

            $.each(resultados, function (i, j) {
                $('<div class="autocomplete-item">')
                    .text(j.nombre + ' (' + j.equipo + ')')
                    .on('click', function () {
                        $('#' + inputId).val(j.nombre);
                        $('#' + hiddenId).val(j.id);
                        $list.hide();
                        seleccion[slot] = j;
                        actualizarEstadoLibre();
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

    function actualizarEstadoLibre() {
        if (seleccion.j1 && seleccion.j2) {
            $('#nombre-voto-j1').text(seleccion.j1.nombre.split(' ')[0]);
            $('#nombre-voto-j2').text(seleccion.j2.nombre.split(' ')[0]);
            $('#zona-voto').removeClass('d-none');
            $('#zona-calcular').addClass('d-none');
            votoUsuario = null;
            resetBotonesVoto();
        } else {
            $('#zona-voto, #zona-calcular').addClass('d-none');
        }
        $('#resultado-cara').html('');
    }

    function resetBotonesVoto() {
        $('#voto-j1, #voto-j2').removeClass('btn-primary').addClass('btn-outline-primary');
        $('#voto-empate').removeClass('btn-secondary').addClass('btn-outline-secondary');
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
                renderResultadoLibre(res);
            },
            error: function () {
                $('#resultado-cara').html('<div class="alert alert-danger">Error al conectar con el servidor.</div>');
            },
            complete: function () {
                $('#btn-comparar').prop('disabled', false).html('<i class="bi bi-play-fill me-1"></i>Comparar');
            }
        });
    });

    function renderResultadoLibre(res) {
        var acertado = votoUsuario === res.ganador;
        var textoRes = textoGanador(res.ganador, res.jugador1.nombre, res.jugador2.nombre);

        var html = '<div class="card shadow-sm mb-4">';
        html += '<div class="card-header ' + (acertado ? 'bg-success' : 'bg-danger') + ' text-white">';
        html += '<h5 class="mb-0">' + (acertado ? '<i class="bi bi-check-circle me-2"></i>¡Acertaste!' : '<i class="bi bi-x-circle me-2"></i>¡Fallaste!') + ' — ' + textoRes + '</h5>';
        html += '</div><div class="card-body"><div class="row g-4">';
        html += tarjetaJugador(res.jugador1, res.ganador === 1, res.ganador === 0);
        html += '<div class="col-12 col-md-2 d-flex align-items-center justify-content-center"><span class="fs-2 fw-bold text-secondary">VS</span></div>';
        html += tarjetaJugador(res.jugador2, res.ganador === 2, res.ganador === 0);
        html += '</div></div></div>';

        $('#resultado-cara').html(html);
        $('html, body').animate({ scrollTop: $('#resultado-cara').offset().top - 20 }, 400);
    }

    /* ─────────────────────────────────────────
       MODO INFINITO
    ───────────────────────────────────────── */

    var inf = {
        active: false,
        aciertos: 0,
        segundos: 0,
        timerInterval: null,
        currentPar: null,
        esperandoSiguiente: false,
    };

    $('#btn-play').on('click', iniciarInfinito);
    $('#btn-rendirse').on('click', function () { gameOver(true); });

    function iniciarInfinito() {
        inf.active = true;
        inf.aciertos = 0;
        inf.segundos = 0;
        inf.esperandoSiguiente = false;

        $('#inf-inicio').addClass('d-none');
        $('#inf-gameover').addClass('d-none').html('');
        $('#inf-juego').removeClass('d-none');
        $('#inf-aciertos').text('0');
        $('#inf-timer').text('00:00');

        inf.timerInterval = setInterval(function () {
            inf.segundos++;
            var m = Math.floor(inf.segundos / 60);
            var s = inf.segundos % 60;
            $('#inf-timer').text((m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s);
        }, 1000);

        cargarPar();
    }

    function cargarPar() {
        inf.currentPar = null;
        $('#inf-par').html(
            '<div class="text-center py-5">' +
            '<span class="spinner-border text-dark me-2"></span>Cargando par...' +
            '</div>'
        );

        $.ajax({
            url: '/juegos/parAleatorio',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (!res.ok) { gameOver(false); return; }
                inf.currentPar = res;
                renderPar(res);
            },
            error: function () { gameOver(false); }
        });
    }

    function renderPar(res) {
        var html = '<div class="row g-4 mb-4">';
        html += tarjetaJugadorInf(res.jugador1, 1);
        html += '<div class="col-12 col-md-2 d-flex align-items-center justify-content-center"><span class="fs-2 fw-bold text-secondary">VS</span></div>';
        html += tarjetaJugadorInf(res.jugador2, 2);
        html += '</div>';
        html += '<div class="d-flex justify-content-center gap-3">';
        html += '<button class="btn btn-outline-primary btn-lg px-4 inf-voto" data-voto="1"><i class="bi bi-hand-index-thumb me-1"></i>' + res.jugador1.nombre.split(' ')[0] + '</button>';
        html += '<button class="btn btn-outline-secondary btn-lg px-4 inf-voto" data-voto="0"><i class="bi bi-dash-circle me-1"></i>Empate</button>';
        html += '<button class="btn btn-outline-primary btn-lg px-4 inf-voto" data-voto="2"><i class="bi bi-hand-index-thumb me-1"></i>' + res.jugador2.nombre.split(' ')[0] + '</button>';
        html += '</div>';
        $('#inf-par').html(html);
    }

    $(document).on('click', '.inf-voto', function () {
        if (!inf.active || inf.esperandoSiguiente || !inf.currentPar) return;
        inf.esperandoSiguiente = true;

        var voto = parseInt($(this).data('voto'));
        var ganador = inf.currentPar.ganador;
        var acertado = voto === ganador;

        $('.inf-voto').prop('disabled', true);

        var feedback = acertado
            ? '<div class="alert alert-success text-center fw-bold mt-3"><i class="bi bi-check-circle me-2"></i>¡Correcto!</div>'
            : '<div class="alert alert-danger text-center fw-bold mt-3"><i class="bi bi-x-circle me-2"></i>Incorrecto — ' + textoGanador(ganador, inf.currentPar.jugador1.nombre, inf.currentPar.jugador2.nombre) + '</div>';
        $('#inf-par').append(feedback);

        if (acertado) {
            inf.aciertos++;
            $('#inf-aciertos').text(inf.aciertos);
            setTimeout(function () {
                inf.esperandoSiguiente = false;
                cargarPar();
            }, 1800);
        } else {
            setTimeout(function () { gameOver(false); }, 1800);
        }
    });

    function gameOver(rendido) {
        clearInterval(inf.timerInterval);
        inf.active = false;

        $('#inf-juego').addClass('d-none');

        var html = '<div class="text-center py-3">';
        if (rendido) {
            html += '<h4 class="mb-1"><i class="bi bi-flag text-secondary me-2"></i>Partida terminada</h4>';
        } else {
            html += '<h4 class="text-danger mb-1"><i class="bi bi-x-circle me-2"></i>¡Fallaste!</h4>';
        }

        var m = Math.floor(inf.segundos / 60), s = inf.segundos % 60;
        var tiempoStr = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;

        html += '<div class="d-flex justify-content-center gap-4 my-3">';
        html += '<div><span class="display-6 fw-bold">' + inf.aciertos + '</span><div class="text-muted small">aciertos</div></div>';
        html += '<div><span class="display-6 fw-bold font-monospace">' + tiempoStr + '</span><div class="text-muted small">tiempo</div></div>';
        html += '</div>';

        if (inf.aciertos > 0) {
            html += '<div id="zona-nombre" class="mt-3">';
            html += '<p class="text-muted">¿Quieres guardar tu puntuación?</p>';
            html += '<div class="d-flex justify-content-center gap-2">';
            html += '<input type="text" id="input-nombre" class="form-control w-auto" placeholder="Tu nombre" maxlength="30" style="max-width:200px">';
            html += '<button id="btn-guardar" class="btn btn-dark">Guardar</button>';
            html += '</div>';
            html += '<div id="msg-guardado" class="mt-2"></div>';
            html += '</div>';
        }

        html += '<button id="btn-rejugar" class="btn btn-outline-dark mt-3 me-2"><i class="bi bi-arrow-clockwise me-1"></i>Volver a jugar</button>';
        html += '<button id="btn-ver-ranking" class="btn btn-dark mt-3"><i class="bi bi-trophy me-1"></i>Ver ranking</button>';
        html += '</div>';
        html += '<div id="ranking-container" class="mt-3"></div>';

        $('#inf-gameover').html(html).removeClass('d-none');

        cargarClasificacion();
    }

    $(document).on('click', '#btn-guardar', function () {
        var nombre = $('#input-nombre').val().trim();
        if (!nombre) return;

        $(this).prop('disabled', true);

        $.ajax({
            url: '/juegos/guardarPuntuacion',
            method: 'POST',
            data: { nombre: nombre, aciertos: inf.aciertos, tiempo_segundos: inf.segundos },
            dataType: 'json',
            success: function (res) {
                if (res.ok) {
                    $('#zona-nombre').html('<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>¡Puntuación guardada!</div>');
                    cargarClasificacion();
                }
            }
        });
    });

    $(document).on('click', '#btn-rejugar', function () {
        $('#inf-gameover').addClass('d-none').html('');
        $('#inf-inicio').removeClass('d-none');
    });

    $(document).on('click', '#btn-ver-ranking', function () {
        $('html, body').animate({ scrollTop: $('#ranking-container').offset().top - 20 }, 400);
    });

    function cargarClasificacion() {
        $.ajax({
            url: '/juegos/clasificacion',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (!res.ok || !res.clasificacion.length) {
                    $('#ranking-container').html('<p class="text-muted text-center">Aún no hay puntuaciones guardadas.</p>');
                    return;
                }
                var html = '<h6 class="fw-bold mb-3"><i class="bi bi-trophy-fill text-warning me-1"></i>Top 10</h6>';
                html += '<table class="table table-sm table-bordered">';
                html += '<thead class="table-dark"><tr><th>#</th><th>Nombre</th><th class="text-center">Aciertos</th><th class="text-center">Tiempo</th></tr></thead><tbody>';
                $.each(res.clasificacion, function (i, p) {
                    var m = Math.floor(p.tiempo_segundos / 60), s = p.tiempo_segundos % 60;
                    var t = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                    var esYo = (p.nombre === $('#input-nombre').val().trim() && i === 0);
                    html += '<tr' + (esYo ? ' class="table-warning fw-bold"' : '') + '>';
                    html += '<td>' + (i + 1) + '</td>';
                    html += '<td>' + p.nombre + '</td>';
                    html += '<td class="text-center">' + p.aciertos + '</td>';
                    html += '<td class="text-center font-monospace">' + t + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                $('#ranking-container').html(html);
            }
        });
    }

    /* ─────────────────────────────────────────
       HELPERS COMPARTIDOS
    ───────────────────────────────────────── */

    function textoGanador(ganador, nombre1, nombre2) {
        return ganador === 1 ? '¡<strong>' + nombre1 + '</strong> aportó más!'
            : ganador === 2 ? '¡<strong>' + nombre2 + '</strong> aportó más!'
            : '¡Empate!';
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
        html += statBox('bi-dribbble', j.goles, 'Goles');
        html += statBox('bi-arrow-up-right-circle', j.asistencias, 'Asistencias');
        html += statBox('bi-calendar-event', j.partidos, 'Partidos');
        html += statBox('bi-exclamation-triangle', j.partidos_clave, 'Partidos clave');
        html += '</div><hr>';
        html += '<div class="text-center"><span class="fs-4 fw-bold ' + (j.pts_aportados > 0 ? 'text-success' : 'text-muted') + '">';
        html += (j.pts_aportados > 0 ? '+' : '') + j.pts_aportados + ' pts</span>';
        html += '<div class="text-muted small">aportados a su equipo</div></div>';
        html += '</div></div></div>';
        return html;
    }

    function tarjetaJugadorInf(j, num) {
        return '<div class="col-12 col-md-5">'
            + '<div class="card h-100 border">'
            + '<div class="card-header fw-semibold">' + j.nombre + '</div>'
            + '<div class="card-body">'
            + '<p class="text-muted mb-2 small">' + j.equipo + '</p>'
            + '<div class="row text-center g-2">'
            + statBox('bi-dribbble', j.goles, 'Goles')
            + statBox('bi-arrow-up-right-circle', j.asistencias, 'Asistencias')
            + '</div>'
            + '</div></div></div>';
    }

    function statBox(icon, valor, label) {
        return '<div class="col-6"><div class="p-2 bg-light rounded">'
            + '<i class="bi ' + icon + ' d-block fs-5 mb-1"></i>'
            + '<strong>' + valor + '</strong>'
            + '<div class="text-muted" style="font-size:0.75rem">' + label + '</div>'
            + '</div></div>';
    }
});
