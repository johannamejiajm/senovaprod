@extends('../vistas.plantilla.modales')
@extends('../vistas.plantilla.plantillaback')
@section('script')
    <script>
        var AJAX = "/publicaciones_peticiones";
        var GUARDAR_PUBLICACIONES = 1;
        var ACTUALIZAR_PUBLICACIONES = 2;
        var ELIMINAR_PUBLICACIONES = 3;
        var BUSCAR_PRECIOSVENDEDOR = 4;
        var BUSCAR_PRECIOSASOCIACION = 5;
        var parametro_seleccionado = "";
        var tablaPublicaciones = "";
        var fila = "";
        var vista = "";
        $(document).ready(function() {
            $('#li_publicaciones').addClass('active');
            $('#i_publicaciones').css('color', '#3BB77E');
            cargarTablaPublicaciones();
            guardarPublicacion();
            buttonClicks();
            selectChanges();
        });

        function buttonClicks() {
            $("#btnmodalguardar").on("click", function(e) {
                vista = 1;
                $('#idunidades').val("").trigger("chosen:updated");
                $('#idproductos').val("").trigger("chosen:updated");
                $('#listadoprecios').val("").trigger("chosen:updated");
                $("#formGuardar")[0].reset();
            });
        }

        // $('#idproductos').change(function () {
        //     var productoSeleccionado = $(this).val();
        //     buscarPrecios(productoSeleccionado); // Llama a la función buscarPrecios con el ID del producto seleccionado
        // });

        function selectChanges() {
            $("#idproductos, #idunidades").change(function() {
                // buscarPrecios($(this).val());
                buscarPreciosAsociacion($("#idproductos").val(), $("#idunidades").val());
                buscarPreciosVendedor($("#idproductos").val(), $("#idunidades").val());
                // buscarPrecios($("#idproductos").val());
            })
        }

        function buscarId(data, modo) {
            vista = 2;
            if (fila != "") {
                $(fila).removeClass('selected');
            }
            fila = tablaPublicaciones.row("." + data).node();
            $(fila).addClass('selected');
            parametro_seleccionado = $("#tablapublicaciones").DataTable().row('.selected').data();
            
            if (modo == 1) {
                $("#oferta").val(parametro_seleccionado.ofertado);
                $("#idproductos").val(parametro_seleccionado.producto_id);
                $("#idproductos").trigger("chosen:updated");
                $("#idunidades").val(parametro_seleccionado.unidades_id);
                $("#idunidades").trigger("chosen:updated");
                $("#listadoprecios").find('option').remove().end().append('<option value=""></option>').trigger("chosen:updated");
                $("#listadoprecios").append(new Option(parametro_seleccionado.productos.precios.precio, parametro_seleccionado.productos.precios.unidades.unidad, parametro_seleccionado.productos.precios.id, true, true, true));
                $('#listadoprecios').trigger("chosen:updated");

                cargarImg("#imagenes", parametro_seleccionado.imagen);
            } else if (modo == 2) {
                eliminarProducto(parametro_seleccionado.id);
                
            }
        }

        function guardarPublicacion() {
            $("#enviar").on("click", function(e) {
                let datosFormulario = "";
                e.preventDefault(); // Previene el envío por defecto del formulario
                // Valida el formulario usando Bootstrap
                var form = document.getElementById("formGuardar");

                if (form.checkValidity() === false) {
                    form.classList.add("was-validated");
                    if ($("#idproductos").val() == "") {
                        $("#idproductos_chosen").removeClass("valid");
                        $("#idproductos_chosen").addClass("is-invalid");
                    } else {
                        $("#idproductos_chosen").removeClass("is-invalid");
                        $("#idproductos_chosen").addClass("valid");
                    }

                    if ($("#idunidades").val() == "") {
                        $("#idunidades_chosen").removeClass("valid");
                        $("#idunidades_chosen").addClass("is-invalid");
                    } else {
                        $("#idunidades_chosen").removeClass("is-invalid");
                        $("#idunidades_chosen").addClass("valid");
                    }

                    if ($("#listadoprecios").val() == "") {
                        $("#listadoprecios_chosen").removeClass("valid");
                        $("#listadoprecios_chosen").addClass("is-invalid");
                    } else {
                        $("#listadoprecios_chosen").removeClass("is-invalid");
                        $("#listadoprecios_chosen").addClass("valid");
                    }

                    if ($("#imagenes").val() == "") {
                        $(".file-input").removeClass("valid");
                        $(".file-input").addClass("is-invalid");
                    } else {
                        $(".file-input").removeClass("is-invalid");
                        $(".file-input").addClass("valid");
                    }
                    return;
                }

                // Recopila los datos del formulario
                datosFormulario = new FormData($('#formGuardar')[0]);
                // datosFormulario.append('precio', $("#precio").unmask());
                if (vista == 1) {
                    datosFormulario.append('accion', GUARDAR_PUBLICACIONES);
                } else if (vista == 2) {
                    datosFormulario.append('id', parametro_seleccionado.id);
                    datosFormulario.append('accion', ACTUALIZAR_PUBLICACIONES);
                }
                console.log(datosFormulario);
                // Realiza la solicitud Ajax
                Swal.fire({
                    title: 'Esta seguro?',
                    text: "Recuerde verificar los datos!",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Si'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "/publicaciones_peticiones", // Reemplaza esto con la URL del servidor
                            method: "POST",
                            data: datosFormulario,
                            processData: false,
                            contentType: false,
                            success: function(respuesta) {
                                // Maneja la respuesta del servidor aquí
                                console.log(respuesta);
                                if (respuesta.estado === 1) {
                                    if (vista == 1) {
                                        mensajeSuccessGeneral(
                                            '- Se ha agregado la publicación con exito');
                                    } else if (vista == 2) {
                                        mensajeSuccessGeneral(
                                            '- Se ha actualizado la publicación con exito');
                                    }
                                    $("#formGuardar")[0].reset();
                                    tablaPublicaciones.ajax.reload();
                                    $('#modalGuardarForm').modal('hide');
                                } else {
                                    mensajeError(respuesta.mensaje);
                                }
                            },
                            error: function(request, status, error) {
                                mensajeErrorGeneral(
                                    "Se produjo un error durante el proceso, vuelve a intentarlo"
                                );
                            }
                        });
                    }
                });
            });
        }

        function cargarTablaPublicaciones() {
            tablaPublicaciones = $('#tablapublicaciones').DataTable({
                "serverSide": true,
                "processing": true,
                "responsive": true,
                "select": true,
                "ajax": {
                    "url": "/publicaciones",
                    "type": "GET",
                },
                "columns": [{
                        data: 'productos.producto'
                    },
                    {
                        data: 'unidades.unidad'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            let datos = "";
                            for (let index = 0; index < data.length; index++) {

                            }
                            return data.apellidos + ' ' + data.nombres;
                        }
                    },
                    {
                        data: 'action'
                    }
                ],
                "columnDefs": [{
                    "className": "dt-center font-xxl",
                    "targets": "_all"
                }],
                "createdRow": function(row, data, dataIndex) {
                    // Set the data-status attribute, and add a class
                    $(row).addClass("" + data.id);
                },
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
                }
            });
        }

        function eliminarPublicacion(id) {
            Swal.fire({
                    title: '¿Esta seguro?',
                    text: "Recuerde que se eliminará la publicación!",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Si'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "/publicaciones_peticiones", // Reemplaza esto con la URL del servidor
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                "_token": "{{ csrf_token() }}",
                                accion: ELIMINAR_PUBLICACIONES,
                                id: parametro_seleccionado.id,
                                
                            },
                            success: function(respuesta) {
                                // Maneja la respuesta del servidor aquí
                                if (respuesta.estado === 1) {
                                    mensajeSuccessGeneral(
                                        '- Se ha eliminado la publicación con exito');
                                    tablaPublicaciones.ajax.reload();
                                } else {
                                    mensajeError(respuesta.mensaje);
                                    
                                }
                            },
                            error: function(request, status, error) {
                                mensajeErrorGeneral(
                                    "Se produjo un error durante el proceso, vuelve a intentarlo"
                                );
                               
                            }
                        });
                    }
                });
        }


        function buscarPreciosAsociacion(idproductos, idunidades) {
            var idproductosSelect = $('#idproductos').val();
            var idunidadesSelect = $('#idunidades').val();
            
            if (idproductosSelect && idunidadesSelect) {
                $.ajax({
                    type: 'POST',
                    url: AJAX,
                    dataType: 'json',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        accion: BUSCAR_PRECIOSASOCIACION,
                        idproductos: idproductos,
                        idunidades: idunidades
                    },
                
                    beforeSend: function() {
                    },
                    success: function(respuesta) {
                        $(".carga").removeClass("show").addClass("hidden");
                        var precios_input ='';
                        if (respuesta) {
                            precios_input = '$'+ number_format(respuesta.precio);
                        }
                        // $("#listadoprecios").val(precios_input);
                        $("#listadoprecios").attr('placeholder', precios_input);
                    },
                    error: function(request, status, error) {
                        $("#listadoprecios").attr('placeholder', 'Tu asociación no ha definido un precio para este producto');
                        // $("#listadoprecios").val('Tu asociación no ha definido un precio para este producto');
                        $(".carga").removeClass("show").addClass("hidden");
                    }
                });
            }
            
        }

        function buscarPreciosVendedor(idproductos, idunidades) {
            var idproductosSelect = $('#idproductos').val();
            var idunidadesSelect = $('#idunidades').val();
            
            if (idproductosSelect && idunidadesSelect) {
                $.ajax({
                    type: 'POST',
                    url: AJAX,
                    dataType: 'json',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        accion: BUSCAR_PRECIOSVENDEDOR,
                        idproductos: idproductos,
                        idunidades: idunidades
                    },
                
                    beforeSend: function() {
                    },
                    success: function(respuesta) {
                        $(".carga").removeClass("show").addClass("hidden");
                        var precios_input ='';
                        $("#precio").val('');
                        if (respuesta) {
                            precios_input = number_format(respuesta.precio);
                        }
                        $("#precio").val(precios_input);
                    },
                    error: function(request, status, error) {
                        $("#precio").val('');
                        $("#precio").attr('placeholder', 'Define un precio');
                        $(".carga").removeClass("show").addClass("hidden");
                    }
                });
            }
        }
    </script>
@endsection