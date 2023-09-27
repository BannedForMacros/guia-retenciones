$(document).on('keyup', '#nro_documento', function(event) {
  // event.preventDefault();
  /* Act on the event */

  var numero = $(this).val();
  // console.log(numero);

  $('#usuario').val(numero)
  $('#password').val(numero)

});


$(document).on('submit', '#form_store', function (event) {
  event.preventDefault();
  /* Act on the event */

  var formElement = document.getElementById("form_store");
  var formData = new FormData(formElement);

  Swal.fire({
    html: `¿!Esta seguro de registrar este empleados¡?`,
    icon: "warning",
    showCancelButton: !0,
    confirmButtonText: "Si, Registrar",
    cancelButtonText: "No, cancelar!",
  }).then((result) => {
    if (result.isConfirmed) {
      store(formData);
    }
  })

});


var store = function (formData) {
  var options = {
    type: 'POST',
    url: route('empleados.store'),
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function (response) {
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