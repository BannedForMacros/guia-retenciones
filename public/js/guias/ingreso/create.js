$(document).ready(function () {
  setTimeout(() => {
    $('.select_2').select2({
      theme: "bootstrap-5",
      width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
      placeholder: $(this).data('placeholder'),
    });

    callListarProveedores();
    callListarArticulos();

  }, 300);
});

var callListarArticulos = () => {


  $(`#producto_id`).select2({
    theme: "bootstrap-5",
    containerCssClass: "select2--small",
    dropdownCssClass: "select2--small",
    ajax: {
      url: route('guiaingreso.listarArticulos'),
      // type: 'POST',
      data: function (params) {
        var codalmacen = $('#codalmacen').val();
        var codlistaprecio = $('#codlistaprecio').val();
        var codestacion = $('#codlistaprecio').find(':selected').data('codestacion');
        var tipo = $('#tipo_busqueda_articulo').val();

        var query = {
          term: params.term,
          _token: _token,
          codalmacen: codalmacen,
          codlistaprecio: codlistaprecio,
          codestacion: codestacion,
          tipo: tipo,
        }
        return query;
      },
      dataType: 'json',
      delay: 250,
      processResults: function (data) {
        return {
          results : data.items
          // results: $.map(data.items, function (obj) {
            
          //   return { id: obj.id, text: obj.name,  };
          // })
        };
      },
      // Additional AJAX parameters go here; see the end of this chapter for the full code of this example
    }
  });

}

$(document).on('click', '#btnAdd', function(event) {
  event.preventDefault();
  /* Act on the event */
  var data=$("#producto_id").select2('data')[0];
  console.log(data);//displays hello world
  var cantidad = $('#cantidad').val();
  var producto_id = $('#producto_id').val();
  // var codigo_barra = $('#producto_id').find(':selected').data('codigo_barra');
  var codigo_barra = data.codigo_barra;
  console.log({codigo_barra});
  var cod_plu = producto_id;
  var descripcion = data.descripcion;
  var precio_publico = data.precio_publico;
  var precio_sin_igv = data.precio_sin_igv;
  
  var base_calculo = $('#base_calculo').val();

  console.log({producto_id});
  var items = $('#tbody tr').map(function(i, row) {
    return {
      'producto_id' : $(this).data('producto_id'),
      // 'codigo_producto' : $(this).find('input[name=item]').val(),
      // 'item_seleccionado' : $(this).find('input[name=item]').prop('checked'),
      // 'acceso' : $(this).find('input[type=radio]:checked').val(),
    };
  }).get();



  var formData = new FormData();
  formData.append('_token', _token);
  formData.append('producto_id', producto_id);
  formData.append('codigo_barra', codigo_barra);
  formData.append('cod_plu', cod_plu);
  formData.append('descripcion', descripcion);
  formData.append('precio_publico', precio_publico);
  formData.append('precio_sin_igv', precio_sin_igv);
  formData.append('cantidad', cantidad);
  formData.append('base_calculo', base_calculo);


  formData.append('items', JSON.stringify(items));
  agregarItem(formData);

});

var agregarItem = function(formData){
  var options = {
    type: 'POST',
    url: route('guiaingreso.agregarItem'),
    data:formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(response){
      if (response.procede == true) {
        $('#tbody').append(response.tr);
      }
      if (response.procede == false) {
        Swal.fire({
          title: '',
          html: response.msj,
          icon: response.msj_tipo,
          allowOutsideClick : false
        })
      }
      calcularTotales();
    }
  };
  $.ajax(options);
};

$(document).on('click', '.delete_item', function(event) {
  event.preventDefault();
  /* Act on the event */

  $(this).parent().parent().remove();

});

var callListarProveedores = () => {

  $(`#proveedor_id`).select2({
    theme: "bootstrap-5",
    containerCssClass: "select2--small",
    dropdownCssClass: "select2--small",
    ajax: {
      url: route('guiaingreso.listarProveedores'),
      // type: 'POST',
      data: function (params) {
        var tipo = $('#tipo_busqueda_proveedor').val();
        var query = {
          term: params.term,
          tipo: tipo,
          _token: _token,
        }
        return query;
      },
      dataType: 'json',
      delay: 250,
      processResults: function (data) {
        // console.log(data.items);
        return {
          results : data.items
          // results: $.map(data.items, function (obj) {
            
          //   return { id: obj.id, text: obj.name,  };
          // })
        };
      },
      // Additional AJAX parameters go here; see the end of this chapter for the full code of this example
    }
  });

}

$(document).on('click', '.delete_item', function(event) {
  event.preventDefault();
  /* Act on the event */

  $(this).parent().parent().remove();

  calcularTotales();

});

$(document).on('keyup', '.input_cantidad_tr', function(event) {
  event.preventDefault();
  /* Act on the event */

  var cantidad = $(this).val();
  var precio = $(this).parent().parent().find('span[name=span_precio]').text();
  // var importe = parseInt(cantidad) * parseFloat(precio);
  var importe = round((parseInt(cantidad) * parseFloat(precio)),2);
  $(this).parent().parent().find('span[name=span_importe]').html(importe)
  
  setTimeout(() => {
    calcularTotales();
    
  }, 300);
});

$(document).on('keyup', '.input_porcentaje_descuento_tr', function(event) {
  event.preventDefault();
  /* Act on the event */
  var porcentaje_descuento = parseFloat($(this).val()) / 100;
  
  if ($(this).val() == '') {
    porcentaje_descuento = 0;
  }

  
  var cantidad = $(this).parent().parent().find('input[name=cantidad]').val();
  var precio = $(this).parent().parent().find('span[name=span_precio]').text();
  var importe = parseInt(cantidad) * parseFloat(precio);
  
  var monto_descuento = parseFloat(importe) * porcentaje_descuento;
  $(this).parent().parent().find('input[name=monto_descuento]').val(monto_descuento);
  var nuevo_importe = importe - monto_descuento;
  $(this).parent().parent().find('span[name=span_importe]').html(round(nuevo_importe,2))
  // console.log({porcentaje_descuento, cantidad, precio, importe, monto_descuento});

  setTimeout(() => {
    calcularTotales();
  }, 300);
});

var calcularTotales = () => {

  var base_calculo = $('#base_calculo').val();

  var items = $('#tbody tr').map(function(i, row) {
    var bonificacion = $(this).find('input[name=bonificacion]').prop('checked');
    // console.log({bonificacion});
    if (bonificacion == false) {
      return {
        'producto_id' : $(this).data('producto_id'),
        'cantidad' :  $(this).find('input[name=cantidad]').val(),
        'importe' :  $(this).find('span[name=span_importe]').text(),
        'porcentaje_descuento' :  $(this).find('input[name=porcentaje_descuento]').val(),
        'monto_descuento' :  $(this).find('input[name=monto_descuento]').val(),
  
      };
    }
  }).get();

  var total_items = items.length;

  var total_cantidad = 0;
  var total_venta = 0;
  var importe_sin_igv = 0;
  var monto_igv = 0;
  var monto_descuento = 0;

  $.map(items, function (element, index) {
    total_cantidad = total_cantidad + parseInt(element.cantidad);
    total_venta = total_venta + parseFloat(element.importe);
    if (element.porcentaje_descuento != '') {
      monto_descuento = monto_descuento + parseFloat(element.monto_descuento)

    }
  });

  total_venta = round(total_venta,2)
  importe_sin_igv = total_venta;

  if (base_calculo == 2) {
    importe_sin_igv = round((total_venta / 1.18),2);
    monto_igv = round((importe_sin_igv * 0.18),2);
  }


  $('#total_items').val(total_items)
  $('#total_cantidad').val(total_cantidad)
  $('#total_venta').val(total_venta)
  $('#importe_sin_igv').val(importe_sin_igv)
  $('#monto_igv').val(monto_igv)
  $('#monto_descuento').val(round(monto_descuento,2))

  console.log({items});
}

$(document).on('submit', '#form_store', function(event) {
  event.preventDefault();
  /* Act on the event */

  var formElement = document.getElementById("form_store");
  var formData = new FormData(formElement);

  var items = $('#tbody tr').map(function(i, row) {
    return {
      'codarticulo' : $(this).data('producto_id'),
      // 'codigo_producto' : $(this).find('input[name=item]').val(),
      'precio' : $(this).find('span[name=span_precio]').text(),
      'cantidad' : $(this).find('input[name=cantidad]').val(),
      'importe' : $(this).find('span[name=span_importe]').text(),
      'porcentaje_descuento' : $(this).find('input[name=porcentaje_descuento]').val(),
      'monto_descuento' : $(this).find('input[name=monto_descuento]').val(),
      'descripcion' : $(this).data('descripcion'),
      'codigo' : $(this).data('codigo'),

    };
  }).get();

  formData.append('detalle', JSON.stringify(items));

  // console.log({items});
  var codestacion = $('#codalmacen').find(':selected').data('codestacion');
  formData.append('codestacion', codestacion)

  var monto_descuento = $('#monto_descuento').val();
  var importe_sin_igv = $('#importe_sin_igv').val();
  var monto_igv = $('#monto_igv').val();
  var total_venta = $('#total_venta').val();
  var comentario = $('#comentario').val();
  var data_proveedor = $('#proveedor_id').select2('data')[0];
  
  if (data_proveedor != null) {
    
    var proveedor_nombre = data_proveedor.proveedor_nombre;
    formData.append('proveedor_nombre', proveedor_nombre);
    var proveedor_ruc = data_proveedor.proveedor_ruc;
    formData.append('proveedor_ruc', proveedor_ruc);
  }

  var vendedor_nombre = $('#vendedor_id').find(':selected').data('vendedor_nombre');
  formData.append('vendedor_nombre', vendedor_nombre);

  var divisa_nombre = $('#divisa_id').find(':selected').data('nombre');
  formData.append('divisa_nombre', divisa_nombre);

  var forma_pago_nombre = $('#forma_pago_id').find(':selected').data('nombre');
  formData.append('forma_pago_nombre', forma_pago_nombre);

  var tipo_operacion_nombre = $('#tipo_operacion_id').find(':selected').data('nombre');
  formData.append('tipo_operacion_nombre', tipo_operacion_nombre);

  var almacen_nombre = $('#codalmacen').find(':selected').data('nombre');
  formData.append('almacen_nombre', almacen_nombre);
  
  var base_calculo = $('#base_calculo').val();
  formData.append('base_calculo', base_calculo);
  
  formData.append('monto_descuento', monto_descuento);
  formData.append('importe_sin_igv', importe_sin_igv);
  formData.append('monto_igv', monto_igv);
  formData.append('total_venta', total_venta);
  formData.append('comentario', comentario);

  // new Response(formData).text().then(console.log)
  // store(formData);

  var procede_store = true;
  var msj_store = '';
  console.log(formData.get('proveedor_nombre'));
  if (formData.get('proveedor_nombre') == null) {
    procede_store = false;
    msj_store = 'Debe indicar un proveedor';
  }

  if (procede_store == true) {
    if (items.length <= 0) {
      procede_store = false;
      msj_store = 'Debe indicar articulos en la guia';
    }
  }

  if (procede_store == true) {
    Swal.fire({
      html: `<b>¿Desea registrar esta Guia de Ingreso?</b>`,
      icon: "warning",
      showCancelButton: !0,
      confirmButtonText: "Si, Registrar",
      cancelButtonText: "No, cancelar!",
  
      // reverseButtons: !0
    }).then((result) => {
      if (result.isConfirmed) {
        store(formData);
  
      }
    })
    
  } else {
    Swal.fire({
      html: msj_store,
      icon: 'error'
    })
  }
  
});

var store = function(formData){
  var options = {
    type: 'POST',
    url: route('guiaingreso.store'),
    data:formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(response){
      Swal.fire({
        html: response.msj,
        icon: response.msj_tipo,
      }).then((result) => {
        if (result) {
          if (response.procede == true) {
            window.location.href = response.url_redirect;
          }
        }
      })
    }
  };
  $.ajax(options);
};

$(document).on('change', '.bonificacion', function(event) {
  event.preventDefault();
  /* Act on the event */

  calcularTotales();

});

$(document).on('change', '#base_calculo', function(event) {
  event.preventDefault();
  /* Act on the event */

  var base_calculo = $(this).val();

  console.log({base_calculo});

  $('#tbody tr').map(function(i, row) {
    // console.log($(this).data());
    var cantidad = $(this).find('input[name=cantidad]').val();

    var precio_unitario = $(this).data('precio_unitario');
    if (base_calculo == 1) {
      var precio_unitario = $(this).data('precio_sin_igv');
    }

    
    $(this).find('span[name=span_precio]').html(precio_unitario);
    
    var importe = round((parseFloat(precio_unitario) * cantidad),2);
    
    $(this).find('span[name=span_importe]').html(importe);

  })

  calcularTotales();

});
