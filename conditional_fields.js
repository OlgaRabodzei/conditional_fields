if (!Drupal.ConditionalFields) {
  Drupal.ConditionalFields = {};
}

Drupal.ConditionalFields.switchField = function(id, values) {
  // For each controlling field:
  // Find the controlled fields
  $.each(Drupal.settings.ConditionalFields.controlling_fields, function(controllingField, controlledFields) {
    if (controllingField == id) {
      var isActive = false;
      // Find the settings of the controlled field
      $.each(controlledFields, function(i, fieldSettings) {
        $(fieldSettings.field_id).show();
        // Find the trigger values of the controlled field (for this controlling field)
        $.each(fieldSettings.trigger_values, function(ii, val) {
          if (Drupal.ConditionalFields.inArray(val, values) != -1) {
            isActive = true;
            return false;
          }
        });
        // If there is an active trigger key in this controlling field, stop searching
        if (isActive == true) {
          return false;
        }
        // To do: feature. This would multiple controlling fields on the same field, but they are
        // not supported for now. I should try other controlling fields.
        else {
          $(fieldSettings.field_id).hide();
        }
      });
    }
  });
}

Drupal.ConditionalFields.findValues = function(field) {
  var values = [];
  field.find("option:selected, input:checked").each( function() {
    values[values.length] = this.value;
  });
  return values;
}       
        
Drupal.ConditionalFields.docReady = function() {
  //Set default state  
  $('.controlling-field').each(function() {
    var values = Drupal.ConditionalFields.findValues($(this));
    var id = '#' + $(this).attr('id');
    Drupal.ConditionalFields.switchField(id, values);
  });
  // Add events
  $('.controlling-field').change(Drupal.ConditionalFields.fieldChange);
}

Drupal.ConditionalFields.fieldChange = function() {
  var values = Drupal.ConditionalFields.findValues($(this));
  var id = '#' + $(this).attr('id');
  Drupal.ConditionalFields.switchField(id, values);
}

Drupal.ConditionalFields.inArray = function( elem, array ) {
  for ( var i = 0, length = array.length; i < length; i++ )
    if ( array[ i ] == elem )
      return i;
    return -1;
}

if (Drupal.jsEnabled) {
  $(document).ready(Drupal.ConditionalFields.docReady);
}