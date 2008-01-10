/* $Id$ */

if (!Drupal.ConditionalFields) {
  Drupal.ConditionalFields = {};
}

Drupal.ConditionalFields.switchField = function(id, values) {
  /* For each controlling field: find the controlled fields */
  $.each(Drupal.settings.ConditionalFields.controlling_fields, function(controllingField, controlledFields) {
    if (controllingField == id) {
      var isActive = false;
      /* Find the settings of the controlled field */
      $.each(controlledFields, function(i, fieldSettings) {
        $(fieldSettings.field_id).hide();
        /* Find the trigger values of the controlled field (for this controlling field) */
        $.each(fieldSettings.trigger_values, function(ii, val) {
          if (Drupal.ConditionalFields.inArray(val, values) != -1) {
            $(fieldSettings.field_id).show();
            /* Stop searching in this field */
            return false;
          }
        });
        /* To do: feature. Multiple controlling fields on the same field, are
           not supported for now. I should try other controlling fields. */
      });
    }
  });
}

Drupal.ConditionalFields.findValues = function(field) {
  var values = [];
  field.find("option:selected, input:checked").each( function() {
    if ($(this)[0].selected || $(this)[0].checked) {
      values[values.length] = this.value;
    }
  });
  return values;
}       
        
Drupal.ConditionalFields.docReady = function() {
  /* Set default state */
  $('.controlling-field').each(function() {
    var values = Drupal.ConditionalFields.findValues($(this));
    var id = '#' + $(this).attr('id');
    Drupal.ConditionalFields.switchField(id, values);
  });
  /* Add events */
  $('.controlling-field').change(Drupal.ConditionalFields.fieldChange);
}

Drupal.ConditionalFields.fieldChange = function() {
  var values = Drupal.ConditionalFields.findValues($(this));
  var id = '#' + $(this).attr('id');
  Drupal.ConditionalFields.switchField(id, values);
}

/**
 * This is the same function from latest jQuery
 * http://code.jquery.com/jquery-latest.js
 */
Drupal.ConditionalFields.inArray = function( elem, array ) {
  for ( var i = 0, length = array.length; i < length; i++ )
    if ( array[ i ] == elem )
      return i;
    return -1;
}

if (Drupal.jsEnabled) {
  $(document).ready(Drupal.ConditionalFields.docReady);
}