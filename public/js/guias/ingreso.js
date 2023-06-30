$(document).ready(function () {
  setTimeout(() => {
    $('.select_2').select2({
      theme: "bootstrap-5",
      width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
      placeholder: $(this).data('placeholder'),
    });
  }, 300);
});

$(document).on('click', '#btnAdd', function(event) {
  event.preventDefault();
  /* Act on the event */

  var cantidad = $('#cantidad').val();
  var producto_id = $('#producto_id').val();
  var codigo_barra = $('#producto_id').find(':selected').data('codigo_barra');
  var cod_plu = $('#producto_id').find(':selected').data('cod_plu');
  var descripcion = $('#producto_id').find(':selected').data('descripcion');
  var precio_publico = $('#producto_id').find(':selected').data('precio_publico');
  var precio_sin_igv = $('#producto_id').find(':selected').data('precio_sin_igv');

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
    }
  };
  $.ajax(options);
};

$(document).on('click', '.delete_item', function(event) {
  event.preventDefault();
  /* Act on the event */

  $(this).parent().parent().remove();

});