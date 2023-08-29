$(document).ready(function () {
  callListarGuias();
});

$(document).on('submit', '#form_busqueda', function(event) {
  event.preventDefault();
  /* Act on the event */
  callListarGuias();
});

var callListarGuias = () => {

  var formElement = document.getElementById("form_busqueda");
  var formData = new FormData(formElement);

  $('#div_loading').show();
  $('#resultados').html('');
  listar(formData);
}

var listar = function(formData){
  var options = {
    type: 'POST',
    url: route('guiasalida.listar'),
    data:formData,
    processData: false,
    contentType: false,
    dataType: 'html',
    success: function(response){
      $('#resultados').html(response);
      $('#div_loading').hide();
      
      $('#tabla_guias').DataTable({
        responsive: true,
        language: DataTable_Spanish
      });
    }
  };
  $.ajax(options);
};

$(document).on('change', '.fecha', function(event) {
  var tipo = $(this).data('tipo');

  var fecha_inicio = $('#fecha_inicio').val();
  
  var fecha_fin = $('#fecha_fin').val();

  if (tipo == 'inicio') {
    
    if (fecha_inicio > fecha_fin) {
      // igualar = true;
      $('#fecha_fin').val(fecha_inicio)
    }
  }
  if (tipo == 'fin') {
    
    if (fecha_inicio > fecha_fin) {
      // igualar = true;
      $('#fecha_inicio').val(fecha_fin)
    }
  }

  // console.log({tipo, fecha_inicio, fecha_fin});

});