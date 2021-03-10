function $_GET(param) {
  var vars = {};
  window.location.href.replace(location.hash, '').replace(
          /[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
          function (m, key, value) { // callback
            vars[key] = value !== undefined ? value : '';
          }
  );

  if (param) {
    return vars[param] ? vars[param] : null;
  }
  return vars;
}


$(document).ready(function () {

  if (window.innerWidth < 1280) {
    $(".sidebar").toggleClass("toggled");
  }

  $('.select2').select2();




  /* DataTable*/
  $.extend($.fn.dataTable.defaults, {
    dom:
            "<'col mb-3 bg-white pb-3'<'row'<'col-sm-10 col-md-6'l><'col-sm-12 col-md-6'f>>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    renderer: 'bootstrap'
  });

  var table = $('.datatable').DataTable(
          {
            "initComplete": function (settings, json) {
              var domDropDownActions = "";
              if ($(this).prop("id") == 'table_users')
              {
                domDropDownActions = '<div class="ml-2 bg-white float-left">' +
                        '<label>Actions' +
                        '<select id="massive-actions-select" class="custom-select custom-select-sm form-control form-control-sm">' +
                        '<option value="">Actions</option>' +
                        '<option data-dialog-title="' + Translator.trans('Entities.WBList.actions.deleteEmailAccount') + '" value="">' + Translator.trans('Entities.User.actions.deleteEmailAccount') + '</option>';
                domDropDownActions += '</select></label></div>';
                $(domDropDownActions).insertAfter("#" + $(this).prop("id") + "_length ");
              }

            },
            "language": {
              url: $('body').data('base-path') + '/js/translations/datatable.' + $('html').attr('lang') + '.json',
            },
            "pageLength": 100,
            "stateSave": true
          });

//massive form action confirm submission
  $(document).on('change', '#massive-actions-select, #massive-actions-form_actions', function (e) {
    e.preventDefault();
    if ($('#massive-actions-form input[type="checkbox"]').filter(':checked').length > 0)
    {
      var curFormAction = $("#massive-actions-form").prop("action");
      if ($(this).val() != '')
      {
        $("#massive-actions-form").prop("action", curFormAction + "/" + $(this).val());
      }


      $('#dialog-confirm').dialog('option', 'title', $(this).find(':selected').data('dialog-title'));
      $('#dialog-confirm').data("type-action-confirm", "form");
      $('#dialog-confirm').data("form-to-confirm", "massive-actions-form");
      $("#dialog-content").html(Translator.trans('Message.Actions.massiveActionContent'));

      $("#dialog-confirm").dialog("open");
      //Reset the form action
      $('#dialog-confirm').on('dialogclose', function (event) {
        $("#massive-actions-form").prop("action", curFormAction);
      });

    } else
    {
      $(this).val('');
    }



    $("#dialog-confirm").on("dialogclose", function (event, ui) {
      $('#massive-actions-select').val('');
    });
  });

  //check all chekboxes in listing
  $(document).on('click', '#checkAll', function (e) {
    $('.' + $(this).data('target-checkall-class')).attr('checked', $(this).is(':checked'));
  });

  $(document).on('click', '.btn-open-modal', function (e) {
    $.ajax({
      url: $(this).data('url-modal-content'),
      type: $(this).data('modal-method-type') ? $(this).data('modal-method-type') : 'post',
//      data: {userid: userid},
      success: function (response) {
        $('#empModal').html(response);

        $('.select2').select2(
                {
                  width: 'resolve',
                  dropdownParent: $('#empModal')
                }
        );
        $('.select2entity').select2entity({
          width: 'resolve',
          dropdownParent: $('#empModal')
        });
        $('#empModal').modal('show');
      }
    });
  });

  $(document).on('click', '.btn-close-modal', function (e) {
    $('#empModal').modal('toggle');
  });

  // Manage ajax form    

  $('body').on('submit', 'form.modal-ajax-form', function (e) {
    e.preventDefault();

    // return;
    var url = $(this).attr('action');
    var type = $(this).attr('method').toUpperCase();

    var formData = new FormData($(this)[0]);
    $.ajax({
      url: url,
      type: type,
      data: formData,
      processData: false,
      contentType: false,
      success: function (data) {
        data = $.parseJSON(data);
        var message = "";
        if (typeof data.message !== "undefined") {
          message = data.message;
        } else {
          message = Translator.trans('Generics.flash.editSuccess');
        }
        $("#flash-message-modal-container").removeClass("d-none alert-danger alert-success alert-secondary alert-info alert-warning");
        $('#flashmessagecontent').html(message);
        $('#flash-message-modal-container').addClass('alert-' + data.status);
        $('#flash-message-modal-container').show();

        if (data.status === "success") {
          window.location.reload();
        }

      },
      error: function (jqXHR) {

      }

    });
  });

  // Show filename in label input
  $(document).on('change', '#domain_logoFile', function (e) {
    //get the file name
    var fileName = $(this).val();
    $(this).next('.custom-file-label').html(fileName);
  });

  // range input management
  var slider = $("#domain_level");
  var output = $("#domain-spam-level");
  output.innerHTML = slider.value; // Display the default slider value

  $(document).on('input', '#domain_level', function (e) {
    $("#domain-spam-level").html($(this).val());

  });

// Update the current slider value (each time you drag the slider handle)
  slider.oninput = function () {
    output.innerHTML = this.value;
  }


  $('#resultsPerPage, #massive-actions-form_per_page').change(function () {
    var url = $(location).attr('pathname') + $(location).attr('search').replace('page=' + $_GET('page'), 'page=1');
    var item = $(this).find(":selected").text();

    if (~url.indexOf('per_page')) {
      jQuery(location).attr('href', url.replace($_GET('per_page'), item));
    } else {
      if (~url.indexOf('?')) {
        jQuery(location).attr('href', url + '&per_page=' + item);
      } else {
        jQuery(location).attr('href', url + '?per_page=' + item);
      }
    }
  });


  $('#dialog-confirm').dialog({
    resizable: false,
    height: 200,
    width: 'auto',
    autoOpen: false,
    modal: true,
    buttons: [
      {
        text: Translator.trans('Generics.labels.yes'),
        class: 'ui-dialog-save-btn',
        click: function () {


          if ($(this).data('type-action-confirm') == 'link' && $(this).data("link-to-confirm"))
          {
            window.location.href = $(this).data("link-to-confirm");
          } else if ($(this).data('type-action-confirm') == 'form')
          {
            $('#' + $(this).data('form-to-confirm')).submit();
          }
          $(this).dialog("close");

        }
      },
      {
        text: Translator.trans('Generics.labels.cancel'),
        click: function () {
          $(this).dialog("close");
        }
      },
    ]
  });


  $("a.confirmModal").click(function (e) {
    e.preventDefault();
    $('#dialog-confirm').dialog('option', 'title', $(this).data('dialog-title'));
    $('#dialog-confirm').data("type-action-confirm", "link");
    $('#dialog-confirm').data("link-to-confirm", $(this).attr("href"));
    $("#dialog-content").html($(this).data('dialog-content'));
    $("#dialog-confirm").dialog("open");
  });

  // mise suppression d'un element via ajax
  $('.delete-ajax-button').click(function (event) {
    event.preventDefault();
    var parent = $(this).closest("tr");
    if ($(this).data("confirm")) {
      message = $(this).data("confirm");
    } else {
      message = Translator.trans('Generics.actions.confirmDelete');
    }
    if (confirm(message)) {
      $.ajax({
        type: 'POST',
        url: $(this).data('target'),
        success: function ()
        {
          message = Translator.trans('Generics.flash.deleteSuccess');
          parent.hide('slow', function () {
            parent.remove();
          });
          // decrement nb span nbResults
          if ($('span.nbResults').length) {
            nbresult = $('span.nbResults').html();
            nbresult--;
            $('span.nbResults').html(nbresult);
          }
          showAlertMessage('success', message);
        },
        failure: function ()
        {
          message = Translator.trans('Generics.flash.deleteFailure');
          showAlertMessage('error', message);
        }

      });
    }
  });





});
function showAlertMessage(type, message) {
  if ($('#alert-box').length) {
    dom = '<div class="alert alert-' + type + ' alert-dismissible alert-auto-hidden" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + message + '</div>';
    $('#alert-box').append(dom);
  } else {
    dom = '<div id="alert-box"><div class="alert alert-' + type + ' alert-dismissible alert-auto-hidden" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + message + '</div>';
    $('#header').append(dom);
  }
  hideAlertMessage();
}
// masquage auto des message d'alerte
function hideAlertMessage() {

  $(".alert-auto-hidden").delay(3000).fadeOut(function () {
    $(".alert-auto-hidden").alert('close');
    $(this).remove();
  });
}

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/serviceworker.js');
}
;
