(function ($) {

/**
 * Override states.js "state:visible" event for fields with custom effects.
 */
{
  $(document).ready(function() {
    $.each(Drupal.settings.conditionalFields.effect, function(dependent, dependee) {
      $('[name="'+dependent+'"]').unbind('state:visible').bind('state:visible', function(e) {
        if (e.trigger) {
          var effect = Drupal.settings.conditionalFields.effect[$(e.target).attr('name')];
          switch (effect) {
            case 'fade':
              $(e.target).closest('.form-item, .form-submit, .form-wrapper')[e.value ? 'fadeIn' : 'fadeOut']();
              break;
            case 'slide':
              $(e.target).closest('.form-item, .form-submit, .form-wrapper')[e.value ? 'slideDown' : 'slideUp']();
              break;
            default:
              // The "effect" variable is a function.
              effect(e);
          }
        }
        // Prevent bubbling of the event to document.
        return false;
      });
    });
  });
}

})(jQuery);
