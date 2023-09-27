$(document).ready(function () {

  setTimeout(() => {
    $('.select_2').select2({
      theme: "bootstrap-5",
      width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
      placeholder: $(this).data('placeholder'),
    });

    callListarProveedores();
    callListarArticulos();
    callFormBusquedaArticulo();

    calcularTotales();

  }, 300);
});

$(document).on('change', '#es_guia_interna', function(event) {
  event.preventDefault();
  /* Act on the event */
  callEsGuiaInterna();
});

var callEsGuiaInterna = () => {

  var es_guia_interna = $('#es_guia_interna').val();
  // console.log({es_guia_interna});

  if (es_guia_interna == 0) {//no es guia interna
    
    $('#div_serie_interna').hide();
    $('#div_serie_externa').show();

  }

  if (es_guia_interna == 1) {// es guia interna

    $('#div_serie_interna').show();
    $('#div_serie_externa').hide();

    callGetSerie();

  }

}

$(document).on('change', '#serie', function(event) {
  event.preventDefault();
  /* Act on the event */
  callGetSerie();
});

var callGetSerie = () => {

  var serie = $('#serie').val();

  var formData = new FormData();
  formData.append('_token', _token);
  formData.append('serie', serie);

  getSerie(formData);
}

var getSerie = function(formData){
  var options = {
    type: 'POST',
    url: route('guiaingreso.getSerie'),
    data:formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(response){
      console.log({response});
      var serie = response.getSerie;
      $('#numero').val(serie.nuevo_numero);
    }
  };
  $.ajax(options);
};


var callListarArticulos = () => {


  $(`#producto_select`).select2({
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

// $(document).on('click', '#btnAdd', function(event) {
//   event.preventDefault();
//   /* Act on the event */
//   var data=$("#producto_id").select2('data')[0];
//   console.log(data);//displays hello world
//   var cantidad = $('#cantidad').val();
//   var producto_id = $('#producto_id').val();
//   // var codigo_barra = $('#producto_id').find(':selected').data('codigo_barra');
//   var codigo_barra = data.codigo_barra;
//   console.log({codigo_barra});
//   var cod_plu = producto_id;
//   var descripcion = data.descripcion;
//   var precio_publico = data.precio_publico;
//   var precio_sin_igv = data.precio_sin_igv;
  
//   var base_calculo = $('#base_calculo').val();

//   console.log({producto_id});
//   var items = $('#tbody tr').map(function(i, row) {
//     return {
//       'producto_id' : $(this).data('producto_id'),
//       // 'codigo_producto' : $(this).find('input[name=item]').val(),
//       // 'item_seleccionado' : $(this).find('input[name=item]').prop('checked'),
//       // 'acceso' : $(this).find('input[type=radio]:checked').val(),
//     };
//   }).get();



//   var formData = new FormData();
//   formData.append('_token', _token);
//   formData.append('producto_id', producto_id);
//   formData.append('codigo_barra', codigo_barra);
//   formData.append('cod_plu', cod_plu);
//   formData.append('descripcion', descripcion);
//   formData.append('precio_publico', precio_publico);
//   formData.append('precio_sin_igv', precio_sin_igv);
//   formData.append('cantidad', cantidad);
//   formData.append('base_calculo', base_calculo);


//   formData.append('items', JSON.stringify(items));
//   agregarItem(formData);

// });

// var agregarItem = function(formData){
//   var options = {
//     type: 'POST',
//     url: route('guiaingreso.agregarItem'),
//     data:formData,
//     processData: false,
//     contentType: false,
//     dataType: 'json',
//     success: function(response){
//       if (response.procede == true) {
//         $('#tbody').append(response.tr);
//       }
//       if (response.procede == false) {
//         Swal.fire({
//           title: '',
//           html: response.msj,
//           icon: response.msj_tipo,
//           allowOutsideClick : false
//         })
//       }
//       calcularTotales();
//     }
//   };
//   $.ajax(options);
// };

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

$(document).on('change', '#proveedor_id', function(event) {
  event.preventDefault();
  /* Act on the event */
  var data = $('#proveedor_id').select2('data')[0];
  console.log({data});

  $('#proveedor_nombre').val(data.proveedor_nombre);
  $('#proveedor_ruc').val(data.proveedor_ruc);
});


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
        'peso' : $(this).data('peso'),
  
      };
    }
  }).get();

  var total_items = items.length;

  var total_cantidad = 0;
  var total_venta = 0;
  var importe_sin_igv = 0;
  var monto_igv = 0;
  var monto_descuento = 0;
  var peso_total = 0;

  $.map(items, function (element, index) {
    total_cantidad = total_cantidad + parseInt(element.cantidad);
    total_venta = total_venta + parseFloat(element.importe);
    peso_total = peso_total + (element.peso * element.cantidad);
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
  $('#monto_descuento').val(round(monto_descuento,2));
  $('#peso_bruto_total').val(round(peso_total,2));

  console.log({items});
}

$(document).on('submit', '#form_store', function(event) {
  event.preventDefault();
  /* Act on the event */

  callStore();

});

var callStore = (guardar_avance = false) => {

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
      'precio_publico' : $(this).data('precio_publico'),
      'precio_sin_igv' : $(this).data('precio_sin_igv'),
      'codigo_barra' : $(this).data('codigo_barra'),
      
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
  var data_proveedor_2 = $('#proveedor_id').data();

  console.log({data_proveedor, data_proveedor_2});
  if (data_proveedor != null) {
    
    var proveedor_nombre = data_proveedor.proveedor_nombre;
    formData.append('proveedor_nombre', $('#proveedor_nombre').val());
    var proveedor_ruc = data_proveedor.proveedor_ruc;
    formData.append('proveedor_ruc', $('#proveedor_ruc').val());
  }
  // new Response(formData).text().then(console.log)

  var vendedor_nombre = $('#vendedor_id').find(':selected').data('vendedor_nombre');
  console.log({vendedor_nombre});
  if (vendedor_nombre == undefined) {
    vendedor_nombre = '';
  }
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
  formData.append('guardar_avance', guardar_avance);

  new Response(formData).text().then(console.log)
  // store(formData);

  var procede_store = true;
  var msj_store = '';
  new Response(formData).text().then(console.log)
  console.log(formData.get('proveedor_nombre'));

  if (formData.get('guardar_avance') == 'false') {
    
    if (formData.get('vendedor_nombre') == '') {
      procede_store = false;
      msj_store = `Debe indicar un vendedor`;
    }

    if (procede_store == true) {
      
      if (formData.get('proveedor_nombre') == null) {
        procede_store = false;
        msj_store = 'Debe indicar un proveedor';
      }
    }
  
    if (procede_store == true) {
      if (items.length <= 0) {
        procede_store = false;
        msj_store = 'Debe indicar articulos en la guia';
      }
    }

    if (procede_store == true) {
      if (formData.get('es_guia_interna') == 0) {
        if (formData.get('serie_externa').length <= 0) {
          procede_store = false;
          msj_store = `<b>Debe ingresar una serie para la guia</b>`
        }
      }
    }
    if (procede_store == true) {
      if (formData.get('es_guia_interna') == 0) {
        if (formData.get('numero').length <= 0) {
          procede_store = false;
          msj_store = `<b>Debe ingresar un numero para la guia</b>`
        }
      }
    }

  }



  if (procede_store == true) {
    var msj_guardado = `<b>¿Desea registrar esta Guia de Ingreso?</b>`;
    if (guardar_avance == true) {
      msj_guardado = `<b>¿Desea guardar el avance de esta Guia de Ingreso?</b>`;
      
    }
    Swal.fire({
      html: msj_guardado,
      icon: "warning",
      showCancelButton: !0,
      confirmButtonText: "Si, Registrar",
      cancelButtonText: "No, cancelar!",
  
      // reverseButtons: !0
    }).then((result) => {
      if (result.isConfirmed) {
        // store(formData);
        modalStore(formData);
      }
    })
    
  } else {
    Swal.fire({
      html: msj_store,
      icon: 'error'
    })
  }
  


}

var modalStore = function(formData){
  var options = {
    type: 'POST',
    url: route('guiaingreso.modalStore'),
    data:formData,
    processData: false,
    contentType: false,
    dataType: 'html',
    success: function(response){
      $('#modales').html(response);
      $('#modalStore').modal('show');
      store(formData);
    }
  };
  $.ajax(options);
};


var store = function(formData){
  var options = {
    type: 'POST',
    url: route('guiaingreso.store'),
    data:formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(response){
      // Swal.fire({
      //   html: response.msj,
      //   icon: response.msj_tipo,
      // }).then((result) => {
      //   if (result) {
      //     if (response.procede == true) {
      //       window.location.href = response.url_redirect;
      //     }
      //   }
      // })

      $('#li_store').html(response.msj);

      if (response.procede == true) {
        formData.append('id', response.id);
        storeDataMart(formData);
      }

    }
  };
  $.ajax(options);
};

var storeDataMart = function(formData){
  var options = {
    type: 'POST',
    url: route('guiaingreso.storeDataMart'),
    data:formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(response){
      $('#li_store_datamart').html(response.msj);

      if (response.procede == true) {
        if (formData.get('guardar_avance') == 'false') {
          if (formData.get('envio_sunat') == 1) {
            if (formData.get('id') == '') {
              formData.append('id', response.id);
            }
            facturacionElectronica(formData);
            
          }
          
        }
      }

    }
  };
  $.ajax(options);
};

$(document).on('click', '#btnReintentarDataMart', function(event) {
  event.preventDefault();
  /* Act on the event */
  // var callGuardarAvance = $(this).data('guardar_avance');

  // $('#modalStore').modal('hide');

  // console.log({callGuardarAvance});
  // callStore();

  var formData = new FormData();
  var id = $(this).data('id');

  formData.append('_token', _token);
  formData.append('id', id);

  $('#li_store_datamart').html(`<b>Registrando en DataMart...</b>
  <span class=""><i class="fa-solid fa-spinner fa-spin fa-lg"></i></span>`);
  storeDataMart(formData);


});


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

$(document).on('click', '#btnGuardarAvance', function(event) {
  event.preventDefault();
  /* Act on the event */
  callStore(true);
});

$(document).on('keypress', '#vendedor_codigo', function(event) {
  // event.preventDefault();
  console.log('enter');
  /* Act on the event */
  console.log(event.keyCode);

});
$(document).on('click', '#btnBuscarVendedor', function(event) {
  event.preventDefault();
  /* Act on the event */
  callGetVendedor();

});

var callGetVendedor = () => {

  var vendedor_codigo = $('#vendedor_codigo').val();
  // console.log({vendedor_codigo});
  var formData = new FormData();
  formData.append('_token', _token);
  formData.append('vendedor_codigo', vendedor_codigo);

  getVendedor(formData);
}

var getVendedor = function(formData){
  var options = {
    type: 'POST',
    url: route('guiaingreso.getVendedor'),
    data:formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(response){
      $('#vendedor_id').html(response.options);
    }
  };
  $.ajax(options);
};

$('#form_store').on('keydown', function(e) {
  var keyCode = e.keyCode || e.which;
  var tag = e.target.tagName
  var tag_id = e.target.id;

  console.log({keyCode, tag});
  console.log(e.target.id);
  if (keyCode === 13 && tag_id !=="vendedor_codigo") {
    console.log("Enter prevented")
    e.preventDefault();
    return false;
  }else{
    // console.log("Enter is ok...")
    if (keyCode == 13) {
      callGetVendedor();
      
    }
  }
});

$(document).on('keypress', '.input_cantidad_tr', function(event) {
  // event.preventDefault();
  var keyCode = event.keyCode || event.which;
  var tipo_busqueda_articulo = $('#tipo_busqueda_articulo').val();
  if (keyCode == 13) {//enter
    if (tipo_busqueda_articulo == 1) {
      // console.log('cambiar foco');
      $('#producto_valor').focus();
      
    }
  }
});