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

      $('#tabla_guias').DataTable({
        responsive: true,
        language: DataTable_Spanish
      });
    }
  };
  $.ajax(options);
};

